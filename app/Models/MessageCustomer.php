<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageCustomer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'message_customers';

    protected $fillable = [
        'session_id',
        'admin_id',
        'message',
        'message_type',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
        'user_id',
        'is_admin',
        'sender_type',
        'username',
        'email',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function toChatPayload(): array
    {
        $this->loadMissing(['customer', 'admin']);
        $senderType = $this->sender_type ?: ($this->is_admin ? User::ROLE_ADMIN : User::ROLE_CUSTOMER);
        $sender = $senderType === User::ROLE_ADMIN ? $this->admin : $this->customer;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'admin_id' => $this->admin_id,
            'message' => $this->message,
            'message_type' => $this->message_type ?: 'text',
            'file_url' => $this->file_path ? asset(ltrim($this->file_path, '/')) : null,
            'file_path' => $this->file_path,
            'file_name' => $this->file_name,
            'file_mime' => $this->file_mime,
            'file_size' => $this->file_size,
            'is_admin' => $this->is_admin,
            'sender_type' => $senderType,
            'sender_name' => $senderType === 'bot' ? 'GlamGo Bot' : ($sender?->name ?? $this->username),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
