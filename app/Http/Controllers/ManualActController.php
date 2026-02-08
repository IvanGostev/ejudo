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
        if ($request->has('amount')) {
            $request->merge([
                'amount' => str_replace(',', '.', $request->amount)
            ]);
        }

        $request->validate([
            'date' => 'required|date',
            'number' => 'required|string|max:255',
            'provider' => 'required|string|max:255',
            'receiver' => 'required|string|max:255',
            'waste_name' => 'required|string',
            'fkko_code' => 'required|string',
            'hazard_class' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'operation_type' => 'required|array|min:1'
        ]);

        $operationType = implode(', ', $request->operation_type);

        $tenantService = app(\App\Services\TenantService::class);
        $company = $tenantService->getCompany();

        if (!$company) {
            return back()->with('error', 'Компания не выбрана');
        }

        // Construct act data similarly to parsed data
        $actData = [
            'number' => $request->number,
            'date' => $request->date,
            'provider' => $request->provider,
            'receiver' => $request->receiver,
            'items' => [
                [
                    'name' => $request->waste_name,
                    'quantity' => (float) $request->amount,
                    'unit' => 'т',
                    'fkko_code' => $request->fkko_code,
                    'hazard_class' => $request->hazard_class,
                    'operation_type' => $operationType
                ]
            ]
        ];

        \App\Models\Act::create([
            'company_id' => $company->id,
            'filename' => 'manual_entry_' . time(),
            'original_name' => 'Ручной ввод',
            'file_size' => 0,
            'act_data' => $actData,
            'status' => 'processed', // Immediately processed
            'processing_result' => $actData,
        ]);

        $period = date('Y-m', strtotime($request->date));

        return redirect()->route('dashboard', ['period' => $period])->with('success', 'Акт успешно добавлен вручную');
    }
}
