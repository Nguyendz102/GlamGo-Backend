<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardItemModel extends Model
{
    use HasFactory;

    protected $table = 'card_item';
    protected $guarded = [];

    protected $casts = [
        'attribute_ids' => 'array',
        'product_variant_id' => 'integer',
        'price' => 'float',
        'quantity' => 'integer',
        'total_price' => 'float',
    ];

    public function cart()
    {
        return $this->belongsTo(CardModel::class, 'card_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariantModel::class, 'product_variant_id');
    }
}
