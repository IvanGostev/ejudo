<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ЖУДО - Кабинет</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bs-primary: #FF4C2B;
            --bs-primary-rgb: 255, 76, 43;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .bg-judo-dark {
            background-color: #000218 !important;
        }

        .bg-judo-navy {
            background-color: #0A1F32 !important;
        }

        .text-judo-orange {
            color: #FF4C2B !important;
        }

        .btn-primary {
            background-color: #FF4C2B;
            border-color: #FF4C2B;
        }

        .btn-primary:hover {
            background-color: #e04326;
            border-color: #d63f24;
        }

        /* Override outline buttons to avoid default blue */
        .btn-outline-primary {
            color: #FF4C2B;
            border-color: #FF4C2B;
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:active,
        .btn-outline-primary.active,
        .btn-outline-primary.dropdown-toggle.show {
            background-color: #FF4C2B !important;
            border-color: #FF4C2B !important;
            color: #fff !important;
        }

        /* Custom Black Button */
        .btn-outline-black {
            color: #1f1f1f;
            border-color: #1f1f1f;
        }

        .btn-outline-black:hover,
        .btn-outline-black:active,
        .btn-outline-black.active,
        .btn-outline-black.dropdown-toggle.show {
            background-color: #1f1f1f !important;
            border-color: #1f1f1f !important;
            color: #fff !important;
        }

        .sidebar {
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid #eee;
        }

        .nav-link {
            color: #333;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 5px;
            transition: all 0.2s;
        }

        /* Specific styles for Top Navbar links */
        .navbar-dark .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9);
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: rgba(255, 76, 43, 0.1);
            color: #FF4C2B !important;
        }

        /* Ensure top navbar links get the white text on hover/active */
        .navbar-dark .navbar-nav .nav-link:hover,
        .navbar-dark .navbar-nav .nav-link.active {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link i {
            width: 24px;
        }

        .dropdown-item.active,
        .dropdown-item:active {
            background-color: #1f1f1f !important;
        }
    </style>
</head>

<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-judo-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">eJydo</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}"
                            class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            Загрузка Актов
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('companies.index') }}"
                            class="nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}">
                            Мои компании
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('profile.index') ? 'active' : '' }}"
                            href="{{ route('profile.index') }}">Профиль</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('subscription.index') ? 'active' : '' }}"
                            href="{{ route('subscription.index') }}">Тарифы</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('journal.index') ? 'active' : '' }}"
                            href="{{ route('journal.index') }}">Формирование ЖУДО</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('instruction.index') ? 'active' : '' }}"
                            href="{{ route('instruction.index') }}">Инструкция</a>
                    </li>
                </ul>
                <div class="d-flex text-white align-items-center">
                    <!-- Company Selector -->
                    <div class="dropdown me-3">
                        @php
                            $user = auth()->user();
                            $allCompanies = $user->companies;
                            $currentCompany = app(\App\Services\TenantService::class)->getCompany();
                        @endphp
                        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            {{ $currentCompany ? $currentCompany->name : 'Выберите компанию' }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if($allCompanies->count() > 0)
                                @foreach($allCompanies as $comp)
                                    <li>
                                        <a class="dropdown-item {{ ($currentCompany && $currentCompany->id === $comp->id) ? 'active' : '' }}"
                                            href="#"
                                            onclick="event.preventDefault(); document.getElementById('header-switch-company-{{ $comp->id }}').submit();">
                                            {{ $comp->name }}
                                        </a>
                                        <form id="header-switch-company-{{ $comp->id }}" action="{{ route('companies.switch', $comp->id) }}"
                                            method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </li>
                                @endforeach
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            <li>
                                <a class="dropdown-item" href="{{ route('companies.create') }}">
                                    <i class="bi bi-plus-lg me-1"></i>Добавить новую
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Logout Button -->
                    <form action="{{ route('logout') }}" method="POST" class="d-inline ms-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
    </nav>

     <div class="container-fluid">
         <div class="row">
            <!-- Left Sidebar: FKKO Reference -->
            <div class="col-md-3 col-lg-2 sidebar p-3 d-none d-md-block bg-white border-end"
                style="min-height: calc(100vh - 56px);">
                <!-- ... sidebar content ... -->
                <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                    Справочник ФККО</h6>

                <div class="mb-3">
                    <input type="text" id="fkko-search" class="form-control form-control-sm"
                        placeholder="Поиск по коду/названию...">
                </div>

                <div class="fkko-tree small text-muted" id="fkko-results" style="max-height: 70vh; overflow-y: auto;">
                    <div id="loading-spinner" class="text-center d-none">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                    <div id="tree-content">
                        <!-- Default Blocks -->
                        <div class="mb-2">Блок 1. Отходы с/х</div>
                        <div class="mb-2">Блок 2. Отходы добычи</div>
                        <div class="mb-2">Блок 3. Обрабатывающие</div>
                        <div class="ms-3 mb-1">3 01 ... Древесина</div>
                        <div class="ms-3 mb-1">3 02 ... Бумага</div>
                        <div class="mb-2">Блок 4. Потребление</div>
                        <p class="mt-3 fst-italic text-center">Введите поисковый запрос...</p>
                    </div>
                </div>

                @push('scripts')
                    <script>
                        $(document).ready(function () {
                            let debounceTimer;
                            $('#fkko-search').on('input', function () {
                                const query = $(this).val();
                                clearTimeout(debounceTimer);

                                if (query.length < 2) {
                                    // Restore default view if empty
                                    if (query.length === 0) {
                                        $('#tree-content').html(`
                                                    <div class="mb-2">Блок 1. Отходы с/х</div>
                                                    <div class="mb-2">Блок 2. Отходы добычи</div>
                                                    <div class="mb-2">Блок 3. Обрабатывающие</div>
                                                    <div class="ms-3 mb-1">3 01 ... Древесина</div>
                                                    <div class="ms-3 mb-1">3 02 ... Бумага</div>
                                                    <div class="mb-2">Блок 4. Потребление</div>
                                                `);
                                    }
                                    return;
                                }

                                $('#loading-spinner').removeClass('d-none');

                                debounceTimer = setTimeout(() => {
                                    $.get('{{ route("fkko.search") }}', { q: query })
                                        .done(function (data) {
                                            let html = '';
                                            if (data.length === 0) {
                                                html = '<p class="text-center mt-2">Ничего не найдено</p>';
                                            } else {
                                                data.forEach(item => {
                                                    html += `
                                                                                                <div class="mb-2 border-bottom pb-1" title="${item.name}"
                                                                                                     style="cursor: pointer;"
                                                                                                     onclick="window.location.href='{{ route('acts.manual.create') }}?fkko_code=${item.code}'">
                                                                                                    <div class="fw-bold text-dark">${item.code}</div>
                                                                                                    <div class="text-truncate">${item.name}</div>
                                                                                                </div>
                                                                                            `;
                                                });
                                            }
                                            $('#tree-content').html(html);
                                        })
                                        .always(() => {
                                            $('#loading-spinner').addClass('d-none');
                                        });
                                }, 500);
                            });
                        });
                    </script>
                @endpush
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4 bg-light">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{!! session('error') !!}</div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light pt-5 pb-2 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="text-judo-orange mb-3">Компания</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('page.show', 'about') }}" class="text-decoration-none text-light opacity-75 hover-opacity-100">О сервисе</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'pricing') }}" class="text-decoration-none text-light opacity-75 hover-opacity-100">Тарифы и цены</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'faq') }}" class="text-decoration-none text-light opacity-75 hover-opacity-100">Частые вопросы</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'contacts') }}" class="text-decoration-none text-light opacity-75 hover-opacity-100">Контакты</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="text-judo-orange mb-3">Документы</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('page.show', 'offer') }}" class="text-decoration-none text-light opacity-75 hover-opacity-100">Публичная оферта</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'privacy') }}" class="text-decoration-none text-light opacity-75 hover-opacity-100">Политика конфиденциальности</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'agreement') }}" class="text-decoration-none text-light opacity-75 hover-opacity-100">Согласие на обработку данных</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'terms') }}" class="text-decoration-none text-light opacity-75 hover-opacity-100">Условия использования</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="text-judo-orange mb-3">Для бизнеса</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('page.show', 'refund') }}" class="text-decoration-none text-light opacity-75 hover-opacity-100">Возврат средств</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'support') }}" class="text-decoration-none text-light opacity-75 hover-opacity-100">Поддержка</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'templates') }}" class="text-decoration-none text-light opacity-75 hover-opacity-100">Шаблоны документов</a></li>
                        <li class="mb-2"><a href="{{ route('page.show', 'partners') }}" class="text-decoration-none text-light opacity-75 hover-opacity-100">Партнерам</a></li>
                    </ul>
                </div>
            </div>
            <div class="row mt-4 pt-3 border-top border-secondary">
                <div class="col-12 text-center text-white small">
                    <p class="mb-0">© 2026 ejydo.ru. ИП Самофалов Денис Олегович ИНН 272336634478</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    @stack('scripts')
</body>

</html>
