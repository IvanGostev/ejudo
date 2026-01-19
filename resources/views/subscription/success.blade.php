@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success display-1"></i>
                        </div>
                        <h2 class="mb-3">Оплата прошла успешно!</h2>
                        <p class="text-muted mb-4">Спасибо за оплату. Ваша подписка активирована.</p>
                        <a href="{{ route('dashboard') }}" class="btn btn-primary px-5">Перейти в кабинет</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection