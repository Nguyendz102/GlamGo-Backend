<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryModel extends Model
{
    use HasFactory;
    protected $table = 'country';
    protected $guarded = [];

    public function status()
    {
        return $this->belongsTo(StatusModel::class, 'status_id');
    }
}
