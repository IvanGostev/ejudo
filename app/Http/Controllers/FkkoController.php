<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FkkoController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');

        $results = \App\Models\FkkoCode::query();

        if ($query) {
            $results->where('code', 'like', "%{$query}%")
                ->orWhere('name', 'like', "%{$query}%");
        }

        // Limit results for performance
        $data = $results->limit(50)->get();

        return response()->json($data);
    }
}
