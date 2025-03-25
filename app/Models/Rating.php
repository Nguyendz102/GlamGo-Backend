<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Rating extends Model
{
    use HasFactory, HasApiTokens;
    protected $fillable = ['id', 'product_id', 'fullname', 'phone', 'status_id', 'is_introduce', 'comment', 'image_real', 'country_id', 'rating_value', 'sessionId'];

    protected $casts = [
        'image_real' => 'array'
    ];

    public function product()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id');
    }

    public function status()
    {
        return $this->belongsTo(StatusModel::class, 'status_id');
    }
}
