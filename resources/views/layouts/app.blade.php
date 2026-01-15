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
            <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">eJudo</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}"
                            class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="bi bi-grid-1x2-fill me-2"></i>
                            Загрузка Актов
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('companies.index') }}"
                            class="nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}">
                            <i class="bi bi-building me-2"></i>
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
                    <!-- Role Selector -->
                    <div class="dropdown me-3">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button"
                            data-bs-toggle="dropdown">
                            {{ session('user_role', 'Отходообразователь') }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"
                                    onclick="event.preventDefault(); setRole('Отходообразователь');">Отходообразователь</a>
                            </li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="event.preventDefault(); setRole('Переработчик отходов');">Переработчик
                                    отходов</a></li>
                        </ul>
                        <script>
                            function setRole(role) {
                                fetch('{{ route('role.set') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({ role: role })
                                })
                                    .then(response => {
                                        if (response.ok) {
                                            location.reload();
                                        } else {
                                            alert('Ошибка смены роли');
                                        }
                                    })
                                    .catch(error => console.error('Error:', error));
                            }
                        </script>


                    </div>

                    <!-- Logout Button -->
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-right"></i> Выйти
                        </button>
                    </form>
                </div>
            </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Left Sidebar: FKKO Reference -->
            <div class="col-md-3 col-lg-2 sidebar p-3 d-none d-md-block bg-white border-end"
                style="min-height: calc(100vh - 56px);">
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
                        <div class="mb-2"><i class="bi bi-folder me-1 text-warning"></i> Блок 1. Отходы с/х</div>
                        <div class="mb-2"><i class="bi bi-folder me-1 text-warning"></i> Блок 2. Отходы добычи</div>
                        <div class="mb-2"><i class="bi bi-folder me-1 text-warning"></i> Блок 3. Обрабатывающие</div>
                        <div class="ms-3 mb-1"><i class="bi bi-file-earmark-text me-1"></i> 3 01 ... Древесина</div>
                        <div class="ms-3 mb-1"><i class="bi bi-file-earmark-text me-1"></i> 3 02 ... Бумага</div>
                        <div class="mb-2"><i class="bi bi-folder me-1 text-warning"></i> Блок 4. Потребление</div>
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
                                                                <div class="mb-2"><i class="bi bi-folder me-1 text-warning"></i> Блок 1. Отходы с/х</div>
                                                                <div class="mb-2"><i class="bi bi-folder me-1 text-warning"></i> Блок 2. Отходы добычи</div>
                                                                <div class="mb-2"><i class="bi bi-folder me-1 text-warning"></i> Блок 3. Обрабатывающие</div>
                                                                <div class="ms-3 mb-1"><i class="bi bi-file-earmark-text me-1"></i> 3 01 ... Древесина</div>
                                                                <div class="ms-3 mb-1"><i class="bi bi-file-earmark-text me-1"></i> 3 02 ... Бумага</div>
                                                                <div class="mb-2"><i class="bi bi-folder me-1 text-warning"></i> Блок 4. Потребление</div>
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
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

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