@extends('layouts.app')

@push('styles')
    <style>
        .act-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .act-row:hover {
            background-color: #f8f9fa;
        }

        .expanded-content {
            display: none;
            background-color: #f8f9fa;
        }

        .expanded-content.show {
            display: table-row;
        }

        .editable-field {
            border: 1px solid transparent;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .editable-field:hover {
            border-color: #dee2e6;
            background-color: #fff;
        }

        .editable-field:focus {
            outline: none;
            border-color: #FF4C2B;
            background-color: #fff;
        }

        .btn-open {
            background-color: #FF4C2B;
            border-color: #FF4C2B;
            color: white;
        }

        .btn-open:hover {
            background-color: #e04326;
            border-color: #d63f24;
            color: white;
        }

        .nav-tabs .nav-link {
            color: #6c757d;
        }

        .nav-tabs .nav-link.active {
            color: #FF4C2B;
            border-color: #dee2e6 #dee2e6 #fff;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Реестр актов</h2>
            <div>
                <a href="{{ route('acts.manual.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Добавить акт вручную
                </a>
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
                                    <th>Дата</th>
                                    <th>Номер акта</th>
                                    <th>Поставщик</th>
                                    <th>Получатель</th>
                                    <th width="200">Действие</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($acts as $act)
                                    @php
                                        $actData = $act->act_data;
                                    @endphp
                                    <tr class="act-row" data-act-id="{{ $act->id }}">
                                        <td>
                                            <span class="editable-field" contenteditable="true" data-act-id="{{ $act->id }}"
                                                data-field="date">{{ $actData['date'] ?? 'Не указано' }}</span>
                                        </td>
                                        <td>
                                            <span class="editable-field" contenteditable="true" data-act-id="{{ $act->id }}"
                                                data-field="number">{{ $actData['number'] ?? 'Не указано' }}</span>
                                        </td>
                                        <td>
                                            <span class="editable-field" contenteditable="true" data-act-id="{{ $act->id }}"
                                                data-field="provider">{{ $actData['provider'] ?? 'Не указано' }}</span>
                                        </td>
                                        <td>
                                            <span class="editable-field" contenteditable="true" data-act-id="{{ $act->id }}"
                                                data-field="receiver">{{ $actData['receiver'] ?? 'Не указано' }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-3">
                                                <button class="btn btn-sm btn-open toggle-expand" data-act-id="{{ $act->id }}">
                                                    <i class="bi bi-chevron-down"></i> Открыть
                                                </button>
                                                <button class="btn btn-sm delete-act"
                                                    style="background-color: #000; border-color: #000; color: #fff;"
                                                    data-act-id="{{ $act->id }}">
                                                    <i class="bi bi-trash"></i> Удалить
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="expanded-content" id="expanded-{{ $act->id }}">
                                        <td colspan="5" class="p-4">
                                            <ul class="nav nav-tabs mb-3" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active" data-bs-toggle="tab"
                                                        data-bs-target="#composition-{{ $act->id }}" type="button">
                                                        1. Состав
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link" data-bs-toggle="tab"
                                                        data-bs-target="#summary-{{ $act->id }}" type="button">
                                                        2. Обобщенные данные
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link" data-bs-toggle="tab"
                                                        data-bs-target="#transferred-{{ $act->id }}" type="button">
                                                        3. Переданные
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link" data-bs-toggle="tab"
                                                        data-bs-target="#received-{{ $act->id }}" type="button">
                                                        4. Полученные
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
                                                                            <span class="editable-field" contenteditable="true"
                                                                                data-act-id="{{ $act->id }}"
                                                                                data-field="items.{{ $index }}.fkko_code">{{ $item['fkko_code'] ?? '' }}</span>
                                                                        </td>
                                                                        <td>
                                                                            <span class="editable-field" contenteditable="true"
                                                                                data-act-id="{{ $act->id }}"
                                                                                data-field="items.{{ $index }}.hazard_class">{{ $item['hazard_class'] ?? '' }}</span>
                                                                        </td>
                                                                        <td>
                                                                            <span class="editable-field" contenteditable="true"
                                                                                data-act-id="{{ $act->id }}"
                                                                                data-field="items.{{ $index }}.quantity">{{ $item['quantity'] ?? '' }}</span>
                                                                        </td>
                                                                        <td>
                                                                            <span class="editable-field" contenteditable="true"
                                                                                data-act-id="{{ $act->id }}"
                                                                                data-field="items.{{ $index }}.operation_type">{{ $item['operation_type'] ?? '' }}</span>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                {{-- Tab 2: Обобщенные данные --}}
                                                <div class="tab-pane fade" id="summary-{{ $act->id }}">
                                                    <h6 class="fw-bold mb-3">Обобщенные данные</h6>
                                                    @php
                                                        $summary = [];
                                                        foreach ($actData['items'] ?? [] as $item) {
                                                            $code = $item['fkko_code'] ?? 'Не указано';
                                                            if (!isset($summary[$code])) {
                                                                $summary[$code] = [
                                                                    'name' => $item['name'] ?? '',
                                                                    'quantity' => 0,
                                                                    'hazard_class' => $item['hazard_class'] ?? ''
                                                                ];
                                                            }
                                                            $summary[$code]['quantity'] += (float) ($item['quantity'] ?? 0);
                                                        }
                                                    @endphp
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Код ФККО</th>
                                                                    <th>Наименование</th>
                                                                    <th>Класс опасности</th>
                                                                    <th>Общее количество (т)</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($summary as $code => $data)
                                                                    <tr>
                                                                        <td>{{ $code }}</td>
                                                                        <td>{{ $data['name'] }}</td>
                                                                        <td>{{ $data['hazard_class'] }}</td>
                                                                        <td>{{ number_format($data['quantity'], 3, '.', ' ') }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                {{-- Tab 3: Переданные --}}
                                                <div class="tab-pane fade" id="transferred-{{ $act->id }}">
                                                    <h6 class="fw-bold mb-3">Переданные отходы</h6>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p><strong>Поставщик:</strong>
                                                                {{ $actData['provider'] ?? 'Не указано' }}</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>Получатель:</strong>
                                                                {{ $actData['receiver'] ?? 'Не указано' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Наименование отхода</th>
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
                                                        </table>
                                                    </div>
                                                </div>

                                                {{-- Tab 4: Полученные --}}
                                                <div class="tab-pane fade" id="received-{{ $act->id }}">
                                                    <h6 class="fw-bold mb-3">Полученные отходы</h6>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p><strong>От кого получено:</strong>
                                                                {{ $actData['provider'] ?? 'Не указано' }}</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>Кем получено:</strong>
                                                                {{ $actData['receiver'] ?? 'Не указано' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Наименование отхода</th>
                                                                    <th>Код ФККО</th>
                                                                    <th>Класс опасности</th>
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
                const icon = $(this).find('i');

                if (expandedRow.hasClass('show')) {
                    expandedRow.removeClass('show');
                    icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
                    $(this).html('<i class="bi bi-chevron-down"></i> Открыть');
                } else {
                    expandedRow.addClass('show');
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
                    $(this).html('<i class="bi bi-chevron-up"></i> Закрыть');
                }
            });

            $('.editable-field').on('blur', function () {
                const actId = $(this).data('act-id');
                const field = $(this).data('field');
                const value = $(this).text().trim();

                $.ajax({
                    url: '/acts-archive/' + actId,
                    method: 'PUT',
                    data: {
                        field: field,
                        value: value
                    },
                    success: function (response) {
                        const originalBg = $(this).css('background-color');
                        $(this).css('background-color', '#d4edda');
                        setTimeout(() => {
                            $(this).css('background-color', originalBg);
                        }, 1000);
                    }.bind(this),
                    error: function (xhr) {
                        alert('Ошибка при сохранении данных');
                        console.error(xhr);
                    }
                });
            });

            $('.editable-field').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $(this).blur();
                }
            });

            $('.delete-act').on('click', function (e) {
                e.stopPropagation();
                const actId = $(this).data('act-id');

                if (!confirm('Вы уверены, что хотите удалить этот акт? Это действие нельзя отменить.')) {
                    return;
                }

                $.ajax({
                    url: '/acts/' + actId,
                    method: 'DELETE',
                    success: function (response) {
                        $('tr[data-act-id="' + actId + '"]').fadeOut(300, function () {
                            $(this).remove();
                        });
                        $('#expanded-' + actId).fadeOut(300, function () {
                            $(this).remove();
                        });

                        if ($('tbody tr.act-row').length === 0) {
                            location.reload();
                        }
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