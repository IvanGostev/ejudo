@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Справочник контрагентов</h4>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control" placeholder="Поиск по названию или ИНН..."
                value="{{ request('q') }}" style="max-width: 400px;">
            <button type="submit" class="btn btn-primary">Найти</button>
            @if(request('q'))
                <a href="{{ route('counterparties.index') }}" class="btn btn-outline-secondary">Сбросить</a>
            @endif
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Наименование</th>
                        <th>ИНН</th>
                        <th>КПП</th>
                        <th>Юр. адрес</th>
                        <th>Лицензия</th>
                        <th>Срок лицензии</th>
                        <th width="60"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($counterparties as $cp)
                        <tr>
                            <td class="fw-medium">{{ $cp->name }}</td>
                            <td>{{ $cp->inn ?? '—' }}</td>
                            <td>{{ $cp->kpp ?? '—' }}</td>
                            <td>{{ $cp->legal_address ?? '—' }}</td>
                            <td>{{ $cp->license_number ?? '—' }}</td>
                            <td>{{ $cp->license_valid_until?->format('d.m.Y') ?? '—' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger delete-cp" data-id="{{ $cp->id }}" title="Удалить">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <div class="mb-2"><i class="bi bi-people display-4 opacity-25"></i></div>
                                Справочник пуст. Контрагенты добавляются при создании актов.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($counterparties->hasPages())
    <div class="mt-4">{{ $counterparties->links() }}</div>
@endif
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.delete-cp').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!confirm('Удалить контрагента из справочника?')) return;
            const id = this.dataset.id;
            fetch('/counterparties/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(r => r.json()).then(data => {
                if (data.success) this.closest('tr').remove();
            });
        });
    });
</script>
@endpush
