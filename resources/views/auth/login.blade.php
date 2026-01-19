@extends('layouts.auth')

@section('content')
    <div class="card auth-card">
        <div class="auth-header">
            <h4>Вход в {{ config('app.name') }}</h4>
        </div>
        <div class="card-body p-4">
            <div id="alert-container"></div>

            <form id="auth-form">
                @csrf
                <div class="mb-3">
                    <label for="phone" class="form-label">Номер телефона</label>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="+7 (999) 000-00-00"
                        required>
                </div>

                <div class="mb-3 d-none" id="code-group">
                    <label for="code" class="form-label">Код из СМС</label>
                    <input type="text" class="form-control" id="code" name="code" placeholder="1234" maxlength="4">
                </div>

                <button type="submit" class="btn btn-primary w-100" id="submit-btn">Получить код</button>
            </form>

            <div class="mt-3 text-center">
                <small class="text-muted">Первый раз? Аккаунт будет создан автоматически.</small>
            </div>

            <hr class="my-4">

            <div class="mb-3">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle me-1"></i>О сервисе</h6>
                <p class="small text-muted mb-0" style="line-height: 1.4;">
                    Сервис для автоматического формирования Журналов учета движения отходов (ЖУДО).
                    Просто загрузите акты выполненных работ, и система сама распознает данные и заполнит таблицы.
                </p>
            </div>

            <div>
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-headset me-1"></i>Поддержка</h6>
                <p class="small text-muted mb-0">
                    <a href="mailto:support@ejudo.ru" class="text-decoration-none text-muted"><i
                            class="bi bi-envelope me-1"></i>support@ejudo.ru</a><br>
                    <a href="tel:+79991234567" class="text-decoration-none text-muted"><i
                            class="bi bi-telephone me-1"></i>+7 (999) 123-45-67</a>
                </p>
            </div>

            <div class="mt-4 text-center">
                <a href="#" class="text-decoration-none text-muted small" data-bs-toggle="modal" data-bs-target="#devModal">
                    <i class="bi bi-code-slash me-1"></i>Разработка сайта
                </a>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="devModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Разработка сайта</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Данный сайт разработал Иван Гостев. Создаю сайты любой сложности и любого типа. Буду рад обсудить ваш
                        проект. Пишите для сотрудничества.</p>
                </div>
                <div class="modal-footer">
                    <a href="https://t.me/ivangostevdeveloper" target="_blank" class="btn btn-primary">
                        <i class="bi bi-telegram me-1"></i>Связаться
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script type="module">
        $(document).ready(function () {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            let step = 'send'; // send or verify

            $('#auth-form').on('submit', function (e) {
                e.preventDefault();

                const phone = $('#phone').val();
                const code = $('#code').val();

                if (step === 'send') {
                    if (phone.length < 10) {
                        alert('Введите корректный номер телефона');
                        return;
                    }

                    $.post('{{ route('auth.send-code') }}', {
                        phone: phone
                    })
                        .done(function (res) {
                            step = 'verify';
                            $('#code-group').removeClass('d-none');
                            $('#submit-btn').text('Войти');
                            // $('#phone').prop('readonly', true);
                            if (res.debug_code) {
                                alert('DEBUG CODE: ' + res.debug_code);
                            }
                            $('#alert-container').html('<div class="alert alert-success">Код отправлен!</div>');
                        })
                        .fail(function (err) {
                            const msg = err.responseJSON?.message || 'Ошибка отправки СМС';
                            $('#alert-container').html('<div class="alert alert-danger">' + msg + '</div>');
                        });
                } else {
                    if (code.length < 4) {
                        alert('Введите код');
                        return;
                    }

                    $.post('{{ route('auth.verify-code') }}', {
                        phone: phone,
                        code: code
                    })
                        .done(function (res) {
                            if (res.redirect_url) {
                                window.location.href = res.redirect_url;
                            } else if (res.company) {
                                window.location.href = '{{ route('dashboard') }}';
                            } else {
                                window.location.href = '{{ route('company.create') }}';
                            }
                        })
                        .fail(function (err) {
                            const msg = err.responseJSON?.message || 'Ошибка проверки кода';
                            $('#alert-container').html('<div class="alert alert-danger">' + msg + '</div>');
                        });
                }
            });
        });
    </script>
@endsection