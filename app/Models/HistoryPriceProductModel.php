<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryPriceProductModel extends Model
{
    use HasFactory;
    protected $table = 'history_price_product';
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id');
    }
}
