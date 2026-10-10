<?php

namespace App\Http\Controllers;

use App\Models\ChatBroadcast;
use App\Models\ChatMessage;
use App\Models\User;
use App\Notifications\ChatActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Chat admin: kotak masuk semua member + broadcast ke semua member.
 */
class AdminChatController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $members = User::query()
            ->where('role', 'customer')
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . mb_strtolower($search) . '%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(email) LIKE ?', [$like]));
            })
            ->addSelect([
                'last_chat_id' => ChatMessage::select('id')->whereColumn('member_id', 'users.id')->orderByDesc('id')->limit(1),
                'unread_chat'  => ChatMessage::selectRaw('COUNT(*)')->whereColumn('member_id', 'users.id')
                    ->where('sender_role', ChatMessage::FROM_MEMBER)->whereNull('read_at'),
            ])
            ->get(['users.id', 'users.name', 'users.email']);

        $lastIds  = $members->pluck('last_chat_id')->filter()->all();
        $lastMsgs = ChatMessage::with('broadcast:id,title')->whereIn('id', $lastIds)->get()->keyBy('id');

        $members = $members->map(function ($m) use ($lastMsgs) {
            $last = $m->last_chat_id ? $lastMsgs->get($m->last_chat_id) : null;
            $m->last_body = $last?->broadcast ? '📢 ' . $last->broadcast->title : $last?->body;
            $m->last_from = $last?->sender_role;
            $m->last_at   = $last?->created_at;
            return $m;
        })->sortByDesc(fn ($m) => [(int) $m->unread_chat > 0 ? 1 : 0, $m->last_at?->timestamp ?? 0])->values();

        $active   = null;
        $messages = collect();

        if ($request->filled('member')) {
            $active = User::where('role', 'customer')->find((int) $request->query('member'));

            if ($active) {
                $messages = ChatMessage::with('broadcast')->where('member_id', $active->id)
                    ->orderByDesc('id')->limit(80)->get()->reverse()->values()
                    ->map(fn ($m) => $m->toChatArray(ChatMessage::FROM_ADMIN));
                $this->markRead($active->id);
            }
        }

        return view('admin.chat.index', [
            'members'    => $members,
            'active'     => $active,
            'messages'   => $messages,
            'search'     => $search,
            'broadcasts' => ChatBroadcast::latest()->limit(5)->get(),
            'memberCount'=> User::where('role', 'customer')->count(),
        ]);
    }

    public function poll(Request $request, User $member): JsonResponse
    {
        abort_unless($member->role === 'customer', 404);
        $after = (int) $request->query('after', 0);

        $messages = ChatMessage::with('broadcast')->where('member_id', $member->id)->where('id', '>', $after)
            ->orderBy('id')->limit(100)->get();

        if ($messages->isNotEmpty()) {
            $this->markRead($member->id);
        }

        return response()->json([
            'messages' => $messages->map(fn ($m) => $m->toChatArray(ChatMessage::FROM_ADMIN)),
            'unread'   => ChatMessage::unreadForAdmin(),
        ]);
    }

    public function send(Request $request, User $member): JsonResponse
    {
        abort_unless($member->role === 'customer', 404);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']], ['body.required' => 'Pesan masih kosong.']);

        $message = ChatMessage::create([
            'member_id'   => $member->id,
            'sender_id'   => $request->user()->id,
            'sender_role' => ChatMessage::FROM_ADMIN,
            'body'        => trim($data['body']),
        ]);

        $member->notify(new ChatActivity('chat', 'Pesan baru dari Admin', Str::limit($message->body, 140), '/chat'));

        return response()->json(['message' => $message->toChatArray(ChatMessage::FROM_ADMIN)]);
    }

    /** Kirim informasi penting ke semua member: masuk ke chat & notifikasi masing-masing. */
    public function broadcast(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body'  => ['required', 'string', 'max:2000'],
        ], [
            'title.required' => 'Judul pengumuman wajib diisi.',
            'body.required'  => 'Isi pengumuman wajib diisi.',
        ]);

        $members = User::where('role', 'customer')->get(['id', 'name', 'email', 'role']);

        if ($members->isEmpty()) {
            return back()->with('error', 'Belum ada member untuk menerima pengumuman.');
        }

        $admin = $request->user();

        DB::transaction(function () use ($data, $members, $admin, &$broadcast) {
            $broadcast = ChatBroadcast::create([
                'admin_id'   => $admin->id,
                'title'      => trim($data['title']),
                'body'       => trim($data['body']),
                'recipients' => $members->count(),
            ]);

            $now = now();
            foreach ($members->chunk(200) as $chunk) {
                ChatMessage::insert($chunk->map(fn ($m) => [
                    'member_id'    => $m->id,
                    'sender_id'    => $admin->id,
                    'sender_role'  => ChatMessage::FROM_ADMIN,
                    'body'         => $broadcast->body,
                    'broadcast_id' => $broadcast->id,
                    'read_at'      => null,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ])->all());
            }
        });

        Notification::send($members, new ChatActivity(
            'broadcast',
            'Pengumuman: ' . $broadcast->title,
            Str::limit($broadcast->body, 160),
            '/chat',
        ));

        return redirect()->route('admin.chat.index')->with('success', 'Pengumuman terkirim ke ' . $members->count() . ' member.');
    }

    private function markRead(int $memberId): void
    {
        ChatMessage::where('member_id', $memberId)->where('sender_role', ChatMessage::FROM_MEMBER)
            ->whereNull('read_at')->update(['read_at' => now()]);
    }
}
