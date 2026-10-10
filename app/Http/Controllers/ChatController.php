<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Coach;
use App\Models\User;
use App\Notifications\ChatActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Chat member <-> admin (halaman customer).
 */
class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $member = $request->user();

        $messages = ChatMessage::with('broadcast')
            ->where('member_id', $member->id)
            ->orderByDesc('id')->limit(80)->get()->reverse()->values();

        $this->markRead($member->id);

        return view('chat.index', [
            'messages'   => $messages->map(fn ($m) => $m->toChatArray(ChatMessage::FROM_MEMBER)),
            'coachName'  => Coach::chatName(),
            'coachPhoto' => Coach::chatPhoto(),
        ]);
    }

    /** Ambil pesan baru (dipanggil berkala oleh halaman chat). */
    public function poll(Request $request): JsonResponse
    {
        $member = $request->user();
        $after  = (int) $request->query('after', 0);

        $messages = ChatMessage::with('broadcast')
            ->where('member_id', $member->id)->where('id', '>', $after)
            ->orderBy('id')->limit(100)->get();

        if ($messages->isNotEmpty()) {
            $this->markRead($member->id);
        }

        return response()->json([
            'messages' => $messages->map(fn ($m) => $m->toChatArray(ChatMessage::FROM_MEMBER)),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']], ['body.required' => 'Pesan masih kosong.']);
        $member = $request->user();

        $message = ChatMessage::create([
            'member_id'   => $member->id,
            'sender_id'   => $member->id,
            'sender_role' => ChatMessage::FROM_MEMBER,
            'body'        => trim($data['body']),
        ]);

        // Kabari semua admin lewat lonceng notifikasi
        $admins = User::where('role', 'admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new ChatActivity(
                'chat',
                'Pesan baru dari ' . $member->name,
                Str::limit($message->body, 140),
                '/admin/chat?member=' . $member->id,
            ));
        }

        return response()->json(['message' => $message->toChatArray(ChatMessage::FROM_MEMBER)]);
    }

    private function markRead(int $memberId): void
    {
        ChatMessage::where('member_id', $memberId)
            ->where('sender_role', ChatMessage::FROM_ADMIN)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
