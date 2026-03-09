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

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:doc,docx|max:10240',
        ], [
            'files.*.mimes' => 'Допускаются только файлы в форматах .doc и .docx. PDF-файлы временно не поддерживаются.',
        ]);

        $files = $request->file('files');
        $processed = [];
        $errors = [];

        $ai = new \App\Services\GigaChatService();

        foreach ($files as $file) {
            try {
                if (!$file->isValid()) {
                    throw new \Exception("File upload error: " . $file->getErrorMessage());
                }
                $path = $file->store('acts', 'local');

                if ($path === false) {
                    throw new \Exception("Failed to store file. Check permissions or disk configuration.");
                }

                $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);

                if (!file_exists($fullPath)) {

                    throw new \Exception("File saved at '$path' but not found at '$fullPath'");
                }
                $data = $ai->extractJsonFromAct($fullPath);
                $tenantService = app(\App\Services\TenantService::class);
                $company = $tenantService->getCompany();

                if (!$company) {
                    $user = auth()->user();

                    $company = $user->companies()->first();

                    if (!$company) {

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
                    'act_number' => $company ? \App\Models\Act::nextActNumber($company->id) : null,
                    'filename' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'act_data' => $data,
                    'status' => 'processed',
                    'processing_result' => $data,
                ]);
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

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $act = \App\Models\Act::findOrFail($id);
        $data = $act->act_data;

        $field = $request->input('field');
        $value = $request->input('value');
        $itemIndex = $request->input('item_index');
        if (in_array($field, ['date', 'number', 'provider', 'receiver'])) {

            if (isset($data['items']) && count($data['items']) > 1 && $itemIndex !== null && isset($data['items'][$itemIndex])) {

                $newAct = $act->replicate();
                $newAct->status = 'processed';
                $newDataForNewAct = $data;
                $targetItem = $data['items'][$itemIndex];
                $newDataForNewAct['items'] = [$targetItem];

                $newDataForNewAct[$field] = $value;
                $newAct->act_data = $newDataForNewAct;
                $newAct->save();
                unset($data['items'][$itemIndex]);

                $data['items'] = array_values($data['items']);
                $act->act_data = $data;
                $act->save();
                return response()->json([
                    'success' => true,
                    'split' => true,
                    'new_act_id' => $newAct->id,
                    'new_item_index' => 0
                ]);
            }

            $data[$field] = $value;
        }

        elseif (in_array($field, ['quantity', 'name'])) {
            if (isset($data['items'][$itemIndex])) {
                $data['items'][$itemIndex][$field] = $value;
            }
        }

        $act->act_data = $data;
        $act->save();

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $act = \App\Models\Act::findOrFail($id);
        $act->delete();
        return response()->json(['success' => true]);
    }

    public function destroyItem(string $id, int $itemIndex)
    {
        $act = \App\Models\Act::findOrFail($id);
        $data = $act->act_data;

        if (isset($data['items']) && isset($data['items'][$itemIndex])) {
            unset($data['items'][$itemIndex]);

            $data['items'] = array_values($data['items']);

            if (empty($data['items'])) {
                $act->delete();
            } else {
                $act->act_data = $data;
                $act->save();
            }
        }

        return response()->json(['success' => true]);
    }
}
