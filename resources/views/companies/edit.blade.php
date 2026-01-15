@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('companies.index') }}" class="btn btn-link text-decoration-none ps-0 me-2 text-muted">
                    <i class="bi bi-arrow-left fs-5"></i>
                </a>
                <h4 class="mb-0">Редактирование компании</h4>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('companies.update', $company->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Основные данные</h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Тип организации <span class="text-danger">*</span></label>
                                <select class="form-select" name="type" id="type-select" required>
                                    <option value="ООО" {{ $company->type == 'ООО' ? 'selected' : '' }}>ООО</option>
                                    <option value="ИП" {{ $company->type == 'ИП' ? 'selected' : '' }}>ИП</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Наименование <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" value="{{ $company->name }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">ИНН <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="inn" value="{{ $company->inn }}"
                                    minlength="10" maxlength="12" required>
                            </div>

                            <div class="col-md-6" id="kpp-group">
                                <label class="form-label">КПП <span class="text-danger" id="kpp-asterisk">*</span></label>
                                <input type="text" class="form-control" name="kpp" value="{{ $company->kpp }}"
                                    maxlength="9">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">ОГРН/ОГРНИП <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ogrn" value="{{ $company->ogrn }}" required>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Адреса</h6>

                        <div class="mb-3">
                            <label class="form-label">Юридический адрес <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="legal_address"
                                value="{{ $company->legal_address }}" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Фактический адрес</label>
                            <input type="text" class="form-control" name="actual_address"
                                value="{{ $company->actual_address }}">
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Контактные данные</h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Контактное лицо</label>
                                <input type="text" class="form-control" name="contact_person"
                                    value="{{ $company->contact_person }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Телефон</label>
                                <input type="tel" class="form-control" name="phone" value="{{ $company->phone }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="{{ $company->email }}">
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
        document.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.getElementById('type-select');
            const kppInput = document.querySelector('input[name="kpp"]');
            const kppAsterisk = document.getElementById('kpp-asterisk');

            function updateKpp() {
                if (typeSelect.value === 'ИП') {
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