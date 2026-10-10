@extends('admin.layouts.panel')

@section('title', 'Tambah Coach')

@section('content')
    <section class="page-head">
        <div>
            <h1>Tambah coach</h1>
            <p>Coach baru langsung tampil di halaman Home jika status "Tampilkan" dicentang.</p>
        </div>
        <div class="head-actions">
            <a href="{{ route('admin.coaches.index') }}" class="btn btn-ghost">← Kembali ke daftar</a>
        </div>
    </section>

    @include('admin.coaches._form', ['action' => route('admin.coaches.store'), 'isEdit' => false])
@endsection
