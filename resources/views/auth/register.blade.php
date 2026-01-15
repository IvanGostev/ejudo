@extends('layouts.auth')

@section('content')
    <div class="card auth-card">
        <div class="auth-header">
            <h4>Регистрация</h4>
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

            <form action="{{ route('register.send-code') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="phone" class="form-label">Номер телефона</label>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="+7 (XXX) XXX-XX-XX"
                        required>
                    <div class="form-text">На этот номер придет СМС с кодом.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Получить код</button>
            </form>
            <div class="mt-3 text-center">
                <a href="{{ route('login') }}">Уже есть аккаунт? Войти</a>
            </div>
        </div>
    </div>
@endsection