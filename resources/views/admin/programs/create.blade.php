@extends('admin.layouts.panel')

@section('title', 'Tambah Program')

@section('content')
    <section class="page-head">
        <div>
            <h1>Tambah program</h1>
            <p>Program baru langsung tampil di halaman Program jika status "Tampilkan" dicentang.</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('admin.programs.index') }}" class="btn btn-ghost">← Kembali ke daftar</a>
        </div>
    </section>

    @include('admin.programs._form', [
        'action' => route('admin.programs.store'),
        'isEdit' => false,
    ])
@endsection
