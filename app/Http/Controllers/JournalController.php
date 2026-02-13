<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class JournalController extends Controller
{
    public function index()
    {
        $tenantService = app(\App\Services\TenantService::class);
        $company = $tenantService->getCompany();

        $journals = collect();
        if ($company) {
            $journals = \App\Models\JudoJournal::where('company_id', $company->id)
                ->orderBy('period', 'desc')
                ->get();
        }
        $periods = [];
        $now = now();

        $periods[$now->year] = $now->year . ' год';
        $periods[$now->year - 1] = ($now->year - 1) . ' год';
        $periods['divider1'] = '---';

        for ($q = 1; $q <= 4; $q++)
            $periods[$now->year . '-Q' . $q] = $q . ' кв. ' . $now->year;
        for ($q = 1; $q <= 4; $q++)
            $periods[($now->year - 1) . '-Q' . $q] = $q . ' кв. ' . ($now->year - 1);
        $periods['divider2'] = '---';

        $current = now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $periods[$current->format('Y-m')] = \Illuminate\Support\Str::ucfirst($current->translatedFormat('F Y'));
            $current->subMonth();
        }

        return view('journal.index', compact('journals', 'periods'));
    }

    public function create()
    {

    }

    public function store(Request $request)
    {
        $request->validate([
            'period' => 'required|string',
        ]);

        $company = app(\App\Services\TenantService::class)->getCompany();
        if (!$company) {
            return back()->with('error', 'Компания не выбрана.');
        }
        $roleName = session('user_role', 'Отходообразователь');
        $roleKey = ($roleName === 'Переработчик отходов') ? 'waste_processor' : 'waste_generator';

        $periodInput = trim($request->input('period'));
        $anyJournalExists = \App\Models\JudoJournal::where('company_id', $company->id)->exists();
        $initialBalancesExist = \App\Models\InitialBalance::where('company_id', $company->id)->exists();
        if (!$anyJournalExists && !$initialBalancesExist) {

            return redirect()->route('journal.initial-balance.create', ['period' => $periodInput]);
        }

        return $this->generateJournal($company, $periodInput, $roleKey);
    }

    private function generateJournal($company, $periodInput, $roleKey)
    {

        $startDate = null;
        $endDate = null;
        $type = 'month';
        $periodLabel = $periodInput;

        try {
            if (strlen($periodInput) === 4 && is_numeric($periodInput)) {

                $type = 'year';
                $startDate = \Carbon\Carbon::createFromDate((int) $periodInput, 1, 1)->startOfDay();
                $endDate = $startDate->copy()->endOfYear();
                $periodLabel = $periodInput . ' год';
            } elseif (str_contains($periodInput, '-Q')) {

                $type = 'quarter';
                $parts = explode('-Q', $periodInput);
                $year = (int) $parts[0];
                $quarter = (int) $parts[1];

                $startMonth = ($quarter - 1) * 3 + 1;
                $startDate = \Carbon\Carbon::createFromDate($year, $startMonth, 1)->startOfDay();

                $endDate = $startDate->copy()->addMonths(2)->endOfMonth();
                $periodLabel = $quarter . ' квартал ' . $year;
            } else {

                $type = 'month';
                if (!preg_match('/^\d{4}-\d{2}$/', $periodInput)) {
                    throw new \Exception("Формат Y-m ожидался, получено: $periodInput");
                }
                $startDate = \Carbon\Carbon::createFromFormat('Y-m', $periodInput)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
                $periodLabel = $startDate->translatedFormat('F Y');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Journal Period Error: ' . $e->getMessage());
            return back()->with('error', 'Неверный формат периода: ' . $periodInput . ' (' . $e->getMessage() . ')');
        }
        $prevJournal = \App\Models\JudoJournal::where('company_id', $company->id)
            ->where('period', '<', $startDate->format('Y-m-d'))
            ->where('role', $roleKey)
            ->orderBy('period', 'desc')
            ->first();

        $allPrev = \App\Models\JudoJournal::where('company_id', $company->id)
            ->where('period', '<', $startDate->format('Y-m-d'))
            ->where('role', $roleKey)
            ->orderBy('period', 'desc')
            ->limit(10)
            ->get();

        $validPrevJournal = null;
        foreach ($allPrev as $pj) {
            $pjStart = \Carbon\Carbon::parse($pj->period);
            $pjEnd = $pjStart->copy();
            if ($pj->type === 'year')
                $pjEnd->endOfYear();
            elseif ($pj->type === 'quarter')
                $pjEnd->endOfQuarter();
            else
                $pjEnd->endOfMonth();
            if ($pjEnd->lt($startDate)) {
                $validPrevJournal = $pj;
                break;
            }
        }
        $prevJournal = $validPrevJournal;
        $prevBalances = [];
        $wasteStats = [];

        if (!$prevJournal) {

            $initials = \App\Models\InitialBalance::where('company_id', $company->id)->get();
            foreach ($initials as $init) {
                $prevBalances[$init->waste_name] = (float) $init->amount;
                $wasteStats[$init->waste_name] = [
                    'generated' => 0,
                    'used' => 0,
                    'utilized' => 0,
                    'neutralized' => 0,
                    'buried' => 0,
                    'transferred' => 0,
                    'received' => 0,
                    'fkko' => $init->fkko_code,
                    'hazard' => $init->hazard_class
                ];
            }
        } elseif (!empty($prevJournal->table2_data)) {
            foreach ($prevJournal->table2_data as $item) {
                if (isset($item['name']) && isset($item['balance_end'])) {
                    $prevBalances[$item['name']] = (float) $item['balance_end'];
                    $wasteStats[$item['name']] = [
                        'generated' => 0,
                        'used' => 0,
                        'utilized' => 0,
                        'neutralized' => 0,
                        'buried' => 0,
                        'transferred' => 0,
                        'received' => 0,
                        'fkko' => $item['fkko'] ?? '',
                        'hazard' => $item['hazard'] ?? ''
                    ];
                }
            }
        }
        $acts = \App\Models\Act::where('company_id', $company->id)
            ->where('status', 'processed')
            ->get()
            ->filter(function ($act) use ($startDate, $endDate) {
                $data = $act->act_data;
                $dateVal = $data['date'] ?? null;
                $actDate = $dateVal ? \Carbon\Carbon::parse($dateVal) : $act->created_at;
                return $actDate->between($startDate, $endDate);
            });

        $table3_data = [];
        $table4_data = [];

        foreach ($acts as $act) {
            $data = $act->act_data;
            $items = $data['items'] ?? [];
            $operationType = mb_strtolower($data['operation_type'] ?? '');
            $provider = $data['provider'] ?? '';
            $receiver = $data['receiver'] ?? '';
            $actNumber = $data['number'] ?? 'б/н';
            $date = $data['date'] ?? $act->created_at->format('Y-m-d');

            $compName = mb_strtolower($company->name);
            $provName = mb_strtolower($provider);
            $recvName = mb_strtolower($receiver);

            $isWasteGenerator = (mb_strpos($provName, $compName) !== false);
            $isWasteRecipient = (mb_strpos($recvName, $compName) !== false);
            $isInternal = ($isWasteRecipient && $isWasteGenerator);

            foreach ($items as $item) {
                $name = $item['name'] ?? 'Unknown';
                $qty = (float) ($item['quantity'] ?? 0);
                $opItem = mb_strtolower($item['operation_type'] ?? $operationType);
                $fkko = $item['fkko_code'] ?? '';
                $hazard = $item['hazard_class'] ?? '';

                if (!isset($wasteStats[$name])) {
                    $wasteStats[$name] = [
                        'generated' => 0,
                        'used' => 0,
                        'utilized' => 0,
                        'neutralized' => 0,
                        'buried' => 0,
                        'transferred' => 0,
                        'received' => 0,
                        'fkko' => $fkko,
                        'hazard' => $hazard
                    ];
                }
                if (empty($wasteStats[$name]['fkko']))
                    $wasteStats[$name]['fkko'] = $fkko;
                if (empty($wasteStats[$name]['hazard']))
                    $wasteStats[$name]['hazard'] = $hazard;
                if ($isWasteGenerator && !$isInternal) {
                    $wasteStats[$name]['transferred'] += $qty;
                    $table3_data[] = [
                        'date' => $date,
                        'number' => $actNumber,
                        'counterparty' => $receiver,
                        'waste' => $name,
                        'fkko' => $fkko,
                        'hazard' => $hazard,
                        'amount' => $qty,
                        'operation' => $opItem
                    ];
                } elseif ($isWasteRecipient && !$isInternal) {
                    $wasteStats[$name]['received'] += $qty;
                    $table4_data[] = [
                        'date' => $date,
                        'number' => $actNumber,
                        'counterparty' => $provider,
                        'waste' => $name,
                        'fkko' => $fkko,
                        'hazard' => $hazard,
                        'amount' => $qty,
                        'operation' => $opItem
                    ];

                    if (str_contains($opItem, 'утилиз'))
                        $wasteStats[$name]['utilized'] += $qty;
                    elseif (str_contains($opItem, 'обезвреж'))
                        $wasteStats[$name]['neutralized'] += $qty;
                    elseif (str_contains($opItem, 'захорон'))
                        $wasteStats[$name]['buried'] += $qty;
                } elseif ($isInternal) {
                    if (str_contains($opItem, 'утилиз'))
                        $wasteStats[$name]['utilized'] += $qty;
                    elseif (str_contains($opItem, 'обезвреж'))
                        $wasteStats[$name]['neutralized'] += $qty;
                    elseif (str_contains($opItem, 'захорон'))
                        $wasteStats[$name]['buried'] += $qty;
                }

                if (str_contains($opItem, 'образован')) {
                    $wasteStats[$name]['generated'] += $qty;
                }
            }
        }
        $table2 = [];
        $uniqueWastes = array_unique(array_merge(array_keys($prevBalances), array_keys($wasteStats)));
        $table1_data = [];

        foreach ($uniqueWastes as $wasteName) {
            $start = $prevBalances[$wasteName] ?? 0;
            $stats = $wasteStats[$wasteName] ?? [
                'generated' => 0,
                'utilized' => 0,
                'neutralized' => 0,
                'buried' => 0,
                'transferred' => 0,
                'received' => 0,
                'fkko' => '',
                'hazard' => ''
            ];

            $end = $start + $stats['generated'] + $stats['received'] - $stats['utilized'] - $stats['neutralized'] - $stats['transferred'] - $stats['buried'];

            $table2[] = [
                'name' => $wasteName,
                'fkko' => $stats['fkko'],
                'hazard' => $stats['hazard'],
                'balance_begin' => $start,
                'generated' => $stats['generated'],
                'received' => $stats['received'],
                'utilized' => $stats['utilized'],
                'neutralized' => $stats['neutralized'],
                'buried' => $stats['buried'],
                'transferred' => $stats['transferred'],
                'balance_end' => $end,
                'used' => $stats['utilized'] + $stats['neutralized']
            ];

            if (!empty($stats['fkko'])) {
                $table1_data[] = ['name' => $wasteName, 'fkko' => $stats['fkko'], 'hazard' => $stats['hazard']];
            }
        }

        \App\Models\JudoJournal::updateOrCreate(
            [
                'company_id' => $company->id,
                'period' => $startDate->format('Y-m-d'),
                'type' => $type,
                'role' => $roleKey
            ],
            [
                'table1_data' => $table1_data,
                'table2_data' => $table2,
                'table3_data' => $table3_data,
                'table4_data' => $table4_data,
                'is_paid' => false
            ]
        );

        return redirect()->route('journal.index')->with('success', 'Журнал успешно сформирован: ' . $periodLabel);
    }

    public function createInitialBalance(Request $request)
    {
        $period = $request->query('period', now()->format('Y-m'));
        return view('journal.initial_balance', compact('period'));
    }

    public function storeInitialBalance(Request $request)
    {
        $request->validate([
            'period' => 'required|string',
            'wastes' => 'nullable|array',
            'wastes.*.name' => 'required_with:wastes|string',
            'wastes.*.fkko' => 'nullable|string',
            'wastes.*.hazard' => 'nullable|string',
            'wastes.*.amount' => 'required_with:wastes|numeric|min:0'
        ]);

        $company = app(\App\Services\TenantService::class)->getCompany();
        if (!$company)
            abort(404);

        $periodInput = $request->input('period');
        $periodDate = now();

        try {
            if (strlen($periodInput) === 4 && is_numeric($periodInput)) {

                $periodDate = \Carbon\Carbon::createFromDate((int) $periodInput, 1, 1)->startOfYear();
            } elseif (str_contains($periodInput, '-Q')) {

                $parts = explode('-Q', $periodInput);
                $year = (int) $parts[0];
                $quarter = (int) $parts[1];
                $startMonth = ($quarter - 1) * 3 + 1;
                $periodDate = \Carbon\Carbon::createFromDate($year, $startMonth, 1)->startOfQuarter();
            } else {

                $periodDate = \Carbon\Carbon::createFromFormat('Y-m', $periodInput)->startOfMonth();
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Invalid period format');
        }

        if ($request->has('wastes') && is_array($request->wastes)) {
            foreach ($request->wastes as $waste) {
                if (empty($waste['amount']) || $waste['amount'] <= 0)
                    continue;

                \App\Models\InitialBalance::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'waste_name' => $waste['name'],
                        'period' => $periodDate->format('Y-m-d')
                    ],
                    [
                        'fkko_code' => $waste['fkko'] ?? null,
                        'hazard_class' => $waste['hazard'] ?? null,
                        'amount' => $waste['amount'],
                        'year' => $periodDate->year
                    ]
                );
            }
        }

        $roleName = session('user_role', 'Отходообразователь');
        $roleKey = ($roleName === 'Переработчик отходов') ? 'waste_processor' : 'waste_generator';

        return $this->generateJournal($company, $request->period, $roleKey);
    }

    public function show(string $id)
    {
        $company = app(\App\Services\TenantService::class)->getCompany();
        $journal = \App\Models\JudoJournal::where('company_id', $company->id)->findOrFail($id);
        $wastes = \App\Models\FkkoCode::orderBy('name')->get(['name', 'code', 'hazard_class']);

        return view('journal.show', compact('journal', 'wastes'));
    }

    public function edit(string $id)
    {

    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'table' => 'required|in:table1_data,table2_data,table3_data,table4_data',
            'row_index' => 'required|integer',
            'column' => 'required|string',
            'value' => 'nullable'
        ]);

        $company = app(\App\Services\TenantService::class)->getCompany();
        $journal = \App\Models\JudoJournal::where('company_id', $company->id)->findOrFail($id);

        $table = $request->table;
        $data = $journal->$table;

        $extraUpdates = [];

        if (isset($data[$request->row_index])) {

            $data[$request->row_index][$request->column] = $request->value;
            if ($request->column === 'waste') {
                $fkkoEntry = \App\Models\FkkoCode::where('name', $request->value)->first();
                if ($fkkoEntry) {
                    $data[$request->row_index]['fkko'] = $fkkoEntry->code;
                    $data[$request->row_index]['hazard'] = $fkkoEntry->hazard_class;

                    $extraUpdates = [
                        'fkko' => $fkkoEntry->code,
                        'hazard' => $fkkoEntry->hazard_class
                    ];
                }
            } elseif ($request->column === 'fkko') {
                $fkkoEntry = \App\Models\FkkoCode::where('code', $request->value)->first();
                if ($fkkoEntry) {
                    $data[$request->row_index]['waste'] = $fkkoEntry->name;
                    $data[$request->row_index]['hazard'] = $fkkoEntry->hazard_class;

                    $extraUpdates = [
                        'waste' => $fkkoEntry->name,
                        'hazard' => $fkkoEntry->hazard_class
                    ];
                }
            }
            if ($table === 'table3_data' && in_array($request->column, ['p_process', 'p_util', 'p_neutr', 'p_store', 'p_bury'])) {
                $row = $data[$request->row_index];
                $sum = (float) str_replace(',', '.', $row['p_process'] ?? 0) +
                    (float) str_replace(',', '.', $row['p_util'] ?? 0) +
                    (float) str_replace(',', '.', $row['p_neutr'] ?? 0) +
                    (float) str_replace(',', '.', $row['p_store'] ?? 0) +
                    (float) str_replace(',', '.', $row['p_bury'] ?? 0);

                $data[$request->row_index]['amount'] = $sum;
                $extraUpdates['amount'] = rtrim(rtrim(number_format($sum, 3), '0'), '.');
            }
            if ($table === 'table4_data' && in_array($request->column, ['p_process', 'p_util', 'p_neutr'])) {
                $row = $data[$request->row_index];
                $sum = (float) str_replace(',', '.', $row['p_process'] ?? 0) +
                    (float) str_replace(',', '.', $row['p_util'] ?? 0) +
                    (float) str_replace(',', '.', $row['p_neutr'] ?? 0);

                $data[$request->row_index]['amount'] = $sum;
                $extraUpdates['amount'] = rtrim(rtrim(number_format($sum, 3), '0'), '.');
            }
            $currentAmount = str_replace(',', '.', $data[$request->row_index]['amount'] ?? 0);
            if ((float) $currentAmount == 0) {
                unset($data[$request->row_index]);

                $journal->$table = array_values($data);
                $journal->save();
                return response()->json(['success' => true, 'action' => 'deleted']);
            }

            $journal->$table = $data;
            $journal->save();
            return response()->json(['success' => true, 'updates' => $extraUpdates]);
        }

        return response()->json(['error' => 'Row not found'], 404);
    }

    public function destroy(string $id)
    {
        $company = app(\App\Services\TenantService::class)->getCompany();
        $journal = \App\Models\JudoJournal::where('company_id', $company->id)->findOrFail($id);

        $journal->delete();

        return redirect()->route('journal.index')->with('success', 'Журнал успешно удален.');
    }

    public function download(string $id)
    {
        $user = auth()->user();
        $isSubscribed = $user->subscription_ends_at && $user->subscription_ends_at->isFuture();
        if (!$isSubscribed) {
            return back()->with('error', 'Скачивание Excel доступно только по подписке. <a href="' . route('subscription.index') . '" class="alert-link">Купить подписку</a>');
        }

        try {
            $data = $this->prepareSpreadsheet($id);
            $spreadsheet = $data['spreadsheet'];
            $filename = $data['filename'];

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename);
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка генерации Excel: ' . $e->getMessage());
        }
    }

    public function downloadPdf(string $id)
    {
        $user = auth()->user();
        $company = app(\App\Services\TenantService::class)->getCompany();
        $journal = \App\Models\JudoJournal::where('company_id', $company->id)->findOrFail($id);

        if (!($user->subscription_ends_at && $user->subscription_ends_at->isFuture())) {
            return back()->with('error', 'Скачивание PDF доступно только по подписке. <a href="' . route('subscription.index') . '" class="alert-link">Купить подписку</a>');
        }

        try {

            $periodDate = \Carbon\Carbon::parse($journal->period);
            $periodStr = \Illuminate\Support\Str::ucfirst($periodDate->translatedFormat('F Y'));
            if ($journal->type === 'year') {
                $periodStr = $periodDate->year . ' год';
            } elseif ($journal->type === 'quarter') {
                $q = ceil($periodDate->month / 3);
                $periodStr = $q . ' квартал ' . $periodDate->year . ' года';
            }
            $table1 = $journal->table1_data ?? [];
            $table2 = $journal->table2_data ?? [];
            $mapOperations = function ($items, $isReceived = false) {
                return collect($items)->map(function ($item) use ($isReceived) {
                    $qty = $item['amount'];
                    $op = mb_strtolower($item['operation'] ?? '');
                    $item['p_process'] = $item['p_process'] ?? (str_contains($op, 'обработ') ? $qty : '-');
                    $item['p_util'] = $item['p_util'] ?? (str_contains($op, 'утилиз') ? $qty : '-');
                    $item['p_neutr'] = $item['p_neutr'] ?? (str_contains($op, 'обезвреж') ? $qty : '-');

                    if (!$isReceived) {
                        $item['p_store'] = $item['p_store'] ?? (str_contains($op, 'хран') ? $qty : '-');
                        $item['p_bury'] = $item['p_bury'] ?? (str_contains($op, 'захорон') ? $qty : '-');
                    }
                    return $item;
                })->toArray();
            };

            $table3 = $mapOperations($journal->table3_data ?? [], false);
            $table4 = $mapOperations($journal->table4_data ?? [], true);
            $html = view('journal.pdf', compact('journal', 'company', 'periodStr', 'table1', 'table2', 'table3', 'table4'))->render();
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'default_font' => 'DejaVuSans'
            ]);

            $mpdf->WriteHTML($html);

            $filename = 'Журнал_' . \Illuminate\Support\Str::slug($periodStr) . '.pdf';

            return response()->streamDownload(function () use ($mpdf) {
                echo $mpdf->Output('', 'S');
            }, $filename);

        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка генерации PDF: ' . $e->getMessage());
        }
    }

    private function prepareSpreadsheet(string $id)
    {
        $company = app(\App\Services\TenantService::class)->getCompany();
        $journal = \App\Models\JudoJournal::where('company_id', $company->id)->findOrFail($id);

        $templatePath = public_path('ЖУДО.xls');
        if (!file_exists($templatePath)) {
            throw new \Exception('Шаблон файла не найден (public/ЖУДО.xls)');
        }

        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
            $spreadsheet = $reader->load($templatePath);

            $periodDate = \Carbon\Carbon::parse($journal->period);
            $periodStr = \Illuminate\Support\Str::ucfirst($periodDate->translatedFormat('F Y'));

            if ($journal->type === 'year') {
                $periodStr = $periodDate->year;
            } elseif ($journal->type === 'quarter') {
                $q = ceil($periodDate->month / 3);
                $periodStr = $q . ' квартал ' . $periodDate->year;
            }
            $sheetTitular = $spreadsheet->getSheet(0);
            foreach ($sheetTitular->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $val = $cell->getValue();
                    if (is_string($val)) {
                        if (mb_strpos($val, 'июнь 2025') !== false) {
                            $cell->setValue(str_replace('июнь 2025', $periodStr, $val));
                        }

                        if (mb_strpos($val, 'ЭкоСфера') !== false) {
                            $cell->setValue($company->name);
                        }

                        if (mb_strpos($val, 'Ларин') !== false) {
                            $cell->setValue(str_replace('Ларин И.А.', $company->contact_person ?? '', $val));
                        } elseif (mb_strpos($val, 'Руководитель') !== false) {

                        }
                    }
                }
            }
            $populateTable = function ($sheetIndex, $data, $columns) use ($spreadsheet) {
                $sheet = $spreadsheet->getSheet($sheetIndex);

                $startRow = 10;
                foreach ($sheet->getRowIterator() as $row) {
                    if ($row->getRowIndex() > 20)
                        break;
                    foreach ($row->getCellIterator() as $cell) {

                        if (trim($cell->getValue()) === '1') {
                            $startRow = $row->getRowIndex() + 1;
                            break 2;
                        }
                    }
                }

                $r = $startRow;
                $rowNum = 1;

                $rowNumCol = ($sheetIndex === 3) ? 'A' : 'B';
                $dataStartCol = ($sheetIndex === 3) ? 'B' : 'C';

                foreach ($data as $item) {
                    $sheet->setCellValue($rowNumCol . $r, $rowNum++);

                    $colIndex = $dataStartCol;

                    foreach ($columns as $key) {
                        $val = $item[$key] ?? '-';
                        if (is_numeric($val) && $key !== 'fkko' && $key !== 'hazard' && $key !== 'number' && $key !== 'date') {
                            $val = $val == 0 ? '-' : $val;
                        }
                        $sheet->setCellValue($colIndex . $r, $val);
                        $colIndex++;
                    }
                    $r++;
                }
                while ($sheet->getCell($rowNumCol . $r)->getValue() != '') {
                    $sheet->setCellValue($rowNumCol . $r, '');
                    $c = $dataStartCol;
                    for ($i = 0; $i < count($columns); $i++) {
                        $sheet->setCellValue($c . $r, '');
                        $c++;
                    }
                    $r++;
                }
            };
            $populateTable(1, $journal->table1_data ?? [], ['name', 'fkko', 'hazard']);
            $t2_data = collect($journal->table2_data)->map(function ($item) {
                $item['rec_copy'] = $item['received'];
                $item['storage'] = 0;
                return $item;
            })->toArray();

            $populateTable(2, $t2_data, [
                'name',
                'fkko',
                'hazard',
                'balance_begin',
                'generated',
                'received',
                'rec_copy',
                'utilized',
                'neutralized',
                'storage',
                'buried',
                'transferred',
                'balance_end'
            ]);

            $t3_data = collect($journal->table3_data)->map(function ($item) {
                $op = $item['operation'] ?? '';
                $qty = $item['amount'];
                $item['p_process'] = $item['p_process'] ?? (str_contains($op, 'обработ') ? $qty : '-');
                $item['p_util'] = $item['p_util'] ?? (str_contains($op, 'утилиз') ? $qty : '-');
                $item['p_neutr'] = $item['p_neutr'] ?? (str_contains($op, 'обезвреж') ? $qty : '-');
                $item['p_store'] = $item['p_store'] ?? (str_contains($op, 'хран') ? $qty : '-');
                $item['p_bury'] = $item['p_bury'] ?? (str_contains($op, 'захорон') ? $qty : '-');
                if (!isset($item['p_transf'])) {
                    $isOther = !str_contains($op, 'обработ')
                        && !str_contains($op, 'утилиз')
                        && !str_contains($op, 'обезвреж')
                        && !str_contains($op, 'хран')
                        && !str_contains($op, 'захорон');
                    $item['p_transf'] = $isOther ? $qty : '-';
                }

                $item['validity'] = '-';
                return $item;
            })->toArray();

            $populateTable(3, $t3_data, [
                'waste',
                'fkko',
                'hazard',
                'amount',
                'p_process',
                'p_util',
                'p_neutr',
                'p_store',
                'p_bury',
                'counterparty',
                'number',
                'validity'
            ]);

            $t4_data = collect($journal->table4_data)->map(function ($item) {
                $op = $item['operation'] ?? '';
                $qty = $item['amount'];
                $item['p_process'] = $item['p_process'] ?? (str_contains($op, 'обработ') ? $qty : '-');
                $item['p_util'] = $item['p_util'] ?? (str_contains($op, 'утилиз') ? $qty : '-');
                $item['p_neutr'] = $item['p_neutr'] ?? (str_contains($op, 'обезвреж') ? $qty : '-');
                $item['p_store'] = $item['p_store'] ?? (str_contains($op, 'хран') ? $qty : '-');
                $item['p_bury'] = $item['p_bury'] ?? (str_contains($op, 'захорон') ? $qty : '-');

                if (!isset($item['p_transf'])) {
                    $isOther = !str_contains($op, 'обработ') && !str_contains($op, 'утилиз') && !str_contains($op, 'обезвреж') && !str_contains($op, 'хран') && !str_contains($op, 'захорон');
                    $item['p_transf'] = $isOther ? $qty : '-';
                }

                $item['validity'] = '-';
                return $item;
            })->toArray();

            $populateTable(4, $t4_data, [
                'waste',
                'fkko',
                'hazard',
                'amount',
                'p_transf',

                'p_process',
                'p_util',
                'p_neutr',
                'p_store',
                'p_bury',
                'counterparty',
                'number',
                'validity'
            ]);
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $sheet->getSheetView()->setView(\PhpOffice\PhpSpreadsheet\Worksheet\SheetView::SHEETVIEW_NORMAL);
            }

            $filename = 'ЖУДО_' . $company->name . '_' . $periodStr . '.xls';
            $filename = str_replace(' ', '_', $filename);

            $spreadsheet->getProperties()->setTitle(str_replace('.xls', '', $filename));

            return ['spreadsheet' => $spreadsheet, 'filename' => $filename];

        } catch (\Exception $e) {
            throw $e;
        }
    }
}

