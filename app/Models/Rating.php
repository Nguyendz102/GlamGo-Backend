<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $table = 'ratings';

    protected $fillable = [
        'product_id',
        'fullname',
        'phone',
        'status_id',
        'is_introduce',
        'comment',
        'image_real',
        'country_id',
        'user_id',
        'admin_id',
        'admin_reply',
        'admin_replied_at',
        'rating_value',
        'sessionId',
    ];

    protected $casts = [
        'image_real' => 'array',
        'is_introduce' => 'boolean',
        'rating_value' => 'integer',
        'admin_replied_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id');
    }

    public function status()
    {
        return $this->belongsTo(StatusModel::class, 'status_id');
    }

    public function user()
    {
        return $this->belongsTo(UsersModel::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(UsersModel::class, 'admin_id');
    }
}
