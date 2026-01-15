<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ActController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:doc,docx,pdf|max:10240',
        ]);

        $files = $request->file('files');
        $processed = [];
        $errors = [];

        $ai = new \App\Services\GigaChatService();

        foreach ($files as $file) {
            try {
                // Save temp file
                $path = $file->store('acts', 'local');
                $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);

                // AI Processing (Now handles file upload itself)
                $data = $ai->extractJsonFromAct($fullPath);

                // Save to DB
                $tenantService = app(\App\Services\TenantService::class);
                $company = $tenantService->getCompany();

                if (!$company) {
                    $user = auth()->user();
                    // Try to find any company
                    $company = $user->companies()->first();

                    if (!$company) {
                        // Create default company if none exists
                        $company = $user->companies()->create([
                            'name' => 'Моя Организация',
                            'inn' => '0000000000',
                            'type' => 'ООО',
                            'ogrn' => '0000000000000',
                            'legal_address' => 'Адрес не указан',
                            'is_active' => true
                        ]);
                    }
                    $tenantService->setCompany($company);
                }

                $act = \App\Models\Act::create([
                    'company_id' => $company ? $company->id : null,
                    'filename' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'act_data' => $data,
                    'status' => 'processed',
                    'processing_result' => $data,
                ]);

                // For now ensuring we return the data to frontend to show "Processed"
                $processed[] = [
                    'filename' => $file->getClientOriginalName(),
                    'data' => $data,
                    'db_id' => $act->id
                ];

            } catch (\Exception $e) {
                $errors[] = $file->getClientOriginalName() . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Обработка завершена',
            'processed' => $processed,
            'errors' => $errors
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
