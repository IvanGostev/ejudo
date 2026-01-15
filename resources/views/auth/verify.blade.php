@extends('layouts.auth')

@section('content')
    <div class="card auth-card">
        <div class="auth-header">
            <h4>Подтверждение номера</h4>
        </div>
        <div class="card-body p-4">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register.verify') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="code" class="form-label">Код из СМС</label>
                    <input type="text" class="form-control" id="code" name="code" maxlength="4" placeholder="XXXX" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Подтвердить</button>
            </form>
        </div>
    </div>
@endsection