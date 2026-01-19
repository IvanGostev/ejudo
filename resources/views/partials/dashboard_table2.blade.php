<table class="table table-bordered table-striped" id="table2-content">
    <thead>
        <tr>
            <th>Наименование отхода</th>
            <th>Образовано (т)</th>
            <th>Передано (т)</th>
            <th>Получено (т)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($wasteComposition as $waste)
            @php
                $transferredQty = $transferred->where('waste', $waste['name'])->sum('amount');
                $receivedQty = $received->where('waste', $waste['name'])->sum('amount');
            @endphp
            <tr>
                <td>{{ $waste['name'] }}</td>
                <td>0.000</td>
                <td>{{ number_format($transferredQty, 3) }}</td>
                <td>{{ number_format($receivedQty, 3) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center text-muted">Данные отсутствуют. Загрузите акты.</td>
            </tr>
        @endforelse
    </tbody>
</table>