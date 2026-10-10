@extends('admin.layouts.panel')

@section('title', 'Edit Coach')

@section('content')
    <section class="page-head">
        <div>
            <h1>Edit coach</h1>
            <p>Perubahan langsung tampil di bagian "Kenali Coach Kami" di halaman Home.</p>
        </div>
        <div class="head-actions">
            <a href="{{ route('admin.coaches.index') }}" class="btn btn-ghost">← Kembali ke daftar</a>
        </div>
    </section>

    @include('admin.coaches._form', ['action' => route('admin.coaches.update', $coach), 'isEdit' => true])
@endsection
