@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Ручное добавление акта</h5>
                    </div>
                    <div class="card-body p-4">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('acts.manual.store') }}" method="POST">
                            @csrf

                            <!-- Current Company (Read-only or Selector logic handled in middleware/service usually, here just display) -->
                            <div class="mb-4">
                                <label class="form-label text-muted small text-uppercase fw-bold">Ваша организация</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $currentCompany->name ?? 'Не выбрана' }}" readonly>
                                @if(!$currentCompany)
                                    <div class="form-text text-danger">Пожалуйста, выберите компанию в меню "Мои компании" или
                                        на главной.</div>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Дата акта</label>
                                    <input type="date" name="date" class="form-control"
                                        value="{{ old('date', date('Y-m-d')) }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Номер акта (Договор)</label>
                                    <input type="text" name="number" class="form-control" value="{{ old('number') }}"
                                        placeholder="Например: 123 или 104/ХФЗТ/24" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Контрагент (Поставщик)</label>
                                    <input type="text" name="provider" class="form-control"
                                        value="{{ old('provider', (session('user_role') === 'Переработчик отходов' ? '' : ($currentCompany->name ?? ''))) }}"
                                        required>
                                    <div class="form-text">Кто передал отход (Исполнитель по акту)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Контрагент (Получатель)</label>
                                    <input type="text" name="receiver" class="form-control"
                                        value="{{ old('receiver', (session('user_role') === 'Переработчик отходов' ? ($currentCompany->name ?? '') : '')) }}"
                                        required>
                                    <div class="form-text">Кто принял отход (Заказчик по акту)</div>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="text-uppercase text-muted fw-bold mb-3">Инфорация об отходе</h6>

                            <div class="mb-3">
                                <label class="form-label">Наименование отхода</label>
                                <input type="text" name="waste_name" class="form-control bg-light"
                                    value="{{ old('waste_name', $fkko->name ?? '') }}" readonly required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Код ФККО</label>
                                    <input type="text" name="fkko_code" class="form-control bg-light"
                                        value="{{ old('fkko_code', $fkko->code ?? '') }}" readonly required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Класс опасности</label>
                                    <input type="text" name="hazard_class" class="form-control bg-light"
                                        value="{{ old('hazard_class', isset($fkko) ? substr($fkko->code, -1) : '') }}"
                                        readonly required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Количество (тонн)</label>
                                    <input type="number" step="0.001" name="amount" class="form-control"
                                        value="{{ old('amount') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Вид обращения</label>
                                    <select name="operation_type" class="form-select" required>
                                        <option value="Транспортирование">Транспортирование</option>
                                        <option value="Утилизация">Утилизация</option>
                                        <option value="Обезвреживание">Обезвреживание</option>
                                        <option value="Захоронение">Захоронение</option>
                                        <option value="Обработка">Обработка</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary me-2">Отмена</a>
                                <button type="submit" class="btn btn-primary px-4">Сохранить акт</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection