@extends('admin.layouts.panel')

@section('title', 'Edit Lokasi')

@section('content')
    <section class="page-head">
        <div>
            <h1>Edit lokasi latihan</h1>
            <p>{{ $location->name }}</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('admin.contact.index') }}" class="btn btn-ghost">← Kembali</a>
        </div>
    </section>

    @include('admin.contact._location-form', [
        'action' => route('admin.contact.locations.update', $location),
        'isEdit' => true,
    ])
@endsection
