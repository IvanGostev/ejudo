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
        $act = \App\Models\Act::findOrFail($id);
        $data = $act->act_data;

        $field = $request->input('field');
        $value = $request->input('value');
        $itemIndex = $request->input('item_index'); // This will be null if not provided, or an integer

        // Root fields: date, number, provider, receiver
        if (in_array($field, ['date', 'number', 'provider', 'receiver'])) {
            // Check if we need to SPLIT the act
            // This happens if:
            // 1. The act has multiple items.
            // 2. The edit is for a specific item (itemIndex is provided).
            // 3. The user wants to change a root field for *that specific item* only.
            if (isset($data['items']) && count($data['items']) > 1 && $itemIndex !== null && isset($data['items'][$itemIndex])) {
                // --- SPLIT LOGIC ---

                // 1. Create a new Act instance by replicating the original
                $newAct = $act->replicate();
                $newAct->status = 'processed'; // Ensure status is correct for new act

                // 2. Prepare data for the new Act
                $newDataForNewAct = $data; // Start with a copy of the original act_data
                $targetItem = $data['items'][$itemIndex]; // Get the item to be moved

                // The new Act will contain ONLY this target item
                $newDataForNewAct['items'] = [$targetItem];
                // Apply the update to the root field in the new Act's data
                $newDataForNewAct[$field] = $value;
                $newAct->act_data = $newDataForNewAct;
                $newAct->save();

                // 3. Modify the original Act: remove the item that was moved to the new Act
                unset($data['items'][$itemIndex]);
                // Re-index the array to prevent gaps
                $data['items'] = array_values($data['items']);
                $act->act_data = $data;
                $act->save();

                // Return response indicating a split occurred
                return response()->json([
                    'success' => true,
                    'split' => true,
                    'new_act_id' => $newAct->id,
                    'new_item_index' => 0 // The moved item is now the first (and only) item in the new act
                ]);
            }

            // If not splitting (e.g., only one item in the act, or itemIndex not provided),
            // then update the root field directly on the current act.
            $data[$field] = $value;
        }
        // Item-level fields
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
            // Re-index array to prevent gaps
            $data['items'] = array_values($data['items']);

            // If no items left, delete the act? Or keep empty act?
            // User likely expects "Deleting report" -> if it's the last one, the Act itself is gone.
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
