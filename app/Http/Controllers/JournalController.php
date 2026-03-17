<?php

namespace App\Http\Controllers;

use App\Models\Act;
use App\Models\FkkoCode;
use App\Models\InitialBalance;
use App\Models\JudoJournal;
use App\Services\PolygonModeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class JournalController extends Controller
{
    private function formatFkko($code)
    {
        $clean = str_replace(' ', '', $code);
        if (strlen($clean) === 11) {
            return substr($clean, 0, 1) . ' ' .
                   substr($clean, 1, 2) . ' ' .
                   substr($clean, 3, 3) . ' ' .
                   substr($clean, 6, 2) . ' ' .
                   substr($clean, 8, 2) . ' ' .
                   substr($clean, 10, 1);
        }
        return $code;
    }

    public function index(Request $request)
    {
        $tenantService = app(\App\Services\TenantService::class);
        $company = $tenantService->getCompany();

        $hasPolygons = PolygonModeService::isEnabled($company);
        $polygons    = $hasPolygons
            ? $company->polygons()->where('status', 'active')->orderBy('name')->get()
            : collect();


        $selectedPolygonId = $hasPolygons ? $request->query('polygon_id') : null;
        $selectedPolygon   = null;

        $journals = collect();
        if ($company) {
            $query = JudoJournal::where('company_id', $company->id)
                ->with(['polygon']);

            if ($selectedPolygonId) {
                $query->where('polygon_id', $selectedPolygonId);
                $selectedPolygon = $polygons->firstWhere('id', $selectedPolygonId);
            }

            $journals = $query->orderBy('period', 'desc')->get();
        }

        $periods = [];
        $now = now();

        $periods[$now->year]       = $now->year . ' год';
        $periods[$now->year - 1]   = ($now->year - 1) . ' год';
        $periods['divider1']       = '---';

        for ($q = 1; $q <= 4; $q++)
            $periods[$now->year . '-Q' . $q] = $q . ' кв. ' . $now->year;
        for ($q = 1; $q <= 4; $q++)
            $periods[($now->year - 1) . '-Q' . $q] = $q . ' кв. ' . ($now->year - 1);
        $periods['divider2'] = '---';

        $current = now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $periods[$current->format('Y-m')] = Str::ucfirst($current->translatedFormat('F Y'));
            $current->subMonth();
        }

        return view('journal.index', compact(
            'journals', 'periods', 'hasPolygons', 'polygons', 'selectedPolygon'
        ));
    }

    public function store(Request $request)
    {
        $company = app(\App\Services\TenantService::class)->getCompany();
        if (!$company) {
            return back()->with('error', 'Компания не выбрана.');
        }

        $hasPolygons = PolygonModeService::isEnabled($company);

        $rules = ['period' => 'required|string'];
        if ($hasPolygons) {
            $rules['polygon_id'] = 'required|exists:polygons,id';
        }
        $request->validate($rules, [
            'polygon_id.required' => 'Выберите полигон для формирования журнала.',
            'polygon_id.exists'   => 'Выбранный полигон не найден.',
        ]);


        $polygonId = null;
        if ($hasPolygons) {
            $polygon = $company->polygons()->findOrFail($request->integer('polygon_id'));
            $polygonId = $polygon->id;
        }

        $roleName = session('user_role', 'Отходообразователь');
        $roleKey = ($roleName === 'Переработчик отходов') ? 'waste_processor' : 'waste_generator';

        $periodInput = trim($request->input('period'));
        $anyJournalExists = JudoJournal::where('company_id', $company->id)->exists();
        $initialBalancesExist = InitialBalance::where('company_id', $company->id)->exists();

        if (!$anyJournalExists && !$initialBalancesExist) {
            return redirect()->route('journal.initial-balance.create', ['period' => $periodInput]);
        }

        return $this->generateJournal($company, $periodInput, $roleKey, $polygonId);
    }

    private function generateJournal($company, $periodInput, $roleKey, $polygonId = null)
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
            return back()->with('error', 'Неверный формат периода: ' . $periodInput);
        }


        $prevJournal = JudoJournal::where('company_id', $company->id)
            ->where('period', '<', $startDate->format('Y-m-d'))
            ->where('role', $roleKey)
            ->orderBy('period', 'desc')
            ->first();


        $prevBalances = [];
        $wasteStats = [];

        if (!$prevJournal) {
            $initials = InitialBalance::where('company_id', $company->id)->get();
            foreach ($initials as $init) {
                $f_code = $this->formatFkko($init->fkko_code);
                $prevBalances[$init->waste_name] = (float) $init->amount;
                $wasteStats[$init->waste_name] = $this->emptyStats($f_code, $init->hazard_class);
            }
        } else {
            foreach ($prevJournal->table2_data as $item) {
                $f_code = $this->formatFkko($item['fkko'] ?? '');
                $prevBalances[$item['name']] = (float) $item['balance_end'];
                $wasteStats[$item['name']] = $this->emptyStats($f_code, $item['hazard'] ?? '');
            }
        }

        $acts = Act::where('company_id', $company->id)
            ->where('status', 'processed')
            ->get()
            ->filter(function ($act) use ($startDate, $endDate) {
                $dateVal = $act->act_data['date'] ?? null;
                $actDate = $dateVal ? \Carbon\Carbon::parse($dateVal) : $act->created_at;
                return $actDate->between($startDate, $endDate);
            });

        $table3_data = [];
        $table4_data = [];

        foreach ($acts as $act) {
            $data = $act->act_data;
            $items = $data['items'] ?? [];
            $provider = mb_strtolower($data['provider'] ?? '');
            $receiver = mb_strtolower($data['receiver'] ?? '');
            $compName = mb_strtolower($company->name);

            $isWasteGenerator = (str_contains($provider, $compName));
            $isWasteRecipient = (str_contains($receiver, $compName));
            $isInternal = ($isWasteRecipient && $isWasteGenerator);

            $actType = $act->act_type ?? 'transfer';

            foreach ($items as $item) {
                $name = $item['name'] ?? 'Unknown';
                $qty = (float) ($item['quantity'] ?? 0);

                $fkko = $this->formatFkko($item['fkko_code'] ?? '');
                $hazard = $item['hazard_class'] ?? '';

                $opItemOriginal = $item['operation_type'] ?? '';
                $opArr = array_filter(array_map('trim', explode(',', $opItemOriginal)), function($op) {
                    return mb_strtolower($op) !== 'транспортирование';
                });
                $opItem = mb_strtolower(implode(', ', $opArr));

                if (empty($opArr) && empty($actType)) {
                    continue;
                }

                if (!isset($wasteStats[$name])) {
                    $wasteStats[$name] = $this->emptyStats($fkko, $hazard);
                }


                if (str_contains($opItem, 'образован')) {
                    $wasteStats[$name]['generated'] += $qty;
                }

                if ($isWasteRecipient && !$isInternal) {
                    $wasteStats[$name]['received'] += $qty;
                }

                if ($isWasteGenerator && !$isInternal && $actType === 'transfer') {
                    $wasteStats[$name]['transferred_total'] += $qty;
                }

                if ($actType === 'processing') {
                    $wasteStats[$name]['processed'] += $qty;
                } elseif ($actType === 'utilization') {
                    $wasteStats[$name]['utilized'] += $qty;
                } elseif ($actType === 'neutralization') {
                    $wasteStats[$name]['neutralized'] += $qty;
                } elseif ($actType === 'storage') {
                    $wasteStats[$name]['stored'] += $qty;
                } elseif ($actType === 'burial') {
                    $wasteStats[$name]['buried'] += $qty;
                }

                if ($isWasteGenerator && !$isInternal && $actType === 'transfer') {
                    $table3_data[] = [
                        'date'              => $data['date'] ?? '',
                        'number'            => $act->act_number,
                        'counterparty'      => $data['receiver'] ?? '',
                        'waste'             => $name,
                        'fkko'              => $fkko,
                        'hazard'            => $hazard,
                        'amount'            => $qty,
                        'operation'         => $opItem,
                        'amt_process'       => 0,
                        'amt_util'          => 0,
                        'amt_neutr'         => 0,
                        'amt_store'         => 0,
                        'amt_bury'          => 0,
                        'contract_details'  => $data['contract_details'] ?? '',
                        'contract_validity' => '',
                        'license'           => $company->license_details ?? '',
                    ];
                }

                if ($isWasteRecipient && !$isInternal) {
                    $table4_data[] = [
                        'date'              => $data['date'] ?? '',
                        'number'            => $act->act_number,
                        'counterparty'      => $data['provider'] ?? '',
                        'waste'             => $name,
                        'fkko'              => $fkko,
                        'hazard'            => $hazard,
                        'amount'            => $qty,
                        'operation'         => $opItem,
                        'amt_third_party'   => str_contains($opItem, 'третьим лицам') ? $qty : 0,
                        'amt_process'       => str_contains($opItem, 'обработ') ? $qty : 0,
                        'amt_util'          => str_contains($opItem, 'утилиз') ? $qty : 0,
                        'amt_neutr'         => str_contains($opItem, 'обезвреж') ? $qty : 0,
                        'amt_store'         => str_contains($opItem, 'хран') ? $qty : 0,
                        'amt_bury'          => str_contains($opItem, 'захорон') ? $qty : 0,
                        'contract_details'  => $data['contract_details'] ?? '',
                        'contract_validity' => '',
                        'license'           => $company->license_details ?? '',
                    ];
                }
            }
        }

        $table2 = [];
        $uniqueWastes = array_unique(array_merge(array_keys($prevBalances), array_keys($wasteStats)));
        foreach ($uniqueWastes as $wasteName) {
            $start = $prevBalances[$wasteName] ?? 0;
            $s = $wasteStats[$wasteName];


            $end = $start + $s['generated'] + $s['received'] - $s['processed'] - $s['utilized'] - $s['neutralized'] - $s['transferred_total'] - $s['buried'];

            $table2[] = [
                'name' => $wasteName,
                'fkko' => $s['fkko'],
                'hazard' => $s['hazard'],
                'balance_begin' => $start,
                'generated' => $s['generated'],
                'received' => $s['received'],
                'processed' => $s['processed'],
                'utilized' => $s['utilized'],
                'neutralized' => $s['neutralized'],
                'transferred_total' => $s['transferred_total'],
                'trans_process' => $s['trans_process'],
                'trans_util' => $s['trans_util'],
                'trans_neutr' => $s['trans_neutr'],
                'trans_store' => $s['trans_store'],
                'trans_bury' => $s['trans_bury'],
                'stored' => $s['stored'],
                'buried' => $s['buried'],
                'accumulated' => $end,
                'balance_end' => $end
            ];
        }

        JudoJournal::updateOrCreate(
            [
                'company_id' => $company->id,
                'period'     => $startDate->format('Y-m-d'),
                'type'       => $type,
                'role'       => $roleKey,
                'polygon_id' => $polygonId,
            ],
            [
                'table1_data' => array_values(collect($table2)->map(fn($x) => ['name' => $x['name'], 'fkko' => $x['fkko'], 'hazard' => $x['hazard']])->toArray()),
                'table2_data' => $table2,
                'table3_data' => $table3_data,
                'table4_data' => $table4_data,
            ]
        );

        return redirect()->route('journal.index')->with('success', 'Журнал сформирован: ' . $periodLabel);
    }

    private function emptyStats($fkko = '', $hazard = '') {
        return [
            'generated' => 0, 'received' => 0, 'processed' => 0, 'utilized' => 0, 'neutralized' => 0,
            'transferred_total' => 0, 'trans_process' => 0, 'trans_util' => 0, 'trans_neutr' => 0, 'trans_store' => 0, 'trans_bury' => 0,
            'stored' => 0, 'buried' => 0, 'fkko' => $fkko, 'hazard' => $hazard
        ];
    }

    public function createInitialBalance(Request $request) {
        $period = $request->query('period', now()->format('Y-m'));
        return view('journal.initial_balance', compact('period'));
    }

    public function storeInitialBalance(Request $request) {
        $request->validate(['period' => 'required', 'wastes' => 'nullable|array']);
        $company = app(\App\Services\TenantService::class)->getCompany();
        if (!$company) abort(404);

        $periodDate = \Carbon\Carbon::parse($request->period);
        foreach ($request->wastes ?? [] as $waste) {
            if (empty($waste['amount']) || $waste['amount'] <= 0) continue;
            InitialBalance::updateOrCreate(
                ['company_id' => $company->id, 'waste_name' => $waste['name'], 'period' => $periodDate->format('Y-m-d')],
                ['fkko_code' => $waste['fkko'], 'hazard_class' => $waste['hazard'], 'amount' => $waste['amount'], 'year' => $periodDate->year]
            );
        }
        return $this->generateJournal($company, $request->period, session('user_role') === 'Переработчик отходов' ? 'waste_processor' : 'waste_generator');
    }

    public function assignPolygon(Request $request, string $id)
    {
        $company = app(\App\Services\TenantService::class)->getCompany();
        $journal = JudoJournal::where('company_id', $company->id)->findOrFail($id);

        $polygonId = $request->input('polygon_id');

        if ($polygonId) {

            $company->polygons()->findOrFail($polygonId);
        }

        $journal->polygon_id = $polygonId ?: null;
        $journal->save();

        return back()->with('success', $polygonId
            ? 'Полигон привязан к журналу.'
            : 'Привязка полигона снята.'
        );
    }

    public function show(string $id) {
        $company = app(\App\Services\TenantService::class)->getCompany();
        $journal = JudoJournal::where('company_id', $company->id)->findOrFail($id);
        $wastes  = FkkoCode::orderBy('name')->get(['name', 'code', 'hazard_class']);

        $hasPolygons = \App\Services\PolygonModeService::isEnabled($company);
        $polygons    = $hasPolygons
            ? $company->polygons()->where('status', 'active')->orderBy('name')->get()
            : collect();

        return view('journal.show', compact('journal', 'wastes', 'hasPolygons', 'polygons'));
    }

    public function update(Request $request, string $id) {
        $journal = JudoJournal::findOrFail($id);
        $data = $journal->{$request->table};
        $data[$request->row_index][$request->column] = $request->value;
        $journal->{$request->table} = $data;
        $journal->save();
        return response()->json(['success' => true]);
    }

    public function destroy(string $id) {
        JudoJournal::where('company_id', app(\App\Services\TenantService::class)->getCompany()->id)->findOrFail($id)->delete();
        return redirect()->route('journal.index')->with('success', 'Журнал удален.');
    }

    public function download(string $id) {
        $user = auth()->user();
        if (!$user->subscription_ends_at || $user->subscription_ends_at->isPast()) {
            return back()->with('error', 'Скачивание Excel доступно по подписке.');
        }

        $data = $this->prepareSpreadsheet($id);
        $writer = IOFactory::createWriter($data['spreadsheet'], 'Xls');
        return response()->streamDownload(fn() => $writer->save('php:
    }

    private function prepareSpreadsheet(string $id) {
        $company = app(\App\Services\TenantService::class)->getCompany();
        $journal = JudoJournal::where('company_id', $company->id)->findOrFail($id);
        $templatePath = public_path('ЖУДО.xls');
        $spreadsheet = IOFactory::load($templatePath);

        $periodDate = \Carbon\Carbon::parse($journal->period);
        $startDate = $periodDate->copy()->startOfMonth();
        $endDate = $periodDate->copy()->endOfMonth();
        $periodStr = Str::ucfirst($periodDate->translatedFormat('F Y'));

        if ($journal->type === 'year') {
            $periodStr = $periodDate->year . ' год';
            $startDate = $periodDate->copy()->startOfYear();
            $endDate = $periodDate->copy()->endOfYear();
        } elseif ($journal->type === 'quarter') {
            $q = ceil($periodDate->month / 3);
            $periodStr = $q . ' квартал ' . $periodDate->year . ' года';
            $startDate = $periodDate->copy()->startOfQuarter();
            $endDate = $periodDate->copy()->endOfQuarter();
        }


        $sheet0 = $spreadsheet->getSheet(0);

        $monthsGenitive = [
            1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
            5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
            9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'
        ];
        $currentMonthRus = $monthsGenitive[(int)date('n')];
        $currentDateLine = '" ' . date('d') . ' " ' . $currentMonthRus . ' ' . date('Y') . ' г.';

        $sheet0->setCellValue('F3', 'Генеральный директор ' . ($company->name ?? ''));
        $sheet0->setCellValue('M5', $company->contact_person ?? '');
        $sheet0->setCellValue('M8', $currentDateLine);
        
        $sheet0->setCellValue('D13', $company->name ?? '');
        $sheet0->setCellValue('C21', $company->name ?? '');

        $sheet0->setCellValue('D27', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($startDate));
        $sheet0->getStyle('D27')->getNumberFormat()->setFormatCode('dd.mm.yyyy');
        
        $sheet0->setCellValue('D29', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($endDate));
        $sheet0->getStyle('D29')->getNumberFormat()->setFormatCode('dd.mm.yyyy');


        $populate = function($sheetIdx, $data, $columns, $startRow, $numCol = 'B', $dataStart = 3) use ($spreadsheet) {
            $sheet = $spreadsheet->getSheet($sheetIdx);
            $r = $startRow;
            foreach ($data as $idx => $item) {

                $sheet->setCellValue($numCol . $r, $idx + 1);
                $sheet->getStyle($numCol . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($numCol . $r)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle($numCol . $r)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE);

                $colIndex = $dataStart;
                foreach ($columns as $key) {
                    $val = $item[$key] ?? 0;
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->setCellValue($colLetter . $r, $val);
                    $sheet->getStyle($colLetter . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle($colLetter . $r)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle($colLetter . $r)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $sheet->getStyle($colLetter . $r)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE);
                    $colIndex++;
                }

                if ($sheetIdx === 1) {
                    for ($extra = $colIndex; $extra <= 8; $extra++) {
                        $colLetter = Coordinate::stringFromColumnIndex($extra);
                        $sheet->setCellValue($colLetter . $r, '-');
                        $sheet->getStyle($colLetter . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle($colLetter . $r)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                        $sheet->getStyle($colLetter . $r)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE);
                    }
                }

                $sheet->getRowDimension($r)->setRowHeight(-1);
                $r++;
            }
        };

        $populate(1, $journal->table1_data, ['name', 'fkko', 'hazard'], 7, 'B', 3);

        $table2_excel_data = collect($journal->table2_data)->map(function ($item) {
            $item['start_storage'] = $item['start_storage'] ?? 0;
            $item['start_accumulation'] = $item['start_accumulation'] ?? $item['balance_begin'] ?? 0;
            $item['placed_total'] = ($item['stored'] ?? 0) + ($item['buried'] ?? 0);
            $item['end_storage'] = $item['end_storage'] ?? 0;
            $item['end_accumulation'] = $item['end_accumulation'] ?? $item['balance_end'] ?? 0;
            return $item;
        })->toArray();
        $populate(2, $table2_excel_data, [
            'name', 'fkko', 'hazard', 'start_storage', 'start_accumulation', 'generated', 'received',
            'processed', 'utilized', 'neutralized', 'transferred_total',
            'placed_total', 'stored', 'buried', 'end_storage', 'end_accumulation'
        ], 9, 'B', 3);

        $populate(3, $journal->table3_data, [
            'waste', 'fkko', 'hazard', 'amount', 'amt_process', 'amt_util', 'amt_neutr', 'amt_store', 'amt_bury', 'counterparty', 'contract_details', 'contract_validity', 'license'
        ], 8, 'A', 2);

        $populate(4, $journal->table4_data, [
            'waste', 'fkko', 'hazard', 'amount', 'amt_third_party', 'amt_process', 'amt_util', 'amt_neutr', 'amt_store', 'amt_bury', 'counterparty', 'contract_details', 'contract_validity', 'license'
        ], 10, 'B', 3);

        $filename = "ЖУДО_" . str_replace(' ', '_', $company->name) . "_" . $periodDate->format('Y-m') . ".xls";
        return ['spreadsheet' => $spreadsheet, 'filename' => $filename];
    }
}
