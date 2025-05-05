<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticalModel extends Model
{
    use HasFactory;
    protected $table = 'artical';
    protected $guarded = [];
    public function status()
    {
        return $this->belongsTo(StatusModel::class, 'status_id');
    }
    public function categoryArtical()
    {
        return $this->belongsTo(CategoriesArticalModel::class, 'category_artical_id');
    }
    public function product()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id');
    }
}
