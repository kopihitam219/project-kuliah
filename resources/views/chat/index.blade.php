@extends('notifications.customer-layout')

@section('title', 'Chat Admin')

@section('content')
    <style>
        .chat-page { display: flex; flex-direction: column; gap: 14px; }
        .chat-page h1 { font-size: clamp(26px, 4vw, 34px); font-weight: 900; letter-spacing: -.8px; }
        .chat-page h1 span { color: #9cff38; }
        .chat-page > p { color: rgba(242, 247, 243, .62); font-size: 14px; line-height: 1.6; max-width: 60ch; }
        .chat-box { height: min(68vh, 640px); }
        @media (max-width: 820px) {
            .chat-box { height: calc(100vh - 64px - 150px - 96px); min-height: 380px; }
            .chat-page > p { display: none; }
        }
    </style>

    <div class="chat-page">
        <div>
            <h1>Chat <span>Admin</span></h1>
        </div>
        <p>Tanya jadwal, pembayaran, atau perubahan booking langsung ke admin. Pengumuman penting dari admin juga muncul di sini.</p>

        <div class="chat-box">
            @include('partials.chat-thread', [
                'messages'  => $messages,
                'pollUrl'   => route('chat.poll'),
                'sendUrl'   => route('chat.send'),
                'viewer'    => 'member',
                'peerName'  => 'Admin ' . \App\Support\Brand::name(),
                'emptyText' => 'Belum ada pesan. Kirim pertanyaan Anda, admin akan membalas di sini.',
            ])
        </div>
    </div>
@endsection
