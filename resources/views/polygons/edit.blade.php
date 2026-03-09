@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('polygons.index') }}" class="btn btn-link text-decoration-none ps-0 me-2 text-muted">
                    <i class="bi bi-arrow-left fs-5"></i>
                </a>
                <h4 class="mb-0">Редактирование полигона</h4>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body px-4 py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small">Текущая загрузка:</span>
                        @if($polygon->capacity && $polygon->capacity > 0)
                            @php
                                $pct = min(100, ($polygon->current_load / $polygon->capacity) * 100);
                                $color = $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : 'success');
                            @endphp
                            <span class="fw-bold text-{{ $color }} ms-1">
                                {{ number_format($polygon->current_load, 2) }} /
                                {{ number_format($polygon->capacity, 2) }} т
                                ({{ number_format($pct, 1) }}%)
                            </span>
                        @else
                            <span class="text-muted ms-1">данные не заданы</span>
                        @endif
                    </div>
                    <span class="badge {{ $polygon->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $polygon->status === 'active' ? 'Активен' : 'Неактивен' }}
                    </span>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('polygons.update', $polygon->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Основные сведения</h6>

                        <div class="mb-3">
                            <label class="form-label">Название <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   name="name" value="{{ old('name', $polygon->name) }}"
                                   placeholder="Полигон ТБО «Северный»...">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Адрес <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('address') is-invalid @enderror"
                                      name="address" rows="2">{{ old('address', $polygon->address) }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Описание</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      name="description" rows="2">{{ old('description', $polygon->description) }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Характеристики</h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Площадь (га)</label>
                                <input type="number" step="0.01" min="0"
                                       class="form-control @error('area') is-invalid @enderror"
                                       name="area" value="{{ old('area', $polygon->area) }}">
                                @error('area') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Проектная вместимость (т)</label>
                                <input type="number" step="0.01" min="0"
                                       class="form-control @error('capacity') is-invalid @enderror"
                                       name="capacity" value="{{ old('capacity', $polygon->capacity) }}">
                                @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Статус <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" name="status">
                                <option value="active" {{ old('status', $polygon->status) === 'active' ? 'selected' : '' }}>Активен</option>
                                <option value="inactive" {{ old('status', $polygon->status) === 'inactive' ? 'selected' : '' }}>Неактивен</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('polygons.index') }}" class="btn btn-light me-2">Отмена</a>
                            <button type="submit" class="btn btn-primary px-4">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
