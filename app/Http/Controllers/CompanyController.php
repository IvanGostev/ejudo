<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = auth()->user()->companies;
        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isSubscribed = $user->subscription_ends_at && $user->subscription_ends_at->isFuture();

        if (!$isSubscribed && $user->companies()->count() >= 1) {
            return back()->with('error', 'Без активной подписки можно создать только одну компанию. Оформите подписку для создания дополнительных компаний.');
        }

        $validated = $request->validate([
            'type' => 'required|in:ООО,ИП',
            'name' => 'required|string|max:255',
            'inn' => 'required|string|min:10|max:12',
            'kpp' => 'nullable|string|max:9',
            'ogrn' => 'required|string|min:13|max:15',
            'legal_address' => 'required|string',
            'actual_address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $company = $user->companies()->create($validated);

        return redirect()->route('companies.index')->with('success', 'Компания успешно добавлена');
    }

    public function edit(\App\Models\UserCompany $company)
    {
        if ($company->user_id !== auth()->id()) {
            abort(403);
        }
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, \App\Models\UserCompany $company)
    {
        if ($company->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:ООО,ИП',
            'name' => 'required|string|max:255',
            'inn' => 'required|string|min:10|max:12',
            'kpp' => 'nullable|string|max:9',
            'ogrn' => 'required|string|min:13|max:15',
            'legal_address' => 'required|string',
            'actual_address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $company->update($validated);

        return redirect()->route('companies.index')->with('success', 'Данные компании обновлены');
    }

    public function destroy(\App\Models\UserCompany $company)
    {
        if ($company->user_id !== auth()->id()) {
            abort(403);
        }

        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Компания удалена');
    }

    public function switch(\App\Models\UserCompany $company)
    {
        if ($company->user_id !== auth()->id()) {
            abort(403);
        }

        app(\App\Services\TenantService::class)->setCompany($company);

        return back()->with('success', 'Компания переключена');
    }
}
