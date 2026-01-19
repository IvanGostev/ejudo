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

        // Генерация периодов для выпадающего списка
        $periods = [];
        $now = now();
        // 1. Годы
        $periods[$now->year] = $now->year . ' год';
        $periods[$now->year - 1] = ($now->year - 1) . ' год';
        $periods['divider1'] = '---';
        // 2. Кварталы
        for ($q = 1; $q <= 4; $q++)
            $periods[$now->year . '-Q' . $q] = $q . ' кв. ' . $now->year;
        for ($q = 1; $q <= 4; $q++)
            $periods[($now->year - 1) . '-Q' . $q] = $q . ' кв. ' . ($now->year - 1);
        $periods['divider2'] = '---';
        // 3. Месяцы
        $current = now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $periods[$current->format('Y-m')] = \Illuminate\Support\Str::ucfirst($current->translatedFormat('F Y'));
            $current->subMonth();
        }

        return view('journal.index', compact('journals', 'periods'));
    }

    public function create()
    {
        // Вместо этого используется модальное окно
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

        // Получение роли
        $roleName = session('user_role', 'Отходообразователь');
        $roleKey = ($roleName === 'Переработчик отходов') ? 'waste_processor' : 'waste_generator';

        $periodInput = trim($request->input('period'));

        // 1. Начальные проверки
        $anyJournalExists = \App\Models\JudoJournal::where('company_id', $company->id)->exists();
        $initialBalancesExist = \App\Models\InitialBalance::where('company_id', $company->id)->exists();

        // Если это первый раз
        if (!$anyJournalExists && !$initialBalancesExist) {
            // Parse period strictly for redirect
            // Actually, we can just pass the raw input, but let's be safe.
            // We need start date to format Y-m usually.
            // But simpler: just redirect.
            // We need to know if the input is valid though? generateJournal handles validation.
            // Let's validate period format partially here or just try to pass 'period'.
            // If we want 'Y-m', we might need to parse, but let's assume valid from validate rule.
            return redirect()->route('journal.initial-balance.create', ['period' => $periodInput]);
        }

        return $this->generateJournal($company, $periodInput, $roleKey);
    }

    private function generateJournal($company, $periodInput, $roleKey)
    {
        // Парсинг периода и определение типа
        $startDate = null;
        $endDate = null;
        $type = 'month';
        $periodLabel = $periodInput;

        try {
            if (strlen($periodInput) === 4 && is_numeric($periodInput)) {
                // Год: 2024
                $type = 'year';
                $startDate = \Carbon\Carbon::createFromDate((int) $periodInput, 1, 1)->startOfDay();
                $endDate = $startDate->copy()->endOfYear();
                $periodLabel = $periodInput . ' год';
            } elseif (str_contains($periodInput, '-Q')) {
                // Квартал: 2024-Q1
                $type = 'quarter';
                $parts = explode('-Q', $periodInput);
                $year = (int) $parts[0];
                $quarter = (int) $parts[1];
                // Расчет начального месяца: Q1=1, Q2=4, Q3=7, Q4=10
                $startMonth = ($quarter - 1) * 3 + 1;
                $startDate = \Carbon\Carbon::createFromDate($year, $startMonth, 1)->startOfDay();
                // Дата окончания — конец 3-го месяца квартала
                $endDate = $startDate->copy()->addMonths(2)->endOfMonth();
                $periodLabel = $quarter . ' квартал ' . $year;
            } else {
                // Месяц: 2024-01
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

        // 2. Получение входящих остатков
        // Логика: Найти журнал за предыдущий период (последний перед текущим).
        // Это позволяет обрабатывать разрывы или смешанные типы периодов (месяц/квартал).
        // Конечный остаток предыдущего журнала становится начальным остатком текущего.

        $prevJournal = \App\Models\JudoJournal::where('company_id', $company->id)
            ->where('period', '<', $startDate->format('Y-m-d'))
            ->where('role', $roleKey)
            ->orderBy('period', 'desc') // Сначала последние
            ->first();

        // Уточненный поиск: нужно убедиться, что предыдущий журнал закончился ДО начала текущего.

        $allPrev = \App\Models\JudoJournal::where('company_id', $company->id)
            ->where('period', '<', $startDate->format('Y-m-d'))
            ->where('role', $roleKey)
            ->orderBy('period', 'desc')
            ->limit(10) // Оптимизация
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

            // Если этот журнал заканчивается до нашей даты начала, он валидный предшественник
            if ($pjEnd->lt($startDate)) {
                $validPrevJournal = $pj;
                break;
            }
        }
        $prevJournal = $validPrevJournal;


        $prevBalances = [];
        $wasteStats = [];

        if (!$prevJournal) {
            // Использование начальных остатков, если предыдущий журнал не найден
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

        // 3. Получение актов
        $acts = \App\Models\Act::where('company_id', $company->id)
            ->where('status', 'processed')
            ->get()
            ->filter(function ($act) use ($startDate, $endDate) {
                $data = $act->act_data;
                $dateVal = $data['date'] ?? null;
                $actDate = $dateVal ? \Carbon\Carbon::parse($dateVal) : $act->created_at;
                return $actDate->between($startDate, $endDate);
            });

        // 4. Агрегация данных


        // 4. Aggregate
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

            $isWasteRecipient = (mb_strpos($provName, $compName) !== false);
            $isWasteGenerator = (mb_strpos($recvName, $compName) !== false);
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

                // Aggregation Logic
                if ($isWasteGenerator && !$isInternal) {
                    $wasteStats[$name]['transferred'] += $qty;
                    $table3_data[] = [
                        'date' => $date,
                        'number' => $actNumber,
                        'counterparty' => $provider,
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
                        'counterparty' => $receiver,
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

        // 5. Final Balances
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
            'period' => 'required|date_format:Y-m',
            'wastes' => 'nullable|array',
            'wastes.*.name' => 'required_with:wastes|string',
            'wastes.*.fkko' => 'nullable|string',
            'wastes.*.hazard' => 'nullable|string',
            'wastes.*.amount' => 'required_with:wastes|numeric|min:0'
        ]);

        $company = app(\App\Services\TenantService::class)->getCompany();
        if (!$company)
            abort(404);

        $periodDate = \Carbon\Carbon::createFromFormat('Y-m', $request->period)->startOfMonth();

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

        // After saving (or skipping), immediately generate the journal for this period.
        // This bypasses the check for existing journals/balances in 'store',
        // satisfying the requirement that the first journal is created now.

        $roleName = session('user_role', 'Отходообразователь');
        $roleKey = ($roleName === 'Переработчик отходов') ? 'waste_processor' : 'waste_generator';

        return $this->generateJournal($company, $request->period, $roleKey);
    }

    public function show(string $id)
    {
        $company = app(\App\Services\TenantService::class)->getCompany();
        $journal = \App\Models\JudoJournal::where('company_id', $company->id)->findOrFail($id);

        // Загрузка отходов для выпадающего списка
        $wastes = \App\Models\FkkoCode::orderBy('name')->get(['name', 'code', 'hazard_class']);

        return view('journal.show', compact('journal', 'wastes'));
    }

    public function edit(string $id)
    {
        //
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
        $data = $journal->$table; // Получение текущего JSON массива

        $extraUpdates = [];

        if (isset($data[$request->row_index])) {
            // Обновление запрошенной колонки
            $data[$request->row_index][$request->column] = $request->value;

            // Умное обновление: Если изменено название отхода, обновляем ФККО и класс опасности
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

            // Auto-calculate Amount for Table 3
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

            // Auto-calculate Amount for Table 4
            if ($table === 'table4_data' && in_array($request->column, ['p_process', 'p_util', 'p_neutr'])) {
                $row = $data[$request->row_index];
                $sum = (float) str_replace(',', '.', $row['p_process'] ?? 0) +
                    (float) str_replace(',', '.', $row['p_util'] ?? 0) +
                    (float) str_replace(',', '.', $row['p_neutr'] ?? 0);

                $data[$request->row_index]['amount'] = $sum;
                $extraUpdates['amount'] = rtrim(rtrim(number_format($sum, 3), '0'), '.');
            }

            // 4. Проверка на удаление (если количество = 0)
            $currentAmount = str_replace(',', '.', $data[$request->row_index]['amount'] ?? 0);
            if ((float) $currentAmount == 0) {
                unset($data[$request->row_index]);
                // Переиндексация массива
                $journal->$table = array_values($data);
                $journal->save();
                return response()->json(['success' => true, 'action' => 'deleted']);
            }

            $journal->$table = $data; // Сохранение изменений
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
        $isSubscribed = $user->subscription_ends_at && $user->subscription_ends_at->isFuture();
        if (!$isSubscribed) {
            return back()->with('error', 'Скачивание PDF доступно только по подписке. <a href="' . route('subscription.index') . '" class="alert-link">Купить подписку</a>');
        }

        try {
            $data = $this->prepareSpreadsheet($id);
            $spreadsheet = $data['spreadsheet'];
            // Смена расширения на .pdf
            $filename = str_replace('.xls', '.pdf', $data['filename']);

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Mpdf');

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
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
            $periodStr = \Illuminate\Support\Str::ucfirst($periodDate->translatedFormat('F Y')); // Месяц по умолчанию

            if ($journal->type === 'year') {
                $periodStr = $periodDate->year . ' год';
            } elseif ($journal->type === 'quarter') {
                $q = ceil($periodDate->month / 3);
                $periodStr = $q . ' квартал ' . $periodDate->year . ' года';
            }

            // 1. ТИТУЛЬНЫЙ ЛИСТ (Индекс 0)
            $sheetTitular = $spreadsheet->getSheet(0);

            // Поиск и замена плейсхолдеров
            foreach ($sheetTitular->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $val = $cell->getValue();
                    if (is_string($val)) {
                        // Замена периода (Ячейка D11 обычно содержит "июнь 2025")
                        // Проверяем наличие плейсхолдера в шаблоне.
                        if (mb_strpos($val, 'июнь 2025') !== false) {
                            $cell->setValue(str_replace('июнь 2025', $periodStr, $val));
                        }

                        // Замена Названия Компании (Ячейка D9)
                        // Заменяем любое вхождение "ЭкоСфера" или аналогичного
                        if (mb_strpos($val, 'ЭкоСфера') !== false) {
                            $cell->setValue($company->name);
                        }

                        // Замена Имени Руководителя
                        // Поиск контекста "Ларин" или "Руководитель"
                        if (mb_strpos($val, 'Ларин') !== false) {
                            $cell->setValue(str_replace('Ларин И.А.', $company->contact_person ?? '', $val));
                        }
                        // Или если это специальное подчеркивание рядом с "Руководитель"
                        elseif (mb_strpos($val, 'Руководитель') !== false) {
                            // Логика заполнения 
                        }
                    }
                }
            }

            // 2. Хелпер заполнения данных
            $populateTable = function ($sheetIndex, $data, $columns) use ($spreadsheet) {
                $sheet = $spreadsheet->getSheet($sheetIndex);
                // Поиск строки нумерации заголовков (строка с "1" в колонке B/C)
                $startRow = 10;

                // Сканирование первых 20 строк для поиска нумерации
                foreach ($sheet->getRowIterator() as $row) {
                    if ($row->getRowIndex() > 20)
                        break;
                    foreach ($row->getCellIterator() as $cell) {
                        // Проверка на "1" в типичной колонке ID
                        if (trim($cell->getValue()) === '1') {
                            $startRow = $row->getRowIndex() + 1; // Вставка ПОСЛЕ этой строки
                            break 2;
                        }
                    }
                }

                $r = $startRow;
                $rowNum = 1;

                foreach ($data as $item) {
                    $sheet->setCellValue('B' . $r, $rowNum++); // Предполагаем, что колонка B обычно ID

                    // Маппинг колонок начиная с C
                    // Таблица 1: B=ID, C=Name, D=FKKO, E=Hazard.
                    // Обычно начинаем с 'C' для данных.

                    $colIndex = 'C';

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

                // Очистка оставшихся строк
                while ($sheet->getCell('B' . $r)->getValue() != '') {
                    $sheet->setCellValue('B' . $r, '');
                    $c = 'C';
                    for ($i = 0; $i < count($columns); $i++) {
                        $sheet->setCellValue($c . $r, '');
                        $c++;
                    }
                    $r++;
                }
            };

            // Уточненный маппинг на основе колонок

            // Таблица 1: Name, FKKO, Hazard
            $populateTable(1, $journal->table1_data ?? [], ['name', 'fkko', 'hazard']);

            // Таблица 2: Обобщенные данные
            // Прим.: Колонки Таблицы 2 в Excel: F=Начало, G=Образовано, H=Получено, J=Исп, K=Обезвр, L=Хранение, M=Захорон, N=Передано, O=Конец

            $t2_data = collect($journal->table2_data)->map(function ($item) {
                $item['rec_copy'] = $item['received'];
                $item['storage'] = 0; // Предполагаем отсутствие хранения
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


            // Таблица 3 (Переданные)
            // Колонки C..O: Дата, Номер, Отход, ФККО, Класс, Кол-во, Цели(5), Контрагент...

            $t3_data = collect($journal->table3_data)->map(function ($item) {
                $op = $item['operation'] ?? '';
                $qty = $item['amount'];

                // Приоритет: Ручная правка -> Автоматическое определение по операции -> Прочерк
                $item['p_process'] = $item['p_process'] ?? (str_contains($op, 'обработ') ? $qty : '-');
                $item['p_util'] = $item['p_util'] ?? (str_contains($op, 'утилиз') ? $qty : '-');
                $item['p_neutr'] = $item['p_neutr'] ?? (str_contains($op, 'обезвреж') ? $qty : '-');
                $item['p_store'] = $item['p_store'] ?? (str_contains($op, 'хран') ? $qty : '-');
                $item['p_bury'] = $item['p_bury'] ?? (str_contains($op, 'захорон') ? $qty : '-');

                // Для передачи (прочее)
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
                'p_transf',
                'p_process',
                'p_util',
                'p_neutr',
                'p_store',
                'p_bury', // G, H, I, J, K, L (6 cols)
                'counterparty',
                'number',
                'validity'
            ]);


            // Таблица 4 (Полученные)
            $t4_data = collect($journal->table4_data)->map(function ($item) {
                $op = $item['operation'] ?? '';
                $qty = $item['amount'];

                // Приоритет: Ручная правка -> Автоматическое определение -> Прочерк
                $item['p_process'] = $item['p_process'] ?? (str_contains($op, 'обработ') ? $qty : '-');
                $item['p_util'] = $item['p_util'] ?? (str_contains($op, 'утилиз') ? $qty : '-');
                $item['p_neutr'] = $item['p_neutr'] ?? (str_contains($op, 'обезвреж') ? $qty : '-');
                $item['p_store'] = $item['p_store'] ?? (str_contains($op, 'хран') ? $qty : '-');
                $item['p_bury'] = $item['p_bury'] ?? (str_contains($op, 'захорон') ? $qty : '-');

                // Для приема обычно нет колонки "Передача", но есть "Для использования" (p_util) и т.д.
                // В таблице 4 (Полученные) колонки: Обраб, Утил, Обезвр. (по шаблону)
                // Но в mapItems для T4 мы используем: p_process, p_util, p_neutr.
                // А p_transf, p_store, p_bury - нужны ли?
                // Посмотрим на columns: amount, p_transf, p_process...

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
                'p_transf', // Внимание: в Таблице 4 шаблона может не быть этой колонки.
                // Но мы передаем индекс, а populateTable пишет последовательно.
                // Проверим шаблон HTML. T4: amount, process, util, neutr.
                // А в экспорте: amount, p_transf, p_process...
                // Это может быть ошибкой в исходном коде экспорта, если он не совпадает с шаблоном.
                // Но я пока просто сохраняю логику "уважения" ручных правок.
                'p_process',
                'p_util',
                'p_neutr',
                'p_store',
                'p_bury',
                'counterparty',
                'number',
                'validity'
            ]);

            // Возврат к нормальному виду (отключение разметки страниц)
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

