<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'EJYDO') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Restored Auth Styles */
        .auth-card {
            max-width: 400px;
            margin: 50px auto;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .auth-header {
            background-color: #000218;
            color: white;
            text-align: center;
            padding: 20px;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
            border-bottom: 3px solid #FF4C2B;
        }

        .btn-primary {
            background-color: #FF4C2B;
            border-color: #FF4C2B;
        }

        .btn-primary:hover,
        .btn-primary:active,
        .btn-primary:focus {
            background-color: #000218 !important;
            border-color: #000218 !important;
        }

        .alert-success {
            background-color: #FF4C2B;
            border-color: #FF4C2B;
            color: #ffffff;
        }

        /* Footer styles */
        .text-judo-orange {
            color: #FF4C2B !important;
        }

        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container.auth-container {
            flex: 1;
        }
    </style>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
        })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=106411023', 'ym');

        ym(106411023, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/106411023" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
</head>

<body>
    <div class="container auth-container">
        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light pt-5 pb-2 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="text-judo-orange mb-3">Компания</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('page.show', 'about') }}"
                                class="text-decoration-none text-light opacity-75 hover-opacity-100">О сервисе</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'pricing') }}"
                                class="text-decoration-none text-light opacity-75 hover-opacity-100">Тарифы и цены</a>
                        </li>
                        <li class="mb-2"><a href="{{ route('page.show', 'faq') }}"
                                class="text-decoration-none text-light opacity-75 hover-opacity-100">Частые вопросы</a>
                        </li>
                        <li class="mb-2"><a href="{{ route('page.show', 'contacts') }}"
                                class="text-decoration-none text-light opacity-75 hover-opacity-100">Контакты</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="text-judo-orange mb-3">Документы</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('page.show', 'offer') }}"
                                class="text-decoration-none text-light opacity-75 hover-opacity-100">Публичная
                                оферта</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'privacy') }}"
                                class="text-decoration-none text-light opacity-75 hover-opacity-100">Политика
                                конфиденциальности</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'agreement') }}"
                                class="text-decoration-none text-light opacity-75 hover-opacity-100">Согласие на
                                обработку данных</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'terms') }}"
                                class="text-decoration-none text-light opacity-75 hover-opacity-100">Условия
                                использования</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="text-judo-orange mb-3">Для бизнеса</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('page.show', 'refund') }}"
                                class="text-decoration-none text-light opacity-75 hover-opacity-100">Возврат средств</a>
                        </li>
                        <li class="mb-2"><a href="{{ route('page.show', 'support') }}"
                                class="text-decoration-none text-light opacity-75 hover-opacity-100">Поддержка</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'templates') }}"
                                class="text-decoration-none text-light opacity-75 hover-opacity-100">Шаблоны
                                документов</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'partners') }}"
                                class="text-decoration-none text-light opacity-75 hover-opacity-100">Партнерам</a></li>
                    </ul>
                </div>
            </div>
            <div class="row mt-4 pt-3 border-top border-secondary">
                <div class="col-12 text-center text-white small">
                    <p class="mb-0">© 2026 ejydo.ru ИНН 272336634478 ОГРНИП 325270000036421
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>