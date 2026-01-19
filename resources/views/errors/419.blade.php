@extends('layouts.auth')

@section('content')
    <div class="card auth-card">
        <div class="auth-header">
            <h4>419</h4>
            <p class="mb-0">Страница устарела</p>
        </div>
        <div class="card-body text-center p-4">
            <p class="mb-4">Срок действия страницы истек. Пожалуйста, обновите её и попробуйте снова.</p>
            <a href="{{ url('/') }}" class="btn btn-primary w-100">На главную</a>
        </div>
    </div>
@endsection