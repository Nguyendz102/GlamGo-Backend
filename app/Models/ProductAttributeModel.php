<?php

namespace App\Models;

use App\Http\Controllers\Api\Product\ProductAttributeValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeModel extends Model
{
    use HasFactory;
    protected $table = 'product_attribute';
    protected $guarded = [];

    public function attributeValue()
    {
        return $this->hasMany(ProductAttributeValuesModel::class, 'product_attribute_id');
    }
    public function product()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id');
    }
}
