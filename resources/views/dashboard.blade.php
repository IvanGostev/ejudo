@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Загрузка Актов</h4>
        <div class="d-flex">
            <div class="dropdown me-2">
                <button class="btn btn-outline-black dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-building me-1"></i>
                    {{ isset($company) ? $company->name : 'Выберите компанию' }}
                </button>
                <ul class="dropdown-menu">
                    @if(isset($userCompanies) && $userCompanies->count() > 0)
                        @foreach($userCompanies as $uComp)
                            <li>
                                <a class="dropdown-item {{ (isset($company) && $company->id === $uComp->id) ? 'active' : '' }}"
                                    href="#"
                                    onclick="event.preventDefault(); document.getElementById('switch-company-{{ $uComp->id }}').submit();">
                                    {{ $uComp->name }}
                                </a>
                                <form id="switch-company-{{ $uComp->id }}" action="{{ route('companies.switch', $uComp->id) }}"
                                    method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        @endforeach
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                    @endif
                    <li><a class="dropdown-item" href="{{ route('companies.create') }}"><i
                                class="bi bi-plus-lg me-1"></i>Добавить новую</a></li>
                </ul>
            </div>

            <div class="dropdown">
                <button class="btn btn-outline-black dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Период:
                    {{ $selectedPeriod === 'all' ? 'За все время' : ($periods[$selectedPeriod] ?? $selectedPeriod) }}
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item {{ $selectedPeriod === 'all' ? 'active' : '' }}"
                            href="{{ route('dashboard', ['period' => 'all']) }}">За все время</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    @foreach($periods as $key => $label)
                        <li><a class="dropdown-item {{ $selectedPeriod === $key ? 'active' : '' }}"
                                href="{{ route('dashboard', ['period' => $key]) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- Drag & Drop Zone -->
    <!-- Drag & Drop Zone -->
    @if(isset($company))
        <div class="card border-dashed mb-4" id="drop-zone"
            style="border: 2px dashed #ccc; background-color: #f9f9f9; transition: background 0.3s;">
            <div class="card-body text-center py-5">
                <i class="bi bi-cloud-upload display-4 text-muted mb-3"></i>
                <h5 class="text-muted">Перетащите Акты (doc, docx) сюда</h5>
                <p class="small text-muted mb-3">или нажмите кнопку для выбора файлов</p>
                <button class="btn btn-primary" onclick="$('#file-input').click()">Загрузить Акты</button>
                <input type="file" id="file-input" class="d-none" multiple accept=".doc,.docx">
                <div id="file-list" class="mt-3"></div>
            </div>
        </div>
    @else
        <div class="alert text-center py-5 mb-4 shadow-sm" style="background-color: #212529; color: #fff; border: none;">
            <i class="bi bi-exclamation-triangle display-4 text-warning mb-3"></i>
            <h4 class="alert-heading fw-bold">Внимание!</h4>
            <p class="lead mb-0">Выберите компанию чтобы продолжить</p>
        </div>
    @endif

    <!-- Tables Tabs -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#table1">1. Состав отходов</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#table2">2. Обобщенные данные</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#table3">3. Переданные</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#table4">4. Полученные</button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content pt-3">
                <!-- Table 1: Состав отходов -->
                <div class="tab-pane fade show active" id="table1">
                    <table class="table table-bordered table-striped">
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
                </div>

                <!-- Table 2: Обобщенные данные -->
                <div class="tab-pane fade" id="table2">
                    <table class="table table-bordered table-striped">
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
                                    <td colspan="4" class="text-center text-muted">Нет данных</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Table 3: Переданные -->
                <div class="tab-pane fade" id="table3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Номер акта</th>
                                <th>Контрагент (Получатель)</th>
                                <th>Отход</th>
                                <th>Количество (т)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transferred as $item)
                                <tr>
                                    <td>{{ rescue(fn() => \Carbon\Carbon::parse($item['date'])->format('d.m.Y'), $item['date'], false) }}
                                    </td>
                                    <td>&#8470;	{{ $item['number'] }}</td>
                                    <td>{{ $item['counterparty'] }}</td>
                                    <td>{{ $item['waste'] }}</td>
                                    <td><strong>{{ number_format($item['amount'], 3) }}</strong></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Нет данных о передаче</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Table 4: Полученные -->
                <div class="tab-pane fade" id="table4">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Номер акта</th>
                                <th>Контрагент (Поставщик)</th>
                                <th>Отход</th>
                                <th>Количество (т)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($received as $item)
                                <tr>
                                    <td>{{ rescue(fn() => \Carbon\Carbon::parse($item['date'])->format('d.m.Y'), $item['date'], false) }}
                                    </td>
                                    <td>&#8470;{{ $item['number'] }}</td>
                                    <td>{{ $item['counterparty'] }}</td>
                                    <td>{{ $item['waste'] }}</td>
                                    <td><strong>{{ number_format($item['amount'], 3) }}</strong></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Нет данных о получении</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const dropZone = $('#drop-zone');

            dropZone.on('dragover', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).css('background-color', '#e9ecef');
            });

            dropZone.on('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).css('background-color', '#f9f9f9');
            });

            dropZone.on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).css('background-color', '#f9f9f9');

                const files = e.originalEvent.dataTransfer.files;
                handleFiles(files);
            });

            $('#file-input').on('change', function () {
                handleFiles(this.files);
            });

            function handleFiles(files) {
                if (files.length > 0) {
                    const formData = new FormData();
                    // Append files
                    for (let i = 0; i < files.length; i++) {
                        formData.append('files[]', files[i]);
                    }

                    $('#file-list').html('<div class="alert alert-info py-2"><i class="spinner-border spinner-border-sm me-2"></i>Обработка и распознавание ' + files.length + ' файлов...</div>');

                    $.ajax({
                        url: '{{ route('acts.store') }}',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            if (response.processed.length > 0) {
                                // Reload to update tables
                                location.reload();
                            } else {
                                // Show errors if only errors occurred
                                let html = '';
                                response.errors.forEach(err => {
                                    html += '<div class="alert alert-danger py-2 mb-1"><i class="bi bi-exclamation-triangle me-2"></i>' + err + '</div>';
                                });
                                $('#file-list').html(html);
                            }
                        },
                        error: function (xhr) {
                            const msg = xhr.responseJSON?.message || 'Ошибка загрузки';
                            $('#file-list').html('<div class="alert alert-danger py-2">' + msg + '</div>');
                        }
                    });
                }
            }
        });
    </script>
@endpush