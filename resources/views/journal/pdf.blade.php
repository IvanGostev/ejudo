<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <title>Журнал Учета Движения Отходов</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        .page-break {
            page-break-after: always;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .text-left {
            text-align: left;
        }

        .no-border {
            border: none;
        }

        .title-page {
            text-align: center;
            margin-top: 100px;
        }

        .title-page h1 {
            font-size: 18px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .title-page p {
            font-size: 14px;
            margin: 10px 0;
        }

        .signature-block {
            margin-top: 50px;
            text-align: left;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 200px;
        }

        .header-info {
            margin-bottom: 20px;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <!-- Титульный лист -->
    <div class="title-page">
        <h1>ЖУРНАЛ УЧЕТА ДВИЖЕНИЯ ОТХОДОВ</h1>
        <p>за <strong>{{ $periodStr }}</strong></p>

        <br><br><br>
        <p style="text-align: left; width: 100%;">
            <strong>Наименование индивидуального предпринимателя или юридического лица:</strong>
            {{ $company->name }}<br>
        </p>

        <br><br><br><br>
        <div class="signature-block">
            <table class="no-border" style="width: 100%; border: none;">
                <tr class="no-border">
                    <td class="no-border text-left" style="width: 200px;">Руководитель организации<br>(индивидуальный
                        предприниматель)</td>
                    <td class="no-border" style="border-bottom: 1px solid black; width: 150px;"></td>
                    <td class="no-border" style="width: 20px;"></td>
                    <td class="no-border text-center" style="border-bottom: 1px solid black; width: 200px;">
                        {{ $company->contact_person ?? '________________' }}
                    </td>
                </tr>
                <tr class="no-border">
                    <td class="no-border"></td>
                    <td class="no-border" style="font-size: 8px; vertical-align: top; text-align: center;">(подпись)
                    </td>
                    <td class="no-border"></td>
                    <td class="no-border" style="font-size: 8px; vertical-align: top; text-align: center;">(Ф.И.О.)</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- Таблица 1 -->
    @if(!empty($table1))
        <h3>I. Данные о составе и физико-химических свойствах отходов</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">№ п/п</th>
                    <th>Наименование вида отхода</th>
                    <th>Код по ФККО</th>
                    <th>Класс опасности</th>
                    <th>Происхождение / Состав / Свойства</th>
                </tr>
            </thead>
            <tbody>
                @foreach($table1 as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="text-left">{{ $row['name'] ?? '-' }}</td>
                        <td>{{ $row['fkko'] ?? '-' }}</td>
                        <td>{{ $row['hazard'] ?? '-' }}</td>
                        <td>{{ $row['origin'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="page-break"></div>
    @endif

    <!-- Таблица 2 -->
    @if(!empty($table2))
        <h3>II. Данные об учете отходов (Обобщенные)</h3>
        <!-- Assuming standard 12-column structure -->
        <table>
            <thead>
                <tr>
                    <th rowspan="2">№ п/п</th>
                    <th rowspan="2">Наименование вида отхода</th>
                    <th rowspan="2">Код по ФККО</th>
                    <th rowspan="2">Класс опасности</th>
                    <th rowspan="2">Наличие на начало периода, т</th>
                    <th rowspan="2">Образовано, т</th>
                    <th rowspan="2">Получено от других лиц, т</th>
                    <th colspan="2">Использовано / Обезврежено, т</th>
                    <th rowspan="2">Хранение, т</th>
                    <th rowspan="2">Захоронение, т</th>
                    <th rowspan="2">Передано другим лицам, т</th>
                    <th rowspan="2">Наличие на конец периода, т</th>
                </tr>
                <tr>
                    <th>Утилизировано</th>
                    <th>Обезврежено</th>
                </tr>
                <tr>
                    <th style="font-size: 8px;">1</th>
                    <th style="font-size: 8px;">2</th>
                    <th style="font-size: 8px;">3</th>
                    <th style="font-size: 8px;">4</th>
                    <th style="font-size: 8px;">5</th>
                    <th style="font-size: 8px;">6</th>
                    <th style="font-size: 8px;">7</th>
                    <th style="font-size: 8px;">8</th>
                    <th style="font-size: 8px;">9</th>
                    <th style="font-size: 8px;">10</th>
                    <th style="font-size: 8px;">11</th>
                    <th style="font-size: 8px;">12</th>
                    <th style="font-size: 8px;">13</th>
                </tr>
            </thead>
            <tbody>
                @foreach($table2 as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="text-left">{{ $row['name'] }}</td>
                        <td>{{ $row['fkko'] }}</td>
                        <td>{{ $row['hazard'] }}</td>
                        <td>{{ $row['balance_begin'] }}</td>
                        <td>{{ $row['generated'] }}</td>
                        <td>{{ $row['received'] }}</td>
                        <td>{{ $row['utilized'] }}</td>
                        <td>{{ $row['neutralized'] }}</td>
                        <td>0</td> <!-- Storage -->
                        <td>{{ $row['buried'] }}</td>
                        <td>{{ $row['transferred'] }}</td>
                        <td>{{ $row['balance_end'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="page-break"></div>
    @endif

    <!-- Таблица 3 (Переданные) -->
    @if(!empty($table3))
        <h3>III. Данные об отходах, переданных другим лицам</h3>
        <table>
            <thead>
                <tr>
                    <th rowspan="2">№ п/п</th>
                    <th rowspan="2">Дата</th>
                    <th rowspan="2">Номер акта</th>
                    <th rowspan="2">Наименование отхода</th>
                    <th rowspan="2">Код ФККО</th>
                    <th rowspan="2">Класс</th>
                    <th rowspan="2">Количество, т</th>
                    <th colspan="5">Цель передачи (Количество, т)</th>
                    <th rowspan="2">Контрагент (Наименование, ИНН)</th>
                </tr>
                <tr>
                    <th>Обработка</th>
                    <th>Утилизация</th>
                    <th>Обезвреж.</th>
                    <th>Хранение</th>
                    <th>Захор.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($table3 as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($row['date'])->format('d.m.Y') }}</td>
                        <td>{{ $row['number'] }}</td>
                        <td class="text-left">{{ $row['waste'] }}</td>
                        <td>{{ $row['fkko'] }}</td>
                        <td>{{ $row['hazard'] }}</td>
                        <td>{{ $row['amount'] }}</td>
                        <td>{{ $row['p_process'] == 0 || $row['p_process'] == '-' ? '-' : $row['p_process'] }}</td>
                        <td>{{ $row['p_util'] == 0 || $row['p_util'] == '-' ? '-' : $row['p_util'] }}</td>
                        <td>{{ $row['p_neutr'] == 0 || $row['p_neutr'] == '-' ? '-' : $row['p_neutr'] }}</td>
                        <td>{{ $row['p_store'] == 0 || $row['p_store'] == '-' ? '-' : $row['p_store'] }}</td>
                        <td>{{ $row['p_bury'] == 0 || $row['p_bury'] == '-' ? '-' : $row['p_bury'] }}</td>
                        <td class="text-left">{{ $row['counterparty'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="page-break"></div>
    @endif

    <!-- Таблица 4 (Полученные) -->
    @if(!empty($table4))
        <h3>IV. Данные об отходах, полученных от других лиц</h3>
        <table>
            <thead>
                <tr>
                    <th rowspan="2">№ п/п</th>
                    <th rowspan="2">Дата</th>
                    <th rowspan="2">Номер акта</th>
                    <th rowspan="2">Наименование отхода</th>
                    <th rowspan="2">Код ФККО</th>
                    <th rowspan="2">Класс</th>
                    <th rowspan="2">Количество, т</th>
                    <th colspan="3">Цель приема (Количество, т)</th>
                    <th rowspan="2">Контрагент (Наименование, ИНН)</th>
                </tr>
                <tr>
                    <th>Обработка</th>
                    <th>Утилизация</th>
                    <th>Обезвреж.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($table4 as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($row['date'])->format('d.m.Y') }}</td>
                        <td>{{ $row['number'] }}</td>
                        <td class="text-left">{{ $row['waste'] }}</td>
                        <td>{{ $row['fkko'] }}</td>
                        <td>{{ $row['hazard'] }}</td>
                        <td>{{ $row['amount'] }}</td>
                        <td>{{ $row['p_process'] == 0 || $row['p_process'] == '-' ? '-' : $row['p_process'] }}</td>
                        <td>{{ $row['p_util'] == 0 || $row['p_util'] == '-' ? '-' : $row['p_util'] }}</td>
                        <td>{{ $row['p_neutr'] == 0 || $row['p_neutr'] == '-' ? '-' : $row['p_neutr'] }}</td>
                        <td class="text-left">{{ $row['counterparty'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</body>

</html>