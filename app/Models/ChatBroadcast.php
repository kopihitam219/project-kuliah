<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatBroadcast extends Model
{
    protected $fillable = ['admin_id', 'title', 'body', 'recipients'];

    protected $casts = ['recipients' => 'integer'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
