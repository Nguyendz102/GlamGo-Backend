<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantModel extends Model
{
    use HasFactory;

    protected $table = 'product_variants';
    protected $guarded = [];

    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
        'status' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id');
    }

    public function values()
    {
        return $this->hasMany(ProductVariantValueModel::class, 'product_variant_id');
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            ProductAttributeValuesModel::class,
            'product_variant_values',
            'product_variant_id',
            'product_attribute_value_id'
        );
    }
}
