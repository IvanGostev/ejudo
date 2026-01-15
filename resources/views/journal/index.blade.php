@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Формирование ЖУДО</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createJournalModal">
            <i class="bi bi-plus-lg me-1"></i> Сформировать
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Период</th>
                            <th>Компания</th>
                            <th>Роль</th>
                            <th>Дата создания</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($journals as $journal)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($journal->period)->translatedFormat('F Y') }}</td>
                                <td>{{ $journal->company->name ?? '-' }}</td>
                                <td>{{ $journal->role === 'waste_processor' ? 'Переработчик' : 'Отходообразователь' }}</td>
                                <td>{{ $journal->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('journal.show', $journal->id) }}"
                                            class="btn btn-sm btn-primary text-white">
                                            <i class="bi bi-eye me-1"></i> Посмотреть
                                        </a>
                                        <form action="{{ route('journal.destroy', $journal->id) }}" method="POST"
                                            onsubmit="return confirm('Вы уверены, что хотите удалить этот журнал?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-dark"
                                                style="background-color: #000218; border-color: #000218;">
                                                <i class="bi bi-trash me-1"></i> Удалить
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <div class="mb-3"><i class="bi bi-journal-text display-4 opacity-50"></i></div>
                                    Журналы еще не сформированы.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Journal Modal -->
    <div class="modal fade" id="createJournalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('journal.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Сформировать журнал</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="period" class="form-label">Выберите период</label>
                            <input type="month" class="form-control" id="period" name="period" required
                                value="{{ date('Y-m') }}">
                        </div>
                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle me-1"></i>
                            Журнал будет сформирован на основе загруженных актов за выбранный месяц.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сформировать</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection