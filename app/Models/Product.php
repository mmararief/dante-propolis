<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kategori_id',
        'sku',
        'nama_produk',
        'deskripsi',
        'harga_ecer',
        'stok',
        'gambar',
        'status',
    ];

    protected $casts = [
        'harga_ecer' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'kategori_id');
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function priceTiers()
    {
        return $this->hasMany(PriceTier::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    public function refreshStockCache(): void
    {
        $total = $this->batches()->sum('qty_remaining');
        $this->forceFill(['stok' => $total])->save();
    }
}
