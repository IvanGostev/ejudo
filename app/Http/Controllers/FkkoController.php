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
            // Нормализуем запрос: убираем лишние пробелы для поиска по имени/коду
            $queryNormalized = preg_replace('/\s+/', ' ', trim($query));
            // Для поиска без пробелов — убираем все пробелы из запроса
            $queryNoSpaces = preg_replace('/\s+/', '', $query);

            $results->where(function ($q) use ($queryNormalized, $queryNoSpaces) {
                // Поиск по имени (с нормализованными пробелами)
                $q->where('name', 'like', "%{$queryNormalized}%")
                  // Поиск по коду как есть (с пробелами)
                  ->orWhere('code', 'like', "%{$queryNormalized}%")
                  // Поиск по коду без пробелов (пользователь ввёл без пробелов)
                  ->orWhereRaw("REPLACE(code, ' ', '') LIKE ?", ["%{$queryNoSpaces}%"]);
            });
        }

        $data = $results->limit(50)->get()->map(function ($item) {
            return [
                'id'           => $item->id,
                'code'         => $item->code,         // с пробелами для отображения
                'name'         => $item->name,
                'hazard_class' => $item->hazard_class,
            ];
        });

        return response()->json($data);
    }
}
