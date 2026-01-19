@extends('layouts.app')

@section('content')
    @php
        $periodDate = \Carbon\Carbon::parse($journal->period);
        $periodStr = \Illuminate\Support\Str::ucfirst($periodDate->translatedFormat('F Y'));
        $startDate = $periodDate->copy()->startOfMonth();
        $endDate = $periodDate->copy()->endOfMonth();

        if (($journal->type ?? 'month') === 'quarter') {
            $q = ceil($periodDate->month / 3);
            $periodStr = $q . ' квартал ' . $periodDate->year . ' года';
            // Period stored is start of the quarter
            $endDate = $periodDate->copy()->addMonths(2)->endOfMonth();
        } elseif (($journal->type ?? 'month') === 'year') {
            $periodStr = $periodDate->year . ' год';
            $startDate = $periodDate->copy()->startOfYear();
            $endDate = $periodDate->copy()->endOfYear();
        }
    @endphp
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Журнал учета движения отходов</h4>
            <p class="text-muted mb-0">
                Период: {{ $periodStr }} |
                Компания: {{ $journal->company->name ?? '-' }}
            </p>
        </div>
        <div class="d-flex align-items-center">
            <a href="{{ route('journal.index') }}" class="btn btn-outline-secondary me-3">Назад</a>
            <div class="d-flex" style="gap: 10px;">
                <a href="{{ route('journal.download', $journal->id) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Скачать Excel</a>
                <a href="{{ route('journal.download-pdf', $journal->id) }}" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> Скачать PDF</a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <style>
            tr:hover .row-number { visibility: hidden; }
            tr:hover .delete-row-btn { display: inline-block !important; }
        </style>
        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sheet1">Титульный лист</button>
                </li>
                <li class="nav-item">
                     <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sheet-app1">Пр. 1 (Состав)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sheet2">Пр. 2 (Обобщенные)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sheet3">Пр. 3 (Переданные)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sheet4">Пр. 4 (Полученные)</button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content">

                <!-- Titular -->
                <div class="tab-pane fade show active" id="sheet1">
                    <div class="bg-white p-5 mx-auto shadow-sm" style="max-width: 210mm; min-height: 297mm; border: 1px solid #dee2e6; color: #000; font-family: 'Times New Roman', serif;">
                        
                        <div class="text-center mt-4">
                            <h2 class="fw-bold text-uppercase mb-2">ЖУРНАЛ УЧЕТА ДВИЖЕНИЯ ОТХОДОВ</h2>
                            <div class="fs-5 mb-0">за <u>{{ $periodStr }}</u></div>
                            <div class="small text-muted mb-3">(месяц, год)</div>
                            
                            <div class="mb-4">
                                {{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }}
                            </div>
                            <div class="small text-muted" style="margin-top: -1.5rem;">(дата начала ведения журнала) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (дата окончания ведения журнала)</div>
                        </div>

                        <div class="mt-5">
                            <!-- Name -->
                            <div class="mb-2">
                                <div>Наименование индивидуального предпринимателя или юридического лица:</div>
                                <div class="border-bottom border-dark text-center fw-bold text-nowrap" style="line-height: 1.5;">
                                    {{ $journal->company->full_formal_name ?? '' }}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- App 1: Composition (Table 1) -->
                <div class="tab-pane fade" id="sheet-app1">
                    <div class="bg-white p-4 mx-auto shadow-sm" style="max-width: 297mm; min-height: 210mm; border: 1px solid #dee2e6; color: #000; font-family: 'Times New Roman', serif;">
                         <div class="table-responsive">
                             <table class="table table-bordered table-sm text-center align-middle caption-top" style="font-size: 0.9rem; border-color: #000;">
                                <caption style="color: #000; font-weight: bold;">Данные о видах отходов (Пр. 1)</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th>№ п/п</th>
                                        <th>Наименование вида отхода</th>
                                        <th>Код по ФККО</th>
                                        <th>Класс опасности</th>
                                        <th>Происхождение/Условия образования</th>
                                        <th>Хим. состав</th>
                                        <th>Агрегатное состояние</th>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">1</th>
                                        <th class="text-muted">2</th>
                                        <th class="text-muted">3</th>
                                        <th class="text-muted">4</th>
                                        <th class="text-muted">5</th>
                                        <th class="text-muted">6</th>
                                        <th class="text-muted">7</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $row=1; @endphp
                                    @forelse($journal->table1_data as $item)
                                        <tr>
                                            <td>{{ $row++ }}</td>
                                            <td class="text-start">{{ $item['name'] }}</td>
                                            <td>{{ $item['fkko'] }}</td>
                                            <td>{{ $item['hazard'] }}</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7">Нет данных</td></tr>
                                    @endforelse
                                </tbody>
                             </table>
                        </div>
                    </div>
                </div>

                <!-- App 2: Summary (Table 2) -->
                <div class="tab-pane fade" id="sheet2">
                    <div class="bg-white p-4 mx-auto shadow-sm" style="max-width: 297mm; min-height: 210mm; border: 1px solid #dee2e6; color: #000; font-family: 'Times New Roman', serif;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle caption-top"
                                style="font-size: 0.85rem; border-color: #000;">
                                <caption style="color: #000; font-weight: bold;">Обобщенные данные (Таблица 2)</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="2">№</th>
                                        <th rowspan="2">Наименование отхода</th>
                                        <th rowspan="2">Код ФККО</th>
                                        <th rowspan="2">Класс</th>
                                        <th rowspan="2">Наличие на начало (т)</th>
                                        <th rowspan="2">Образовано (т)</th>
                                        <th rowspan="2">Получено (т)</th>
                                        <th rowspan="2">Утилизировано (т)</th>
                                        <th rowspan="2">Обезврежено (т)</th>
                                        <th colspan="5">Передано другим лицам (т)</th>
                                        <th colspan="2">Размещено (т)</th>
                                        <th rowspan="2">Наличие на конец (т)</th>
                                    </tr>
                                    <tr>
                                        <th>Всего</th>
                                        <th>Для обраб.</th>
                                        <th>Для утил.</th>
                                        <th>Для обезвр.</th>
                                        <th>Для разм.</th>
                                        <th>Хранение</th>
                                        <th>Захор.</th>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">1</th>
                                        <th class="text-muted">2</th>
                                        <th class="text-muted">3</th>
                                        <th class="text-muted">4</th>
                                        <th class="text-muted">5</th>
                                        <th class="text-muted">6</th>
                                        <th class="text-muted">7</th>
                                        <th class="text-muted">8</th>
                                        <th class="text-muted">9</th>
                                        <th class="text-muted">10</th>
                                        <th class="text-muted">11</th>
                                        <th class="text-muted">12</th>
                                        <th class="text-muted">13</th>
                                        <th class="text-muted">14</th>
                                        <th class="text-muted">15</th>
                                        <th class="text-muted">16</th>
                                        <th class="text-muted">17</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $row = 1; @endphp
                                    @forelse($journal->table2_data as $item)
                                        <tr>
                                            <td>{{ $row++ }}</td>
                                            <td class="text-start">{{ $item['name'] }}</td>
                                            <td>{{ $item['fkko'] }}</td>
                                            <td>{{ $item['hazard'] }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['balance_begin'], 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['generated'], 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['received'], 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['utilized'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['neutralized'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['transferred'], 3), '0'), '.') }}</td>
                                            <td>-</td>
                                            <td>{{ rtrim(rtrim(number_format($item['transferred'], 3), '0'), '.') }}</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>{{ rtrim(rtrim(number_format($item['buried'] ?? 0, 3), '0'), '.') }}</td>
                                            <td><strong>{{ rtrim(rtrim(number_format($item['balance_end'], 3), '0'), '.') }}</strong>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="17">Нет данных</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- App 3: Transferred -->
                <div class="tab-pane fade" id="sheet3">
                    <div class="bg-white p-4 mx-auto shadow-sm" style="max-width: 297mm; min-height: 210mm; border: 1px solid #dee2e6; color: #000; font-family: 'Times New Roman', serif;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle caption-top"
                                style="font-size: 0.85rem; border-color: #000;">
                                <caption style="color: #000; font-weight: bold;">Данные о переданных другим лицам отходах (Таблица 3)</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="2">№</th>
                                        <th rowspan="2">Дата</th>
                                        <th rowspan="2">Номер акта</th>
                                        <th rowspan="2">Наименование отхода</th>
                                        <th rowspan="2">Код ФККО</th>
                                        <th rowspan="2">Класс</th>
                                        <th rowspan="2">Контрагент (Получатель)</th>
                                        <th rowspan="2">Количество (т)</th>
                                        <th class="text-nowrap" colspan="5">Цель передачи</th>
                                    </tr>
                                    <tr>
                                        <th>Обраб.</th>
                                        <th>Утил.</th>
                                        <th>Обезвр.</th>
                                        <th>Хран.</th>
                                        <th>Захор.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $row = 1;
                                    $totalTrans = 0; @endphp
                                    @forelse($journal->table3_data as $index => $item)
                                        @php $totalTrans += (float) $item['amount']; @endphp
                                        <tr>
                                            <td class="position-relative">
                                                <span class="row-number">{{ $loop->iteration }}</span>
                                                <button class="btn btn-sm btn-dark text-white p-0 border-0 delete-row-btn position-absolute top-50 start-50 translate-middle"
                                                        style="display: none; width: 24px; height: 24px; z-index: 100;"
                                                        title="Удалить строку"
                                                        data-table="table3_data"
                                                        data-row="{{ $index }}">
                                                    <i class="bi bi-trash text-white"></i>
                                                </button>
                                            </td>
                                            <td class="editable" data-table="table3_data" data-row="{{ $index }}" data-column="date">{{ \Carbon\Carbon::parse($item['date'])->format('d.m.Y') }}</td>
                                            <td class="editable" data-table="table3_data" data-row="{{ $index }}" data-column="number">{{ $item['number'] }}</td>
                                            <td class="editable text-start" data-table="table3_data" data-row="{{ $index }}" data-column="waste">{{ $item['waste'] }}</td>
                                            <td class="editable" data-table="table3_data" data-row="{{ $index }}" data-column="fkko">{{ $item['fkko'] }}</td>
                                            <td data-table="table3_data" data-row="{{ $index }}" data-column="hazard">{{ $item['hazard'] }}</td>
                                            <td class="editable text-start" data-table="table3_data" data-row="{{ $index }}" data-column="counterparty">{{ $item['counterparty'] }}</td>
                                            <td class="editable" data-table="table3_data" data-row="{{ $index }}" data-column="amount"><strong>{{ rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') }}</strong></td>
                                            <td class="editable" data-table="table3_data" data-row="{{ $index }}" data-column="p_process">{{ $item['p_process'] ?? (str_contains($item['operation']??'', 'обраб') ? rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') : '') }}</td>
                                            <td class="editable" data-table="table3_data" data-row="{{ $index }}" data-column="p_util">{{ $item['p_util'] ?? (str_contains($item['operation']??'', 'утилиз') ? rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') : '') }}</td>
                                            <td class="editable" data-table="table3_data" data-row="{{ $index }}" data-column="p_neutr">{{ $item['p_neutr'] ?? (str_contains($item['operation']??'', 'обезвреж') ? rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') : '') }}</td>
                                            <td class="editable" data-table="table3_data" data-row="{{ $index }}" data-column="p_store">{{ $item['p_store'] ?? (str_contains($item['operation']??'', 'хран') ? rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') : '') }}</td>
                                            <td class="editable" data-table="table3_data" data-row="{{ $index }}" data-column="p_bury">{{ $item['p_bury'] ?? (str_contains($item['operation']??'', 'захор') ? rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') : '') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="14">Нет данных</td>
                                        </tr>
                                    @endforelse
                                    @if(count($journal->table3_data) > 0)
                                        <tr class="fw-bold bg-light">
                                            <td colspan="7" class="text-end">Итого:</td>
                                            <td id="total-table3_data">{{ rtrim(rtrim(number_format($totalTrans, 3), '0'), '.') }}</td>
                                            <td colspan="5"></td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- App 4: Received -->
                <div class="tab-pane fade" id="sheet4">
                    <div class="bg-white p-4 mx-auto shadow-sm" style="max-width: 297mm; min-height: 210mm; border: 1px solid #dee2e6; color: #000; font-family: 'Times New Roman', serif;">
                        <div class="table-responsive">
                             <table class="table table-bordered table-sm text-center align-middle caption-top" style="font-size: 0.85rem; border-color: #000;">
                                <caption style="color: #000; font-weight: bold;">Данные о полученных отходах (Таблица 4)</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="2">№</th>
                                        <th rowspan="2">Дата</th>
                                        <th rowspan="2">Номер акта</th>
                                        <th rowspan="2">Наименование отхода</th>
                                        <th rowspan="2">Код ФККО</th>
                                        <th rowspan="2">Класс</th>
                                        <th rowspan="2">Контрагент (Поставщик)</th>
                                        <th rowspan="2">Количество (т)</th>
                                        <th class="text-nowrap" colspan="3">Цель приема</th>
                                    </tr>
                                    <tr>
                                        <th>Обраб.</th>
                                        <th>Утил.</th>
                                        <th>Обезвр.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $row=1; $totalRec=0; @endphp
                                    @forelse($journal->table4_data as $index => $item)
                                        @php $totalRec += (float)$item['amount']; @endphp
                                        <tr>
                                            <td class="position-relative">
                                                <span class="row-number">{{ $loop->iteration }}</span>
                                                <button class="btn btn-sm btn-dark text-white p-0 border-0 delete-row-btn position-absolute top-50 start-50 translate-middle"
                                                        style="display: none; width: 24px; height: 24px; z-index: 100;"
                                                        title="Удалить строку"
                                                        data-table="table4_data"
                                                        data-row="{{ $index }}">
                                                    <i class="bi bi-trash text-white"></i>
                                                </button>
                                            </td>
                                            <td class="editable" data-table="table4_data" data-row="{{ $index }}" data-column="date">{{ \Carbon\Carbon::parse($item['date'])->format('d.m.Y') }}</td>
                                            <td class="editable" data-table="table4_data" data-row="{{ $index }}" data-column="number">{{ $item['number'] }}</td>
                                            <td class="editable text-start" data-table="table4_data" data-row="{{ $index }}" data-column="waste">{{ $item['waste'] }}</td>
                                            <td class="editable" data-table="table4_data" data-row="{{ $index }}" data-column="fkko">{{ $item['fkko'] }}</td>
                                            <td data-table="table4_data" data-row="{{ $index }}" data-column="hazard">{{ $item['hazard'] }}</td>
                                            <td class="editable text-start" data-table="table4_data" data-row="{{ $index }}" data-column="counterparty">{{ $item['counterparty'] }}</td>
                                            <td class="editable" data-table="table4_data" data-row="{{ $index }}" data-column="amount"><strong>{{ rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') }}</strong></td>
                                            <td class="editable" data-table="table4_data" data-row="{{ $index }}" data-column="p_process">{{ $item['p_process'] ?? (str_contains($item['operation']??'', 'обраб') ? rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') : '') }}</td>
                                            <td class="editable" data-table="table4_data" data-row="{{ $index }}" data-column="p_util">{{ $item['p_util'] ?? (str_contains($item['operation']??'', 'утилиз') ? rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') : '') }}</td>
                                            <td class="editable" data-table="table4_data" data-row="{{ $index }}" data-column="p_neutr">{{ $item['p_neutr'] ?? (str_contains($item['operation']??'', 'обезвреж') ? rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') : '') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="12">Нет данных</td></tr>
                                    @endforelse
                                    @if(count($journal->table4_data) > 0)
                                        <tr class="fw-bold bg-light">
                                            <td colspan="7" class="text-end">Итого:</td>
                                            <td id="total-table4_data">{{ rtrim(rtrim(number_format($totalRec, 3), '0'), '.') }}</td>
                                            <td colspan="3"></td>
                                        </tr>
                                    @endif
                                </tbody>
                             </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <script>
        const wasteOptions = @json($wastes ?? []);

        document.addEventListener('DOMContentLoaded', function() {
            // Функция пересчета итогов
            const recalculateTotal = (tableName) => {
                const totalCell = document.getElementById(`total-${tableName}`);
                if (!totalCell) return;

                let total = 0;
                document.querySelectorAll(`td[data-table="${tableName}"][data-column="amount"]`).forEach(td => {
                    // Удаляем пробелы и запятые (разделители тысяч) перед парсингом. Десятичный разделитель - точка.
                    let raw = td.innerText.replace(/,/g, '').trim(); 
                    let val = parseFloat(raw) || 0;
                    total += val;
                });

                // Форматирование: до 3 знаков, удаление лишних нулей
                // Для итогов разделитель тысяч тоже может быть полезен, но пока вернем просто число или отформатированное
                // number_format в JS аналог:
                let s = total.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 3});
                // toLocaleString('en-US') использует запятые как разделители тысяч и точку как десятичный.
                
                s = s.replace(/\.?0+$/, '');
                totalCell.innerText = s;
            };

            const handleRowDeletion = (row, tableName) => {
                const tableBody = row.closest('tbody');
                row.remove();
                
                // Перенумерация строк и обновление индексов data-row
                let rowNum = 1;
                let dataRowIndex = 0;
                tableBody.querySelectorAll('tr').forEach(tr => {
                    // Пропускаем строки без обычных ячеек (например, "Итого" с colspan)
                    const firstCell = tr.cells[0];
                    if (firstCell && !firstCell.hasAttribute('colspan')) {
                        const numSpan = firstCell.querySelector('.row-number');
                        if (numSpan) numSpan.innerText = rowNum++;
                        else firstCell.innerText = rowNum++; // Fallback
                        
                        // Обновляем индекс строки для будущих запросов (включая кнопку удаления)
                        tr.querySelectorAll('[data-row]').forEach(el => {
                            el.dataset.row = dataRowIndex;
                        });
                        dataRowIndex++;
                    }
                });

                recalculateTotal(tableName);
            };

            // Обработчик кнопки удаления
            document.body.addEventListener('click', async function(e) {
                const btn = e.target.closest('.delete-row-btn');
                if (!btn) return;

                if (!confirm('Вы уверены, что хотите удалить эту строку?')) return;

                const table = btn.dataset.table;
                const rowIndex = btn.dataset.row;
                const row = btn.closest('tr');

                try {
                    const response = await fetch('{{ route('journal.update', $journal->id) }}', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            table: table,
                            row_index: rowIndex,
                            column: 'amount', // Установка 0 triggers deletion
                            value: 0
                        })
                    });
                    
                    if (!response.ok) throw new Error('Failed');
                    const data = await response.json();

                    if (data.action === 'deleted') {
                        handleRowDeletion(row, table);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Ошибка при удалении строки');
                }
            });

            document.querySelectorAll('.editable').forEach(cell => {
                cell.style.cursor = 'pointer';
                cell.title = 'Нажмите для изменения';
                
                cell.addEventListener('click', function() {
                    if (this.isEditing) return;
                    this.isEditing = true;
                    
                    const column = this.dataset.column;
                    const originalValue = this.innerText.trim();
                    let input;

                    // Логика выпадающего списка отходов
                    if (column === 'waste') {
                        input = document.createElement('select');
                        input.className = 'form-select form-select-sm p-0 border-0 shadow-none bg-transparent';
                        
                        // Заполнение опций
                        let found = false;
                        wasteOptions.forEach(w => {
                            const opt = document.createElement('option');
                            opt.value = w.name;
                            opt.text = w.name;
                            if (w.name === originalValue) {
                                opt.selected = true;
                                found = true;
                            }
                            input.appendChild(opt);
                        });
                        
                        // Сохранение исходного значения, если его нет в списке
                        if (!found && originalValue) {
                            const opt = document.createElement('option');
                            opt.value = originalValue;
                            opt.text = originalValue;
                            opt.selected = true;
                            input.appendChild(opt);
                        }
                    } else if (column === 'fkko') {
                        input = document.createElement('select');
                        input.className = 'form-select form-select-sm p-0 border-0 shadow-none bg-transparent';
                        
                        let found = false;
                        wasteOptions.forEach(w => {
                            const opt = document.createElement('option');
                            opt.value = w.code;
                            opt.text = w.code;
                            opt.title = w.name;
                            if (w.code === originalValue) {
                                opt.selected = true;
                                found = true;
                            }
                            input.appendChild(opt);
                        });
                        
                        if (!found && originalValue) {
                            const opt = document.createElement('option');
                            opt.value = originalValue;
                            opt.text = originalValue;
                            opt.selected = true;
                            input.appendChild(opt);
                        }
                    } else {
                        // Стандартное поле ввода
                        input = document.createElement('input');
                        input.type = 'text';
                        input.value = originalValue;
                        input.className = 'form-control form-control-sm p-0 border-0 shadow-none bg-transparent';
                        // Сохранение выравнивания
                        if (this.classList.contains('text-start')) {
                            input.classList.add('text-start');
                        } else {
                            input.classList.add('text-center');
                        }
                    }
                    
                    input.style.width = '100%';
                    input.style.minWidth = '50px';
                    
                    this.innerHTML = '';
                    this.appendChild(input);
                    input.focus();
                    
                    const save = async () => {
                        const newValue = input.value;
                        if (newValue === originalValue) {
                            this.innerHTML = originalValue;
                            this.isEditing = false;
                            return;
                        }
                        
                        try {
                            const response = await fetch('{{ route('journal.update', $journal->id) }}', {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    table: this.dataset.table,
                                    row_index: this.dataset.row,
                                    column: this.dataset.column,
                                    value: newValue
                                })
                            });
                            
                            if (!response.ok) throw new Error('Failed');
                            
                            const data = await response.json();
                            
                            // Проверка на удаление строки
                            if (data.action === 'deleted') {
                                handleRowDeletion(this.closest('tr'), this.dataset.table);
                                return;
                            }
                            
                            // Обновление интерфейса
                            this.innerHTML = newValue;
                            
                            // Визуальное подтверждение успеха
                            this.classList.add('bg-success-subtle');
                            setTimeout(() => this.classList.remove('bg-success-subtle'), 1000);

                            // Обработка дополнительных обновлений
                            if (data.updates) {
                                const row = this.closest('tr');
                                for (const [key, val] of Object.entries(data.updates)) {
                                    const sibling = row.querySelector(`[data-column="${key}"]`);
                                    if (sibling) {
                                        sibling.innerText = val;
                                        sibling.classList.add('bg-success-subtle');
                                        setTimeout(() => sibling.classList.remove('bg-success-subtle'), 1000);
                                    }
                                }
                            }
                            
                            // Пересчет итогов
                            recalculateTotal(this.dataset.table);

                        } catch (e) {
                            console.error(e);
                            this.innerHTML = originalValue;
                            alert('Не удалось сохранить изменения');
                        } finally {
                            this.isEditing = false;
                        }
                    };
                    
                    input.addEventListener('blur', save);
                    input.addEventListener('keypress', (e) => {
                        if (e.key === 'Enter') {
                            input.blur();
                        }
                    });
                });
            });
        });
    </script>
@endsection