@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Мои Компании</h4>
        <a href="{{ route('companies.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Добавить компанию
        </a>
    </div>

    @if($companies->isEmpty())
        <div class="text-center py-5 bg-white rounded shadow-sm">
            <i class="bi bi-building display-4 text-muted mb-3"></i>
            <h5 class="text-muted">Нет добавленных компаний</h5>
            <p class="text-muted mb-4">Добавьте данные вашей организации для автоматического заполнения документов.</p>
            <a href="{{ route('companies.create') }}" class="btn btn-outline-primary">Создать компанию</a>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Наименование</th>
                                <th>ИНН</th>
                                <th>Тип</th>
                                <th>Адрес</th>
                                <th>Телефон</th>
                                <th class="text-end pe-4">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($companies as $company)
                                <tr>
                                    <td class="ps-4 fw-medium">{{ $company->name }}</td>
                                    <td>{{ $company->inn }}</td>
                                    <td><span class="badge bg-secondary">{{ $company->type }}</span></td>
                                    <td class="text-truncate" style="max-width: 250px;" title="{{ $company->legal_address }}">
                                        {{ $company->legal_address }}
                                    </td>
                                    <td>{{ $company->phone ?? '-' }}</td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-sm btn-primary text-white">
                                            <i class="bi bi-pencil me-1"></i>Редактировать
                                        </a>
                                        <form action="{{ route('companies.destroy', $company->id) }}" method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить эту компанию?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-dark" style="background-color: #000218; border-color: #000218;">
                                                <i class="bi bi-trash me-1"></i>Удалить
                                            </button>
                                        </form>
                                    </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection