@extends('layouts.auth')

@section('content')
    <div class="card auth-card">
        <div class="auth-header">
            <h4>429</h4>
            <p class="mb-0">Слишком много запросов</p>
        </div>
        <div class="card-body text-center p-4">
            <p class="mb-4">Вы отправили слишком много запросов. Пожалуйста, подождите немного.</p>
            <a href="{{ url('/') }}" class="btn btn-primary w-100">На главную</a>
        </div>
    </div>
@endsection