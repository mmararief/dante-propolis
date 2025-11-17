<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanTransaksi extends Model
{
    use HasFactory;

    protected $table = 'laporan_transaksi';

    protected $fillable = [
        'user_id',
        'periode_awal',
        'periode_akhir',
        'total_transaksi',
        'total_pendapatan',
        'total_produk_terjual',
        'tanggal_dibuat',
    ];

    protected $casts = [
        'periode_awal' => 'date',
        'periode_akhir' => 'date',
        'total_pendapatan' => 'decimal:2',
        'tanggal_dibuat' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


