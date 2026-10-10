@extends('notifications.customer-layout')

@section('title', 'Chat Coach')
@section('no_footer', true)
@section('main_class', 'narrow')

@section('content')
    <style>
        .chat-box { height: min(70vh, 660px); }
        @media (max-width: 820px) {
            .chat-box { height: calc(100svh - 62px - 78px - 100px); min-height: 380px; }
        }
    </style>

    <div class="fw-pagehead">
        <div class="fw-pagehead-title">
            <a href="{{ route('dashboard') }}" class="fw-back" aria-label="Kembali">{!! \App\Support\Icons::svg('back') !!}</a>
            <div>
                <h1 class="fw-h1">Chat Coach</h1>
                <p class="fw-sub">Tanya jadwal, teknik, pembayaran, atau perubahan booking langsung ke {{ $coachName }}.</p>
            </div>
        </div>
    </div>

        <div class="chat-box">
            @include('partials.chat-thread', [
                'messages'  => $messages,
                'pollUrl'   => route('chat.poll'),
                'sendUrl'   => route('chat.send'),
                'viewer'    => 'member',
                'peerName'  => $coachName,
                'peerPhoto' => $coachPhoto,
                'peerSub'   => 'Coach · biasanya membalas di jam operasional',
                'emptyText' => 'Belum ada pesan. Kirim pertanyaan Anda, coach akan membalas di sini.',
            ])
        </div>
@endsection
