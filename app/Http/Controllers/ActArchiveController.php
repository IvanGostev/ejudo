<?php

namespace App\Http\Controllers;

use App\Models\Act;
use Illuminate\Http\Request;

class ActArchiveController extends Controller
{
    public function index(Request $request)
    {
        $tenantService = app(\App\Services\TenantService::class);
        $company = $tenantService->getCompany();

        if (!$company) {
            return redirect()->route('dashboard')->with('error', 'Пожалуйста, выберите компанию');
        }

        $acts = Act::where('company_id', $company->id)
            ->where('status', 'processed')
            ->orderBy('act_data->date', 'desc')
            ->paginate(20);

        return view('acts.archive', compact('acts', 'company'));
    }

    public function update(Request $request, Act $act)
    {
        $tenantService = app(\App\Services\TenantService::class);
        $company = $tenantService->getCompany();

        if (!$company || $act->company_id !== $company->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'field' => 'required|string',
            'value' => 'required'
        ]);

        $actData = $act->act_data;

        $field = $request->field;
        $value = $request->value;

        if (in_array($field, ['date', 'number', 'provider', 'receiver'])) {
            $actData[$field] = $value;
        } elseif (str_starts_with($field, 'items.')) {
            $parts = explode('.', $field);
            $index = (int) $parts[1];
            $itemField = $parts[2];

            if (isset($actData['items'][$index])) {
                $actData['items'][$index][$itemField] = $value;
            }
        }

        $act->act_data = $actData;
        $act->processing_result = $actData;
        $act->save();

        return response()->json(['success' => true, 'message' => 'Данные обновлены']);
    }
}
