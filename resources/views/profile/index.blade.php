@extends('layouts.app')

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h4 class="mb-4">Профиль пользователя</h4>
            <form>
                <div class="mb-3">
                    <label class="form-label">Телефон</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->phone ?? '' }}" readonly>
                </div>
                <!-- Add more profile fields -->
            </form>
        </div>
    </div>
@endsection