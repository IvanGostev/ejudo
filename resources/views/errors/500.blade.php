@extends('layouts.auth')

@section('content')
    <div class="card auth-card">
        <div class="auth-header">
            <h4>500</h4>
            <p class="mb-0">Ошибка сервера</p>
        </div>
        <div class="card-body text-center p-4">
            <p class="mb-4">Что-то пошло не так на нашем сервере. Пожалуйста, попробуйте позже.</p>
            <a href="{{ url('/') }}" class="btn btn-primary w-100">На главную</a>
        </div>
    </div>
@endsection