@extends('layouts.app')

@section('content')
    <style>
        .profile-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 15px;
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .gradient-balance {
            background: linear-gradient(135deg, #FF4C2B 0%, #FF8C76 100%);
            color: white;
        }

        .gradient-referrals {
            background: linear-gradient(135deg, #0A1F32 0%, #1A3A5A 100%);
            color: white;
        }

        .ref-link-input {
            background-color: #f8f9fa;
            border: 1px dashed #FF4C2B;
            font-family: monospace;
        }

        .history-card {
            border-radius: 15px;
            overflow: hidden;
        }

        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            opacity: 0.8;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            background: #FF4C2B;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(255, 76, 43, 0.3);
        }
    </style>

    <div class="container-fluid py-4">
        <div class="row align-items-center mb-4">
            <div class="col-12">
                <h2 class="fw-bold text-judo-dark mb-0">Личный кабинет</h2>
                <p class="text-muted">Управление профилем и реферальной программой</p>
            </div>
        </div>

        <div class="row">
            <!-- Left: User Details -->
            <div class="col-lg-4 mb-4">
                <div class="card profile-card shadow-sm border-0 h-100">
                    <div class="card-body p-4 text-center">
                        <div class="d-flex justify-content-center">
                            <div class="user-avatar">
                                {{ strtoupper(substr($user->email, 0, 1)) }}
                            </div>
                        </div>
                        <h5 class="fw-bold mb-1">{{ explode('@', $user->email)[0] }}</h5>
                        <p class="text-muted small mb-4">{{ $user->email }}</p>

                        <hr class="my-4 opacity-50">

                        <div class="text-start">
                        <div class="mb-4">
                            <label class="stat-label d-block text-muted mb-1">Номер телефона</label>
                            <div class="fw-bold text-dark d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="bi bi-telephone me-2 text-primary"></i>
                                    {{ $user->phone ?? 'Не привязан' }}
                                </div>
                                <button type="button" class="btn btn-link btn-sm text-primary p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    Изменить
                                </button>
                            </div>
                        </div>

                            <div class="mb-0">
                                <label class="stat-label d-block text-muted mb-1">Подписка</label>
                                @php
                                    $isSubscribed = $user->subscription_ends_at && $user->subscription_ends_at->isFuture();
                                @endphp
                                <div class="d-flex align-items-center">
                                    @if($isSubscribed)
                                        <div
                                            class="p-2 bg-success-subtle text-success rounded-3 d-flex align-items-center w-100">
                                            <i class="bi bi-patch-check-fill me-2 fs-5"></i>
                                            <div>
                                                <div class="fw-bold small">Активна</div>
                                                <div class="text-dark small" style="font-size: 0.7rem;">до
                                                    {{ $user->subscription_ends_at->format('d.m.Y') }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <div
                                            class="p-2 bg-light border rounded-3 d-flex align-items-center w-100 justify-content-between">
                                            <div class="d-flex align-items-center text-muted">
                                                <i class="bi bi-x-circle me-2 fs-5"></i>
                                                <span class="fw-bold small">Не активна</span>
                                            </div>
                                            <a href="{{ route('subscription.index') }}"
                                                class="btn btn-sm btn-primary py-1 px-3">Купить</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Referral Stats & Actions -->
            <div class="col-lg-8">
                <div class="row g-4 mb-4">
                    <!-- Balance Card -->
                    <div class="col-md-6">
                        <div class="card profile-card border-0 shadow-sm gradient-balance h-100">
                            <div class="card-body p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <i class="bi bi-wallet2 fs-3 mb-3 d-block"></i>
                                    <label class="stat-label opacity-75">Ваш баланс</label>
                                    <h2 class="fw-bold mb-0">{{ number_format($user->referral_balance, 2, ',', ' ') }} ₽
                                    </h2>
                                </div>
                                <div class="mt-4">
                                    <button type="button"
                                        class="btn btn-outline-light btn-sm border-0 bg-white bg-opacity-10 w-100 text-white"
                                        data-bs-toggle="modal" data-bs-target="#withdrawModal">
                                        <i class="bi bi-arrow-up-right me-1"></i> Вывести средства
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Referral Count Card -->
                    <div class="col-md-6">
                        <div class="card profile-card border-0 shadow-sm gradient-referrals h-100">
                            <div class="card-body p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <i class="bi bi-people fs-3 mb-3 d-block"></i>
                                    <label class="stat-label opacity-75">Реферальная программа</label>
                                    <h2 class="fw-bold mb-0">{{ $referralCount }} человек</h2>
                                </div>
                                <div class="mt-4">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control bg-white bg-opacity-10 border-0 text-white"
                                            id="refLink" value="{{ url('/login?ref=' . $user->referral_code) }}" readonly>
                                        <button class="btn btn-light px-3" type="button" onclick="copyRefLink()">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                    <div class="small opacity-50 mt-1">Скопируйте ссылку для приглашения</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Last Earnings -->
                    <div class="col-md-6">
                        <div class="card profile-card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <h6 class="fw-bold mb-4 d-flex align-items-center">
                                    <i class="bi bi-journal-text me-2 text-primary"></i> Последние бонусы
                                </h6>
                                <div class="list-group list-group-flush">
                                    @forelse($referralEarnings as $earning)
                                        <div
                                            class="list-group-item px-0 py-3 border-light d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold small mb-1">{{ $earning->referral->email }}</div>
                                                <div class="text-muted" style="font-size: 0.7rem;">
                                                    {{ $earning->created_at->translatedFormat('d F Y') }}</div>
                                            </div>
                                            <div class="text-end">
                                                <span
                                                    class="text-success fw-bold small">+{{ number_format($earning->amount, 2, ',', ' ') }}
                                                    ₽</span>
                                                <div class="text-muted" style="font-size: 0.65rem;">Комиссия
                                                    {{ (int) $earning->percent }}%</div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-4">
                                            <i class="bi bi-cloud-slash fs-2 text-muted opacity-25 d-block mb-2"></i>
                                            <div class="text-muted small">У вас пока нет начислений</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payout Status -->
                    <div class="col-md-6">
                        <div class="card profile-card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <h6 class="fw-bold mb-4 d-flex align-items-center">
                                    <i class="bi bi-clock-history me-2 text-primary"></i> История выплат
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-borderless align-middle mb-0">
                                        <tbody>
                                            @forelse($referralPayouts as $payout)
                                                <tr class="border-bottom border-light">
                                                    <td class="ps-0 py-3">
                                                        <div class="fw-bold small">
                                                            {{ number_format($payout->amount, 2, ',', ' ') }} ₽</div>
                                                        <div class="text-muted mt-1" style="font-size: 0.7rem;">
                                                            {{ $payout->created_at->format('d.m.y H:i') }}</div>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge rounded-pill fw-normal px-3 py-1
                                                                @if($payout->status === 'pending') bg-warning-subtle text-warning @elseif($payout->status === 'completed') bg-success-subtle text-success @else bg-danger-subtle text-danger @endif">
                                                            @if($payout->status === 'pending') В обработке
                                                            @elseif($payout->status === 'completed') Выплачено @else Отклонено
                                                            @endif
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center py-4">
                                                        <div class="text-muted small">Нет недавних выплат</div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Withdraw Modal -->
    <div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold">Запрос выплаты</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('profile.withdraw') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4 pt-0">
                        <div class="alert bg-light border-0 small mb-4">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            Минимальная сумма вывода — 100 ₽. Срок обработки заявки: 1-3 рабочих дня.
                        </div>

                        <div class="mb-3">
                            <label class="p-0 mb-1 stat-label text-muted">Сумма вывода</label>
                            <div class="input-group">
                                <input type="number" name="amount"
                                    class="form-control form-control-lg border-light bg-light fw-bold" min="100"
                                    max="{{ $user->referral_balance }}" placeholder="0.00" required>
                                <span class="input-group-text border-light bg-light fw-bold">₽</span>
                            </div>
                            <div class="text-end mt-1 small text-muted">Доступно:
                                {{ number_format($user->referral_balance, 2, ',', ' ') }} ₽</div>
                        </div>

                        <div class="mb-3">
                            <label class="p-0 mb-1 stat-label text-muted">Способ получения</label>
                            <select name="payment_method" class="form-select form-select-lg border-light bg-light small"
                                style="font-size: 0.9rem;" required>
                                <option value="SBP">Система быстрых платежей (СБП)</option>
                                <option value="Card">Банковская карта (РФ)</option>
                                <option value="Other">Иной способ</option>
                            </select>
                        </div>

                        <div class="mb-0">
                            <label class="p-0 mb-1 stat-label text-muted">Реквизиты</label>
                            <input type="text" name="payment_details"
                                class="form-control form-control-lg border-light bg-light small" style="font-size: 0.9rem;"
                                placeholder="Например: +79001234567 или номер карты" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary px-4 shadow" {{ $user->referral_balance < 100 ? 'disabled' : '' }}>Создать заявку</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4">
                <h5 class="modal-title fw-bold">Редактировать профиль</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                <div class="modal-body p-4 pt-0">
                    <div class="mb-0">
                        <label class="p-0 mb-1 stat-label text-muted">Номер телефона</label>
                        <input type="text" name="phone" class="form-control form-control-lg border-light bg-light small" style="font-size: 0.9rem;" value="{{ $user->phone }}" placeholder="+7 (___) ___-__-__" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary px-4 shadow">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
        function copyRefLink() {
            var copyText = document.getElementById("refLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);

            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2"></i>';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-light');

            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-light');
            }, 2000);
        }
    </script>
@endsection