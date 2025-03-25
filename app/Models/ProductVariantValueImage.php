<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantValueImage extends Model
{
    protected $table = 'product_variant_value_images';
    use HasFactory;
    protected $fillable = [
        'product_attribute_value_id',
        'image',
        'alt_image',
    ];
}
