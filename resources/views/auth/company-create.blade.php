@extends('layouts.auth')

@section('content')
    <div class="card auth-card">
        <div class="auth-header">
            <h4>Создание организации</h4>

        </div>
        <div class="card-body p-4">
            <div id="alert-container"></div>

            <form id="company-form">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Тип организации</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="type" id="type1" value="ООО" checked>
                        <label class="btn btn-outline-success" for="type1">ООО</label>

                        <input type="radio" class="btn-check" name="type" id="type2" value="ИП">
                        <label class="btn btn-outline-success" for="type2">ИП</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">Название</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder='ООО "Ромашка"' required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="inn" class="form-label">ИНН</label>
                        <input type="text" class="form-control" id="inn" name="inn" placeholder="7700000000" maxlength="12"
                            required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="ogrn" class="form-label">ОГРН/ОГРНИП</label>
                        <input type="text" class="form-control" id="ogrn" name="ogrn" placeholder="1027700000000" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="legal_address" class="form-label">Юридический адрес</label>
                    <textarea class="form-control" id="legal_address" name="legal_address" rows="2"
                        placeholder="г. Москва, ул. Пушкина, д. 1" required></textarea>
                </div>

                <button type="submit" class="btn btn-success w-100">Создать и продолжить</button>
            </form>
        </div>
    </div>

    <script type="module">
        $(document).ready(function () {
            $('#company-form').on('submit', function (e) {
                e.preventDefault();

                $.post('{{ route('company.store') }}', $(this).serialize())
                    .done(function (res) {
                        window.location.href = '{{ route('dashboard') }}';
                    })
                    .fail(function (err) {
                        const msg = err.responseJSON?.message || 'Ошибка создания компании';
                        $('#alert-container').html('<div class="alert alert-danger">' + msg + '</div>');
                    });
            });
        });
    </script>
@endsection