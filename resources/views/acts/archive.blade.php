@extends('layouts.app')

@push('styles')
    <style>
        .act-row { cursor: pointer; transition: background-color 0.2s; }
        .act-row:hover { background-color: #f8f9fa; }
        .expanded-content { display: none; background-color: #f8f9fa; }
        .expanded-content.show { display: table-row; }
        .editable-field {
            border: 1px solid transparent;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .editable-field:hover { border-color: #dee2e6; background-color: #fff; }
        .editable-field:focus { outline: none; border-color: #FF4C2B; background-color: #fff; }
        .btn-open { background-color: #FF4C2B; border-color: #FF4C2B; color: white; }
        .btn-open:hover { background-color: #e04326; border-color: #d63f24; color: white; }
        .nav-tabs .nav-link { color: #6c757d; }
        .nav-tabs .nav-link.active { color: #FF4C2B; border-color: #dee2e6 #dee2e6 #fff; }
        .badge-act-type { font-size: 0.75rem; }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Реестр актов</h2>
            {{-- Кнопки добавления всех видов актов (п.6) --}}
            <div class="d-flex flex-wrap gap-2">
                @foreach(\App\Models\Act::TYPES as $typeKey => $typeLabel)
                    <a href="{{ route('acts.manual.create', ['act_type' => $typeKey]) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> {{ $typeLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Фильтры (пп. 10, 11) --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('acts.archive') }}" class="row g-2 align-items-end" id="filter-form">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Тип акта</label>
                        <select name="act_type" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Все типы</option>
                            @foreach(\App\Models\Act::TYPES as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" {{ request('act_type') === $typeKey ? 'selected' : '' }}>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Год</label>
                        <select name="period_year" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Все годы</option>
                            @foreach($availableYears as $yr)
                                <option value="{{ $yr }}" {{ request('period_year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Квартал</label>
                        <select name="period_quarter" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">—</option>
                            @for($q = 1; $q <= 4; $q++)
                                <option value="{{ $q }}" {{ request('period_quarter') == $q ? 'selected' : '' }}>{{ $q }} квартал</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Месяц</label>
                        <select name="period_month" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">—</option>
                            @php $months = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь']; @endphp
                            @foreach($months as $mi => $mn)
                                <option value="{{ $mi+1 }}" {{ request('period_month') == $mi+1 ? 'selected' : '' }}>{{ $mn }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <a href="{{ route('acts.archive') }}" class="btn btn-outline-secondary btn-sm">Сбросить</a>
                    </div>
                </form>
            </div>
        </div>


        @if($acts->isEmpty())
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> У вас пока нет обработанных актов.
            </div>
        @else
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    {{-- Порядок столбцов: № акта первым --}}
                                    <th>№ акта</th>
                                    <th>Дата</th>
                                    <th>Поставщик</th>
                                    <th>Договор</th>
                                    <th>Тип акта</th>
                                    <th>Получатель</th>
                                    <th width="220">Действие</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($acts as $act)
                                    @php
                                        $actData = $act->act_data;
                                        $totalQty = collect($actData['items'] ?? [])->sum(fn($i) => (float)($i['quantity'] ?? 0));
                                    @endphp
                                    <tr class="act-row" data-act-id="{{ $act->id }}">
                                        <td>
                                            <strong>№ {{ $act->act_number ?? '—' }}</strong>
                                        </td>
                                        <td>
                                            <span class="editable-field" contenteditable="true"
                                                data-act-id="{{ $act->id }}" data-field="date">{{ $actData['date'] ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="editable-field" contenteditable="true"
                                                data-act-id="{{ $act->id }}" data-field="provider">{{ $actData['provider'] ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="editable-field" contenteditable="true"
                                                data-act-id="{{ $act->id }}" data-field="contract_details">{{ $actData['contract_details'] ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary badge-act-type">{{ $act->getTypeLabel() }}</span>
                                        </td>
                                        <td>
                                            <span class="editable-field" contenteditable="true"
                                                data-act-id="{{ $act->id }}" data-field="receiver">{{ $actData['receiver'] ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 flex-wrap text-nowrap">
                                                <a href="{{ route('acts.manual.download', $act->id) }}"
                                                   class="btn btn-sm btn-outline-primary shadow-sm">
                                                    <i class="bi bi-file-earmark-word"></i> Скачать
                                                </a>
                                                <button class="btn btn-sm btn-open toggle-expand" data-act-id="{{ $act->id }}">
                                                    <i class="bi bi-chevron-down"></i> Открыть
                                                </button>
                                                <button class="btn btn-sm delete-act"
                                                    style="background:#000; border-color:#000; color:#fff;"
                                                    data-act-id="{{ $act->id }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="expanded-content" id="expanded-{{ $act->id }}">
                                        <td colspan="7" class="p-4">
                                            <ul class="nav nav-tabs mb-3" role="tablist">
                                                <li class="nav-item">
                                                    <button class="nav-link active" data-bs-toggle="tab"
                                                        data-bs-target="#composition-{{ $act->id }}" type="button">
                                                        Таблица 1. Состав
                                                    </button>
                                                </li>
                                                <li class="nav-item">
                                                    <button class="nav-link" data-bs-toggle="tab"
                                                        data-bs-target="#summary-{{ $act->id }}" type="button">
                                                        Таблица 2. Обобщённые
                                                    </button>
                                                </li>
                                                <li class="nav-item">
                                                    <button class="nav-link" data-bs-toggle="tab"
                                                        data-bs-target="#transferred-{{ $act->id }}" type="button">
                                                        Таблица 3. Переданные
                                                    </button>
                                                </li>
                                                <li class="nav-item">
                                                    <button class="nav-link" data-bs-toggle="tab"
                                                        data-bs-target="#received-{{ $act->id }}" type="button">
                                                        Таблица 4. Полученные
                                                    </button>
                                                </li>
                                            </ul>

                                            <div class="tab-content">
                                                {{-- Tab 1: Состав --}}
                                                <div class="tab-pane fade show active" id="composition-{{ $act->id }}">
                                                    <h6 class="fw-bold mb-3">Состав акта</h6>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>№</th>
                                                                    <th>Наименование</th>
                                                                    <th>Код ФККО</th>
                                                                    <th>Класс</th>
                                                                    <th>Количество (т)</th>
                                                                    <th>Вид обращения</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($actData['items'] ?? [] as $index => $item)
                                                                    <tr>
                                                                        <td>{{ $index + 1 }}</td>
                                                                        <td>
                                                                            <span class="editable-field" contenteditable="true"
                                                                                data-act-id="{{ $act->id }}"
                                                                                data-field="items.{{ $index }}.name">{{ $item['name'] ?? '' }}</span>
                                                                        </td>
                                                                         <td>
                                                                            @php
                                                                                $rawCode = $item['fkko_code'] ?? '';

                                                                                $cleanCode = str_replace(' ', '', $rawCode);
                                                                                $formattedCode = $rawCode;
                                                                                if (strlen($cleanCode) === 11) {

                                                                                    $formattedCode = substr($cleanCode, 0, 1) . ' ' .
                                                                                                   substr($cleanCode, 1, 2) . ' ' .
                                                                                                   substr($cleanCode, 3, 3) . ' ' .
                                                                                                   substr($cleanCode, 6, 2) . ' ' .
                                                                                                   substr($cleanCode, 8, 2) . ' ' .
                                                                                                   substr($cleanCode, 10, 1);
                                                                                }
                                                                            @endphp
                                                                            <span class="editable-field" contenteditable="true"
                                                                                data-act-id="{{ $act->id }}"
                                                                                data-field="items.{{ $index }}.fkko_code">{{ $formattedCode }}</span>
                                                                         </td>
                                                                         <td>{{ $item['hazard_class'] ?? '' }}</td>
                                                                        <td>
                                                                            <span class="editable-field" contenteditable="true"
                                                                                data-act-id="{{ $act->id }}"
                                                                                data-field="items.{{ $index }}.quantity">{{ $item['quantity'] ?? '' }}</span>
                                                                        </td>
                                                                        <td>{{ $item['operation_type'] ?? '' }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                            <tfoot class="table-light fw-bold">
                                                                <tr>
                                                                    <td colspan="4" class="text-end">Итого:</td>
                                                                    <td>{{ number_format($totalQty, 3, '.', ' ') }} т</td>
                                                                    <td></td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>

                                                {{-- Tab 2: Обобщённые --}}
                                                <div class="tab-pane fade" id="summary-{{ $act->id }}">
                                                    <h6 class="fw-bold mb-3">Обобщённые данные</h6>
                                                    @php
                                                        $summary = [];
                                                        foreach ($actData['items'] ?? [] as $item) {
                                                            $code = $item['fkko_code'] ?? 'Не указано';
                                                            if (!isset($summary[$code])) {
                                                                $summary[$code] = ['name' => $item['name'] ?? '', 'quantity' => 0, 'hazard_class' => $item['hazard_class'] ?? ''];
                                                            }
                                                            $summary[$code]['quantity'] += (float)($item['quantity'] ?? 0);
                                                        }
                                                        $summaryTotal = array_sum(array_column($summary, 'quantity'));
                                                    @endphp
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>№</th>
                                                                    <th class="text-start">Наименование</th>
                                                                    <th>Код ФККО</th>
                                                                    <th>Класс</th>
                                                                    <th>Итого (т)</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                 @foreach($summary as $code => $data)
                                                                    @php
                                                                        $cleanCode = str_replace(' ', '', $code);
                                                                        $formattedCode = $code;
                                                                        if (strlen($cleanCode) === 11) {
                                                                            $formattedCode = substr($cleanCode, 0, 1) . ' ' .
                                                                                           substr($cleanCode, 1, 2) . ' ' .
                                                                                           substr($cleanCode, 3, 3) . ' ' .
                                                                                           substr($cleanCode, 6, 2) . ' ' .
                                                                                           substr($cleanCode, 8, 2) . ' ' .
                                                                                           substr($cleanCode, 10, 1);
                                                                        }
                                                                    @endphp
                                                                    <tr>
                                                                        <td>{{ $loop->iteration }}</td>
                                                                        <td class="text-start">{{ $data['name'] }}</td>
                                                                        <td>{{ $formattedCode }}</td>
                                                                        <td>{{ $data['hazard_class'] }}</td>
                                                                        <td>{{ number_format($data['quantity'], 3, '.', ' ') }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                            <tfoot class="table-light fw-bold">
                                                                <tr>
                                                                    <td colspan="3" class="text-end">Итого:</td>
                                                                    <td>{{ number_format($summaryTotal, 3, '.', ' ') }} т</td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>

                                                {{-- Tab 3: Переданные --}}
                                                <div class="tab-pane fade" id="transferred-{{ $act->id }}">
                                                    <h6 class="fw-bold mb-3">Переданные отходы</h6>
                                                    <p><strong>Поставщик:</strong> {{ $actData['provider'] ?? '—' }}</p>
                                                    @php
                                                        $transferredItems = collect($actData['items'] ?? []);
                                                        $transferredTotal = $transferredItems->sum(fn($i) => (float)($i['quantity'] ?? 0));
                                                    @endphp
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Наименование</th>
                                                                    <th>Код ФККО</th>
                                                                    <th>Количество (т)</th>
                                                                    <th>Вид обращения</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($actData['items'] ?? [] as $item)
                                                                    <tr>
                                                                        <td>{{ $item['name'] ?? '' }}</td>
                                                                        <td>{{ $item['fkko_code'] ?? '' }}</td>
                                                                        <td>{{ $item['quantity'] ?? '' }}</td>
                                                                        <td>{{ $item['operation_type'] ?? '' }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                            <tfoot class="table-light fw-bold">
                                                                <tr>
                                                                    <td colspan="2" class="text-end">Итого:</td>
                                                                    <td>{{ number_format($transferredTotal, 3, '.', ' ') }} т</td>
                                                                    <td></td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>

                                                {{-- Tab 4: Полученные --}}
                                                <div class="tab-pane fade" id="received-{{ $act->id }}">
                                                    <h6 class="fw-bold mb-3">Полученные отходы</h6>
                                                    <p><strong>Получатель:</strong> {{ $actData['receiver'] ?? '—' }}</p>
                                                    @php
                                                        $receivedItems = collect($actData['items'] ?? []);
                                                        $receivedTotal = $receivedItems->sum(fn($i) => (float)($i['quantity'] ?? 0));
                                                    @endphp
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Наименование</th>
                                                                    <th>Код ФККО</th>
                                                                    <th>Класс</th>
                                                                    <th>Количество (т)</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($actData['items'] ?? [] as $item)
                                                                    <tr>
                                                                        <td>{{ $item['name'] ?? '' }}</td>
                                                                        <td>{{ $item['fkko_code'] ?? '' }}</td>
                                                                        <td>{{ $item['hazard_class'] ?? '' }}</td>
                                                                        <td>{{ $item['quantity'] ?? '' }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                            <tfoot class="table-light fw-bold">
                                                                <tr>
                                                                    <td colspan="3" class="text-end">Итого:</td>
                                                                    <td>{{ number_format($receivedTotal, 3, '.', ' ') }} т</td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                {{ $acts->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('.toggle-expand').on('click', function (e) {
                e.stopPropagation();
                const actId = $(this).data('act-id');
                const expandedRow = $('#expanded-' + actId);

                if (expandedRow.hasClass('show')) {
                    expandedRow.removeClass('show');
                    $(this).html('<i class="bi bi-chevron-down"></i> Открыть');
                } else {
                    expandedRow.addClass('show');
                    $(this).html('<i class="bi bi-chevron-up"></i> Закрыть');
                }
            });

            $('.editable-field').on('blur', function () {
                const actId  = $(this).data('act-id');
                const field  = $(this).data('field');
                const value  = $(this).text().trim();
                const $el    = $(this);

                $.ajax({
                    url: '/acts-archive/' + actId,
                    method: 'PUT',
                    data: { field: field, value: value },
                    success: function () {
                        $el.css('background-color', '#d4edda');
                        setTimeout(() => $el.css('background-color', ''), 1000);
                    },
                    error: function (xhr) {
                        alert('Ошибка при сохранении данных');
                        console.error(xhr);
                    }
                });
            });

            $('.editable-field').on('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); $(this).blur(); }
            });

            $('.delete-act').on('click', function (e) {
                e.stopPropagation();
                const actId = $(this).data('act-id');
                if (!confirm('Вы уверены, что хотите удалить этот акт?')) return;

                $.ajax({
                    url: '/acts/' + actId,
                    method: 'DELETE',
                    success: function () {
                        $('tr[data-act-id="' + actId + '"]').fadeOut(300, function () { $(this).remove(); });
                        $('#expanded-' + actId).fadeOut(300, function () { $(this).remove(); });
                        if ($('tbody tr.act-row').length === 0) location.reload();
                    },
                    error: function (xhr) {
                        alert('Ошибка при удалении акта');
                        console.error(xhr);
                    }
                });
            });
        });
    </script>
@endpush