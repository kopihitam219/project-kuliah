@extends('admin.layouts.panel')

@section('title', 'Edit Event')

@section('content')
    <section class="page-head">
        <div>
            <h1>Edit event</h1>
            <p>{{ $event->title }}</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('admin.events.index') }}" class="btn btn-ghost">← Kembali ke daftar</a>
        </div>
    </section>

    @include('admin.events._form', [
        'action' => route('admin.events.update', $event),
        'isEdit' => true,
    ])
@endsection
