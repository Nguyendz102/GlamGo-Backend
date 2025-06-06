<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriesModel extends Model
{
    use HasFactory;
    protected $table = 'categories';
    protected $guarded = [];

    public function status()
    {
        return $this->belongsTo(StatusModel::class, 'status_id');
    }

    public function children()
    {
        return $this->hasMany(CategoriesModel::class, 'parent_id');
    }

    public function allChildrenIds()
    {
        return $this->children()->with('allChildrenIds')->pluck('id')->toArray();
    }

    public function products()
    {
        return $this->hasMany(ProductsModel::class, 'category_id');
    }
}
