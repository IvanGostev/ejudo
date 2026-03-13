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
                    <div class="bg-white p-5 mx-auto shadow-sm" style="max-width: 210mm; min-height: 297mm; border: 1px solid #dee2e6; color: #000; font-family: 'Times New Roman', serif; line-height: 1.3;">
                        
                        <!-- Approval Block (Excel Style) -->
                        <div class="row">
                            <div class="col-5"></div>
                            <div class="col-7 text-start">
                                 <div class="fw-bold mb-1">УТВЕРЖДАЮ</div>
                                 <div class="mb-3">Генеральный директор {{ $journal->company->name ?? 'ООО "Эко Полимер"' }}</div>
                                 <div class="mt-4 pt-1">
                                     <table class="w-100" style="border-collapse: collapse;">
                                         <tr>
                                             <td class="border-bottom border-dark text-center" style="width: 40%; height: 1.5em;"></td>
                                             <td style="width: 10%;"></td>
                                             <td class="border-bottom border-dark text-center fw-bold" style="width: 50%;">{{ $journal->company->contact_person ?? '' }}</td>
                                         </tr>
                                         <tr>
                                             <td class="text-center" style="font-size: 7.5pt; vertical-align: top;">(подпись)</td>
                                             <td></td>
                                             <td class="text-center" style="font-size: 7.5pt; vertical-align: top;">(Ф.И.О.)</td>
                                         </tr>
                                     </table>
                                 </div>
                                 <div class="mt-3">{{ date('d') }} {{ \Carbon\Carbon::now()->translatedFormat('F') }} {{ date('Y') }} г.</div>
                            </div>
                        </div>

                         <!-- Title -->
                        <div class="text-center mt-5 mb-5 pt-5">
                            <h2 class="fw-bold mb-0" style="font-size: 18pt;">Журнал учета движения отходов</h2>
                        </div>

                        <!-- Facility Name (Excel style) -->
                        <div class="text-center mt-4 pt-5">
                            <div class="border-bottom border-dark fw-bold mb-1" style="min-height: 1.5em; line-height: 1.2;">{{ $journal->company->name ?? '' }}</div>
                            <div class="small" style="font-size: 8pt;">(наименование объекта, оказывающего негативное воздействие на окружающую среду)</div>
                        </div>

                        <div class="text-center mt-4">
                            <div class="border-bottom border-dark mb-1" style="min-height: 1.5em;">&nbsp;</div>
                            <div class="small" style="font-size: 8pt;">(категория объекта)</div>
                        </div>

                        <!-- Company Name (Excel style) -->
                        <div class="text-center mt-5 pt-4">
                            <div class="border-bottom border-dark fw-bold mb-1" style="min-height: 1.5em; line-height: 1.2;">{{ $journal->company->contact_person ?? '' }}</div>
                            <div class="small" style="font-size: 8pt;">(наименование юридического лица, индивидуального предпринимателя)</div>
                        </div>

                        <!-- Dates (Excel style) -->
                        <div class="mt-5 pt-5">
                            <div class="d-flex align-items-center mb-4">
                                <div class="me-3" style="width: 80px;">начато</div>
                                <div class="border-bottom border-dark flex-grow-1 text-center fw-bold">{{ $startDate->format('d.m.Y') }}</div>
                            </div>
                            <div class="d-flex align-items-center mb-4">
                                <div class="me-3" style="width: 80px;">окончен</div>
                                <div class="border-bottom border-dark flex-grow-1 text-center fw-bold">{{ $endDate->format('d.m.Y') }}</div>
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
                                        <th>Происхождение или условия<br>образования вида отхода</th>
                                        <th>Агрегатное состояние и<br>физическая форма</th>
                                        <th>Химический и (или)<br>компонентный состав, %</th>
                                    </tr>
                                    <tr class="text-muted small">
                                        <th>1</th>
                                        <th>2</th>
                                        <th>3</th>
                                        <th>4</th>
                                        <th>5</th>
                                        <th>6</th>
                                        <th>7</th>
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
                                            <td>-</td>
                                            <td>-</td>
                                            <td>-</td>
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
                    <div class="bg-white p-4 mx-auto shadow-sm" style="max-width: 350mm; min-height: 210mm; border: 1px solid #dee2e6; color: #000; font-family: 'Times New Roman', serif;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle caption-top"
                                style="font-size: 0.7rem; border-color: #000;">
                                <caption style="color: #000; font-weight: bold;">Обобщенные данные (Таблица 2)</caption>
                                 <thead class="table-light">
                                    <tr>
                                        <th rowspan="2">N строки</th>
                                        <th rowspan="2">Наименование вида отхода</th>
                                        <th rowspan="2">Код по ФККО</th>
                                        <th rowspan="2">Класс опасности</th>
                                        <th rowspan="2" style="min-width: 60px;">На начало, тонн</th>
                                        <th rowspan="2" style="min-width: 60px;">Образовано, тонн</th>
                                        <th rowspan="2" style="min-width: 60px;">Получено, тонн</th>
                                        <th rowspan="2" style="min-width: 60px;">Обработано, тонн</th>
                                        <th rowspan="2" style="min-width: 60px;">Утилизировано, тонн</th>
                                        <th rowspan="2" style="min-width: 60px;">Обезврежено, тонн</th>
                                        <th rowspan="2" style="min-width: 60px;">Передано (всего), тонн</th>
                                        <th colspan="5">В том числе передано для:</th>
                                        <th rowspan="2" style="min-width: 60px;">Размещено (хранение), тонн</th>
                                        <th rowspan="2" style="min-width: 60px;">На конец, тонн</th>
                                    </tr>
                                    <tr>
                                        <th>обраб.</th>
                                        <th>утил.</th>
                                        <th>обезвреж.</th>
                                        <th>хран.</th>
                                        <th>захорон.</th>
                                    </tr>
                                    <tr class="text-muted" style="font-size: 0.65rem;">
                                        <th>А</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th><th>8</th><th>9</th><th>10</th><th>11</th><th>12</th><th>13</th><th>14</th><th>15</th><th>16</th><th>17</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $row = 1; @endphp
                                    @forelse($journal->table2_data as $item)
                                        <tr>
                                            <td>{{ $row++ }}</td>
                                            <td class="text-start" style="max-width: 15rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $item['name'] }}">{{ $item['name'] }}</td>
                                            <td>{{ $item['fkko'] }}</td>
                                            <td>{{ $item['hazard'] }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['balance_begin'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['generated'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['received'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['processed'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['utilized'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['neutralized'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['transferred_total'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['trans_process'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['trans_util'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['trans_neutr'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['trans_store'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['trans_bury'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['stored'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
                                            <td class="fw-bold">{{ rtrim(rtrim(number_format($item['balance_end'] ?? 0, 3), '0'), '.') ?: '-' }}</td>
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
                    <div class="bg-white p-4 mx-auto shadow-sm" style="max-width: 350mm; min-height: 210mm; border: 1px solid #dee2e6; color: #000; font-family: 'Times New Roman', serif;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle caption-top" style="font-size: 0.75rem; border-color: #000;">
                                <caption style="color: #000; font-weight: bold;">Передано (Таблица 3)</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="2">N п/п</th>
                                        <th rowspan="2">Наименование вида отхода</th>
                                        <th rowspan="2">Код по ФККО</th>
                                        <th rowspan="2">Класс опасности вида отхода</th>
                                        <th colspan="6">Количество переданных отходов за отчетный период, тонн</th>
                                        <th rowspan="2">Сведения о лицах, которым переданы отходы</th>
                                        <th rowspan="2">Дата и номер договора на передачу</th>
                                        <th rowspan="2">Срок действия договора</th>
                                        <th rowspan="2">Реквизиты лицензии на осуществление деятельности по сбору, транспортированию, обработке, утилизации, обезвреживанию, размещению отходов I-IV классов опасности</th>
                                    </tr>
                                    <tr>
                                        <th>всего</th>
                                        <th>для обработки</th>
                                        <th>для утилизации</th>
                                        <th>для обезвреживания</th>
                                        <th>для хранения</th>
                                        <th>для захоронения</th>
                                    </tr>
                                    <tr class="text-muted" style="font-size: 0.7rem;">
                                        <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th><th>8</th><th>9</th><th>10</th><th>11</th><th>12</th><th>13</th><th>14</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($journal->table3_data as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="text-start" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $item['waste'] }}">{{ $item['waste'] }}</td>
                                            <td>{{ $item['fkko'] }}</td>
                                            <td>{{ $item['hazard'] }}</td>
                                            <td class="fw-bold">{{ rtrim(rtrim(number_format($item['amount'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['amt_process'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['amt_util'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['amt_neutr'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['amt_store'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['amt_bury'] ?? 0, 3), '0'), '.') }}</td>
                                            <td class="text-start" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $item['counterparty'] }}">{{ $item['counterparty'] }}</td>
                                            <td>{{ $item['contract_details'] ?? '' }}</td>
                                            <td>{{ $item['contract_validity'] ?? '' }}</td>
                                            <td>{{ $item['license'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Table 4 -->
                <div class="tab-pane fade" id="sheet4">
                    <div class="bg-white p-4 mx-auto shadow-sm" style="max-width: 350mm; min-height: 210mm; border: 1px solid #dee2e6; color: #000; font-family: 'Times New Roman', serif;">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle caption-top" style="font-size: 0.75rem; border-color: #000;">
                                <caption style="color: #000; font-weight: bold;">Получено (Таблица 4)</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="3">N п/п</th>
                                        <th rowspan="3">Наименование вида отхода</th>
                                        <th rowspan="3">Код по ФККО</th>
                                        <th rowspan="3">Класс опасности вида отхода</th>
                                        <th colspan="7">Количество полученных отходов, тонн</th>
                                        <th rowspan="3">Сведения о лицах, от которых получены отходы</th>
                                        <th rowspan="3">Дата и номер договора на передачу отходов</th>
                                        <th rowspan="3">Срок действия договора</th>
                                        <th rowspan="3">Реквизиты лицензии на осуществление деятельности по сбору, транспортированию, обработке, утилизации, обезвреживанию, размещению отходов I-IV классов опасности</th>
                                    </tr>
                                    <tr>
                                        <th rowspan="2">всего</th>
                                        <th colspan="6">в том числе</th>
                                    </tr>
                                    <tr>
                                        <th style="min-width: 120px;">для накопления и последующей передачи другим индивидуальным предпринимателям и юридическим лицам</th>
                                        <th>для обработки</th>
                                        <th>для утилизации</th>
                                        <th>для обезвреживания</th>
                                        <th>для хранения</th>
                                        <th>для захоронения</th>
                                    </tr>
                                    <tr class="text-muted" style="font-size: 0.7rem;">
                                        <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th><th>8</th><th>9</th><th>10</th><th>11</th><th>12</th><th>13</th><th>14</th><th>15</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($journal->table4_data as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="text-start" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $item['waste'] }}">{{ $item['waste'] }}</td>
                                            <td>{{ $item['fkko'] }}</td>
                                            <td>{{ $item['hazard'] }}</td>
                                            <td class="fw-bold">{{ rtrim(rtrim(number_format($item['amount'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['amt_third_party'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['amt_process'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['amt_util'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['amt_neutr'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['amt_store'] ?? 0, 3), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($item['amt_bury'] ?? 0, 3), '0'), '.') }}</td>
                                            <td class="text-start" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $item['counterparty'] }}">{{ $item['counterparty'] }}</td>
                                            <td>{{ $item['contract_details'] ?? '' }}</td>
                                            <td>{{ $item['contract_validity'] ?? '' }}</td>
                                            <td>{{ $item['license'] ?? '' }}</td>
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