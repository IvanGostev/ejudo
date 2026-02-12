@extends('layouts.app')

@push('styles')
    <style>
        #waste-search-section {
            display: none;
        }
    </style>
@endpush

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Ручное добавление акта</h5>
                    </div>
                    <div class="card-body p-4">


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
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-uppercase text-muted fw-bold mb-0">Информация об отходах</h6>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addWasteItem()">
                                    <i class="bi bi-plus-circle"></i> Добавить отход
                                </button>
                            </div>

                            <div id="waste-items-container">
                                <!-- Waste items will be added here -->
                            </div>

                            <div id="waste-search-section" class="border rounded p-3 mb-3">
                                <div class="mb-3 position-relative">
                                    <label class="form-label">Поиск отхода (Наименование или код ФККО)</label>
                                    <input type="text" id="waste-search" class="form-control"
                                        placeholder="Начните вводить..." autocomplete="off">

                                    <div id="waste-results" class="list-group position-absolute w-100 shadow-sm"
                                        style="display:none; z-index: 1000; max-height: 250px; overflow-y: auto;"></div>
                                </div>

                                <div id="selected-waste-display" class="alert alert-light border mb-3 d-none">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="small text-muted mb-1">Выбранный отход:</div>
                                            <div class="fw-bold" id="display-name"></div>
                                            <div class="small">
                                                Код: <span class="fw-bold" id="display-fkko"></span> |
                                                Класс: <span class="fw-bold" id="display-hazard"></span>
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
                                        <input type="text" id="temp-amount" class="form-control" inputmode="decimal"
                                            placeholder="0.000">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label d-block">Вид обращения</label>
                                        <div class="row">
                                            <div class="col-md-4 col-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="temp-op1"
                                                        value="Транспортирование" checked>
                                                    <label class="form-check-label" for="temp-op1">Транспортирование</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="temp-op2"
                                                        value="Утилизация">
                                                    <label class="form-check-label" for="temp-op2">Утилизация</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="temp-op3"
                                                        value="Обезвреживание">
                                                    <label class="form-check-label" for="temp-op3">Обезвреживание</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="temp-op4"
                                                        value="Захоронение">
                                                    <label class="form-check-label" for="temp-op4">Захоронение</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="temp-op5"
                                                        value="Обработка">
                                                    <label class="form-check-label" for="temp-op5">Обработка</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="temp-op6"
                                                        value="Размещение">
                                                    <label class="form-check-label" for="temp-op6">Размещение</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-text">Выберите одно или несколько действий, совершаемых с отходом.
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="button" class="btn btn-primary" onclick="addSelectedWaste()">
                                        Добавить в список
                                    </button>
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
        let wasteItems = [];
        let currentWasteData = null;
        let wasteItemCounter = 0;

        function clearWasteSelection() {
            document.getElementById('waste-search').value = '';
            currentWasteData = null;
            document.getElementById('selected-waste-display').classList.add('d-none');
            document.getElementById('waste-search').focus();
        }

        function addWasteItem() {
            const section = document.getElementById('waste-search-section');
            section.style.display = 'block';
            section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            document.getElementById('waste-search').focus();
        }

        function addSelectedWaste() {
            if (!currentWasteData) {
                alert('Пожалуйста, выберите отход из списка');
                return;
            }

            const amount = document.getElementById('temp-amount').value.trim().replace(',', '.');
            if (!amount || parseFloat(amount) <= 0) {
                alert('Пожалуйста, укажите корректное количество');
                return;
            }

            const operationTypes = [];
            document.querySelectorAll('[id^="temp-op"]:checked').forEach(cb => {
                operationTypes.push(cb.value);
            });

            if (operationTypes.length === 0) {
                alert('Пожалуйста, выберите хотя бы один вид обращения');
                return;
            }

            const wasteItem = {
                id: ++wasteItemCounter,
                name: currentWasteData.name,
                fkko_code: currentWasteData.code,
                hazard_class: currentWasteData.hazard_class,
                amount: parseFloat(amount),
                operation_types: operationTypes
            };

            wasteItems.push(wasteItem);
            renderWasteItems();
            resetWasteForm();

            const errorAlert = document.querySelector('.alert-danger');
            if (errorAlert) {
                errorAlert.style.display = 'none';
            }
        }

        function removeWasteItem(id) {
            wasteItems = wasteItems.filter(item => item.id !== id);
            renderWasteItems();
        }

        function renderWasteItems() {
            const container = document.getElementById('waste-items-container');

            if (wasteItems.length === 0) {
                container.innerHTML = '<div class="alert alert-info">Отходы не добавлены. Нажмите "Добавить отход" для начала.</div>';
                return;
            }

            let html = '';
            wasteItems.forEach((item, index) => {
                html += `
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-2">${index + 1}. ${item.name}</h6>
                                                    <div class="small text-muted mb-2">
                                                        <strong>Код ФККО:</strong> ${item.fkko_code} | 
                                                        <strong>Класс опасности:</strong> ${item.hazard_class} | 
                                                        <strong>Количество:</strong> ${item.amount} т
                                                    </div>
                                                    <div class="small">
                                                        <strong>Вид обращения:</strong> ${item.operation_types.join(', ')}
                                                    </div>
                                                    <input type="hidden" name="wastes[${index}][name]" value="${item.name}">
                                                    <input type="hidden" name="wastes[${index}][fkko_code]" value="${item.fkko_code}">
                                                    <input type="hidden" name="wastes[${index}][hazard_class]" value="${item.hazard_class}">
                                                    <input type="hidden" name="wastes[${index}][amount]" value="${item.amount}">
                                                    <input type="hidden" name="wastes[${index}][operation_types]" value="${item.operation_types.join(', ')}">
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger ms-3" onclick="removeWasteItem(${item.id})">
                                                    <i class="bi bi-trash"></i> Удалить
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;
            });

            container.innerHTML = html;
        }

        function resetWasteForm() {
            document.getElementById('waste-search').value = '';
            document.getElementById('temp-amount').value = '';
            document.querySelectorAll('[id^="temp-op"]').forEach(cb => {
                cb.checked = cb.id === 'temp-op1';
            });
            currentWasteData = null;
            document.getElementById('selected-waste-display').classList.add('d-none');
            document.getElementById('waste-search-section').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function () {
            renderWasteItems();

            const form = document.querySelector('form');
            form.addEventListener('submit', function (e) {
                if (wasteItems.length === 0) {
                    e.preventDefault();
                    alert('Пожалуйста, добавьте хотя бы один вид отхода');
                    return false;
                }
            });

            const input = document.getElementById('waste-search');
            const results = document.getElementById('waste-results');
            const display = document.getElementById('selected-waste-display');

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

                                        currentWasteData = {
                                            name: item.name,
                                            code: item.code,
                                            hazard_class: item.hazard_class
                                        };

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