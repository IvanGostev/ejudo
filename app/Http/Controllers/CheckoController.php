<?php

namespace App\Http\Controllers;

use App\Services\CheckoService;
use Illuminate\Http\Request;

class CheckoController extends Controller
{
    protected $checko;

    public function __construct(CheckoService $checko)
    {
        $this->checko = $checko;
    }

    /**
     * Поиск компании по ИНН через сервис Checko.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findByInn(Request $request)
    {
        $inn = $request->query('inn');

        if (!$inn || strlen($inn) < 10) {
            return response()->json(['error' => 'Неверный ИНН'], 400);
        }

        $data = $this->checko->findByInn($inn);

        if (!$data) {
            return response()->json(['error' => 'Компания не найдена'], 404);
        }

        return response()->json($data);
    }
}
