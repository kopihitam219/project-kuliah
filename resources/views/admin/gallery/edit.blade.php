@extends('admin.gallery.layout')

@section('title', 'Edit Media')

@section('content')
    <section class="page-head">
        <div>
            <h1>Edit media</h1>
            <p>{{ $gallery->title }}</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('admin.gallery.index') }}" class="btn btn-ghost">← Kembali ke daftar</a>
        </div>
    </section>

    @include('admin.gallery._form', [
        'action' => route('admin.gallery.update', $gallery),
        'isEdit' => true,
    ])
@endsection
