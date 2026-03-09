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
            $endDate = $periodDate->copy()->addMonths(2)->endOfMonth();
        } elseif (($journal->type ?? 'month') === 'year') {
            $periodStr = $periodDate->year . ' год';
            $startDate = $periodDate->copy()->startOfYear();
            $endDate = $periodDate->copy()->endOfYear();
        }
    @endphp
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h4 class="mb-0">Журнал учета движения отходов</h4>
            <p class="text-muted mb-0">
                Период: {{ $periodStr }} |
                Компания: {{ $journal->company->name ?? '-' }}
                @if($hasPolygons && $journal->polygon)
                    | <span class="text-primary"><i class="bi bi-geo-alt-fill me-1"></i>{{ $journal->polygon->name }}</span>
                @endif
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('journal.index') }}" class="btn btn-outline-secondary">Назад</a>
            {{-- Виджет привязки полигона --}}
            @if($hasPolygons)
                <div class="dropdown">
                    <button class="btn {{ $journal->polygon ? 'btn-outline-primary' : 'btn-outline-secondary' }} dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-geo-alt me-1"></i>
                        {{ $journal->polygon ? $journal->polygon->name : 'Полигон не выбран' }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 220px;">
                        <li><h6 class="dropdown-header">Привязать к полигону</h6></li>
                        @foreach($polygons as $polygon)
                            <li>
                                <form action="{{ route('journal.assign-polygon', $journal->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="polygon_id" value="{{ $polygon->id }}">
                                    <button type="submit"
                                        class="dropdown-item {{ $journal->polygon_id == $polygon->id ? 'fw-bold text-primary' : '' }}">
                                        <i class="bi bi-geo-alt{{ $journal->polygon_id == $polygon->id ? '-fill text-primary' : '' }} me-1"></i>
                                        {{ $polygon->name }}
                                        @if($journal->polygon_id == $polygon->id)
                                            <i class="bi bi-check2 ms-1 text-success"></i>
                                        @endif
                                    </button>
                                </form>
                            </li>
                        @endforeach
                        @if($journal->polygon_id)
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form action="{{ route('journal.assign-polygon', $journal->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="polygon_id" value="">
                                    <button type="submit" class="dropdown-item text-muted">
                                        <i class="bi bi-x-circle me-1"></i>Снять привязку
                                    </button>
                                </form>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
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
                     <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sheet-app1">Таблица 1 (Состав)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sheet2">Таблица 2 (Обобщённые)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sheet3">Таблица 3 (Переданные)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sheet4">Таблица 4 (Полученные)</button>
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
                            <div class="mb-2">
                                <div>Наименование индивидуального предпринимателя или юридического лица:</div>
                                <div class="border-bottom border-dark text-center fw-bold" style="line-height: 1.5;">
                                    {{ $journal->company->name ?? '' }}
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
                                <caption style="color: #000; font-weight: bold;">Данные о видах отходов (Таблица 1)</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th>№ п/п</th>
                                        <th>Наименование вида отхода</th>
                                        <th>Код по ФККО</th>
                                        <th>Класс опасности</th>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">1</th>
                                        <th class="text-muted">2</th>
                                        <th class="text-muted">3</th>
                                        <th class="text-muted">4</th>
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
                                        </tr>
                                    @empty
                                        <tr><td colspan="4">Нет данных</td></tr>
                                    @endforelse
                                </tbody>
                             </table>
                        </div>
                    </div>
                </div>

                <!-- App 2: Summary (Table 2) -->
                <div class="tab-pane fade" id="sheet2">
                    <div class="bg-white p-4 mx-auto shadow-sm" style="max-width: 350mm; min-height: 210mm; border: 1px solid #dee2e6; color: #000; font-family: 'Times New Roman', serif;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle caption-top"
                                style="font-size: 0.75rem; border-color: #000;">
                                <caption style="color: #000; font-weight: bold;">Обобщенные данные (Таблица 2)</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="2">№</th>
                                        <th rowspan="2">Наименование отхода</th>
                                        <th rowspan="2">Код ФККО</th>
                                        <th rowspan="2">Класс</th>
                                        <th rowspan="2">На начало (т)</th>
                                        <th rowspan="2">Образов. (т)</th>
                                        <th rowspan="2">Получено (т)</th>
                                        <th rowspan="2">Обработ. (т)</th>
                                        <th rowspan="2">Утилиз. (т)</th>
                                        <th rowspan="2">Обезвреж. (т)</th>
                                        <th colspan="5">Передано другим лицам (т)</th>
                                        <th rowspan="2">Хран. (т)</th>
                                        <th rowspan="2">Захор. (т)</th>
                                        <th rowspan="2">На конец (т)</th>
                                    </tr>
                                    <tr>
                                        <th>Всего</th>
                                        <th>Обраб.</th>
                                        <th>Утил.</th>
                                        <th>Обезвр.</th>
                                        <th>Разм.</th>
                                    </tr>
                                    <tr class="text-muted" style="font-size: 0.7rem;">
                                        <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th><th>8</th><th>9</th><th>10</th><th>11</th><th>12</th><th>13</th><th>14</th><th>15</th><th>16</th><th>17</th><th>18</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $row = 1; @endphp
                                    @forelse($journal->table2_data as $item)
                                        <tr>
                                            <td>{{ $row++ }}</td>
                                            <td class="text-start" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $item['name'] }}">{{ $item['name'] }}</td>
                                            <td>{{ $item['fkko'] }}</td>
                                            <td>{{ $item['hazard'] }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['balance_begin'], 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['generated'], 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['received'], 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['processed'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['utilized'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['neutralized'] ?? 0, 3), '0'), '.') }}</td>
                                            
                                            <td class="fw-bold">{{ rtrim(rtrim(number_format($item['transferred_total'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['trans_process'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['trans_util'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['trans_neutr'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format(($item['trans_store'] ?? 0) + ($item['trans_bury'] ?? 0), 3), '0'), '.') }}</td>
                                            
                                            <td>{{ rtrim(rtrim(number_format($item['stored'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['buried'] ?? 0, 3), '0'), '.') }}</td>
                                            <td><strong>{{ rtrim(rtrim(number_format($item['balance_end'], 3), '0'), '.') }}</strong></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="18">Нет данных</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Table 3 -->
                <div class="tab-pane fade" id="sheet3">
                    <div class="bg-white p-4 mx-auto shadow-sm" style="max-width: 297mm; min-height: 210mm; border: 1px solid #dee2e6; color: #000; font-family: 'Times New Roman', serif;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle caption-top" style="font-size: 0.85rem; border-color: #000;">
                                <caption style="color: #000; font-weight: bold;">Передано (Таблица 3)</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th>№</th><th>Дата</th><th>№ акта</th><th>Наименование</th><th>ФККО</th><th>Класс</th><th>Получатель</th><th>Кол-во (т)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($journal->table3_data as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $item['date'] }}</td>
                                            <td>{{ $item['number'] }}</td>
                                            <td class="text-start">{{ $item['waste'] }}</td>
                                            <td>{{ $item['fkko'] }}</td>
                                            <td>{{ $item['hazard'] }}</td>
                                            <td class="text-start">{{ $item['counterparty'] }}</td>
                                            <td>{{ $item['amount'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Table 4 -->
                <div class="tab-pane fade" id="sheet4">
                    <div class="bg-white p-4 mx-auto shadow-sm" style="max-width: 297mm; min-height: 210mm; border: 1px solid #dee2e6; color: #000; font-family: 'Times New Roman', serif;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle caption-top" style="font-size: 0.85rem; border-color: #000;">
                                <caption style="color: #000; font-weight: bold;">Получено (Таблица 4)</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th>№</th><th>Дата</th><th>№ акта</th><th>Наименование</th><th>ФККО</th><th>Класс</th><th>Поставщик</th><th>Кол-во (т)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($journal->table4_data as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $item['date'] }}</td>
                                            <td>{{ $item['number'] }}</td>
                                            <td class="text-start">{{ $item['waste'] }}</td>
                                            <td>{{ $item['fkko'] }}</td>
                                            <td>{{ $item['hazard'] }}</td>
                                            <td class="text-start">{{ $item['counterparty'] }}</td>
                                            <td>{{ $item['amount'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection