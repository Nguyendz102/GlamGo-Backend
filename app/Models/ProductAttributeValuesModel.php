<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeValuesModel extends Model
{
    use HasFactory;
    protected $table = 'product_attribute_values';
    protected $guarded = [];
    public function  productImages()
    {
        return $this->hasOne(ProductImagesModel::class, 'product_attribute_value_id');
    }
    public function  productAttributeValueImage()
    {
        return $this->hasMany(ProductVariantValueImage::class, 'product_attribute_value_id');
    }
    public function attribute(){
        return $this->belongsTo(ProductAttributeModel::class, 'product_attribute_id');
    }
}
