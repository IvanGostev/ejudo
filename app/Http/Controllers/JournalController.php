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

        // Фильтр по полигону (GET-параметр polygon_id)
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

        // Дополнительно проверяем, что полигон принадлежит этой компании
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

        // Поиск последнего журнала
        $prevJournal = JudoJournal::where('company_id', $company->id)
            ->where('period', '<', $startDate->format('Y-m-d'))
            ->where('role', $roleKey)
            ->orderBy('period', 'desc')
            ->first();

        // Проверяем актуальность остатков
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

            foreach ($items as $item) {
                $name = $item['name'] ?? 'Unknown';
                $qty = (float) ($item['quantity'] ?? 0);
                $opItem = mb_strtolower($item['operation_type'] ?? '');
                $fkko = $this->formatFkko($item['fkko_code'] ?? '');
                $hazard = $item['hazard_class'] ?? '';

                if (!isset($wasteStats[$name])) {
                    $wasteStats[$name] = $this->emptyStats($fkko, $hazard);
                }
                
                // Внутренние операции (Сами себе передали или образовали)
                if ($isInternal || (str_contains($opItem, 'образован'))) {
                    if (str_contains($opItem, 'образован')) $wasteStats[$name]['generated'] += $qty;
                    if (str_contains($opItem, 'обработ')) $wasteStats[$name]['processed'] += $qty;
                    if (str_contains($opItem, 'утилиз')) $wasteStats[$name]['utilized'] += $qty;
                    if (str_contains($opItem, 'обезвреж')) $wasteStats[$name]['neutralized'] += $qty;
                    if (str_contains($opItem, 'хран')) $wasteStats[$name]['stored'] += $qty;
                    if (str_contains($opItem, 'захорон')) $wasteStats[$name]['buried'] += $qty;
                } 
                // Передача другим лицам
                elseif ($isWasteGenerator) {
                    $wasteStats[$name]['transferred_total'] += $qty;
                    if (str_contains($opItem, 'обработ')) {
                        $wasteStats[$name]['trans_process'] += $qty;
                    } elseif (str_contains($opItem, 'утилиз')) {
                        $wasteStats[$name]['trans_util'] += $qty;
                    } elseif (str_contains($opItem, 'обезвреж')) {
                        // Передано на обезвреживание → в Excel ст.14 (trans_neutr) И ст.10 (neutralized)
                        $wasteStats[$name]['trans_neutr']   += $qty;
                        $wasteStats[$name]['neutralized']   += $qty;
                    } elseif (str_contains($opItem, 'хран')) {
                        $wasteStats[$name]['trans_store'] += $qty;
                    } elseif (str_contains($opItem, 'захорон')) {
                        $wasteStats[$name]['trans_bury'] += $qty;
                    }

                    $table3_data[] = [
                        'date' => $data['date'] ?? '',
                        'number' => $act->act_number,
                        'counterparty' => $data['receiver'] ?? '',
                        'waste' => $name,
                        'fkko' => $fkko,
                        'hazard' => $hazard,
                        'amount' => $qty,
                        'operation' => $opItem
                    ];
                }
                // Получение от других лиц
                elseif ($isWasteRecipient) {
                    $wasteStats[$name]['received'] += $qty;
                    if (str_contains($opItem, 'обработ')) $wasteStats[$name]['processed'] += $qty;
                    elseif (str_contains($opItem, 'утилиз')) $wasteStats[$name]['utilized'] += $qty;
                    elseif (str_contains($opItem, 'обезвреж')) $wasteStats[$name]['neutralized'] += $qty;
                    elseif (str_contains($opItem, 'хран')) $wasteStats[$name]['stored'] += $qty;
                    elseif (str_contains($opItem, 'захорон')) $wasteStats[$name]['buried'] += $qty;

                    $table4_data[] = [
                        'date' => $data['date'] ?? '',
                        'number' => $act->act_number,
                        'counterparty' => $data['provider'] ?? '',
                        'waste' => $name,
                        'fkko' => $fkko,
                        'hazard' => $hazard,
                        'amount' => $qty,
                        'operation' => $opItem,
                        'license' => $company->license_details // Лицензия получателя (наша) п.18
                    ];
                }
            }
        }

        $table2 = [];
        $uniqueWastes = array_unique(array_merge(array_keys($prevBalances), array_keys($wasteStats)));
        foreach ($uniqueWastes as $wasteName) {
            $start = $prevBalances[$wasteName] ?? 0;
            $s = $wasteStats[$wasteName];
            
            // Расчет конечного остатка (п. 16: накопление - это все, что осталось)
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
                'accumulated' => $end, // Накопление ст.16 в веб-журнале
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
            // Проверяем что полигон принадлежит компании
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
        return response()->streamDownload(fn() => $writer->save('php://output'), $data['filename']);
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

        // Лист 0: Титульный (п.15: явно задаём строковый формат для дат)
        $sheet0 = $spreadsheet->getSheet(0);
        $sheet0->setCellValue('C12', $periodStr);
        // Устанавливаем даты как строки, чтобы Excel не переконвертировал их
        $sheet0->setCellValueExplicit('C14', $startDate->format('d.m.Y'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet0->setCellValueExplicit('E14', $endDate->format('d.m.Y'),   \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        $sheet0->setCellValue('C17', $company->name);
        $sheet0->setCellValue('C19', $company->inn);
        $sheet0->setCellValue('C21', $company->ogrn);
        $sheet0->setCellValue('C23', $company->legal_address);

        // Функция-помощник для заполнения таблиц с центрированием (п. 14)
        $populate = function($sheetIdx, $data, $columns, $startRow = 11) use ($spreadsheet) {
            $sheet = $spreadsheet->getSheet($sheetIdx);
            $r = $startRow;
            foreach ($data as $idx => $item) {
                $numCell = $sheet->getCell('A'.$r);
                $numCell->setValue($idx + 1);
                $sheet->getStyle('A'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $colIndex = 1; // Колонки B, C, D...
                foreach ($columns as $key) {
                    $val = $item[$key] ?? '-';
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
                    $cell = $sheet->getCell($colLetter . $r);
                    $cell->setValue($val === 0 ? '-' : $val);

                    // Центрирование всех ячеек (п. 14)
                    $sheet->getStyle($colLetter . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $colIndex++;
                }
                $r++;
            }
        };

        // Таблица 1
        $populate(1, $journal->table1_data, ['name', 'fkko', 'hazard'], 11);

        // Таблица 2 (п. 16: маппинг 17 столбцов)
        // Ст.11 - transferred_total, Ст.12 - trans_process, Ст.13 - trans_util, Ст.14 - trans_neutr
        $populate(2, $journal->table2_data, [
            'name', 'fkko', 'hazard', 'balance_begin', 'generated', 'received', 
            'processed', 'utilized', 'neutralized', 'transferred_total', 
            'trans_process', 'trans_util', 'trans_neutr', 'trans_store', 'trans_bury',
            'stored', 'balance_end' 
        ], 13);

        // Таблица 3
        $populate(3, $journal->table3_data, [
            'date', 'number', 'waste', 'fkko', 'hazard', 'counterparty', 'amount'
        ], 11);

        // Таблица 4 (п. 18: включая лицензию в ст.14, если она в маппинге столбцов шаблона)
        $populate(4, $journal->table4_data, [
            'date', 'number', 'waste', 'fkko', 'hazard', 'counterparty', 'amount', 'license'
        ], 11);

        $filename = "ЖУДО_" . str_replace(' ', '_', $company->name) . "_" . $periodDate->format('Y-m') . ".xls";
        return ['spreadsheet' => $spreadsheet, 'filename' => $filename];
    }
}
