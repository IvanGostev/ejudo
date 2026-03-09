@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">
                Формирование ЖУДО
                @if($selectedPolygon)
                    <span class="badge ms-2 fw-normal" style="background:rgba(255,76,43,0.12);color:#FF4C2B;font-size:0.75rem;">
                        <i class="bi bi-geo-alt-fill me-1"></i>{{ $selectedPolygon->name }}
                    </span>
                @endif
            </h4>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createJournalModal">
            <i class="bi bi-plus-lg me-1"></i> Сформировать
        </button>
    </div>

    {{-- Фильтр по полигону (показывается только если есть полигоны) --}}
    @if($hasPolygons && $polygons->isNotEmpty())
        <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
            <span class="text-muted small me-1">Полигон:</span>
            <a href="{{ route('journal.index') }}"
               class="btn btn-sm {{ !$selectedPolygon ? 'btn-dark' : 'btn-outline-secondary' }}">
                Все
            </a>
            @foreach($polygons as $polygon)
                <a href="{{ route('journal.index', ['polygon_id' => $polygon->id]) }}"
                   class="btn btn-sm {{ $selectedPolygon?->id == $polygon->id ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-geo-alt me-1"></i>{{ $polygon->name }}
                </a>
            @endforeach
        </div>
    @endif

    {{-- Предупреждение о непривязанных записях (режим полигонов) --}}
    @if($hasPolygons)
        @php
            $company = app(\App\Services\TenantService::class)->getCompany();
            $unassigned = $company ? \App\Models\JudoJournal::where('company_id', $company->id)->whereNull('polygon_id')->exists() : false;
        @endphp
        @if($unassigned)
            <div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
                <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
                <div>
                    Найдены записи без привязки к полигону. Рекомендуем распределить их по полигонам для более точного учёта.
                </div>
            </div>
        @endif
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Период</th>
                            <th>Компания</th>
                            @if($hasPolygons)
                                <th>Полигон</th>
                            @endif
                            <th>Дата создания</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($journals as $journal)
                            <tr>
                                <td>
                                    @if($journal->type === 'year')
                                        {{ \Carbon\Carbon::parse($journal->period)->year }} год
                                    @elseif($journal->type === 'quarter')
                                        {{ ceil(\Carbon\Carbon::parse($journal->period)->month / 3) }} квартал
                                        {{ \Carbon\Carbon::parse($journal->period)->year }}
                                    @else
                                        {{ \Carbon\Carbon::parse($journal->period)->translatedFormat('F Y') }}
                                    @endif
                                </td>
                                <td>{{ $journal->company->name ?? '-' }}</td>
                                @if($hasPolygons)
                                    <td>
                                        @if($journal->polygon)
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                                {{ $journal->polygon->name }}
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                @endif
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
                                <td colspan="{{ $hasPolygons ? 5 : 4 }}" class="text-center text-muted py-5">
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('journal.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="period" id="selectedPeriodInput" value="{{ date('Y-m') }}">

                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Сформировать журнал</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body pt-2">
                        <p class="text-muted small mb-4">Выберите отчетный период для формирования журнала. Акты, попадающие
                            в выбранный период, будут автоматически включены в отчет.</p>

                        {{-- Выбор полигона (только в режиме полигонов) --}}
                        @if($hasPolygons)
                            <div class="mb-4">
                                <label class="form-label fw-medium">
                                    Полигон <span class="text-danger">*</span>
                                </label>
                                @if($polygons->isEmpty())
                                    <div class="alert alert-warning mb-0 py-2">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Нет активных полигонов. <a href="{{ route('polygons.create') }}">Добавьте полигон</a>.
                                    </div>
                                @else
                                    <select class="form-select" name="polygon_id" required>
                                        <option value="">— Выберите полигон —</option>
                                        @foreach($polygons as $polygon)
                                            <option value="{{ $polygon->id }}">{{ $polygon->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Журнал будет сформирован для выбранного объекта размещения отходов.</div>
                                @endif
                            </div>
                        @endif

                        <div class="card border-0 shadow-sm bg-light">
                            <div class="card-body p-3">
                                <div class="row g-0">
                                    <!-- Years -->
                                    <div class="col-3 border-end pe-3">
                                        <h6 class="text-uppercase text-secondary fw-bold x-small mb-3 ps-1"
                                            style="font-size: 0.75rem;">
                                            Годы
                                        </h6>
                                        <div class="d-grid gap-1">
                                            @foreach($periods as $key => $label)
                                                @if(strlen((string) $key) === 4 && is_numeric($key))
                                                    <button type="button"
                                                        class="btn btn-sm text-start period-btn {{ date('Y') == $key ? 'btn-dark text-white' : 'btn-white text-dark border-0' }}"
                                                        data-value="{{ $key }}" onclick="selectPeriod(this)">
                                                        {{ $label }}
                                                    </button>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Quarters -->
                                    <div class="col-4 border-end px-3">
                                        <h6 class="text-uppercase text-secondary fw-bold x-small mb-3 ps-1"
                                            style="font-size: 0.75rem;">
                                            Кварталы
                                        </h6>
                                        <div class="d-grid gap-1">
                                            @foreach($periods as $key => $label)
                                                @if(str_contains($key, '-Q'))
                                                    <button type="button"
                                                        class="btn btn-sm text-start period-btn btn-white text-dark border-0"
                                                        data-value="{{ $key }}" onclick="selectPeriod(this)">
                                                        {{ $label }}
                                                    </button>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Months -->
                                    <div class="col-5 ps-3">
                                        <h6 class="text-uppercase text-secondary fw-bold x-small mb-3 ps-1"
                                            style="font-size: 0.75rem;">
                                            Месяцы
                                        </h6>
                                        <div class="row g-1">
                                            @foreach($periods as $key => $label)
                                                @if(strlen((string) $key) === 7 && str_contains($key, '-') && !str_contains($key, 'Q'))
                                                    <div class="col-6">
                                                        <button type="button"
                                                            class="btn btn-sm w-100 text-start text-truncate period-btn {{ date('Y-m') == $key ? 'btn-dark text-white' : 'btn-white text-dark border-0' }}"
                                                            data-value="{{ $key }}" title="{{ $label }}"
                                                            onclick="selectPeriod(this)">
                                                            {{ $label }}
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-end text-muted small">
                            Выбран период: <span id="selectedPeriodLabel"
                                class="fw-bold text-dark">{{ $periods[date('Y-m')] ?? date('Y-m') }}</span>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light text-muted" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary px-4">Сформировать</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function selectPeriod(btn) {
            const value = btn.getAttribute('data-value');
            document.getElementById('selectedPeriodInput').value = value;

            document.querySelectorAll('.period-btn').forEach(b => {
                b.classList.remove('btn-dark', 'text-white');
                b.classList.add('btn-white', 'text-dark', 'border-0');
                b.style.backgroundColor = '';
                b.style.borderColor = '';
            });

            btn.classList.remove('btn-white', 'text-dark', 'border-0');
            btn.classList.add('btn-dark', 'text-white');
            btn.style.backgroundColor = '#000218';
            btn.style.borderColor = '#000218';

            document.getElementById('selectedPeriodLabel').innerText = btn.innerText.trim();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const currentVal = document.getElementById('selectedPeriodInput').value;
            const activeBtn = document.querySelector(`.period-btn[data-value="${currentVal}"]`);
            if (activeBtn) {
                selectPeriod(activeBtn);
            }
        });
    </script>

    <style>
        .btn-white {
            background-color: #fff;
        }

        .btn-white:hover {
            background-color: #f8f9fa;
        }
    </style>
@endsection