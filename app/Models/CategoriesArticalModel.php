<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriesArticalModel extends Model
{
    use HasFactory;
    protected $table = 'categories_artical';
    protected $guarded = [];
    public function status()
    {
        return $this->belongsTo(StatusModel::class, 'status_id');
    }

    public function artical() {
        return $this->hasMany(ArticalModel::class, 'category_artical_id');
    }
}
