@extends('admin.gallery.layout')

@section('title', 'Tambah Media')

@section('content')
    <section class="page-head">
        <div>
            <h1>Tambah foto / video</h1>
            <p>Media baru langsung tampil di halaman Galeri jika status "Tampilkan" dicentang.</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('admin.gallery.index') }}" class="btn btn-ghost">← Kembali ke daftar</a>
        </div>
    </section>

    @include('admin.gallery._form', [
        'action' => route('admin.gallery.store'),
        'isEdit' => false,
    ])
@endsection
