<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/orders/{id}",
     *     tags={"Orders"},
     *     summary="Detail order milik pelanggan",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detail order beserta alokasi batch"),
     *     @OA\Response(response=403, description="Tidak berhak mengakses order ini"),
     *     @OA\Response(response=404, description="Order tidak ditemukan")
     * )
     */
    public function show(Request $request, int $orderId)
    {
        $order = Order::with(['items.product', 'items.batches.batch'])->findOrFail($orderId);

        $this->authorize('view', $order);

        return $this->success($order);
    }
}
