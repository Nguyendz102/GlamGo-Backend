<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryLogModel extends Model
{
    use HasFactory;
    protected $table = 'history_log';
    protected $guarded = [];
}
