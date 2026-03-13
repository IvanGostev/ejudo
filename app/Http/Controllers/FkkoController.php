<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FkkoController extends Controller
{
    /**
     * Поиск ФККО с учётом пробелов в коде.
     * Код ФККО хранится с пробелами (напр. "4 05 211 01 52 4"),
     * пользователь может вводить как с пробелами, так и без.
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        $results = \App\Models\FkkoCode::query();

        if ($query) {

            $queryNormalized = preg_replace('/\s+/', ' ', trim($query));

            $queryNoSpaces = preg_replace('/\s+/', '', $query);

            $results->where(function ($q) use ($queryNormalized, $queryNoSpaces) {

                $q->where('name', 'like', "%{$queryNormalized}%")

                  ->orWhere('code', 'like', "%{$queryNormalized}%")

                  ->orWhereRaw("REPLACE(code, ' ', '') LIKE ?", ["%{$queryNoSpaces}%"]);
            });
        }

        $data = $results->limit(50)->get()->map(function ($item) {
            return [
                'id'           => $item->id,
                'code'         => $item->code,
                'name'         => $item->name,
                'hazard_class' => $item->hazard_class,
            ];
        });

        return response()->json($data);
    }
}
