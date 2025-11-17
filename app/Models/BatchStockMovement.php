<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'change_qty',
        'reason',
        'reference_table',
        'reference_id',
        'note',
    ];

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }
}
