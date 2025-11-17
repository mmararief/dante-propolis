<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\BatchAllocationService;
use Illuminate\Console\Command;

class OrdersReleaseExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:release-expired-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Melepas reservasi batch untuk order yang sudah melewati batas waktu pembayaran.';

    public function __construct(private readonly BatchAllocationService $allocationService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredOrders = Order::needReservationRelease()->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('Tidak ada reservasi yang perlu dilepas.');

            return self::SUCCESS;
        }

        foreach ($expiredOrders as $order) {
            $this->allocationService->releaseReservation($order);
            $order->status = 'expired';
            $order->save();
        }

        $this->info("Reservasi dilepas untuk {$expiredOrders->count()} order.");

        return self::SUCCESS;
    }
}
