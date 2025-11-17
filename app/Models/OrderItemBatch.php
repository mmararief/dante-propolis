<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'batch_id',
        'qty',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }
}
