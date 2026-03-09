@extends('layouts.app')

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h4 class="mb-4">Инструкция по работе с сервисом</h4>

            <div class="timeline">
                <!-- Step 1: Registration -->
                <div
                    class="card mb-4 border-start border-4 @if($steps['registration']) border-success @else border-primary @endif">
                    <div class="card-body">
                        <h5 class="card-title @if($steps['registration']) text-success @else text-primary @endif fw-bold">
                            @if($steps['registration'])
                                <i class="bi bi-check-circle-fill me-2"></i> Регистрация
                                <span class="badge bg-success ms-2" style="font-size: 0.7em;">Выполнено</span>
                            @else
                                <i class="bi bi-1-circle me-2"></i> Регистрация
                            @endif
                        </h5>
                        <p class="card-text @if($steps['registration']) text-muted @endif">
                            @if($steps['registration']) <del> @endif
                                Для начала работы введите адрес электронной почты. На указанный email поступит письмо с кодом
                                подтверждения.
                                После ввода кода вы будете авторизованы в системе.
                                @if($steps['registration']) </del> @endif
                        </p>
                    </div>
                </div>

                <!-- Step 2: Adding Company -->
                <div
                    class="card mb-4 border-start border-4 @if($steps['create_company']) border-success @else border-primary @endif shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title @if($steps['create_company']) text-success @else text-primary @endif fw-bold">
                            @if($steps['create_company'])
                                <i class="bi bi-check-circle-fill me-2"></i> Добавление компании
                                <span class="badge bg-success ms-2" style="font-size: 0.7em;">Выполнено</span>
                            @else
                                <i class="bi bi-2-circle me-2"></i> Добавление компании
                            @endif
                        </h5>
                        <div class="card-text @if($steps['create_company']) text-muted @endif">
                            @if($steps['create_company']) <del> @endif
                                После входа, если у вас еще нет компании, вы будете перенаправлены на страницу создания.
                                Заполните основные реквизиты: ИНН, Название, Директор.
                                <br>
                                <strong>Важно:</strong> Выбранное название компании будет использоваться при автоматическом
                                распознавании актов.
                                @if($steps['create_company']) </del> @endif
                            @if(!$steps['create_company'])
                                <div class="mt-3">
                                    <a href="{{ route('companies.create') }}" class="btn btn-sm btn-outline-primary">Добавить
                                        компанию</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Step 3: Selecting Company -->
                <div
                    class="card mb-4 border-start border-4 @if($steps['select_company']) border-success @else border-primary @endif shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title @if($steps['select_company']) text-success @else text-primary @endif fw-bold">
                            @if($steps['select_company'])
                                <i class="bi bi-check-circle-fill me-2"></i> Выбор активной компании
                                <span class="badge bg-success ms-2" style="font-size: 0.7em;">Выполнено</span>
                            @else
                                <i class="bi bi-3-circle me-2"></i> Выбор активной компании
                            @endif
                        </h5>
                        <div class="card-text @if($steps['select_company']) text-muted @endif">
                            @if($steps['select_company']) <del> @endif
                                В верхнем меню на <a href="{{ route('dashboard') }}"
                                    class="text-judo-orange text-decoration-none fw-bold">странице Загрузки актов</a> вы
                                можете переключаться между вашими компаниями.
                                Все последующие действия будут привязаны к выбранной компании.
                                @if($steps['select_company']) </del> @endif
                        </div>
                    </div>
                </div>

                <!-- Step 4: Uploading Acts -->
                <div
                    class="card mb-4 border-start border-4 @if($steps['upload_acts']) border-success @else border-primary @endif shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title @if($steps['upload_acts']) text-success @else text-primary @endif fw-bold">
                            @if($steps['upload_acts'])
                                <i class="bi bi-check-circle-fill me-2"></i> Загрузка и создание актов
                                <span class="badge bg-success ms-2" style="font-size: 0.7em;">Выполнено</span>
                            @else
                                <i class="bi bi-4-circle me-2"></i> Загрузка и создание актов
                            @endif
                        </h5>
                        <div class="card-text @if($steps['upload_acts']) text-muted @endif">
                            @if($steps['upload_acts']) <del> @endif
                                <strong>Автоматическая загрузка:</strong> На <a href="{{ route('dashboard') }}"
                                    class="text-judo-orange text-decoration-none fw-bold">странице Загрузки актов</a>
                                загрузите акты в формате .docx .doc .pdf.
                                <br>
                                <strong>Ручное создание:</strong> Вы можете создать акт вручную, выбрав отход в справочнике
                                ФККО.
                                @if($steps['upload_acts']) </del> @endif
                        </div>
                    </div>
                </div>

                <!-- Step 5: Creating Journal -->
                <div
                    class="card mb-4 border-start border-4 @if($steps['create_journal']) border-success @else border-primary @endif shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title @if($steps['create_journal']) text-success @else text-primary @endif fw-bold">
                            @if($steps['create_journal'])
                                <i class="bi bi-check-circle-fill me-2"></i> Создание Журнала (ЖУДО)
                                <span class="badge bg-success ms-2" style="font-size: 0.7em;">Выполнено</span>
                            @else
                                <i class="bi bi-5-circle me-2"></i> Создание Журнала (ЖУДО)
                            @endif
                        </h5>
                        <div class="card-text @if($steps['create_journal']) text-muted @endif">
                            @if($steps['create_journal']) <del> @endif
                                Перейдите в раздел <a href="{{ route('journal.index') }}"
                                    class="text-judo-orange text-decoration-none fw-bold">Формирование ЖУДО</a> и нажмите
                                "Сформировать".
                                Система автоматически соберет все данные из загруженных актов.
                                @if($steps['create_journal']) </del> @endif
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
@endsection