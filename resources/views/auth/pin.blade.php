@extends('layouts.auth')

@section('content')
    <div class="card auth-card">
        <div class="auth-header">
            <h4>Создание ПИН-кода</h4>
        </div>
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register.pin') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="pin" class="form-label">Придумайте ПИН-код (4 цифры)</label>
                    <input type="password" class="form-control" id="pin" name="pin" maxlength="4" placeholder="****"
                        required>
                </div>
                <div class="mb-3">
                    <label for="pin_confirmation" class="form-label">Повторите ПИН-код</label>
                    <input type="password" class="form-control" id="pin_confirmation" name="pin_confirmation" maxlength="4"
                        placeholder="****" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Завершить регистрацию</button>
            </form>
        </div>
    </div>
@endsection