<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\BatchAllocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredReservationJob implements ShouldQueue
{
    use Queueable;

    public function handle(BatchAllocationService $allocationService): void
    {
        $orders = Order::needReservationRelease()->get();

        if ($orders->isEmpty()) {
            Log::info('[ReleaseExpiredReservationJob] Tidak ada order expired.');

            return;
        }

        foreach ($orders as $order) {
            $allocationService->releaseReservation($order);
            $order->status = 'expired';
            $order->save();
        }

        Log::info('[ReleaseExpiredReservationJob] Reservasi dilepas', ['count' => $orders->count()]);
    }
}
