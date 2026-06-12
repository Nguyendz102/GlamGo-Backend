<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatSession extends Model
{
    use HasFactory;

    public const HANDLED_BY_BOT = 'bot';
    public const HANDLED_BY_ADMIN = 'admin';

    protected $fillable = [
        'user_id',
        'admin_id',
        'handled_by',
        'admin_active_until',
        'last_customer_message_at',
        'last_admin_message_at',
        'last_bot_message_at',
        'last_handoff_at',
    ];

    protected $casts = [
        'admin_active_until' => 'datetime',
        'last_customer_message_at' => 'datetime',
        'last_admin_message_at' => 'datetime',
        'last_bot_message_at' => 'datetime',
        'last_handoff_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function isHandledByBot(): bool
    {
        return $this->handled_by === self::HANDLED_BY_BOT;
    }

    public function toPayload(): array
    {
        return [
            'handled_by' => $this->handled_by,
            'admin_id' => $this->admin_id,
            'admin_name' => $this->admin?->name,
            'admin_active_until' => $this->admin_active_until?->toISOString(),
        ];
    }
}
