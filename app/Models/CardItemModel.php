<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardItemModel extends Model
{
    use HasFactory;
    protected $table = 'card_item';
    protected $guarded = []; 
}
