@extends('layouts.app')

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body p-5">
            <h1 class="mb-4">{{ $page->title }}</h1>
            <div class="content">
                {!! $page->content !!}
            </div>
        </div>
    </div>
@endsection