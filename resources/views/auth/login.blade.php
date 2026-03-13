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
                    <label for="email" class="form-label">Email адрес</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com"
                        required>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Запомнить меня</label>
                </div>

                <div class="mb-3 d-none" id="code-group">
                    <label for="code" class="form-label">Код из письма</label>
                    <input type="text" class="form-control" id="code" name="code" placeholder="1234" maxlength="4">
                </div>

                <button type="submit" class="btn btn-primary w-100" id="submit-btn"
                    style="background-color: #FF4C2B; border-color: #FF4C2B;">Получить код</button>
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
                    <a href="mailto:support@ejydo.ru" class="text-decoration-none text-muted"><i
                            class="bi bi-envelope me-1"></i>support@ejydo.ru</a><br>
                    <a href="tel:+79145494242" class="text-decoration-none text-muted"><i
                            class="bi bi-telephone me-1"></i>+7 (914) 549-42-42</a>
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

            const savedEmail = localStorage.getItem('auth_email');
            if (savedEmail) {
                $('#email').val(savedEmail);
                $('#remember').prop('checked', true);
            }

            let step = 'send';

            $('#auth-form').on('submit', function (e) {
                e.preventDefault();

                const email = $('#email').val();
                const code = $('#code').val();
                const remember = $('#remember').is(':checked');

                if (step === 'send') {
                    if (!email) {
                        alert('Введите Email');
                        return;
                    }

                    $.post('{{ route('auth.send-code') }}', {
                        email: email
                    })
                        .done(function (res) {
                            step = 'verify';
                            $('#code-group').removeClass('d-none');
                            $('#submit-btn').text('Войти');
                            $('#alert-container').html('<div class="alert alert-success">Код отправлен на почту! Возможно, проверьте папку спам.</div>');
                        })
                        .fail(function (err) {
                            const msg = err.responseJSON?.message || 'Ошибка отправки кода';
                            $('#alert-container').html('<div class="alert alert-danger">' + msg + '</div>');
                        });
                } else {
                    if (code.length < 4) {
                        alert('Введите код');
                        return;
                    }

                    $.post('{{ route('auth.verify-code') }}', {
                        email: email,
                        code: code,
                        remember: remember
                    })
                        .done(function (res) {
                            if (remember) {
                                localStorage.setItem('auth_email', email);
                            } else {
                                localStorage.removeItem('auth_email');
                            }

                            if (res.redirect_url) {
                                window.location.href = res.redirect_url;
                            } else {
                                window.location.href = '{{ route('dashboard') }}';
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