@extends('admin.layouts.panel')

@section('title', 'Tambah Event')

@section('content')
    <section class="page-head">
        <div>
            <h1>Tambah event</h1>
            <p>Event baru langsung tampil di halaman Event jika "Tampilkan" dicentang.</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('admin.events.index') }}" class="btn btn-ghost">← Kembali ke daftar</a>
        </div>
    </section>

    @include('admin.events._form', [
        'action' => route('admin.events.store'),
        'isEdit' => false,
    ])
@endsection
