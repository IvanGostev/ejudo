@extends('layouts.auth')

@section('content')
    <div class="card auth-card">
        <div class="auth-header">
            <h4>403</h4>
            <p class="mb-0">Доступ запрещен</p>
        </div>
        <div class="card-body text-center p-4">
            <p class="mb-4">У вас нет прав для просмотра этой страницы.</p>
            <a href="{{ url('/') }}" class="btn btn-primary w-100">На главную</a>
        </div>
    </div>
@endsection