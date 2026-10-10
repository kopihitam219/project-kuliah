<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    public const FROM_ADMIN  = 'admin';
    public const FROM_MEMBER = 'member';

    protected $fillable = ['member_id', 'sender_id', 'sender_role', 'body', 'broadcast_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(ChatBroadcast::class, 'broadcast_id');
    }

    /** Pesan admin yang belum dibaca member ini. */
    public static function unreadForMember(int $memberId): int
    {
        return static::where('member_id', $memberId)->where('sender_role', self::FROM_ADMIN)->whereNull('read_at')->count();
    }

    /** Pesan member yang belum dibaca admin. */
    public static function unreadForAdmin(): int
    {
        return static::where('sender_role', self::FROM_MEMBER)->whereNull('read_at')->count();
    }

    /** Bentuk data untuk tampilan chat (JSON). */
    public function toChatArray(string $viewerRole): array
    {
        $title = null;

        if ($this->broadcast_id) {
            $title = optional($this->broadcast)->title;
        }

        return [
            'id'        => $this->id,
            'body'      => $this->body,
            'mine'      => $this->sender_role === $viewerRole,
            'from'      => $this->sender_role,
            'broadcast' => (bool) $this->broadcast_id,
            'title'     => $title,
            'time'      => $this->created_at?->timezone(config('app.timezone'))->format('H:i'),
            'date'      => $this->created_at?->timezone(config('app.timezone'))->locale('id')->translatedFormat('d M Y'),
            'read'      => (bool) $this->read_at,
        ];
    }
}
