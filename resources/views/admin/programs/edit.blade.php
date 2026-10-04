@extends('admin.layouts.panel')

@section('title', 'Edit Program')

@section('content')
    <section class="page-head">
        <div>
            <h1>Edit program</h1>
            <p>{{ $program->name }}</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('admin.programs.index') }}" class="btn btn-ghost">← Kembali ke daftar</a>
        </div>
    </section>

    @include('admin.programs._form', [
        'action' => route('admin.programs.update', $program),
        'isEdit' => true,
    ])
@endsection
