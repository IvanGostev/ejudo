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

        $query = Act::where('company_id', $company->id)
            ->where('status', 'processed');


        if ($request->filled('act_type')) {
            $query->where('act_type', $request->act_type);
        }


        if ($request->filled('period_year')) {
            $year = $request->period_year;
            if ($request->filled('period_quarter')) {

                $q = (int) $request->period_quarter;
                $monthFrom = ($q - 1) * 3 + 1;
                $monthTo   = $monthFrom + 2;
                $query->whereRaw(
                    "YEAR(JSON_UNQUOTE(JSON_EXTRACT(act_data, '$.date'))) = ?
                     AND MONTH(JSON_UNQUOTE(JSON_EXTRACT(act_data, '$.date'))) BETWEEN ? AND ?",
                    [$year, $monthFrom, $monthTo]
                );
            } elseif ($request->filled('period_month')) {
                $query->whereRaw(
                    "YEAR(JSON_UNQUOTE(JSON_EXTRACT(act_data, '$.date'))) = ?
                     AND MONTH(JSON_UNQUOTE(JSON_EXTRACT(act_data, '$.date'))) = ?",
                    [$year, $request->period_month]
                );
            } else {
                $query->whereRaw(
                    "YEAR(JSON_UNQUOTE(JSON_EXTRACT(act_data, '$.date'))) = ?",
                    [$year]
                );
            }
        }

        $acts = $query
            ->orderByDesc(\DB::raw("JSON_UNQUOTE(JSON_EXTRACT(act_data, '$.date'))"))
            ->orderByDesc('id')
            ->paginate(20)->withQueryString();


        $availableYears = Act::where('company_id', $company->id)
            ->where('status', 'processed')
            ->selectRaw("DISTINCT YEAR(JSON_UNQUOTE(JSON_EXTRACT(act_data, '$.date'))) as yr")
            ->orderByDesc('yr')
            ->pluck('yr')
            ->filter()
            ->values();

        return view('acts.archive', compact('acts', 'company', 'availableYears'));
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
        $field   = $request->field;
        $value   = $request->value;

        if (in_array($field, ['date', 'contract_details', 'provider', 'receiver'])) {
            $actData[$field] = $value;
        } elseif (str_starts_with($field, 'items.')) {
            $parts     = explode('.', $field);
            $index     = (int) $parts[1];
            $itemField = $parts[2];
            if (isset($actData['items'][$index])) {
                $actData['items'][$index][$itemField] = $value;
            }
        }

        $act->act_data          = $actData;
        $act->processing_result = $actData;
        $act->save();

        return response()->json(['success' => true, 'message' => 'Данные обновлены']);
    }
}
