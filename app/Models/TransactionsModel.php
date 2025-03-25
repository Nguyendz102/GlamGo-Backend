<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionsModel extends Model
{
    use HasFactory;
    protected $table = 'transactions';
    protected $guarded = [];
    public function status()
    {
        return $this->belongsTo(StatusModel::class, 'status_id');
    }
    public function order()
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }
}
