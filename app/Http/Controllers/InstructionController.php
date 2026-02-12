<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InstructionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $tenantService = app(\App\Services\TenantService::class);
        $company = $tenantService->getCompany();

        $steps = [
            'registration' => true,
            'create_company' => $user->companies()->count() > 0,
            'select_company' => $company !== null,
            'upload_acts' => $company ? \App\Models\Act::where('company_id', $company->id)->exists() : false,
            'create_journal' => $company ? \App\Models\JudoJournal::where('company_id', $company->id)->exists() : false,
        ];

        return view('instruction.index', compact('steps'));
    }
}
