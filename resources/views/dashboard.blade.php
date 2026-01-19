@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Загрузка Актов</h4>
        <div class="d-flex">
            <div class="dropdown me-2">
                <button class="btn btn-outline-black dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    {{ isset($company) ? $company->name : 'Выберите компанию' }}
                </button>
                <ul class="dropdown-menu">
                    @if(isset($userCompanies) && $userCompanies->count() > 0)
                        @foreach($userCompanies as $uComp)
                            <li>
                                <a class="dropdown-item {{ (isset($company) && $company->id === $uComp->id) ? 'active' : '' }}"
                                    href="#"
                                    onclick="event.preventDefault(); document.getElementById('switch-company-{{ $uComp->id }}').submit();">
                                    {{ $uComp->name }}
                                </a>
                                <form id="switch-company-{{ $uComp->id }}" action="{{ route('companies.switch', $uComp->id) }}"
                                    method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        @endforeach
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                    @endif
                    <li><a class="dropdown-item" href="{{ route('companies.create') }}"><i
                                class="bi bi-plus-lg me-1"></i>Добавить новую</a></li>
                </ul>
            </div>

            <div class="dropdown">
                <button class="btn btn-outline-black dropdown-toggle d-flex align-items-center gap-2 px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="fw-medium">{{ $selectedPeriod === 'all' ? 'За все время' : ($periods[$selectedPeriod] ?? $selectedPeriod) }}</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow p-3" style="min-width: 700px; background-color: #fff; border: 1px solid #dee2e6;">
                    <!-- All Time -->
                    <a class="btn w-100 mb-3 fw-bold {{ $selectedPeriod === 'all' ? 'btn-dark text-white' : 'btn-light text-dark' }}" 
                       style="{{ $selectedPeriod === 'all' ? 'background-color: #000218; border-color: #000218;' : 'background-color: #f8f9fa;' }}"
                       href="{{ route('dashboard', ['period' => 'all']) }}">
                        За все время
                    </a>

                    <div class="row g-3">
                        <!-- Years -->
                        <div class="col-3 border-end">
                            <h6 class="dropdown-header px-0 text-uppercase text-secondary fw-bold small mb-2">
                                Годы
                            </h6>
                            <div class="d-grid gap-1">
                                @foreach($periods as $key => $label)
                                    @if(strlen((string)$key) === 4 && is_numeric($key))
                                        <a class="btn btn-sm text-start {{ $selectedPeriod === (string)$key ? 'btn-dark text-white' : 'btn-light text-dark bg-transparent' }}" 
                                           style="{{ $selectedPeriod === (string)$key ? 'background-color: #000218; border-color: #000218;' : '' }}"
                                           href="{{ route('dashboard', ['period' => $key]) }}">
                                           {{ $label }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <!-- Quarters -->
                        <div class="col-4 border-end">
                            <h6 class="dropdown-header px-0 text-uppercase text-secondary fw-bold small mb-2">
                                Кварталы
                            </h6>
                            <div class="d-grid gap-1">
                                @foreach($periods as $key => $label)
                                    @if(str_contains($key, '-Q'))
                                        <a class="btn btn-sm text-start {{ $selectedPeriod === (string)$key ? 'btn-dark text-white' : 'btn-light text-dark bg-transparent' }}" 
                                           style="{{ $selectedPeriod === (string)$key ? 'background-color: #000218; border-color: #000218;' : '' }}"
                                           href="{{ route('dashboard', ['period' => $key]) }}">
                                            {{ $label }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <!-- Months -->
                        <div class="col-5">
                            <h6 class="dropdown-header px-0 text-uppercase text-secondary fw-bold small mb-2">
                                Месяцы
                            </h6>
                            <div class="row g-1">
                                @foreach($periods as $key => $label)
                                    @if(strlen((string)$key) === 7 && str_contains($key, '-') && !str_contains($key, 'Q'))
                                        <div class="col-6">
                                            <a class="btn btn-sm w-100 text-start text-truncate {{ $selectedPeriod === (string)$key ? 'btn-dark text-white' : 'btn-light text-dark bg-transparent' }}" 
                                               style="{{ $selectedPeriod === (string)$key ? 'background-color: #000218; border-color: #000218;' : '' }}"
                                               href="{{ route('dashboard', ['period' => $key]) }}" title="{{ $label }}">
                                                {{ $label }}
                                            </a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Drag & Drop Zone -->
    <!-- Drag & Drop Zone -->
    @if(isset($company))
        <div class="card border-dashed mb-4" id="drop-zone"
            style="border: 2px dashed #ccc; background-color: #f9f9f9; transition: background 0.3s;">
            <div class="card-body text-center py-5">
                <i class="bi bi-cloud-upload display-4 text-muted mb-3"></i>
                <h5 class="text-muted">Перетащите Акты (doc, docx) сюда</h5>
                <p class="small text-muted mb-3">или нажмите кнопку для выбора файлов</p>
                <button class="btn btn-primary" onclick="$('#file-input').click()">Загрузить Акты</button>
                <input type="file" id="file-input" class="d-none" multiple accept=".doc,.docx">
                <div id="file-list" class="mt-3"></div>
            </div>
        </div>
    @else
        <div class="alert text-center py-5 mb-4 shadow-sm" style="background-color: #212529; color: #fff; border: none;">
            <h4 class="alert-heading fw-bold">Внимание!</h4>
            <p class="lead mb-0">Выберите компанию чтобы продолжить</p>
        </div>
    @endif

    <!-- Tables Tabs -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#table1">1. Состав отходов</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#table2">2. Обобщенные данные</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#table3">3. Переданные</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#table4">4. Полученные</button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content pt-3">
                <!-- Table 1: Состав отходов -->
                <div class="tab-pane fade show active" id="table1">
                    @include('partials.dashboard_table1')
                </div>

                <!-- Table 2: Обобщенные данные -->
                <div class="tab-pane fade" id="table2">
                    @include('partials.dashboard_table2')
                </div>

                <!-- Table 3: Переданные -->
                <div class="tab-pane fade" id="table3">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Номер акта</th>
                                <th>Контрагент (Получатель)</th>
                                <th>Отход</th>
                                <th>Количество (т)</th>
                                <th style="width: 100px;">Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transferred as $item)
                                <tr data-act-id="{{ $item['id'] }}" data-item-index="{{ $item['item_index'] }}">
                                    <td class="editable-cell" data-field="date" data-value="{{ $item['date'] }}">
                                        {{ rescue(fn() => \Carbon\Carbon::parse($item['date'])->format('d.m.Y'), $item['date'], false) }}
                                    </td>
                                    <td class="editable-cell" data-field="number" data-value="{{ $item['number'] }}">
                                        {{ $item['number'] }}</td>
                                    <td class="editable-cell" data-field="{{ $item['counterparty_field'] }}"
                                        data-value="{{ $item['counterparty'] }}">{{ $item['counterparty'] }}</td>
                                    <td class="editable-cell" data-field="name" data-type="select"
                                        data-value="{{ $item['waste'] }}">{{ $item['waste'] }}</td>
                                    <td class="editable-cell" data-field="quantity" data-value="{{ $item['amount'] }}">
                                        <strong>{{ number_format($item['amount'], 3) }}</strong>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-dark btn-delete-act" style="background-color: #1f1f1f; border-color: #1f1f1f;">Удалить</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Нет данных о передаче</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Table 4: Полученные -->
                <div class="tab-pane fade" id="table4">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Номер акта</th>
                                <th>Контрагент (Поставщик)</th>
                                <th>Отход</th>
                                <th>Количество (т)</th>
                                <th style="width: 100px;">Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($received as $item)
                                <tr data-act-id="{{ $item['id'] }}" data-item-index="{{ $item['item_index'] }}">
                                    <td class="editable-cell" data-field="date" data-value="{{ $item['date'] }}">
                                        {{ rescue(fn() => \Carbon\Carbon::parse($item['date'])->format('d.m.Y'), $item['date'], false) }}
                                    </td>
                                    <td class="editable-cell" data-field="number" data-value="{{ $item['number'] }}">
                                        {{ $item['number'] }}</td>
                                    <td class="editable-cell" data-field="{{ $item['counterparty_field'] }}"
                                        data-value="{{ $item['counterparty'] }}">{{ $item['counterparty'] }}</td>
                                    <td class="editable-cell" data-field="name" data-type="select"
                                        data-value="{{ $item['waste'] }}">{{ $item['waste'] }}</td>
                                    <td class="editable-cell" data-field="quantity" data-value="{{ $item['amount'] }}">
                                        <strong>{{ number_format($item['amount'], 3) }}</strong>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-dark btn-delete-act" style="background-color: #1f1f1f; border-color: #1f1f1f;">Удалить</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Нет данных о получении</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Inject Styles
            $('<style>').prop('type', 'text/css').html(`
                .editable-cell {
                    position: relative;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }
                .editable-cell:hover {
                    background-color: #ffffcc; /* Light yellow hover */
                }
                .editable-cell::after {
                    content: '\\270E'; /* Pencil */
                    position: absolute;
                    right: 5px;
                    top: 50%;
                    transform: translateY(-50%);
                    font-size: 0.8rem;
                    color: #ccc;
                    opacity: 0;
                    transition: opacity 0.2s;
                }
                .editable-cell:hover::after {
                    opacity: 1;
                }
            `).appendTo('head');
                const dropZone = $('#drop-zone');
                const wasteOptions = @json($wasteList ?? []);

                dropZone.on('dragover', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).css('background-color', '#e9ecef');
                });

                dropZone.on('dragleave', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).css('background-color', '#f9f9f9');
                });

                dropZone.on('drop', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).css('background-color', '#f9f9f9');

                    const files = e.originalEvent.dataTransfer.files;
                    handleFiles(files);
                });

                $('#file-input').on('change', function () {
                    handleFiles(this.files);
                });

                function handleFiles(files) {
                    if (files.length > 0) {
                        const formData = new FormData();
                        // Append files
                        for (let i = 0; i < files.length; i++) {
                            formData.append('files[]', files[i]);
                        }

                        $('#file-list').html('<div class="alert alert-info py-2"><i class="spinner-border spinner-border-sm me-2"></i>Обработка и распознавание ' + files.length + ' файлов...</div>');

                        $.ajax({
                            url: '{{ route('acts.store') }}',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                if (response.processed.length > 0) {
                                    // Reload to update tables
                                    location.reload();
                                } else {
                                    // Show errors if only errors occurred
                                    let html = '';
                                    response.errors.forEach(err => {
                                        html += '<div class="alert alert-danger py-2 mb-1"><i class="bi bi-exclamation-triangle me-2"></i>' + err + '</div>';
                                    });
                                    $('#file-list').html(html);
                                }
                            },
                            error: function (xhr) {
                                const msg = xhr.responseJSON?.message || 'Ошибка загрузки';
                                $('#file-list').html('<div class="alert alert-danger py-2">' + msg + '</div>');
                            }
                        });
                    }
                }

                // --- Deletion Logic ---
        // Helper to refresh T1 and T2
        function refreshTables() {
            $.ajax({
                url: window.location.href, // Current URL with params
                method: 'GET',
                data: { refresh_tables: 1 },
                success: function(response) {
                    if(response.table1_html) {
                        $('#table1').html(response.table1_html);
                    }
                    if(response.table2_html) {
                        $('#table2').html(response.table2_html);
                    }
                }
            });
        }

        // Delete Act Item
        $(document).on('click', '.btn-delete-act', function() {
            if (!confirm('Вы уверены, что хотите удалить эту запись?')) return;
            const btn = $(this);
            const tr = btn.closest('tr');
            const actId = tr.data('act-id');
            const itemIndex = tr.data('item-index');
            
            $.ajax({
                url: '/acts/' + actId + '/item/' + itemIndex,
                method: 'POST', // Laravel uses POST w/ _method=DELETE usually, or just DELETE if supported
                data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                success: function() {
                    tr.fadeOut(300, function() { $(this).remove(); });
                    refreshTables();
                },
                error: function(xhr) {
                    alert('Ошибка удаления: ' + (xhr.responseJSON?.message || 'Unknown error'));
                }
            });
        });

        // Inline Editing
        // wasteOptions is already defined above

        $(document).on('click', '.editable-cell', function() {
            const td = $(this);
            if (td.find('input, select').length) return; // Already editing

            const currentVal = td.data('value');
            const field = td.data('field');
            const type = td.data('type') || 'text';
            const actId = td.closest('tr').data('act-id');
            const itemIndex = td.closest('tr').data('item-index');
            const originalHtml = td.html();

            let inputHtml = '';
            if (type === 'select' && field === 'name') {
                inputHtml = '<select class="form-select form-select-sm editor-input">';
                // Add current value if not in list (optional, but good practice)
                // wasteOptions is correct
                wasteOptions.forEach(opt => {
                    const selected = opt === currentVal ? 'selected' : '';
                    inputHtml += `<option value="${opt}" ${selected}>${opt}</option>`;
                });
                inputHtml += '</select>';
            } else if (field === 'date') {
                // Ensure YYYY-MM-DD format for input
                inputHtml = `<input type="date" class="form-control form-control-sm editor-input" value="${currentVal}">`;
            } else {
                inputHtml = `<input type="text" class="form-control form-control-sm editor-input" value="${currentVal}">`;
            }

            td.html(inputHtml);
            const input = td.find('.editor-input');
            input.focus();

            // Save on Blur or Enter
            input.on('blur keypress', function(e) {
                if (e.type === 'keypress' && e.which !== 13) return;
                
                const newVal = $(this).val();
                
                // If unchanged, revert
                if (newVal == currentVal) {
                    td.html(originalHtml);
                    return;
                }

                $.ajax({
                    url: '/acts/' + actId,
                    method: 'POST',
                    data: {
                        _method: 'PUT',
                        _token: '{{ csrf_token() }}',
                        field: field,
                        value: newVal,
                        item_index: itemIndex
                    },
                    success: function (response) {
                        // Handle Split (if Act was split into a new one)
                        if (response.split) {
                            const tr = td.closest('tr');
                            tr.data('act-id', response.new_act_id);
                            tr.data('item-index', response.new_item_index);
                            // Also update the attribute in DOM for future selectors
                            tr.attr('data-act-id', response.new_act_id);
                            tr.attr('data-item-index', response.new_item_index);
                        }

                        // Always update only THIS cell's display
                        // We do not sync other rows because:
                        // 1. If split, this is now a unique act.
                        // 2. If not split (single item), there are no other rows to sync.
                        
                        td.data('value', newVal);

                        // Formatting for display
                        if (field === 'date') {
                            const d = new Date(newVal);
                            const day = String(d.getDate()).padStart(2, '0');
                            const month = String(d.getMonth() + 1).padStart(2, '0');
                            const year = d.getFullYear();
                            td.html(`${day}.${month}.${year}`);
                        } else if (field === 'quantity') {
                            td.html('<strong>' + parseFloat(newVal).toFixed(3) + '</strong>');
                        } else {
                            td.html(newVal);
                        }
                        
                        // Visual feedback
                        td.addClass('bg-success text-white');
                        setTimeout(() => td.removeClass('bg-success text-white'), 1000);
                        
                        refreshTables();
                    },
                    error: function (xhr) {
                        alert('Ошибка сохранения: ' + (xhr.responseJSON?.message || 'Unknown error'));
                        td.html(originalHtml); // Revert on error
                    }
                });
            });
        });
            });
    </script>
@endpush