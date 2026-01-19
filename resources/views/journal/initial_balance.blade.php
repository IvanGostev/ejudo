@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Ввод начальных остатков</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Для формирования первого журнала необходимо указать остатки отходов на начало периода.
                            Если остатков нет, просто нажмите "Сохранить", оставив список пустым.
                        </p>

                        <form action="{{ route('journal.initial-balance.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="period" value="{{ $period }}">

                            <div id="wastes-container">
                                <!-- Existing rows will be added here -->
                            </div>

                            <div class="d-grid gap-2 mb-4">
                                <button type="button" class="btn btn-outline-primary" id="add-waste-btn">
                                    <i class="bi bi-plus-circle me-2"></i>Добавить отход
                                </button>
                            </div>

                            <div class="d-flex justify-content-end border-top pt-3">
                                <a href="{{ route('journal.index') }}" class="btn btn-light me-2">Отмена</a>
                                <button type="submit" class="btn btn-dark me-2"
                                    onclick="clearWastesAndSubmit(event)">Продолжить без ввода остатков</button>
                                <button type="submit" class="btn btn-primary px-4">Сохранить и продолжить</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template id="waste-row-template">
        <div class="waste-row card mb-3 bg-light border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title mb-0 fw-bold text-muted">Отход #<span class="row-number"></span></h6>
                    <button type="button" class="btn btn-sm btn-link text-danger remove-row p-0" title="Удалить">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="row g-3">
                    <div class="col-md-9">
                        <label class="form-label small text-muted">Поиск отхода (ФККО)</label>
                        <div class="position-relative">
                            <input type="text" class="form-control waste-search"
                                placeholder="Начните вводить название или код..." autocomplete="off">
                            <input type="hidden" name="wastes[INDEX][name]" class="waste-name-input">
                            <input type="hidden" name="wastes[INDEX][fkko]" class="fkko-input">
                            <input type="hidden" name="wastes[INDEX][hazard]" class="hazard-input">

                            <div class="list-group position-absolute w-100 shadow-sm waste-results"
                                style="display:none; z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                        </div>
                        <div class="selected-waste-info mt-2 small text-muted" style="display:none;">
                            <span class="fw-bold text-dark display-name"></span><br>
                            <span class="me-3">Код: <span class="fw-bold display-fkko"></span></span>
                            <span>Класс: <span class="fw-bold display-hazard"></span></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Остаток (т)</label>
                        <input type="number" step="0.001" name="wastes[INDEX][amount]" class="form-control text-end fw-bold"
                            placeholder="0.000" required>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('wastes-container');
            const template = document.getElementById('waste-row-template');
            const addBtn = document.getElementById('add-waste-btn');
            let rowCount = 0;

            function addRow() {
                rowCount++;
                const clone = template.content.cloneNode(true);
                const row = clone.querySelector('.waste-row');

                row.innerHTML = row.innerHTML.replace(/INDEX/g, rowCount);
                row.querySelector('.row-number').textContent = rowCount;

                // Remove Handler
                row.querySelector('.remove-row').addEventListener('click', function () {
                    row.remove();
                });

                // Search Logic
                const input = row.querySelector('.waste-search');
                const results = row.querySelector('.waste-results');

                const hiddenName = row.querySelector('.waste-name-input');
                const hiddenFkko = row.querySelector('.fkko-input');
                const hiddenHazard = row.querySelector('.hazard-input');

                const infoBlock = row.querySelector('.selected-waste-info');
                const dispName = row.querySelector('.display-name');
                const dispFkko = row.querySelector('.display-fkko');
                const dispHazard = row.querySelector('.display-hazard');

                let timeout = null;

                input.addEventListener('input', function () {
                    clearTimeout(timeout);
                    const query = this.value.trim();

                    // Force selection: clear hidden values whenever user types
                    hiddenName.value = '';
                    hiddenFkko.value = '';
                    hiddenHazard.value = '';
                    infoBlock.style.display = 'none';

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
                                                            <div class="d-flex justify-content-between">
                                                                <span class="fw-medium text-wrap" style="font-size:0.9em;">${item.name}</span>
                                                                <span class="badge bg-secondary ms-2 align-self-start">${item.code}</span>
                                                            </div>
                                                        `;
                                        a.onclick = (e) => {
                                            e.preventDefault();

                                            // Update Hidden Inputs
                                            hiddenName.value = item.name;
                                            hiddenFkko.value = item.code;
                                            hiddenHazard.value = item.hazard_class;

                                            // Update Display Info
                                            dispName.textContent = item.name;
                                            dispFkko.textContent = item.code;
                                            dispHazard.textContent = item.hazard_class;
                                            infoBlock.style.display = 'block';

                                            // Clear Search Input or keep it? 
                                            // Maybe keep it as "Search/Edit"? 
                                            // Let's hide results.
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

                // Validate on blur: if hidden ID is empty, clear visible input
                input.addEventListener('blur', function () {
                    // Small delay to allow click event on result to fire first
                    setTimeout(() => {
                        if (hiddenName.value === '') {
                            input.value = '';
                        }
                    }, 200);
                });

                // Close dropdown on click outside
                document.addEventListener('click', function (e) {
                    if (!input.contains(e.target) && !results.contains(e.target)) {
                        results.style.display = 'none';
                    }
                });

                container.appendChild(row);
            }

            addBtn.addEventListener('click', addRow);

            // Add first row by default
            addRow();

            window.clearWastesAndSubmit = function (e) {
                // Prevent default submission to modify DOM first if needed, or if we use formatAction
                // Actually, simply clearing the container is enough
                const container = document.getElementById('wastes-container');
                container.innerHTML = '';
                // Since this button is type="submit" inside the form, it will submit after this click handler unless we preventDefault.
                // We want it to submit.
                return true;
            };
        });
    </script>
@endsection