<?php

namespace App\Http\Controllers;

use App\Models\Polygon;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PolygonController extends Controller
{
    private const MAX_POLYGONS = 100;

    private function getCompany()
    {
        $company = app(TenantService::class)->getCompany();

        if (!$company) {
            abort(403, 'Компания не выбрана');
        }

        return $company;
    }

    public function index()
    {
        $company = $this->getCompany();
        $polygons = $company->polygons()->orderBy('name')->get();

        return view('polygons.index', compact('company', 'polygons'));
    }

    public function create()
    {
        $company = $this->getCompany();

        return view('polygons.create', compact('company'));
    }

    public function store(Request $request)
    {
        $company = $this->getCompany();


        if ($company->polygons()->count() >= self::MAX_POLYGONS) {
            return back()->with('error', 'Достигнут максимальный лимит полигонов (' . self::MAX_POLYGONS . ').');
        }

        $validated = $request->validate([
            'name'        => [
                'required', 'string', 'max:255',
                Rule::unique('polygons')->where('company_id', $company->id),
            ],
            'address'     => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
            'area'        => 'nullable|numeric|min:0|max:999999999',
            'capacity'    => 'nullable|numeric|min:0|max:999999999',
            'status'      => 'required|in:active,inactive',
        ], [
            'name.required'  => 'Введите название полигона',
            'name.unique'    => 'Полигон с таким названием уже существует в этой компании',
            'address.required' => 'Введите адрес полигона',
        ]);

        $validated['company_id'] = $company->id;
        Polygon::create($validated);

        return redirect()
            ->route('polygons.index')
            ->with('success', 'Полигон успешно добавлен. Теперь при создании журналов вы можете указывать полигон.');
    }

    public function edit(Polygon $polygon)
    {
        $company = $this->getCompany();

        if ($polygon->company_id !== $company->id) {
            abort(403);
        }

        return view('polygons.edit', compact('company', 'polygon'));
    }

    public function update(Request $request, Polygon $polygon)
    {
        $company = $this->getCompany();

        if ($polygon->company_id !== $company->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name'        => [
                'required', 'string', 'max:255',
                Rule::unique('polygons')->where('company_id', $company->id)->ignore($polygon->id),
            ],
            'address'     => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
            'area'        => 'nullable|numeric|min:0|max:999999999',
            'capacity'    => 'nullable|numeric|min:0|max:999999999',
            'status'      => 'required|in:active,inactive',
        ], [
            'name.required' => 'Введите название полигона',
            'name.unique'   => 'Полигон с таким названием уже существует в этой компании',
            'address.required' => 'Введите адрес полигона',
        ]);

        $polygon->update($validated);

        return redirect()
            ->route('polygons.index')
            ->with('success', 'Данные полигона обновлены');
    }

    public function destroy(Polygon $polygon)
    {
        $company = $this->getCompany();

        if ($polygon->company_id !== $company->id) {
            abort(403);
        }

        if ($polygon->judoJournals()->exists()) {
            return back()->with('error', 'Нельзя удалить полигон, к которому привязаны записи журнала. Сначала перенесите или удалите эти записи.');
        }

        $isLast = $company->polygons()->count() === 1;

        $polygon->delete();

        $message = 'Полигон удалён.';
        if ($isLast) {
            $message .= ' Все полигоны удалены — система вернулась в стандартный режим учёта без разделения по объектам.';
        }

        return redirect()
            ->route('polygons.index')
            ->with('success', $message);
    }
}
