@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('polygons.index') }}" class="btn btn-link text-decoration-none ps-0 me-2 text-muted">
                    <i class="bi bi-arrow-left fs-5"></i>
                </a>
                <h4 class="mb-0">Добавление полигона</h4>
            </div>

            <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
                <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
                <div>
                    <strong>Что такое полигон?</strong>
                    Объект размещения отходов (полигон, свалка, склад) — площадка, которой управляет ваша компания.
                    После добавления первого полигона система перейдёт в расширенный режим учёта:
                    каждая запись журнала будет привязываться к конкретному объекту.
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('polygons.store') }}" method="POST">
                        @csrf

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
                                   name="name" value="{{ old('name') }}"
                                   placeholder="Полигон ТБО «Северный», Площадка №2...">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Адрес <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('address') is-invalid @enderror"
                                      name="address" rows="2"
                                      placeholder="Область, район, населённый пункт...">{{ old('address') }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Описание</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      name="description" rows="2"
                                      placeholder="Тип объекта, допустимые классы отходов и другие примечания">{{ old('description') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Характеристики</h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Площадь (га)</label>
                                <input type="number" step="0.01" min="0"
                                       class="form-control @error('area') is-invalid @enderror"
                                       name="area" value="{{ old('area') }}"
                                       placeholder="0.00">
                                @error('area') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Проектная вместимость (т)</label>
                                <input type="number" step="0.01" min="0"
                                       class="form-control @error('capacity') is-invalid @enderror"
                                       name="capacity" value="{{ old('capacity') }}"
                                       placeholder="0.00">
                                @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">Используется для расчёта процента загрузки.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Статус <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" name="status">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Активен</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Неактивен</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('polygons.index') }}" class="btn btn-light me-2">Отмена</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-plus-lg me-1"></i>Добавить полигон
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
