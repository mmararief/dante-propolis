<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceTier extends Model
{
    use HasFactory;

    protected $table = 'harga_tingkat';

    protected $fillable = [
        'product_id',
        'min_jumlah',
        'max_jumlah',
        'harga_total',
        'label',
    ];

    protected $casts = [
        'harga_total' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}


