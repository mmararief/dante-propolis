<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendOrderShippedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $orderId)
    {
    }

    public function handle(): void
    {
        $order = Order::with('user')->find($this->orderId);

        if (! $order) {
            return;
        }

        // TODO: ganti dengan notifikasi email / push sesuai kebutuhan
        Log::info('Notifikasi pengiriman dikirim', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'resi' => $order->resi,
        ]);
    }
}
