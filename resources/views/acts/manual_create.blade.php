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
                <div class="mb-4">
                    <h4 class="fw-bold">Ручное добавление акта</h4>
                </div>

                <div class="card shadow-sm border-0 mb-4">
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

                            <input type="hidden" id="current-company-info" value="{{ json_encode([
                                'name' => $currentCompany->name,
                                'inn' => $currentCompany->inn,
                                'kpp' => $currentCompany->kpp,
                                'legal_address' => $currentCompany->legal_address,
                                'license_number' => $currentCompany->license_details,
                                'license_valid_until' => $currentCompany->license_valid_until?->format('Y-m-d')
                            ]) }}">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Срок действия договора</label>
                                    <input type="text" name="contract_validity" class="form-control"
                                        value="{{ old('contract_validity') }}"
                                        placeholder="Например: до 31.12.2026 или бессрочный">
                                    <div class="form-text">Заполняется для столбцов [13] Таблицы 3 и [14] Таблицы 4</div>
                                </div>
                            </div>

                            <div class="row align-items-start">
                                {{-- ПОСТАВЩИК --}}
                                <div class="col-md-5 mb-3">
                                    <label class="form-label fw-medium">Поставщик <span class="text-danger">*</span></label>
                                    <div class="counterparty-widget" id="provider-widget">
                                        <div class="input-group">
                                            <input type="text" class="form-control cp-search" id="provider-search"
                                                placeholder="Введите название или ИНН..." autocomplete="off">
                                            <button class="btn btn-outline-secondary cp-add-btn" type="button"
                                                data-target="provider" title="Добавить в справочник">
                                                <i class="bi bi-person-plus"></i>
                                            </button>
                                        </div>
                                        <div class="list-group position-absolute shadow cp-results" id="provider-results"
                                            style="display:none; z-index:1050; width:100%; max-height:220px; overflow-y:auto;"></div>
                                        <input type="hidden" name="provider" id="provider-value" required>
                                        <input type="hidden" name="provider_snapshot" id="provider-snapshot">
                                        <div class="cp-details card mt-2 border-0 bg-light p-2" id="provider-details" style="display:none; font-size:0.85rem;">
                                            <div class="row g-1">
                                                <div class="col-6"><span class="text-muted">ИНН:</span> <span class="fw-medium cp-inn"></span></div>
                                                <div class="col-6"><span class="text-muted">КПП:</span> <span class="fw-medium cp-kpp"></span></div>
                                                <div class="col-12"><span class="text-muted">Адрес:</span> <span class="cp-addr"></span></div>
                                                <div class="col-8"><span class="text-muted">Лицензия:</span> <span class="cp-lic"></span></div>
                                                <div class="col-4"><span class="text-muted">до</span> <span class="cp-lic-date"></span></div>
                                            </div>
                                        </div>
                                        <div class="inn-error text-danger small mt-1" style="display:none;"></div>
                                    </div>
                                </div>

                                {{-- КНОПКА SWAP --}}
                                <div class="col-md-2 mb-3 d-flex justify-content-center align-items-center" style="padding-top:28px;">
                                    <button type="button" class="btn btn-outline-secondary text-nowrap" onclick="swapCompanies()" title="Поменять местами">
                                        <i class="bi bi-arrow-left-right d-none d-md-inline-block"></i>
                                        <i class="bi bi-arrow-down-up d-inline-block d-md-none"></i>
                                    </button>
                                </div>

                                {{-- ПОЛУЧАТЕЛЬ --}}
                                <div class="col-md-5 mb-3">
                                    <label class="form-label fw-medium">Получатель <span class="text-danger">*</span></label>
                                    <div class="counterparty-widget" id="receiver-widget">
                                        <div class="input-group">
                                            <input type="text" class="form-control cp-search" id="receiver-search"
                                                placeholder="Введите название или ИНН..." autocomplete="off">
                                            <button class="btn btn-outline-secondary cp-add-btn" type="button"
                                                data-target="receiver" title="Добавить в справочник">
                                                <i class="bi bi-person-plus"></i>
                                            </button>
                                        </div>
                                        <div class="list-group position-absolute shadow cp-results" id="receiver-results"
                                            style="display:none; z-index:1050; width:100%; max-height:220px; overflow-y:auto;"></div>
                                        <input type="hidden" name="receiver" id="receiver-value" required>
                                        <input type="hidden" name="receiver_snapshot" id="receiver-snapshot">
                                        <div class="cp-details card mt-2 border-0 bg-light p-2" id="receiver-details" style="display:none; font-size:0.85rem;">
                                            <div class="row g-1">
                                                <div class="col-6"><span class="text-muted">ИНН:</span> <span class="fw-medium cp-inn"></span></div>
                                                <div class="col-6"><span class="text-muted">КПП:</span> <span class="fw-medium cp-kpp"></span></div>
                                                <div class="col-12"><span class="text-muted">Адрес:</span> <span class="cp-addr"></span></div>
                                                <div class="col-8"><span class="text-muted">Лицензия:</span> <span class="cp-lic"></span></div>
                                                <div class="col-4"><span class="text-muted">до</span> <span class="cp-lic-date"></span></div>
                                            </div>
                                        </div>
                                        <div class="inn-error text-danger small mt-1" style="display:none;"></div>
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
                                    <label class="form-label">Поиск отхода</label>
                                    <input type="text" id="waste-search" class="form-control" autocomplete="off">
                                    <div id="waste-results" class="list-group position-absolute w-100 shadow-sm" style="display:none; z-index:1000; max-height:250px; overflow-y:auto;"></div>
                                </div>

                                <div id="selected-waste-display" class="alert alert-light border mb-3 d-none">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="fw-bold" id="display-name"></div>
                                            <div class="small">Код: <span id="display-fkko"></span> | Класс: <span id="display-hazard"></span></div>
                                        </div>
                                        <div class="col-auto">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearWasteSelection()">Изменить</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Количество (тонн)</label>
                                        <input type="text" id="temp-amount" class="form-control" placeholder="0.000">
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
                                                        <input class="form-check-input" type="checkbox" id="{{ $opId }}" value="{{ $opLabel }}">
                                                        <label class="form-check-label small" for="{{ $opId }}">{{ $opLabel }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-primary" onclick="addSelectedWaste()">Добавить в список</button>
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

    <div class="modal fade" id="addCpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Добавить контрагента в справочник</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cp-modal-error" class="alert alert-danger d-none"></div>
                    <div class="mb-3">
                        <label class="form-label">Наименование <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cp-modal-name">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ИНН</label>
                            <input type="text" class="form-control" id="cp-modal-inn" maxlength="12">
                            <div class="form-text text-danger" id="cp-modal-inn-err" style="display:none;">Неверный ИНН</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">КПП</label>
                            <input type="text" class="form-control" id="cp-modal-kpp" maxlength="9">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Юр. адрес</label>
                        <input type="text" class="form-control" id="cp-modal-addr">
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Лицензия</label>
                            <input type="text" class="form-control" id="cp-modal-lic">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Срок действия</label>
                            <input type="date" class="form-control" id="cp-modal-lic-date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-dark" id="cp-modal-save">Сохранить</button>
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

    function validateInn(inn) {
        inn = inn.toString().replace(/\D/g, '');
        if (inn.length > 9 && inn.length < 13) {
            return true
        }
        return false
    }

    function updateCpDetails(type, data) {
        const widget = document.getElementById(type + '-widget');
        const details = widget.querySelector('.cp-details');
        const searchInput = widget.querySelector('.cp-search');

        if (!data) {
            details.style.display = 'none';
            searchInput.value = '';
            document.getElementById(type + '-value').value = '';
            document.getElementById(type + '-snapshot').value = '';
            return;
        }

        searchInput.value = data.name || '';
        document.getElementById(type + '-value').value = data.name || '';

        details.querySelector('.cp-inn').textContent = data.inn || '—';
        details.querySelector('.cp-kpp').textContent = data.kpp || '—';
        details.querySelector('.cp-addr').textContent = data.legal_address || data.address || '—';
        details.querySelector('.cp-lic').textContent = data.license_number || data.license || '—';

        let dateStr = '—';
        const rawDate = data.license_valid_until || data.license_date;
        if (rawDate) {
            const d = new Date(rawDate);
            if (!isNaN(d)) dateStr = d.toLocaleDateString('ru-RU');
        }
        details.querySelector('.cp-lic-date').textContent = dateStr;
        details.style.display = 'block';

        const snapshot = {
            name: data.name,
            inn: data.inn,
            kpp: data.kpp,
            legal_address: data.legal_address || data.address,
            license_number: data.license_number || data.license,
            license_valid_until: rawDate
        };
        document.getElementById(type + '-snapshot').value = JSON.stringify(snapshot);
    }

    function swapCompanies() {
        const pSnap = document.getElementById('provider-snapshot').value;
        const rSnap = document.getElementById('receiver-snapshot').value;
        let pData = null;
        let rData = null;
        try { if(pSnap) pData = JSON.parse(pSnap); } catch(e){}
        try { if(rSnap) rData = JSON.parse(rSnap); } catch(e){}
        
        updateCpDetails('provider', rData);
        updateCpDetails('receiver', pData);
    }

    function clearWasteSelection() {
        document.getElementById('waste-search').value = '';
        currentWasteData = null;
        document.getElementById('selected-waste-display').classList.add('d-none');
        document.getElementById('waste-search').focus();
    }

    function addWasteItem() {
        document.getElementById('waste-search-section').style.display = 'block';
        if (typeof handleRole === 'function') handleRole();
        document.getElementById('waste-search').focus();
    }

    function addSelectedWaste() {
        if (!currentWasteData) return alert('Выберите отход');
        const amount = document.getElementById('temp-amount').value.trim().replace(',', '.');
        if (!amount || isNaN(amount)) return alert('Укажите количество');

        const ops = [];
        document.querySelectorAll('[id^="temp-op"]:checked').forEach(cb => ops.push(cb.value));
        if (ops.length === 0) return alert('Выберите вид обращения');

        wasteItems.push({
            id: ++wasteItemCounter,
            name: currentWasteData.name,
            fkko_code: currentWasteData.code,
            hazard_class: currentWasteData.hazard_class,
            amount: parseFloat(amount),
            operation_types: ops
        });
        renderWasteItems();
        resetWasteForm();
    }

    function removeWasteItem(id) {
        wasteItems = wasteItems.filter(i => i.id !== id);
        renderWasteItems();
    }

    function renderWasteItems() {
        const container = document.getElementById('waste-items-container');
        if (wasteItems.length === 0) {
            container.innerHTML = '<div class="alert alert-info">Отходы не добавлены.</div>';
            return;
        }
        container.innerHTML = wasteItems.map((item, idx) => `
            <div class="card mb-2 border-0 shadow-sm"><div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold small">${idx+1}. ${item.name}</div>
                        <div class="text-muted" style="font-size:0.75rem;">${item.fkko_code} | ${item.amount} т | ${item.operation_types.join(', ')}</div>
                        <input type="hidden" name="wastes[${idx}][name]" value="${item.name}">
                        <input type="hidden" name="wastes[${idx}][fkko_code]" value="${item.fkko_code}">
                        <input type="hidden" name="wastes[${idx}][hazard_class]" value="${item.hazard_class}">
                        <input type="hidden" name="wastes[${idx}][amount]" value="${item.amount}">
                        <input type="hidden" name="wastes[${idx}][operation_types]" value="${item.operation_types.join(', ')}">
                    </div>
                    <button type="button" class="btn btn-sm text-danger border-0" onclick="removeWasteItem(${item.id})"><i class="bi bi-trash"></i></button>
                </div>
            </div></div>
        `).join('');
    }

    function resetWasteForm() {
        document.getElementById('waste-search').value = '';
        document.getElementById('temp-amount').value = '';
        document.querySelectorAll('[id^="temp-op"]').forEach(cb => cb.checked = false);
        currentWasteData = null;
        document.getElementById('selected-waste-display').classList.add('d-none');
        document.getElementById('waste-search-section').style.display = 'none';
        if (typeof handleRole === 'function') handleRole();
    }

    document.addEventListener('DOMContentLoaded', function () {
        renderWasteItems();

        ['provider', 'receiver'].forEach(type => {
            const input = document.getElementById(type + '-search');
            const res = document.getElementById(type + '-results');
            let t = null;
            input.addEventListener('input', () => {
                clearTimeout(t);
                const q = input.value.trim();
                if (q.length < 2) return res.style.display = 'none';
                t = setTimeout(() => {
                    fetch('/counterparties/search?q=' + encodeURIComponent(q))
                        .then(r => r.json()).then(data => {
                            res.innerHTML = data.map(cp => `
                                <a href="#" class="list-group-item list-group-item-action py-1 small" onclick="event.preventDefault(); window.selCP('${type}', ${JSON.stringify(cp).replace(/"/g, '&quot;')})">
                                    ${cp.name} <span class="badge bg-light text-dark border float-end">${cp.inn || ''}</span>
                                </a>
                            `).join('');
                            res.style.display = data.length ? 'block' : 'none';
                        });
                }, 300);
            });
            input.addEventListener('blur', () => setTimeout(() => res.style.display = 'none', 200));
        });
        window.selCP = (type, cp) => { updateCpDetails(type, cp); document.getElementById(type + '-results').style.display = 'none'; };

        const cpModal = new bootstrap.Modal(document.getElementById('addCpModal'));
        let targetCP = '';
        document.querySelectorAll('.cp-add-btn').forEach(b => b.onclick = () => {
            targetCP = b.dataset.target;
            document.getElementById('cp-modal-name').value = document.getElementById(targetCP + '-search').value;
            cpModal.show();
        });

        document.getElementById('cp-modal-save').onclick = () => {
            const name = document.getElementById('cp-modal-name').value.trim();
            const inn = document.getElementById('cp-modal-inn').value.trim();
            if (!name) return alert('Имя обязательно');
            if (inn && !validateInn(inn)) return alert('Неверный ИНН');

            fetch('/counterparties', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    name, inn, kpp: document.getElementById('cp-modal-kpp').value,
                    legal_address: document.getElementById('cp-modal-addr').value,
                    license_number: document.getElementById('cp-modal-lic').value,
                    license_valid_until: document.getElementById('cp-modal-lic-date').value
                })
            }).then(r => r.json()).then(res => {
                if (res.error) alert(res.error);
                else { updateCpDetails(targetCP, res); cpModal.hide(); }
            });
        };

        const tempOp8 = document.getElementById('temp-op8');
        window.handleRole = function() {
            const actTypeInput = document.querySelector('input[name="act_type"]');
            if (!actTypeInput) return;
            
            const compEl = document.getElementById('current-company-info');
            if (!compEl) return;
            const comp = JSON.parse(compEl.value);

            const getInn = (type) => {
                const snap = document.getElementById(type + '-snapshot').value;
                if (!snap) return '';
                try { return JSON.parse(snap).inn || ''; } catch(e) { return ''; }
            };

            const cInn = String(comp.inn || '').trim();
            const pInn = String(getInn('provider')).trim();
            const rInn = String(getInn('receiver')).trim();
            const tOp8 = document.getElementById('temp-op8');
            const isChecked = tOp8 && tOp8.checked;

            if (isChecked) {
                // Если галочка нажата: мы должны быть Поставщиком во всех видах актов
                if (pInn !== cInn) {
                    swapCompanies();
                    if (String(getInn('provider')).trim() !== cInn) {
                        updateCpDetails('provider', comp);
                        if (String(getInn('receiver')).trim() === cInn) updateCpDetails('receiver', null);
                    }
                }
            } else {
                // Если галочка НЕ нажата: мы должны быть Получателем во всех видах актов
                if (rInn !== cInn) {
                    swapCompanies();
                    if (String(getInn('receiver')).trim() !== cInn) {
                        updateCpDetails('receiver', comp);
                        if (String(getInn('provider')).trim() === cInn) updateCpDetails('provider', null);
                    }
                }
            }
        };

        if (tempOp8) {
            tempOp8.onchange = function() {
                window.handleRole();
            };
        }
        window.handleRole();

        const wSearch = document.getElementById('waste-search');
        const wRes = document.getElementById('waste-results');
        let wt = null;
        wSearch.addEventListener('input', () => {
            clearTimeout(wt);
            const q = wSearch.value.trim();
            if (q.length < 2) return wRes.style.display = 'none';
            wt = setTimeout(() => {
                fetch('/fkko/search?q=' + encodeURIComponent(q)).then(r => r.json()).then(data => {
                    wRes.innerHTML = data.map(i => `<a href="#" class="list-group-item list-group-item-action py-1 small" onclick="event.preventDefault(); window.selW(${JSON.stringify(i).replace(/"/g, '&quot;')})">${i.name} <span class="badge bg-primary float-end">${i.code}</span></a>`).join('');
                    wRes.style.display = data.length ? 'block' : 'none';
                });
            }, 300);
        });
        window.selW = (i) => {
            currentWasteData = { name: i.name, code: i.code, hazard_class: i.hazard_class };
            document.getElementById('display-name').textContent = i.name;
            document.getElementById('display-fkko').textContent = i.code;
            document.getElementById('display-hazard').textContent = i.hazard_class;
            document.getElementById('selected-waste-display').classList.remove('d-none');
            wSearch.value = i.name;
            wRes.style.display = 'none';
        };
    });
</script>
@endpush
