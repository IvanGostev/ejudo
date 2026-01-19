@extends('layouts.auth')

@section('content')
    <div class="card auth-card">
        <div class="auth-header">
            <h4>503</h4>
            <p class="mb-0">Технические работы</p>
        </div>
        <div class="card-body text-center p-4">
            <p class="mb-4">Сервис временно недоступен. Пожалуйста, попробуйте позже.</p>
            <div class="mt-3">
                <i class="bi bi-cone-striped text-warning" style="font-size: 2rem;"></i>
            </div>
        </div>
    </div>
@endsection