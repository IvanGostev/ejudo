@extends('layouts.app')

@push('styles')
    <style>
        #waste-search-section {
            display: none;
        }
        .act-type-btn {
            border: 2px solid #dee2e6;
            background: white;
            border-radius: 8px;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            font-size: 0.875rem;
        }
        .act-type-btn:hover { border-color: #FF4C2B; color: #FF4C2B; }
        .act-type-btn.selected {
            border-color: #FF4C2B;
            background-color: rgba(255,76,43,0.08);
            color: #FF4C2B;
            font-weight: 600;
        }
        .act-type-btn i { display: block; font-size: 1.5rem; margin-bottom: 4px; }
    </style>
@endpush

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-9">

                {{-- Выбор типа акта --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Ручное добавление акта</h5>
                    </div>
                    <div class="card-body p-4">
                        <label class="form-label text-muted small text-uppercase fw-bold mb-3">Тип акта</label>
                        <div class="row g-2 mb-0">
                            @php
                                $actTypeIcons = [
                                    'transfer'       => 'bi-arrow-left-right',
                                    'processing'     => 'bi-gear',
                                    'utilization'    => 'bi-recycle',
                                    'neutralization' => 'bi-shield-check',
                                    'storage'        => 'bi-box-seam',
                                    'burial'         => 'bi-archive',
                                ];
                            @endphp
                            @foreach($actTypes as $typeKey => $typeLabel)
                                <div class="col-md-4 col-6">
                                    <a href="{{ route('acts.manual.create', ['act_type' => $typeKey]) }}"
                                       class="act-type-btn d-block text-decoration-none {{ $actType === $typeKey ? 'selected' : 'text-secondary' }}">
                                        <i class="bi {{ $actTypeIcons[$typeKey] ?? 'bi-file-earmark-text' }}"></i>
                                        {{ $typeLabel }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                        <span class="badge bg-danger fs-6">{{ $actTypes[$actType] ?? 'Акт передачи' }}</span>
                        @if($currentCompany)
                            <span class="text-muted small">{{ $currentCompany->name }}</span>
                        @endif
                    </div>
                    <div class="card-body p-4">

                        <form action="{{ route('acts.manual.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="act_type" value="{{ $actType }}">

                            {{-- Ваша организация --}}
                            <div class="mb-4">
                                <label class="form-label text-muted small text-uppercase fw-bold">Ваша организация</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $currentCompany->name ?? 'Не выбрана' }}" readonly>
                                @if(!$currentCompany)
                                    <div class="form-text text-danger">Пожалуйста, выберите компанию.</div>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label text-muted small text-uppercase fw-bold">Номер акта (назначается системой)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">№</span>
                                        <input type="text" class="form-control bg-light fw-bold" value="{{ $nextNumber }}" readonly>
                                    </div>
                                    <div class="form-text">Сквозная нумерация для всех типов актов</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Дата акта</label>
                                    <input type="date" name="date" class="form-control"
                                        value="{{ old('date', date('Y-m-d')) }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Номер договора, дата</label>
                                    <input type="text" name="contract_details" class="form-control"
                                        value="{{ old('contract_details') }}"
                                        placeholder="Например: Договор №5 от 01.01.2026">
                                    <div class="form-text">Номер и дата договора с контрагентом</div>
                                </div>
                            </div>

                            <div class="row align-items-end">
                                <div class="col-md-5 mb-3">
                                    <label class="form-label">Поставщик</label>
                                    <div class="input-group">
                                        <input type="text" name="provider" id="provider" class="form-control"
                                            value="{{ old('provider', '') }}"
                                            placeholder="Кто передал отход" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="findCompanyByInn('provider')" title="Найти по ИНН">
                                            <i class="bi bi-search"></i> ИНН
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-3 d-flex justify-content-center">
                                    <button type="button" class="btn btn-outline-secondary text-nowrap" onclick="swapCompanies()" title="Поменять местами">
                                        <i class="bi bi-arrow-left-right d-none d-md-inline-block"></i>
                                        <i class="bi bi-arrow-down-up d-inline-block d-md-none"></i>
                                        <span class="d-inline-block d-md-none ms-2">Поменять</span>
                                    </button>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="form-label">Получатель</label>
                                    <div class="input-group">
                                        <input type="text" name="receiver" id="receiver" class="form-control"
                                            value="{{ old('receiver', ($currentCompany->name ?? '')) }}"
                                            placeholder="Кто принял отход" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="findCompanyByInn('receiver')" title="Найти по ИНН">
                                            <i class="bi bi-search"></i> ИНН
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-uppercase text-muted fw-bold mb-0">Информация об отходах</h6>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addWasteItem()">
                                    <i class="bi bi-plus-circle"></i> Добавить отход
                                </button>
                            </div>

                            <div id="waste-items-container"></div>

                            <div id="waste-search-section" class="border rounded p-3 mb-3">
                                <div class="mb-3 position-relative">
                                    <label class="form-label">Поиск отхода (Наименование или код ФККО)</label>
                                    <input type="text" id="waste-search" class="form-control"
                                        placeholder="Начните вводить название или код (можно без пробелов)..." autocomplete="off">

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
                                            @php

                                                $operations = [
                                                    'temp-op2' => 'Утилизация',
                                                    'temp-op3' => 'Обезвреживание',
                                                    'temp-op4' => 'Обработка',
                                                    'temp-op5' => 'Размещение (Захоронение)',
                                                    'temp-op6' => 'Размещение (Хранение)',
                                                    'temp-op7' => 'Накопление',
                                                    'temp-op8' => 'Передача третьим лицам',
                                                    'temp-op9' => 'Транспортирование',
                                                ];
                                            @endphp
                                            @foreach($operations as $opId => $opLabel)
                                                <div class="col-md-6 col-12 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="{{ $opId }}"
                                                            value="{{ $opLabel }}">
                                                        <label class="form-check-label small" for="{{ $opId }}">{{ $opLabel }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
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
                                <a href="{{ route('acts.archive') }}" class="btn btn-outline-secondary me-2">Отмена</a>
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

        function swapCompanies() {
            const providerInput = document.getElementById('provider');
            const receiverInput = document.getElementById('receiver');
            const temp = providerInput.value;
            providerInput.value = receiverInput.value;
            receiverInput.value = temp;
        }

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
        }

        function removeWasteItem(id) {
            wasteItems = wasteItems.filter(item => item.id !== id);
            renderWasteItems();
        }

        function renderWasteItems() {
            const container = document.getElementById('waste-items-container');
            if (wasteItems.length === 0) {
                container.innerHTML = '<div class="alert alert-info">Отходы не добавлены. Нажмите «Добавить отход» для начала.</div>';
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
                    </div>`;
            });
            container.innerHTML = html;
        }

        function resetWasteForm() {
            document.getElementById('waste-search').value = '';
            document.getElementById('temp-amount').value = '';
            document.querySelectorAll('[id^="temp-op"]').forEach(cb => {
                cb.checked = false;
                if (cb.id === 'temp-op8') cb.disabled = true;
            });
            currentWasteData = null;
            document.getElementById('selected-waste-display').classList.add('d-none');
            document.getElementById('waste-search-section').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function () {
            renderWasteItems();

            const op8 = document.getElementById('temp-op8');
            const otherOps = document.querySelectorAll('[id^="temp-op"]:not(#temp-op8)');

            if (op8) {
                op8.disabled = true;
                const updateOp8 = () => {
                    let anyChecked = Array.from(otherOps).some(c => c.checked);
                    op8.checked = anyChecked;
                };
                otherOps.forEach(cb => cb.addEventListener('change', updateOp8));
            }

            const form = document.querySelector('form[action="{{ route('acts.manual.store') }}"]');
            form.addEventListener('submit', function (e) {
                if (wasteItems.length === 0) {
                    e.preventDefault();
                    alert('Пожалуйста, добавьте хотя бы один вид отхода');
                    return false;
                }
            });

            const input   = document.getElementById('waste-search');
            const results = document.getElementById('waste-results');
            const display = document.getElementById('selected-waste-display');
            const dName   = document.getElementById('display-name');
            const dFkko   = document.getElementById('display-fkko');
            const dHazard = document.getElementById('display-hazard');

            let timeout = null;

            input.addEventListener('input', function () {
                clearTimeout(timeout);
                const query = this.value.trim();
                if (query.length < 2) {
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
                                            <div class="small fw-bold text-wrap" style="max-width: 78%;">${item.name}</div>
                                            <span class="badge bg-primary ms-2 text-nowrap">${item.code}</span>
                                        </div>`;
                                    a.onclick = (e) => {
                                        e.preventDefault();
                                        currentWasteData = { name: item.name, code: item.code, hazard_class: item.hazard_class };
                                        dName.textContent   = item.name;
                                        dFkko.textContent   = item.code;
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

async function findCompanyByInn(targetFieldId) {
    const inn = prompt('Введите ИНН организации для поиска:');
    if (!inn) return;

    const btn = document.querySelector(`button[onclick="findCompanyByInn('${targetFieldId}')"]`);
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

    try {
        const response = await fetch(`{{ route('checko.inn') }}?inn=${inn}`);
        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || 'Ошибка при поиске');
        }

        const data = await response.json();
        const field = document.getElementById(targetFieldId);

        if (data.name) {
            field.value = data.name;
            alert(`Найдена организация: ${data.name}`);
        } else {
            alert('Организация не найдена');
        }
    } catch (error) {
        console.error('Checko Lookup Error:', error);
        alert('Ошибка: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}
</script>
@endpush