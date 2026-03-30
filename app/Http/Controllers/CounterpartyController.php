<?php

namespace App\Http\Controllers;

use App\Models\Counterparty;
use App\Services\TenantService;
use Illuminate\Http\Request;

class CounterpartyController extends Controller
{
    private function company()
    {
        return app(TenantService::class)->getCompany();
    }

    /** GET /counterparties/search?q= */
    public function search(Request $request)
    {
        $company = $this->company();
        if (!$company) return response()->json([]);

        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) return response()->json([]);

        $results = Counterparty::where('company_id', $company->id)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('inn', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'inn', 'kpp', 'legal_address', 'license_number', 'license_valid_until']);

        return response()->json($results);
    }

    /** POST /counterparties */
    public function store(Request $request)
    {
        $company = $this->company();
        if (!$company) return response()->json(['error' => 'Компания не выбрана'], 403);

        $data = $request->validate([
            'name'                => 'required|string|max:500',
            'inn'                 => 'nullable|string|max:12',
            'kpp'                 => 'nullable|string|max:9',
            'legal_address'       => 'nullable|string|max:1000',
            'license_number'      => 'nullable|string|max:255',
            'license_valid_until' => 'nullable|date',
        ]);

        // Валидация ИНН если указан
        if (!empty($data['inn'])) {
            $inn = preg_replace('/\D/', '', $data['inn']);
            if (!Counterparty::validateInn($inn)) {
                return response()->json(['error' => 'Неверное контрольное число ИНН'], 422);
            }
            $data['inn'] = $inn;
        }

        $cp = Counterparty::updateOrCreate(
            ['company_id' => $company->id, 'inn' => $data['inn'] ?? null, 'name' => $data['name']],
            array_merge($data, ['company_id' => $company->id])
        );

        return response()->json($cp);
    }

    /** GET /counterparties - список для страницы справочника */
    public function index(Request $request)
    {
        $company = $this->company();
        if (!$company) return redirect('/login');

        $query = Counterparty::where('company_id', $company->id)->orderBy('name');

        if ($q = $request->input('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")->orWhere('inn', 'like', "%{$q}%");
            });
        }

        $counterparties = $query->paginate(30)->withQueryString();
        return view('counterparties.index', compact('counterparties'));
    }

    /** DELETE /counterparties/{id} */
    public function destroy(string $id)
    {
        $company = $this->company();
        Counterparty::where('company_id', $company->id)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
