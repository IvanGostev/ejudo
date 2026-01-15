@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('companies.index') }}" class="btn btn-link text-decoration-none ps-0 me-2 text-muted">
                    <i class="bi bi-arrow-left fs-5"></i>
                </a>
                <h4 class="mb-0">Добавление компании</h4>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('companies.store') }}" method="POST">
                        @csrf

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Основные данные</h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Тип организации <span class="text-danger">*</span></label>
                                <select class="form-select" name="type" id="type-select" required>
                                    <option value="ООО" selected>ООО</option>
                                    <option value="ИП">ИП</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Наименование <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" placeholder='ООО "Ромашка"' required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">ИНН <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="inn" placeholder="10 или 12 цифр"
                                    minlength="10" maxlength="12" required>
                            </div>

                            <div class="col-md-6" id="kpp-group">
                                <label class="form-label">КПП <span class="text-danger" id="kpp-asterisk">*</span></label>
                                <input type="text" class="form-control" name="kpp" placeholder="9 цифр" maxlength="9">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">ОГРН/ОГРНИП <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ogrn" placeholder="13 или 15 цифр" required>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Адреса</h6>

                        <div class="mb-3">
                            <label class="form-label">Юридический адрес <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="legal_address"
                                placeholder="Индекс, Город, Улица, Дом..." required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Фактический адрес</label>
                            <input type="text" class="form-control" name="actual_address"
                                placeholder="Если отличается от юридического">
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Контактные данные</h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Контактное лицо</label>
                                <input type="text" class="form-control" name="contact_person" placeholder="Иванов И.И.">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Телефон</label>
                                <input type="tel" class="form-control" name="phone" placeholder="+7 (999) 000-00-00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" placeholder="mail@example.com">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('companies.index') }}" class="btn btn-light me-2">Отмена</a>
                            <button type="submit" class="btn btn-primary px-4">Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple script to toggle KPP requirement based on Type
        document.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.getElementById('type-select');
            const kppInput = document.querySelector('input[name="kpp"]');
            const kppAsterisk = document.getElementById('kpp-asterisk');

            function updateKpp() {
                if (typeSelect.value === 'ИП') {
                    // KPP not usually needed for IP, optional or disabled
                    kppInput.removeAttribute('required');
                    kppAsterisk.classList.add('d-none');
                } else {
                    kppInput.setAttribute('required', 'required');
                    kppAsterisk.classList.remove('d-none');
                }
            }

            typeSelect.addEventListener('change', updateKpp);
            updateKpp(); // Init
        });
    </script>
@endsection