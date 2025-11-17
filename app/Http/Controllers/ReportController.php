<?php

namespace App\Http\Controllers;

use App\Models\OrderItemBatch;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class ReportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/reports/batch-stock",
     *     tags={"Reports"},
     *     summary="Laporan stok per batch",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Berhasil memuat laporan")
     * )
     */
    public function batchStock()
    {
        $this->authorize('admin');

        $rows = ProductBatch::with('product')
            ->orderBy('product_id')
            ->orderBy('expiry_date')
            ->get()
            ->map(fn($batch) => [
                'product_id' => $batch->product_id,
                'nama_produk' => $batch->product?->nama_produk,
                'batch_number' => $batch->batch_number,
                'qty_initial' => $batch->qty_initial,
                'qty_remaining' => $batch->qty_remaining,
                'reserved_qty' => $batch->reserved_qty,
                'expiry_date' => optional($batch->expiry_date)->toDateString(),
            ]);

        return $this->success($rows);
    }

    /**
     * @OA\Get(
     *     path="/reports/batch-sales",
     *     tags={"Reports"},
     *     summary="Laporan penjualan per batch",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="from", in="query", description="Tanggal awal (Y-m-d)", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", description="Tanggal akhir (Y-m-d)", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Berhasil memuat laporan")
     * )
     */
    public function batchSales(Request $request)
    {
        $this->authorize('admin');

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = OrderItemBatch::query()
            ->select([
                'products.id as product_id',
                'products.nama_produk',
                'product_batches.batch_number',
                DB::raw('SUM(order_item_batches.qty) as qty_sold'),
            ])
            ->join('order_items', 'order_items.id', '=', 'order_item_batches.order_item_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('product_batches', 'product_batches.id', '=', 'order_item_batches.batch_id')
            ->whereIn('orders.status', ['diproses', 'dikirim', 'selesai'])
            ->groupBy('products.id', 'products.nama_produk', 'product_batches.batch_number');

        if ($request->filled('from')) {
            $query->whereDate('orders.created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('orders.created_at', '<=', $request->date('to'));
        }

        return $this->success($query->orderBy('products.nama_produk')->get());
    }
}
