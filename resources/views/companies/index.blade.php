@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Мои Компании</h4>
        <a href="{{ route('companies.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Добавить компанию
        </a>
    </div>

    @if($companies->isEmpty())
        <div class="text-center py-5 bg-white rounded shadow-sm">
            <i class="bi bi-building display-4 text-muted mb-3"></i>
            <h5 class="text-muted">Нет добавленных компаний</h5>
            <p class="text-muted mb-4">Добавьте данные вашей организации для автоматического заполнения документов.</p>
            <a href="{{ route('companies.create') }}" class="btn btn-outline-primary">Создать компанию</a>
        </div>
    @else
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Наименование</th>
                                <th>ИНН</th>
                                <th>Тип</th>
                                <th>Адрес</th>
                                <th>Телефон</th>
                                <th class="text-end pe-4">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($companies as $company)
                                <tr>
                                    <td class="ps-4 fw-medium">{{ $company->name }}</td>
                                    <td>{{ $company->inn }}</td>
                                    <td><span class="badge bg-secondary">{{ $company->type }}</span></td>
                                    <td class="text-truncate" style="max-width: 250px;" title="{{ $company->legal_address }}">
                                        {{ $company->legal_address }}
                                    </td>
                                    <td>{{ $company->phone ?? '-' }}</td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-sm btn-primary text-white">
                                            <i class="bi bi-pencil me-1"></i>Редактировать
                                        </a>
                                        <form action="{{ route('companies.destroy', $company->id) }}" method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить эту компанию?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-dark" style="background-color: #000218; border-color: #000218;">
                                                <i class="bi bi-trash me-1"></i>Удалить
                                            </button>
                                        </form>
                                    </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Блок «Режим полигонов» — для компаний без полигонов --}}
        @php
            $currentCompany = app(\App\Services\TenantService::class)->getCompany();
        @endphp
        @if($currentCompany && !$currentCompany->hasPolygons())
            <div class="card border-0 shadow-sm" style="border-left: 4px solid #FF4C2B !important;">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-auto pe-0">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 56px; height: 56px; background: rgba(255,76,43,0.1);">
                                <i class="bi bi-geo-alt-fill text-judo-orange fs-4"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h5 class="mb-1 fw-bold">Режим множественных полигонов</h5>
                            <p class="text-muted mb-0 small">
                                Если вы управляете несколькими объектами размещения отходов (полигонами), вы можете
                                вести отдельный учёт ЖУДО для каждого из них. После добавления первого полигона
                                в меню появится раздел <strong>«Полигоны»</strong> и при создании журналов можно будет
                                выбрать конкретный объект.
                            </p>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('polygons.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-1"></i>Добавить первый полигон
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($currentCompany && $currentCompany->hasPolygons())
            @php $polygonCount = $currentCompany->polygons()->count(); @endphp
            <div class="card border-0 shadow-sm" style="border-left: 4px solid #198754 !important;">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col-auto pe-0">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 44px; height: 44px; background: rgba(25,135,84,0.1);">
                                <i class="bi bi-geo-alt-fill text-success fs-5"></i>
                            </div>
                        </div>
                        <div class="col">
                            <div class="fw-medium">Режим полигонов активен для <strong>{{ $currentCompany->name }}</strong></div>
                            <div class="text-muted small">{{ $polygonCount }} {{ $polygonCount === 1 ? 'полигон' : ($polygonCount < 5 ? 'полигона' : 'полигонов') }} — учёт ЖУДО ведётся по каждому объекту отдельно</div>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('polygons.index') }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-geo-alt me-1"></i>Управление полигонами
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
@endsection