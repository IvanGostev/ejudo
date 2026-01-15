@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Журнал учета движения отходов</h4>
            <p class="text-muted mb-0">
                Период: {{ \Carbon\Carbon::parse($journal->period)->translatedFormat('F Y') }} |
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
                            <div class="fs-5 mb-0">за <u>{{ \Carbon\Carbon::parse($journal->period)->translatedFormat('F Y') }}</u></div>
                            <div class="small text-muted mb-3">(месяц, год)</div>
                            
                            <div class="mb-4">
                                {{ \Carbon\Carbon::parse($journal->period)->startOfMonth()->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($journal->period)->endOfMonth()->format('d.m.Y') }}
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
                                    @forelse($journal->table3_data as $item)
                                        @php $totalTrans += (float) $item['amount']; @endphp
                                        <tr>
                                            <td>{{ $row++ }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item['date'])->format('d.m.Y') }}</td>
                                            <td>{{ $item['number'] }}</td>
                                            <td class="text-start">{{ $item['waste'] }}</td>
                                            <td>{{ $item['fkko'] }}</td>
                                            <td>{{ $item['hazard'] }}</td>
                                            <td class="text-start">{{ $item['counterparty'] }}</td>
                                            <td><strong>{{ rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') }}</strong>
                                            </td>
                                            <td></td>
                                            <td>{{ rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') }}</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="14">Нет данных</td>
                                        </tr>
                                    @endforelse
                                    @if(count($journal->table3_data) > 0)
                                        <tr class="fw-bold bg-light">
                                            <td colspan="7" class="text-end">Итого:</td>
                                            <td>{{ rtrim(rtrim(number_format($totalTrans, 3), '0'), '.') }}</td>
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
                                    @forelse($journal->table4_data as $item)
                                        @php $totalRec += (float)$item['amount']; @endphp
                                        <tr>
                                            <td>{{ $row++ }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item['date'])->format('d.m.Y') }}</td>
                                            <td>{{ $item['number'] }}</td>
                                            <td class="text-start">{{ $item['waste'] }}</td>
                                            <td>{{ $item['fkko'] }}</td>
                                            <td>{{ $item['hazard'] }}</td>
                                            <td class="text-start">{{ $item['counterparty'] }}</td>
                                            <td><strong>{{ rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') }}</strong></td>
                                            <td></td>
                                            <td>{{ rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') }}</td>
                                            <td></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="12">Нет данных</td></tr>
                                    @endforelse
                                    @if(count($journal->table4_data) > 0)
                                        <tr class="fw-bold bg-light">
                                            <td colspan="7" class="text-end">Итого:</td>
                                            <td>{{ rtrim(rtrim(number_format($totalRec, 3), '0'), '.') }}</td>
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
@endsection