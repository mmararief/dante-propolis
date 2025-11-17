<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subtotal',
        'ongkos_kirim',
        'total',
        'courier',
        'courier_service',
        'origin_city_id',
        'destination_city_id',
        'destination_district_id',
        'destination_subdistrict_id',
        'address',
        'phone',
        'status',
        'metode_pembayaran',
        'bukti_pembayaran',
        'resi',
        'reservation_expires_at',
    ];

    protected $appends = [
        'bukti_pembayaran_url',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'ongkos_kirim' => 'decimal:2',
        'total' => 'decimal:2',
        'reservation_expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeNeedReservationRelease($query)
    {
        return $query->whereIn('status', ['belum_dibayar', 'menunggu_konfirmasi'])
            ->whereNotNull('reservation_expires_at')
            ->where('reservation_expires_at', '<', now());
    }

    public function getBuktiPembayaranUrlAttribute(): ?string
    {
        if (! $this->bukti_pembayaran) {
            return null;
        }

        return Storage::disk('public')->url($this->bukti_pembayaran);
    }
}
