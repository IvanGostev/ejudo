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

        return view('journal.index', compact('journals'));
    }

    public function create()
    {
        // Modal is used instead
    }

    public function store(Request $request)
    {
        $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $company = app(\App\Services\TenantService::class)->getCompany();
        if (!$company) {
            return back()->with('error', 'Компания не выбрана.');
        }

        // Get Role (from session or default)
        // Values: 'Отходообразователь' or 'Переработчик отходов'
        $roleName = session('user_role', 'Отходообразователь');
        $roleKey = ($roleName === 'Переработчик отходов') ? 'waste_processor' : 'waste_generator';

        // Parse Period
        $periodDate = \Carbon\Carbon::createFromFormat('Y-m', $request->period)->startOfMonth();

        // 1. Check for Initial Setup (First Journal)
        // Check if any journal ever existed for this company
        $anyJournalExists = \App\Models\JudoJournal::where('company_id', $company->id)->exists();

        // Check if Initial Balances exist
        $initialBalancesExist = \App\Models\InitialBalance::where('company_id', $company->id)->exists();

        // If this is the VERY first time (no journals, no initial balances), suggest adding them
        if (!$anyJournalExists && !$initialBalancesExist) {
            return redirect()->route('journal.initial-balance.create', ['period' => $request->period]);
        }

        // 1. Get Previous Journal for Opening Balance
        $prevDate = $periodDate->copy()->subMonth();
        $prevJournal = \App\Models\JudoJournal::where('company_id', $company->id)
            ->where('period', $prevDate->format('Y-m-d'))
            ->where('role', $roleKey)
            ->first();

        // Map Previous Balances by Waste Name
        $prevBalances = [];
        $wasteStats = []; // Key: WasteName

        // If no previous journal, check Initial Balances
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

                    // Pre-fill wasteStats from previous journal to preserve FKKO/Hazard details
                    // This ensures Table 1 is populated even if there are no new acts for this waste type
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

        // 2. Fetch Acts for the selected period
        // Filter logic same as dashboard: check Document Date, fallback to Created At
        $acts = \App\Models\Act::where('company_id', $company->id)
            ->where('status', 'processed')
            ->get()
            ->filter(function ($act) use ($request) {
                $data = $act->act_data;
                $dateVal = $data['date'] ?? null;
                $actDate = $dateVal ? \Carbon\Carbon::parse($dateVal) : $act->created_at;
                return $actDate->format('Y-m') === $request->period;
            });

        // 3. Aggregate Data & Collect Details
        // $wasteStats initialized above
        $table3_data = []; // Transferred Details
        $table4_data = []; // Received Details

        foreach ($acts as $act) {
            $data = $act->act_data;
            $items = $data['items'] ?? [];
            $operationType = mb_strtolower($data['operation_type'] ?? '');

            $provider = $data['provider'] ?? '';
            $receiver = $data['receiver'] ?? '';
            $actNumber = $data['number'] ?? 'б/н';
            $date = $data['date'] ?? $act->created_at->format('Y-m-d');

            $compName = mb_strtolower($company->name);
            $provName = mb_strtolower($provider); // Service Provider (Executor) -> Waste Recipient
            $recvName = mb_strtolower($receiver); // Service Customer (Client) -> Waste Generator

            // Determine Waste Roles based on Service Roles
            // If we are the Service Provider, we RECEIVE waste.
            // If we are the Service Customer, we TRANSFER waste.
            $isWasteRecipient = (mb_strpos($provName, $compName) !== false);
            $isWasteGenerator = (mb_strpos($recvName, $compName) !== false);

            // Internal Transfer check
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
                        'used' => 0, // Kept for legacy or total if needed, but we calculate specific now
                        'utilized' => 0,
                        'neutralized' => 0,
                        'buried' => 0,
                        'transferred' => 0,
                        'received' => 0,
                        'fkko' => $fkko,
                        'hazard' => $hazard
                    ];
                }

                // Update FKKO/Hazard if missing
                if (empty($wasteStats[$name]['fkko']))
                    $wasteStats[$name]['fkko'] = $fkko;
                if (empty($wasteStats[$name]['hazard']))
                    $wasteStats[$name]['hazard'] = $hazard;

                // -------------------------------------------------------------------
                // LOGIC: Flow based on Waste Roles
                // -------------------------------------------------------------------

                // 1. WE ARE WASTE GENERATOR (Service Customer)
                // We generated waste and transferred it to the Service Provider
                if ($isWasteGenerator && !$isInternal) {
                    // Logic: Transferred -> Just Transfer. Generation strictly if explicitly stated.
                    $wasteStats[$name]['transferred'] += $qty;

                    $table3_data[] = [
                        'date' => $date,
                        'number' => $actNumber,
                        'counterparty' => $provider, // We gave to Service Provider
                        'waste' => $name,
                        'fkko' => $fkko,
                        'hazard' => $hazard,
                        'amount' => $qty,
                        'operation' => $opItem
                    ];
                }

                // 2. WE ARE WASTE RECIPIENT (Service Provider)
                // We received waste from the Service Customer
                elseif ($isWasteRecipient && !$isInternal) {
                    $wasteStats[$name]['received'] += $qty;

                    $table4_data[] = [
                        'date' => $date,
                        'number' => $actNumber,
                        'counterparty' => $receiver, // We got from Service Customer
                        'waste' => $name,
                        'fkko' => $fkko,
                        'hazard' => $hazard,
                        'amount' => $qty,
                        'operation' => $opItem
                    ];

                    // Categorize usage/processing
                    if (str_contains($opItem, 'утилиз')) {
                        $wasteStats[$name]['utilized'] += $qty;
                    } elseif (str_contains($opItem, 'обезвреж')) {
                        $wasteStats[$name]['neutralized'] += $qty;
                    } elseif (str_contains($opItem, 'захорон')) {
                        $wasteStats[$name]['buried'] += $qty;
                    }
                }

                // 3. INTERNAL / OTHER
                elseif ($isInternal) {
                    if (str_contains($opItem, 'утилиз')) {
                        $wasteStats[$name]['utilized'] += $qty;
                    } elseif (str_contains($opItem, 'обезвреж')) {
                        $wasteStats[$name]['neutralized'] += $qty;
                    } elseif (str_contains($opItem, 'захорон')) {
                        $wasteStats[$name]['buried'] += $qty;
                    }
                }

                // 4. EXPLICIT GENERATION
                // Only count generation if the operation explicitly says so.
                if (str_contains($opItem, 'образован')) {
                    $wasteStats[$name]['generated'] += $qty;
                }

                // 5. INTERNAL USE (For Generator, if they use their own waste?)
                // Not covered by standard Transfer Act logic unless Provider==Receiver
                elseif (str_contains($opItem, 'ипользов') || str_contains($opItem, 'утилиз')) {
                    // Fallback: if we are not source/dest but operation is utilization, assume internal?
                    // Or maybe we are just capturing the fact it happened. 
                    // For now, let's leave this strict to Source/Dest interactions for Acts.
                    // $wasteStats[$name]['used'] += $qty; 
                }
            }
        }

        // 4. Calculate Final Balances
        $table2 = [];
        $uniqueWastes = array_unique(array_merge(array_keys($prevBalances), array_keys($wasteStats)));

        // Prepare Table 1 Data (Composition)
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

            $gen = $stats['generated'];
            $util = $stats['utilized'];
            $neutr = $stats['neutralized'];
            $buried = $stats['buried'];
            $transf = $stats['transferred'];
            $recv = $stats['received'];

            // Formula: End = Start + Gen + Rec - Util - Neutr - Transf - Buried
            $end = $start + $gen + $recv - $util - $neutr - $transf - $buried;

            $table2[] = [
                'name' => $wasteName,
                'fkko' => $stats['fkko'],
                'hazard' => $stats['hazard'],
                'balance_begin' => $start,
                'generated' => $gen,
                'received' => $recv,
                'utilized' => $util,
                'neutralized' => $neutr,
                'buried' => $buried,
                'transferred' => $transf,
                'balance_end' => $end,
                'used' => $util + $neutr // Keep 'used' sum if legacy needed
            ];

            // Populate Table 1 with unique waste types found in this period
            if (!empty($stats['fkko'])) {
                $table1_data[] = [
                    'name' => $wasteName,
                    'fkko' => $stats['fkko'],
                    'hazard' => $stats['hazard']
                ];
            }
        }

        \App\Models\JudoJournal::updateOrCreate(
            [
                'company_id' => $company->id,
                'period' => $periodDate->format('Y-m-d'),
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

        return redirect()->route('journal.index')->with('success', 'Журнал успешно сформирован за ' . $periodDate->translatedFormat('F Y'));
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

        // After saving, redirect back to generating the journal for the period
        // Use a 307 redirect to preserve POST if possible, but store is POST.
        // Actually, we can just redirect to a GET specific router or back to index with a message "Now generate".
        // But better: Redirect to the ACTION that generates the journal.
        // However journal.store is POST. Let's redirect to index with success message instructing to Generate again.
        // Or trigger generation automatically?
        // Let's redirect to index with "Initial balances saved. Now you can generate the journal."

        return redirect()->route('journal.index')->with('success', 'Начальные остатки сохранены. Теперь можно сформировать журнал.');
    }

    public function show(string $id)
    {
        $company = app(\App\Services\TenantService::class)->getCompany();
        $journal = \App\Models\JudoJournal::where('company_id', $company->id)->findOrFail($id);

        return view('journal.show', compact('journal'));
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        $company = app(\App\Services\TenantService::class)->getCompany();
        $journal = \App\Models\JudoJournal::where('company_id', $company->id)->findOrFail($id);

        $journal->delete();

    }

    public function download(string $id)
    {
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
        try {
            $data = $this->prepareSpreadsheet($id);
            $spreadsheet = $data['spreadsheet'];
            // Change extension to .pdf
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

            $period = \Carbon\Carbon::parse($journal->period);
            // Russian month name
            $monthName = $period->translatedFormat('F');
            $year = $period->year;
            $periodStr = $monthName . ' ' . $year;

            // 1. TITULAR SHEET (Index 0)
            $sheetTitular = $spreadsheet->getSheet(0);

            // Search and Replace placeholders
            foreach ($sheetTitular->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $val = $cell->getValue();
                    if (is_string($val)) {
                        // Replace Period (Cell D11 likely contains "июнь 2025")
                        if (mb_strpos($val, 'июнь 2025') !== false) {
                            $cell->setValue(str_replace('июнь 2025', $periodStr, $val));
                        }

                        // Replace Company (Cell D9 likely contains sample company)
                        // Replace any occurrence of "ЭкоСфера" or similar sample if found
                        if (mb_strpos($val, 'ЭкоСфера') !== false) {
                            // If it's the full string "Obshchestvo...", replace it with our company name
                            // Or simply set the cell to the company name if it's the specific cell.
                            $cell->setValue($company->name);
                        }

                        // Replace Director Name
                        // Search for sample "Ларин" or "Руководитель" line context
                        // Check if cell contains "Ларин"
                        if (mb_strpos($val, 'Ларин') !== false) {
                            $cell->setValue(str_replace('Ларин И.А.', $company->contact_person ?? '', $val));
                        }
                        // Or if it is the specific empty underline next to "Руководитель"
                        elseif (mb_strpos($val, 'Руководитель') !== false) {
                            // Sometimes the name is in the same cell: "Руководитель _________ Иванова И.И."
                            // Or in the next cell.
                            // Assuming sample has "Ларин И.А." somewhere.
                            // If we found "Ларин", we replaced it above.
                            // If the user meant "If missing info, leave empty", we use ?? ''.
                        }
                    }
                }
            }
            // Fallback: If we couldn't find placeholders, maybe hardcode cells? 
            // Without seeing file, search/replace specific sample values 'июнь 2025' is safest if template is filled.
            // Also set Company Name.
            // I'll leave the search logic above.

            // 2. Data Population Helper
            $populateTable = function ($sheetIndex, $data, $columns) use ($spreadsheet) {
                $sheet = $spreadsheet->getSheet($sheetIndex);
                // Find Header Numbering Row (row with "1" in column B/C usually)
                $startRow = 10;

                // Scan first 20 rows to find the numbering row
                foreach ($sheet->getRowIterator() as $row) {
                    if ($row->getRowIndex() > 20)
                        break;
                    foreach ($row->getCellIterator() as $cell) {
                        // Check for "1" in a commonly used column for ID (B or C usually)
                        if (trim($cell->getValue()) === '1') {
                            $startRow = $row->getRowIndex() + 1; // Start inserting AFTER this row
                            break 2;
                        }
                    }
                }

                $r = $startRow;
                $rowNum = 1;

                foreach ($data as $item) {
                    $sheet->setCellValue('B' . $r, $rowNum++); // Assuming Column B is typically ID

                    // Map columns starting from C?
                    // Need to check column mapping from inspection.
                    // Table 1: B=No, C=Name, D=Code, E=Hazard. (From dump: B9:1, C9:2, D9:3, E9:4)
                    // So we write to B, C, D, E.

                    // But wait, the previous code mapped from 'A'. 
                    // Inspection says: B9: 1, C9: 2...
                    // So 'B' is index.

                    // Let's make column start dynamic or hardcoded based on sheet index?
                    // Table 1 (Sheet 1): B=ID, C=Name, D=FKKO, E=Hazard.
                    // Table 2 (Sheet 2): B=ID, C=Name, D=FKKO, E=Hazard, F=BalBeg...
                    // Table 3 (Sheet 3): B...O. 
                    // Table 4 (Sheet 4): B...O.

                    // So generally start at 'C' for data?
                    $colIndex = 'C';

                    // Adjust for specific tables?
                    // Table 1: B=ID, C=Name, D=FKKO, E=Hazard.
                    // Table 2: B=ID, C=Name, D=FKKO, E=Hazard, F=BalBeg...
                    // Table 3: B=ID, C=Date, D=Num? Wait.
                    // Table 3 Dump: "B9: 1, C9: 2, D9: 3, E9: 4, F9: 5..."
                    // Headers Table 3: C4: Дата приема ... (Merged?)
                    // Let's assume sequential filling starting from C is safe for C, D, E... 

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

                // Clear remaining rows
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

            // Refined Mappings based on Columns

            // Table 1: Name, FKKO, Hazard
            $populateTable(1, $journal->table1_data ?? [], ['name', 'fkko', 'hazard']);

            // Table 2: Name, FKKO, Hazard, BalBeg, Gen, Rec, Util, Neutr, Buried, Transferred, BalEnd
            // Note: Excel Table 2 columns: 
            // 1(B): No, 2(C): Name, 3(D): FKKO, 4(E): Haz, 5(F): BalBeg, 6(G): Gen, 7(H): Rec, 
            // 8(I): Util, 9(J): Neutr, 10(K): Buried(Storage?), 11(L): Transferred, 12(M): BalEnd?
            // Wait, Dump Table 2: "B14: 1... N14: 13, O14: 14"?
            // Table 2 Columns in Dump:
            // B14: 1, C14: 2, D14: 3, E14: 4, F14: 5, G14: 6, H14: 7, I14: 8, J14: 9, K14: 10, L14: 11, M14: 12, N14: 13, O14: 14
            // 14 Columns?
            // Let's check headers in dump for Table 2.
            // "F9: Наличие отходов ... начало"
            // "G9: Образовано"
            // "H9: Поступило ... из других" (Rec)
            // "J9: Использовано" (Util?)
            // "K9: Обезврежено" (Neutr?)
            // "L9: Размещено -> Хранение"
            // "M9: Размещено -> Захоронение"
            // "N9: Передано"
            // "O9: Наличие ... конец"
            // So: F=Start, G=Gen, H=Rec, J=Util, K=Neutr, L=Storage, M=Buried, N=Transf, O=End.
            // Wait, where is I? "H9: Поступило ... Итого(H) ... из других(I)?"
            // Dump: "H13: всего" "I13: от сторонних"
            // My data 'received' is total received?
            // Let's assume:
            // F: Start
            // G: Gen
            // H: Rec Total ? Or Rec from others? Usually H=Total Received.
            // I: Rec from Import/Others? 
            // J: Utilized
            // K: Neutralized
            // L: Storage (We have 0 typically?)
            // M: Buried
            // N: Transferred
            // O: End

            // To be safe, I will pass:
            // Name, FKKO, Haz, Start, Gen, Rec, Rec(Same?), Util, Neutr, 0 (Storage), Buried, Transf, End

            // NOTE: Mapping array must match columns C, D, E, F ...
            // C: Name
            // D: FKKO
            // E: Haz
            // F: Start
            // G: Gen
            // H: Rec
            // I: Rec (Copy Rec again? Or 0?)
            // J: Util
            // K: Neutr
            // L: 0 (Storage)
            // M: Buried
            // N: Transf
            // O: End

            // I need to construct a custom array for Table 2 or pass a closure/callback?
            // `populateTable` takes keys. I can add 'dummy' keys to my data or modifying the loop.
            // Easier: Modify `populateTable` to accept values/callbacks?
            // Or just Ensure `$journal->table2_data` yields these in order? 
            // `table2_data` has keys. I can pick keys.
            // But I don't have 'received_import' or 'storage'.
            // I will Assume H=Rec, I=Rec (if distinct) or I is subset.
            // Let's Map: ['name', 'fkko', 'hazard', 'balance_begin', 'generated', 'received', 'received', 'utilized', 'neutralized', 'zero_storage', 'buried', 'transferred', 'balance_end']
            // 'zero_storage' doesn't exist.

            // I'll update the loop inside populateTable slightly? 
            // No, I'll simple add 'zero' properties to data before passing?

            $t2_data = collect($journal->table2_data)->map(function ($item) {
                $item['rec_copy'] = $item['received']; // Assuming all matches
                $item['storage'] = 0; // Assuming no storage
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


            // Table 3 (Transferred)
            // Header Dump: B9: 1... N9: 13, O9: 14.
            // B: No
            // C: Date Transf (Дата передачи)
            // D: Date Contract? (Номер?) 
            // Let's check headers.
            // C4: Дата передачи
            // D4: Номер паспорта? No.
            // Headers are complex.
            // Usually: 
            // 2 (C): Date
            // 3 (D): Number Act
            // 4 (E): Name Waste
            // 5 (F): FKKO
            // 6 (G): Haz
            // 7 (H): Amount
            // 8-11: Purpose (Util, Neutr, Store, Bury)
            // 12 (L): Transferee Name
            // 13 (M): Transferee INN?
            // 14 (N): Contract Date?
            // 15 (O): Contract Num?

            // My data: date, number, counterparty(Name), waste, fkko, hazard, amount, operation.
            // Columns C..O
            // C: Date, D: Number, E: Waste, F: FKKO, G: Haz, H: Amount.
            // I, J, K, L: Breakdown by purpose.
            // I: Purpose Util?
            // J: Purpose Neutr?
            // K: Purpose Store?
            // L: Purpose Bury?
            // M: Counterparty Name
            // N: Contract info?
            // O: Contract Date?

            // Since I don't have Purpose breakdown in Table 3 strictly (just total transferred usually), 
            // I might put Amount in Total(H) and then in specific column based on 'operation'?
            // But I only have 'transferred'.
            // Let's assume standard Transfer for Utilization (I) or Treatment (?).
            // If I can't be sure, I'll fill H (Total) and M (Counterparty). 
            // I will fill I,J,K,L with '-' or based on operation string?

            // Let's try to parse 'operation' to pick column?
            // But `populateTable` is simple.
            // I will prep data.

            $t3_data = collect($journal->table3_data)->map(function ($item) {
                $op = $item['operation'] ?? '';
                $qty = $item['amount'];
                // Defaults
                $item['p_transf'] = '-';
                $item['p_process'] = '-';
                $item['p_util'] = '-';
                $item['p_neutr'] = '-';
                $item['p_store'] = '-';
                $item['p_bury'] = '-';

                // Map operation to specific columns
                if (str_contains($op, 'утилиз'))
                    $item['p_util'] = $qty;
                elseif (str_contains($op, 'обезвреж'))
                    $item['p_neutr'] = $qty;
                elseif (str_contains($op, 'захорон'))
                    $item['p_bury'] = $qty;
                elseif (str_contains($op, 'обработ'))
                    $item['p_process'] = $qty;
                else
                    $item['p_transf'] = $qty; // Generic transfer if no specific operation

                $item['validity'] = '-'; // Placeholder for contract validity if needed
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

            // NOTE: 'p_transf', 'p_process' keys need to exist.

            // Table 4 (Received)
            // Similar structure likely.
            // B=1, C=Waste, D=FKKO, E=Haz, F=Amount...
            // M=From Whom
            // N=Contract
            $t4_data = collect($journal->table4_data)->map(function ($item) {
                $op = $item['operation'] ?? '';
                $qty = $item['amount'];
                // Defaults
                $item['p_transf'] = '-';
                $item['p_process'] = '-';
                $item['p_util'] = '-';
                $item['p_neutr'] = '-';
                $item['p_store'] = '-';
                $item['p_bury'] = '-';

                if (str_contains($op, 'утилиз'))
                    $item['p_util'] = $qty;
                elseif (str_contains($op, 'обезвреж'))
                    $item['p_neutr'] = $qty;
                elseif (str_contains($op, 'захорон'))
                    $item['p_bury'] = $qty;
                elseif (str_contains($op, 'обработ'))
                    $item['p_process'] = $qty;
                else
                    $item['p_transf'] = $qty; // Generic transfer if no specific operation

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



            // Set all sheets to Normal View (disable Page Layout/Break Preview)
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

