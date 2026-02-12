<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ManualActController extends Controller
{
    public function create(Request $request)
    {
        $fkko = null;
        if ($request->has('fkko_code')) {
            $fkko = \App\Models\FkkoCode::where('code', $request->fkko_code)->first();
        }

        $tenantService = app(\App\Services\TenantService::class);
        $currentCompany = $tenantService->getCompany();

        return view('acts.manual_create', compact('fkko', 'currentCompany'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'number' => 'required|string|max:255',
            'provider' => 'required|string|max:255',
            'receiver' => 'required|string|max:255',
            'wastes' => 'required|array|min:1',
            'wastes.*.name' => 'required|string',
            'wastes.*.fkko_code' => 'required|string',
            'wastes.*.hazard_class' => 'required|string',
            'wastes.*.amount' => 'required|numeric|min:0',
            'wastes.*.operation_types' => 'required|string'
        ]);

        $tenantService = app(\App\Services\TenantService::class);
        $company = $tenantService->getCompany();

        if (!$company) {
            return back()->with('error', 'Компания не выбрана');
        }

        $items = [];
        foreach ($request->wastes as $waste) {
            $items[] = [
                'name' => $waste['name'],
                'quantity' => (float) $waste['amount'],
                'unit' => 'т',
                'fkko_code' => $waste['fkko_code'],
                'hazard_class' => $waste['hazard_class'],
                'operation_type' => $waste['operation_types']
            ];
        }

        $actData = [
            'number' => $request->number,
            'date' => $request->date,
            'provider' => $request->provider,
            'receiver' => $request->receiver,
            'items' => $items
        ];

        \App\Models\Act::create([
            'company_id' => $company->id,
            'filename' => 'manual_entry_' . time(),
            'original_name' => 'Ручной ввод',
            'file_size' => 0,
            'act_data' => $actData,
            'status' => 'processed',
            'processing_result' => $actData,
        ]);

        return redirect()->route('acts.archive')->with('success', 'Акт успешно добавлен вручную');
    }
}
