@extends('layouts.auth')

@section('content')
    <div class="card auth-card">
        <div class="auth-header">
            <h4>404</h4>
            <p class="mb-0">Страница не найдена</p>
        </div>
        <div class="card-body text-center p-4">
            <p class="mb-4">К сожалению, запрашиваемая вами страница не существует.</p>
            <a href="{{ url('/') }}" class="btn btn-primary w-100">На главную</a>
        </div>
    </div>
@endsection