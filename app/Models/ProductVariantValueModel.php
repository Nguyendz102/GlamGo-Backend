<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantValueModel extends Model
{
    use HasFactory;

    protected $table = 'product_variant_values';
    protected $guarded = [];

    public function variant()
    {
        return $this->belongsTo(ProductVariantModel::class, 'product_variant_id');
    }

    public function attributeValue()
    {
        return $this->belongsTo(ProductAttributeValuesModel::class, 'product_attribute_value_id');
    }
}
