<?php

namespace App\Console\Commands;

use App\Models\ProductBatch;
use Illuminate\Console\Command;

class BatchReportExpiring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:report-expiring {--days=30 : Rentang hari ke depan untuk mengecek expiry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menampilkan batch yang akan kedaluwarsa dalam rentang hari tertentu.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days') ?: 30;
        $limitDate = now()->addDays($days);

        $batches = ProductBatch::with('product')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->toDateString(), $limitDate->toDateString()])
            ->orderBy('expiry_date')
            ->get();

        if ($batches->isEmpty()) {
            $this->info('Tidak ada batch yang akan expired dalam periode tersebut.');

            return self::SUCCESS;
        }

        $rows = $batches->map(function (ProductBatch $batch) {
            return [
                'Produk' => $batch->product?->nama_produk ?? '-',
                'Batch' => $batch->batch_number,
                'Expiry' => optional($batch->expiry_date)->format('Y-m-d'),
                'Sisa Qty' => $batch->qty_remaining,
                'Reserved' => $batch->reserved_qty,
            ];
        })->toArray();

        $this->table(array_keys($rows[0]), $rows);

        return self::SUCCESS;
    }
}
