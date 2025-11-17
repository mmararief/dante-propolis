<?php

namespace App\Http\Controllers;

use App\Models\BatchStockMovement;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class BatchController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products/{productId}/batches",
     *     tags={"Batches"},
     *     summary="Daftar batch untuk produk",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Daftar batch")
     * )
     */
    public function index(int $productId)
    {
        $product = Product::with(['batches' => function ($query) {
            $query->orderBy('expiry_date');
        }])->findOrFail($productId);

        return $this->success($product->batches);
    }

    /**
     * @OA\Post(
     *     path="/products/{productId}/batches",
     *     tags={"Batches"},
     *     summary="Tambah batch baru / restock",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"batch_number","qty_initial"},
     *             @OA\Property(property="batch_number", type="string"),
     *             @OA\Property(property="qty_initial", type="integer"),
     *             @OA\Property(property="expiry_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="purchase_price", type="number", format="float", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Batch berhasil ditambahkan")
     * )
     */
    public function store(Request $request, int $productId)
    {
        $this->authorize('admin');

        $product = Product::findOrFail($productId);

        $data = $request->validate([
            'batch_number' => ['required', 'string', 'max:50'],
            'qty_initial' => ['required', 'integer', 'min:1'],
            'expiry_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($product, $data) {
            $batch = ProductBatch::create([
                'product_id' => $product->id,
                'batch_number' => $data['batch_number'],
                'qty_initial' => $data['qty_initial'],
                'qty_remaining' => $data['qty_initial'],
                'expiry_date' => $data['expiry_date'] ?? null,
                'purchase_price' => $data['purchase_price'] ?? null,
            ]);

            BatchStockMovement::create([
                'batch_id' => $batch->id,
                'change_qty' => $batch->qty_initial,
                'reason' => 'restock',
                'reference_table' => 'product_batches',
                'reference_id' => $batch->id,
                'note' => 'Restock awal batch',
            ]);

            $product->refreshStockCache();

            return $this->success($batch->fresh(), 'Batch berhasil ditambahkan', 201);
        });
    }
}
