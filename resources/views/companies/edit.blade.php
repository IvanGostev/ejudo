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
                                <input type="text" class="form-control" name="type" id="type-input" value="{{ $company->type }}"
                                    placeholder="ООО, ИП, МУП и другие" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Наименование <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" value="{{ $company->name }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">ИНН <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="inn" id="inn-field" value="{{ $company->inn }}"
                                        minlength="10" maxlength="12" required>
                                    <button class="btn btn-outline-primary" type="button" id="btn-find-inn" title="Обновить информацию через Checko">
                                        <i class="bi bi-search"></i> Найти
                                    </button>
                                </div>
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

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Лицензия на обращение с отходами</h6>
                        <div class="row">
                            <div class="col-md-9 mb-3">
                                <label class="form-label">Реквизиты лицензии</label>
                                <input type="text" class="form-control @error('license_details') is-invalid @enderror"
                                    name="license_details" value="{{ old('license_details', $company->license_details) }}"
                                    placeholder="Номер лицензии, дата выдачи, орган">
                                @error('license_details') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Срок действия</label>
                                <input type="date" class="form-control @error('license_valid_until') is-invalid @enderror"
                                    name="license_valid_until" value="{{ old('license_valid_until', $company->license_valid_until?->format('Y-m-d')) }}">
                                @error('license_valid_until') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 mb-4">
                                <div class="form-text">Используется в Таблице 3, ст. 14 ЖУДО.</div>
                            </div>
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
            const typeInput = document.getElementById('type-input');
            const kppInput = document.querySelector('input[name="kpp"]');
            const kppAsterisk = document.getElementById('kpp-asterisk');
            const innInput = document.getElementById('inn-field');
            const btnFind = document.getElementById('btn-find-inn');

            function updateKpp() {
                const val = typeInput.value.trim().toUpperCase();
                if (val === 'ИП' || val.includes('ИНДИВИДУАЛЬНЫЙ')) {
                    kppInput.removeAttribute('required');
                    kppAsterisk.classList.add('d-none');
                } else {
                    kppInput.setAttribute('required', 'required');
                    kppAsterisk.classList.remove('d-none');
                }
            }

            typeInput.addEventListener('input', updateKpp);
            updateKpp();

            btnFind.addEventListener('click', async function() {
                const inn = innInput.value.trim();
                if (!inn) {
                    alert('Пожалуйста, введите ИНН');
                    return;
                }

                const originalHtml = btnFind.innerHTML;
                btnFind.disabled = true;
                btnFind.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                try {
                    const response = await fetch(`{{ route('checko.inn') }}?inn=${inn}`);
                    if (!response.ok) {
                        const err = await response.json();
                        throw new Error(err.error || 'Ошибка при поиске');
                    }

                    const data = await response.json();

                    if (data.name) {
                        document.querySelector('input[name="name"]').value = data.name;
                        document.querySelector('input[name="type"]').value = data.type || '';
                        document.querySelector('input[name="kpp"]').value = data.kpp || '';
                        document.querySelector('input[name="ogrn"]').value = data.ogrn || '';
                        document.querySelector('input[name="legal_address"]').value = data.address || '';
                        if (data.license_details) {
                            document.querySelector('input[name="license_details"]').value = data.license_details;
                        }
                        if (data.license_valid_until) {
                            document.querySelector('input[name="license_valid_until"]').value = data.license_valid_until;
                        }
                        if (data.contact_person) {
                            document.querySelector('input[name="contact_person"]').value = data.contact_person;
                        }
                        if (data.phone) {
                            document.querySelector('input[name="phone"]').value = data.phone;
                        }
                        if (data.email) {
                            document.querySelector('input[name="email"]').value = data.email;
                        }

                        updateKpp();
                        alert('Данные успешно обновлены!');
                    } else {
                        alert('Организация не найдена');
                    }
                } catch (error) {
                    console.error('Checko Lookup Error:', error);
                    alert('Ошибка: ' + error.message);
                } finally {
                    btnFind.disabled = false;
                    btnFind.innerHTML = originalHtml;
                }
            });
        });
    </script>
@endsection