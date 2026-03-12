<?php

namespace App\Http\Controllers;

use App\Models\Act;
use App\Models\FkkoCode;
use App\Services\TenantService;
use Illuminate\Http\Request;

class ManualActController extends Controller
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

    /** Список доступных типов актов с их метаданными */
    private static function actTypes(): array
    {
        return [
            'transfer'       => ['label' => 'Акт передачи',         'route' => 'acts.manual.create'],
            'processing'     => ['label' => 'Акт обработки',        'route' => 'acts.manual.create'],
            'utilization'    => ['label' => 'Акт утилизации',       'route' => 'acts.manual.create'],
            'neutralization' => ['label' => 'Акт обезвреживания',   'route' => 'acts.manual.create'],
            'storage'        => ['label' => 'Акт хранения',         'route' => 'acts.manual.create'],
            'burial'         => ['label' => 'Акт захоронения',      'route' => 'acts.manual.create'],
        ];
    }

    public function create(Request $request)
    {
        $fkko = null;
        if ($request->has('fkko_code')) {
            $fkko = FkkoCode::where('code', $request->fkko_code)->first();
        }

        $tenantService  = app(TenantService::class);
        $currentCompany = $tenantService->getCompany();
        $nextNumber     = $currentCompany ? Act::nextActNumber($currentCompany->id) : 1;
        $actType        = $request->input('act_type', 'transfer');
        $actTypes       = Act::TYPES;

        return view('acts.manual_create', compact('fkko', 'currentCompany', 'actType', 'actTypes', 'nextNumber'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'act_type'         => 'required|in:transfer,processing,utilization,neutralization,storage,burial',
            'date'             => 'required|date',
            'contract_details' => 'nullable|string|max:500',
            'provider'         => 'required|string|max:255',
            'receiver'         => 'required|string|max:255',
            'wastes'           => 'required|array|min:1',
            'wastes.*.name'    => 'required|string',
            'wastes.*.fkko_code'   => 'required|string',
            'wastes.*.hazard_class'=> 'required|string',
            'wastes.*.amount'      => 'required|numeric|min:0',
            'wastes.*.operation_types' => 'required|string',
        ]);

        $tenantService = app(TenantService::class);
        $company       = $tenantService->getCompany();

        if (!$company) {
            return back()->with('error', 'Компания не выбрана');
        }

        $items = [];
        foreach ($request->wastes as $waste) {
            $items[] = [
                'name'           => $waste['name'],
                'quantity'       => (float) $waste['amount'],
                'unit'           => 'т',
                'fkko_code'      => $waste['fkko_code'],
                'hazard_class'   => $waste['hazard_class'],
                'operation_type' => $waste['operation_types'],
            ];
        }

        $actData = [
            'date'             => $request->date,
            'contract_details' => $request->contract_details,
            'provider'         => $request->provider,
            'receiver'         => $request->receiver,
            'items'            => $items,
        ];

        $actNumber = Act::nextActNumber($company->id);

        Act::create([
            'company_id'       => $company->id,
            'act_type'         => $request->act_type,
            'act_number'       => $actNumber,
            'contract_details' => $request->contract_details,
            'filename'         => 'manual_entry_' . time(),
            'original_name'    => 'Ручной ввод',
            'file_size'        => 0,
            'act_data'         => $actData,
            'status'           => 'processed',
            'processing_result'=> $actData,
        ]);

        return redirect()->route('acts.archive')->with('success', "Акт №{$actNumber} успешно добавлен");
    }

    public function downloadDoc(Act $act, Request $request)
    {
        $tenantService = app(\App\Services\TenantService::class);
        $company = $tenantService->getCompany();

        if ($act->company_id !== $company->id || $act->status !== 'processed') {
            abort(403);
        }

        $actData = $act->act_data;
        $dateStr = \Carbon\Carbon::parse($actData['date'] ?? now())->format('d-m-Y');
        $dateRus = \Carbon\Carbon::parse($actData['date'] ?? now())->format('d.m.Y');
        $items   = $actData['items'] ?? [];

        // Вторая строка шапки в зависимости от типа акта
        $typeSubtitles = [
            'transfer'       => 'О ПЕРЕДАЧЕ ОТХОДОВ III–V КЛАССА ОПАСНОСТИ',
            'processing'     => 'ОБ ОБРАБОТКЕ ОТХОДОВ III–V КЛАССА ОПАСНОСТИ',
            'utilization'    => 'ОБ УТИЛИЗАЦИИ ОТХОДОВ III–V КЛАССА ОПАСНОСТИ',
            'neutralization' => 'ОБ ОБЕЗВРЕЖИВАНИИ ОТХОДОВ III–V КЛАССА ОПАСНОСТИ',
            'storage'        => 'О ХРАНЕНИИ ОТХОДОВ III–V КЛАССА ОПАСНОСТИ',
            'burial'         => 'О ЗАХОРОНЕНИИ ОТХОДОВ III–V КЛАССА ОПАСНОСТИ',
        ];

        $actType  = $act->act_type ?? 'transfer';
        $subtitle = $typeSubtitles[$actType] ?? 'ОБ ОБРАЩЕНИИ С ОТХОДАМИ III–V КЛАССА ОПАСНОСТИ';
        $typeShort = str_replace([' ', '/'], '_', mb_strtoupper($act->getTypeLabel(), 'UTF-8'));
        $filename  = "{$typeShort}_{$act->act_number}_{$dateStr}.doc";

        // Строки таблицы — строго 6 столбцов, без класса опасности
        $rows = '';
        $totalQuantity = 0;
        
        $providerStr = $actData['provider'] ?? '';
        if (!empty($actData['contract_details'])) {
            $providerStr .= ', ' . $actData['contract_details'];
        }
        
        foreach ($items as $index => $item) {
            $qty = (float)($item['quantity'] ?? 0);
            $totalQuantity += $qty;
            
            $opArr = array_map('trim', explode(',', $item['operation_type'] ?? ''));
            $opArr = array_filter($opArr, function($op) {
                return mb_strtolower($op) !== 'передача третьим лицам';
            });
            $opDocStr = implode(', ', $opArr);

            $rows .= "
                <tr>
                    <td>" . ($index + 1) . "</td>
                    <td class='text-start'>" . htmlspecialchars($item['name'] ?? '') . "</td>
                    <td>" . htmlspecialchars($this->formatFkko($item['fkko_code'] ?? '')) . "</td>
                    <td>" . number_format($qty, 3, ',', '') . " т</td>
                    <td class='text-start'>" . htmlspecialchars($providerStr) . "</td>
                    <td>" . htmlspecialchars($opDocStr) . "</td>
                </tr>";
        }
        for ($i = 0; $i < 5; $i++) {
            $rows .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
        }
        $rows .= "
                <tr>
                    <td colspan='3' style='text-align: right; background-color: #e0e0e0;'><b>Итого принято отходов:</b></td>
                    <td style='font-weight: bold; background-color: #e0e0e0;'>" . number_format($totalQuantity, 3, ',', '') . " т</td>
                    <td colspan='2' style='background-color: #e0e0e0;'></td>
                </tr>";

        $html = "
        <html xmlns:o='urn:schemas-microsoft-com:office:office'
              xmlns:w='urn:schemas-microsoft-com:office:word'
              xmlns='http://www.w3.org/TR/REC-html40'>
        <head>
            <meta charset='utf-8'>
            <style>
                @page { margin: 2cm; }
                body  { font-family: 'Times New Roman', serif; font-size: 12pt; margin: 0; }
                .header-block { text-align: center; margin-bottom: 20pt; }
                .act-title    { font-size: 14pt; font-weight: bold; }
                .act-subtitle { font-size: 13pt; font-weight: bold; margin-top: 4pt; }
                .org-block    { font-size: 11pt; margin-bottom: 14pt; }
                table { border-collapse: collapse; width: 100%; }
                th, td {
                    border: 1pt solid black;
                    padding: 5pt 6pt;
                    font-family: 'Times New Roman', serif;
                    font-size: 11pt;
                    vertical-align: middle;
                    text-align: center;
                }
                th { font-weight: bold; background: #f5f5f5; }
                .text-start { text-align: left; }
                .sign-block { margin-top: 30pt; font-size: 11pt; }
                .sign-line  { display: inline-block; width: 180pt;
                              border-bottom: 1pt solid black; margin: 0 6pt; }
            </style>
        </head>
        <body>
            <div class='header-block'>
                <div class='act-title'>АКТ № {$act->act_number} от {$dateRus}</div>
                <div class='act-subtitle'>{$subtitle}</div>
            </div>

            <div class='org-block'>
                <b>Организация:</b> " . htmlspecialchars($company->name) . "<br>
                <b>ИНН:</b> " . htmlspecialchars($company->inn ?? '—') . " &nbsp;&nbsp;
                <b>ОГРН:</b> " . htmlspecialchars($company->ogrn ?? '—') . "<br>
                <b>Адрес:</b> " . htmlspecialchars($company->legal_address ?? '—') . "
            </div>

            <table>
                <thead>
                    <tr>
                        <th width='35'>№ п/п</th>
                        <th>Наименование отхода</th>
                        <th width='120'>Код по ФККО</th>
                        <th width='60'>Вес</th>
                        <th>От кого получены</th>
                        <th>Конечный вид деятельности с отходом</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
            </table>

            <div class='sign-block'>
                <p>Исполнитель:&nbsp; <span class='sign-line'></span> / <span class='sign-line'></span> /</p>
                <p>Заказчик:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span class='sign-line'></span> / <span class='sign-line'></span> /</p>
            </div>
        </body>
        </html>";

        return response($html)
            ->header('Content-Type', 'application/msword')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
