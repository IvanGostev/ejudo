@extends('layouts.app')

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h4 class="mb-4">Тарифы и оплата</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-primary text-white">Платный тариф</div>
                        <div class="card-body">
                            <h2>5000 ₽ <small class="text-muted fs-6">/ мес</small></h2>
                            <ul class="list-unstyled mt-3 mb-4">
                                <li><i class="bi bi-plus-lg text-judo-orange me-2"></i>История за 5 лет</li>
                                <li><i class="bi bi-plus-lg text-judo-orange me-2"></i>Неограниченное скачивание PDF и Excel
                                </li>
                                <li><i class="bi bi-plus-lg text-judo-orange me-2"></i>Неограниченное количество компаний
                                </li>
                                <li><i class="bi bi-plus-lg text-judo-orange me-2"></i>Приоритетная поддержка</li>
                            </ul>
                            <form action="{{ route('subscription.create') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">Выбрать</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">Бесплатный тариф</div>
                        <div class="card-body">
                            <h2>0 ₽ <small class="text-muted fs-6">/ мес</small></h2>
                            <ul class="list-unstyled mt-3 mb-4">
                                <li><i class="bi bi-plus-lg text-judo-orange me-2"></i>История за 30 дней</li>
                                <li><i class="bi bi-x-lg text-dark me-2"></i>Нет скачивания PDF и Excel</li>
                                <li><i class="bi bi-x-lg text-dark me-2"></i>Одна компания</li>
                                <li><i class="bi bi-plus-lg text-judo-orange me-2"></i>Формирование ЖУДО</li>
                            </ul>
                            <button type="button" class="btn btn-outline-secondary w-100" disabled>Текущий</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection