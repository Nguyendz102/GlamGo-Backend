<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageCustomer extends Model
{
    use HasFactory;
    protected $table = 'message_customers';
    protected $fillable = ['id', 'session_id', 'admin_id', 'message', 'user_id', 'is_admin', 'username', 'email', 'created_at', 'updated_at', 'deleted_at'];
}
