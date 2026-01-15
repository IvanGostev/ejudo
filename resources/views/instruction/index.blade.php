@extends('layouts.app')

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h4 class="mb-4">Инструкция по работе с сервисом</h4>

            <div class="timeline">
                <!-- Step 1: Registration -->
                <div class="card mb-3 border-start border-4 border-success">
                    <div class="card-body">
                        <h5 class="card-title text-success fw-bold">
                            <i class="bi bi-check-circle-fill me-2"></i> Регистрация 
                            <span class="badge bg-success ms-2" style="font-size: 0.7em;">Выполнено</span>
                        </h5>
                        <p class="card-text text-muted">
                            <del>Для начала работы введите номер телефона. На указанный номер поступит СМС с кодом подтверждения.
                            После ввода кода вы будете авторизованы в системе.</del>
                        </p>
                    </div>
                </div>

                <!-- Step 2: Adding Company -->
                <div class="card mb-3 border-start border-4 border-primary">
                    <div class="card-body">
                        <h5 class="card-title text-primary fw-bold"><i class="bi bi-2-circle me-2"></i> Добавление и Выбор
                            компании</h5>
                        <p class="card-text">
                            После входа, если у вас еще нет компании, вы будете перенаправлены на страницу создания.
                            Заполните основные реквизиты: ИНН, Название, Директор.
                            <br>
                            <strong>Важно:</strong> Выбранное название компании будет использоваться при автоматическом
                            распознавании актов.
                            Обработчик ищет название вашей компании в файлах актов, чтобы определить сторону
                            (Исполнитель/Заказчик).
                        </p>
                    </div>
                </div>

                <!-- Step 3: Selecting Company -->
                <div class="card mb-3 border-start border-4 border-primary">
                    <div class="card-body">
                        <h5 class="card-title text-primary fw-bold"><i class="bi bi-3-circle me-2"></i> Выбор активной
                            компании</h5>
                        <p class="card-text">
                            В верхнем меню на <a href="{{ route('dashboard') }}"
                                class="text-judo-orange text-decoration-none fw-bold">странице Загрузки актов</a> вы можете
                            переключаться между вашими компаниями, если их несколько.
                            Все последующие действия (загрузка актов, журнал) будут привязаны к выбранной компании.
                        </p>
                    </div>
                </div>

                <!-- Step 4: Uploading Acts -->
                <div class="card mb-3 border-start border-4 border-primary">
                    <div class="card-body">
                        <h5 class="card-title text-primary fw-bold"><i class="bi bi-4-circle me-2"></i> Загрузка и создание
                            актов</h5>
                        <p class="card-text">
                            <strong>Автоматическая загрузка:</strong> На <a href="{{ route('dashboard') }}" class="text-judo-orange text-decoration-none fw-bold">странице Загрузки актов</a> загрузите сканы или фотографии актов. Система
                            распознает данные.
                            <br>
                            <strong>Ручное создание:</strong> Вы можете создать акт вручную.
                            <br>
                            - Для этого выберите тип отхода в справочнике ФККО (слева на экране).
                            <br>
                            - <strong>Важно:</strong> Выбранная в меню "Роль" влияет на автоматическую подстановку вашей
                            компании в поля акта:
                        <ul>
                            <li>Если выбрано <strong>"Отходообразователь"</strong> -> Ваша компания подставится как
                                "Заказчик" (сдающий отход).</li>
                            <li>Если выбрано <strong>"Переработчик"</strong> -> Ваша компания подставится как "Исполнитель"
                                (принимающий отход).</li>
                        </ul>
                        </p>
                    </div>
                </div>

                <!-- Step 5: Creating Journal -->
                <div class="card mb-3 border-start border-4 border-primary">
                    <div class="card-body">
                        <h5 class="card-title text-primary fw-bold"><i class="bi bi-5-circle me-2"></i> Создание Журнала
                            (ЖУДО)</h5>
                        <p class="card-text">
                            Перейдите в раздел <a href="{{ route('journal.index') }}" class="text-judo-orange text-decoration-none fw-bold">Формирование ЖУДО</a>. Нажмите " сформировать".
                            Система соберет все данные из загруженных актов и сформирует 4 таблицы журнала учета движения
                            отходов.
                        </p>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-4">
                <h5><i class="bi bi-info-circle me-2"></i> Дополнительная информация</h5>
                <hr>
                <p>
                    <strong>Роли в системе (Отходообразователь / Переработчик):</strong>
                    <br>
                    Выбранная в верхнем правом углу роль (переключатель) влияет на отображение интерфейса,
                    но <strong>НЕ ВЛИЯЕТ</strong> на формирование самого отчета.
                    <br>
                    Журнал ЖУДО формируется автоматически по всем видам взаимодействия с отходами (образование, передача,
                    получение, переработка),
                    которые были найдены в ваших документах, независимо от текущей выбранной роли.
                </p>
            </div>

        </div>
    </div>
@endsection