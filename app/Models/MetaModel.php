<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaModel extends Model
{
    protected $table = 'meta';
    protected $guarded = [];
    use HasFactory;
}
