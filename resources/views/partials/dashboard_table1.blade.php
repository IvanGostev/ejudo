<table class="table table-bordered table-striped" id="table1-content">
    <thead>
        <tr>
            <th>Наименование отхода</th>
            <th>Код ФККО</th>
            <th>Класс опасности</th>
        </tr>
    </thead>
    <tbody>
        @forelse($wasteComposition as $waste)
            <tr>
                <td>{{ $waste['name'] }}</td>
                <td class="text-nowrap">{{ $waste['code'] }}</td>
                <td>{{ $waste['hazard_class'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center text-muted">Данные отсутствуют. Загрузите акты.</td>
            </tr>
        @endforelse
    </tbody>
</table>