@extends('admin.layouts.panel')

@section('title', 'Tambah Lokasi')

@section('content')
    <section class="page-head">
        <div>
            <h1>Tambah lokasi latihan</h1>
            <p>Lokasi baru muncul di halaman Contact jika status "Tampilkan" dicentang.</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('admin.contact.index') }}" class="btn btn-ghost">← Kembali</a>
        </div>
    </section>

    @include('admin.contact._location-form', [
        'action' => route('admin.contact.locations.store'),
        'isEdit' => false,
    ])
@endsection
