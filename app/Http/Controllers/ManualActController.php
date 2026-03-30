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
            'act_type'            => 'required|in:transfer,processing,utilization,neutralization,storage,burial',
            'date'                => 'required|date',
            'contract_details'    => 'nullable|string|max:500',
            'contract_validity'   => 'nullable|string|max:255',
            'provider'            => 'required|string|max:255',
            'provider_snapshot'   => 'nullable|string',
            'receiver'            => 'required|string|max:255',
            'receiver_snapshot'   => 'nullable|string',
            'wastes'              => 'required|array|min:1',
            'wastes.*.name'       => 'required|string',
            'wastes.*.fkko_code'      => 'required|string',
            'wastes.*.hazard_class'   => 'required|string',
            'wastes.*.amount'         => 'required|numeric|min:0',
            'wastes.*.operation_types'=> 'required|string',
        ], [], [
            'date'              => 'Дата',
            'provider'          => 'Поставщик',
            'receiver'          => 'Получатель',
            'wastes'            => 'Отходы',
            'wastes.*.name'     => 'Наименование отхода',
            'wastes.*.fkko_code'    => 'Код ФККО',
            'wastes.*.amount'       => 'Количество',
        ]);

        $tenantService = app(TenantService::class);
        $company       = $tenantService->getCompany();

        if (!$company) {
            return back()->with('error', 'Компания не выбрана');
        }

        $pSnap = $request->provider_snapshot ? json_decode($request->provider_snapshot, true) : null;
        $rSnap = $request->receiver_snapshot ? json_decode($request->receiver_snapshot, true) : null;

        if (!$pSnap) $pSnap = ['name' => $request->provider];
        if (!$rSnap) $rSnap = ['name' => $request->receiver];

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
            'date'               => $request->date,
            'contract_details'   => $request->contract_details,
            'contract_validity'  => $request->contract_validity,
            'provider'           => $request->provider,
            'provider_snapshot'  => $pSnap,
            'receiver'           => $request->receiver,
            'receiver_snapshot'  => $rSnap,
            'items'              => $items,
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

        $pSnap = json_decode($act->provider_snapshot ?? '{}', true) ?: [];
        $rSnap = json_decode($act->receiver_snapshot ?? '{}', true) ?: [];

        $formatSnapshot = function($snap, $fallbackName) {
            if (empty($snap)) return "<b>" . htmlspecialchars($fallbackName) . "</b>";
            $res = "<b>" . htmlspecialchars($snap['name'] ?? $fallbackName) . "</b>";
            if (!empty($snap['inn'])) $res .= "<br>ИНН: " . htmlspecialchars($snap['inn']);
            if (!empty($snap['kpp'])) $res .= " &nbsp; КПП: " . htmlspecialchars($snap['kpp']);
            if (!empty($snap['legal_address'])) $res .= "<br>Адрес: " . htmlspecialchars($snap['legal_address']);
            if (!empty($snap['license_number'])) {
                $res .= "<br>Лицензия: " . htmlspecialchars($snap['license_number']);
                if (!empty($snap['license_valid_until'])) $res .= " до " . htmlspecialchars($snap['license_valid_until']);
            }
            return $res;
        };

        $providerHeader = $formatSnapshot($pSnap, $actData['provider'] ?? '—');
        $receiverHeader = $formatSnapshot($rSnap, $actData['receiver'] ?? '—');

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
                    <td colspan='3' style='text-align: right; background-color: #e0e0e0;'><b>Итого:</b></td>
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
                body  { font-family: 'Times New Roman', serif; font-size: 11pt; margin: 0; }
                .header-block { text-align: center; margin-bottom: 20pt; }
                .act-title    { font-size: 14pt; font-weight: bold; }
                .act-subtitle { font-size: 12pt; font-weight: bold; margin-top: 4pt; }
                .org-table    { border: none; margin-bottom: 20pt; width: 100%; }
                .org-table td { border: none; padding: 4pt 0; text-align: left; vertical-align: top; font-size: 10pt; width: 50%; }
                table.data-table { border-collapse: collapse; width: 100%; }
                table.data-table th, table.data-table td {
                    border: 1pt solid black;
                    padding: 5pt 6pt;
                    font-size: 10pt;
                    vertical-align: middle;
                    text-align: center;
                }
                table.data-table th { font-weight: bold; background: #f5f5f5; }
                .text-start { text-align: left; }
                .sign-block { margin-top: 40pt; font-size: 10pt; }
                .sign-line  { display: inline-block; width: 150pt; border-bottom: 1pt solid black; margin: 0 4pt; }
            </style>
        </head>
        <body>
            <div class='header-block'>
                <div class='act-title'>АКТ № {$act->act_number} от {$dateRus}</div>
                <div class='act-subtitle'>{$subtitle}</div>
            </div>

            <table class='org-table'>
                <tr>
                    <td><b>ПОСТАВЩИК:</b><br>{$providerHeader}</td>
                    <td><b>ПОЛУЧАТЕЛЬ:</b><br>{$receiverHeader}</td>
                </tr>
            </table>

            <table class='data-table'>
                <thead>
                    <tr>
                        <th width='35'>№</th>
                        <th>Наименование отхода</th>
                        <th width='110'>ФККО</th>
                        <th width='60'>Вес (т)</th>
                        <th>Договор / Контрагент</th>
                        <th>Вид обращения</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
            </table>

            <div class='sign-block'>
                <table style='border:none; width:100%;'>
                    <tr>
                        <td style='border:none; width:50%; text-align:left;'>
                            <b>Поставщик (Исполнитель):</b><br><br>
                            ________________ / <span class='sign-line'></span> /
                        </td>
                        <td style='border:none; width:50%; text-align:left;'>
                            <b>Получатель (Заказчик):</b><br><br>
                            ________________ / <span class='sign-line'></span> /
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>";

        return response($html)
            ->header('Content-Type', 'application/msword')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
