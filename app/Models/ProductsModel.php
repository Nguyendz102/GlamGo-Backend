<?php

namespace App\Models;

use App\Http\Controllers\Api\Product\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCommonRelations;

class ProductsModel extends Model
{
    use HasFactory, HasCommonRelations;
    protected $table = 'products';
    protected $guarded = [];


    public function category()
    {
        return $this->belongsTo(CategoriesModel::class, 'category_id');
    }
    public function  productImages()
    {
        return $this->hasMany(ProductImagesModel::class, 'product_id');
    }
    public function attribute()
    {
        return $this->hasMany(ProductAttributeModel::class, 'product_id');
    }
    public function  productImages2()
    {
        return $this->belongsTo(ProductImagesModel::class, 'id', 'product_id');
    }
    public function country()
    {
        return $this->belongsTo(CountryModel::class, 'country_id');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'product_id');
    }

    public function article()
    {
        return $this->hasMany(ArticalModel::class, 'product_id');
    }
}
