@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Полигоны</h4>
            <small class="text-muted">{{ $company->name }}</small>
        </div>
        <a href="{{ route('polygons.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Добавить полигон
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($polygons->isEmpty())
        <div class="text-center py-5 bg-white rounded shadow-sm">
            <i class="bi bi-map display-4 text-muted mb-3 d-block"></i>
            <h5 class="text-muted">Нет добавленных полигонов</h5>
            <p class="text-muted mb-4">
                Добавьте объект размещения отходов, чтобы вести раздельный учёт по каждой площадке.
            </p>
            <a href="{{ route('polygons.create') }}" class="btn btn-outline-primary">
                <i class="bi bi-plus-lg me-1"></i>Добавить первый полигон
            </a>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Название</th>
                                <th>Адрес</th>
                                <th>Вместимость</th>
                                <th>Загрузка</th>
                                <th>Статус</th>
                                <th class="text-end pe-4">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($polygons as $polygon)
                                <tr>
                                    <td class="ps-4 fw-medium">{{ $polygon->name }}</td>
                                    <td class="text-truncate" style="max-width: 220px;" title="{{ $polygon->address }}">
                                        {{ $polygon->address }}
                                    </td>
                                    <td>
                                        @if($polygon->capacity)
                                            {{ number_format($polygon->capacity, 0, '.', ' ') }} т
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($polygon->capacity && $polygon->capacity > 0)
                                            @php
                                                $pct = min(100, ($polygon->current_load / $polygon->capacity) * 100);
                                                $color = $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : 'success');
                                            @endphp
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px; min-width: 60px;">
                                                    <div class="progress-bar bg-{{ $color }}" style="width: {{ $pct }}%"></div>
                                                </div>
                                                <small class="text-nowrap text-muted">{{ number_format($pct, 0) }}%</small>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($polygon->status === 'active')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Активен</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Неактивен</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('polygons.edit', $polygon->id) }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil me-1"></i>Изменить
                                            </a>
                                            <form action="{{ route('polygons.destroy', $polygon->id) }}" method="POST"
                                                  onsubmit="return confirm('Удалить полигон «{{ $polygon->name }}»? Это действие нельзя отменить.')">
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

        <p class="text-muted small mt-3">
            <i class="bi bi-info-circle me-1"></i>
            Записи журнала учёта автоматически привязываются к выбранному полигону.
            Всего полигонов: <strong>{{ $polygons->count() }}</strong>
        </p>
    @endif
@endsection
