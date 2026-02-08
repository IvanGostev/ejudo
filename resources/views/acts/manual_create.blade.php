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
                                    <div class="form-text">Кто передал отход</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Контрагент (Получатель)</label>
                                    <input type="text" name="receiver" class="form-control"
                                        value="{{ old('receiver', (session('user_role') === 'Переработчик отходов' ? ($currentCompany->name ?? '') : '')) }}"
                                        required>
                                    <div class="form-text">Кто принял отход</div>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="text-uppercase text-muted fw-bold mb-3">Информация об отходе</h6>

                            <div class="mb-3 position-relative">
                                <label class="form-label">Поиск отхода (Наименование или код ФККО)</label>
                                <input type="text" id="waste-search" class="form-control" placeholder="Начните вводить..."
                                    value="{{ old('waste_name', $fkko->name ?? '') }}" autocomplete="off" required>

                                <div id="waste-results" class="list-group position-absolute w-100 shadow-sm"
                                    style="display:none; z-index: 1000; max-height: 250px; overflow-y: auto;"></div>

                                <input type="hidden" name="waste_name" id="hidden-waste-name"
                                    value="{{ old('waste_name', $fkko->name ?? '') }}">
                                <input type="hidden" name="fkko_code" id="hidden-fkko-code"
                                    value="{{ old('fkko_code', $fkko->code ?? '') }}">
                                <input type="hidden" name="hazard_class" id="hidden-hazard-class"
                                    value="{{ old('hazard_class', isset($fkko) ? substr($fkko->code, -1) : '') }}">
                            </div>

                            <div id="selected-waste-display"
                                class="alert alert-light border mb-4 {{ isset($fkko) ? '' : 'd-none' }}">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="small text-muted mb-1">Выбранный отход:</div>
                                        <div class="fw-bold" id="display-name">{{ $fkko->name ?? '' }}</div>
                                        <div class="small">
                                            Код: <span class="fw-bold" id="display-fkko">{{ $fkko->code ?? '' }}</span> |
                                            Класс: <span class="fw-bold"
                                                id="display-hazard">{{ isset($fkko) ? substr($fkko->code, -1) : '' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            onclick="clearWasteSelection()">Изменить</button>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Количество (тонн)</label>
                                    <input type="text" name="amount" class="form-control" value="{{ old('amount') }}"
                                        inputmode="decimal" placeholder="0.000" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label d-block">Вид обращения</label>
                                    <div class="row">
                                        <div class="col-md-4 col-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="operation_type[]"
                                                    value="Транспортирование" id="op1" checked>
                                                <label class="form-check-label" for="op1">Транспортирование</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="operation_type[]"
                                                    value="Утилизация" id="op2">
                                                <label class="form-check-label" for="op2">Утилизация</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="operation_type[]"
                                                    value="Обезвреживание" id="op3">
                                                <label class="form-check-label" for="op3">Обезвреживание</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="operation_type[]"
                                                    value="Захоронение" id="op4">
                                                <label class="form-check-label" for="op4">Захоронение</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="operation_type[]"
                                                    value="Обработка" id="op5">
                                                <label class="form-check-label" for="op5">Обработка</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-text">Выберите одно или несколько действий, совершаемых с отходом.
                                    </div>
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

@push('scripts')
    <script>
        function clearWasteSelection() {
            document.getElementById('waste-search').value = '';
            document.getElementById('hidden-waste-name').value = '';
            document.getElementById('hidden-fkko-code').value = '';
            document.getElementById('hidden-hazard-class').value = '';
            document.getElementById('selected-waste-display').classList.add('d-none');
            document.getElementById('waste-search').focus();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('waste-search');
            const results = document.getElementById('waste-results');
            const display = document.getElementById('selected-waste-display');

            const hName = document.getElementById('hidden-waste-name');
            const hFkko = document.getElementById('hidden-fkko-code');
            const hHazard = document.getElementById('hidden-hazard-class');

            const dName = document.getElementById('display-name');
            const dFkko = document.getElementById('display-fkko');
            const dHazard = document.getElementById('display-hazard');

            let timeout = null;

            input.addEventListener('input', function () {
                clearTimeout(timeout);
                const query = this.value.trim();

                if (query.length < 3) {
                    results.style.display = 'none';
                    return;
                }

                timeout = setTimeout(() => {
                    fetch(`/fkko/search?q=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            results.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(item => {
                                    const a = document.createElement('a');
                                    a.href = '#';
                                    a.className = 'list-group-item list-group-item-action py-2';
                                    a.innerHTML = `
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="small fw-bold text-wrap" style="max-width: 80%;">${item.name}</div>
                                                <span class="badge bg-primary ms-2">${item.code}</span>
                                            </div>
                                        `;
                                    a.onclick = (e) => {
                                        e.preventDefault();

                                        hName.value = item.name;
                                        hFkko.value = item.code;
                                        hHazard.value = item.hazard_class;

                                        dName.textContent = item.name;
                                        dFkko.textContent = item.code;
                                        dHazard.textContent = item.hazard_class;

                                        display.classList.remove('d-none');
                                        input.value = item.name;
                                        results.style.display = 'none';
                                    };
                                    results.appendChild(a);
                                });
                                results.style.display = 'block';
                            } else {
                                results.style.display = 'none';
                            }
                        });
                }, 300);
            });

            document.addEventListener('click', function (e) {
                if (!input.contains(e.target) && !results.contains(e.target)) {
                    results.style.display = 'none';
                }
            });
        });
    </script>
@endpush