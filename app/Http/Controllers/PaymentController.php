<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

class PaymentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/orders/{id}/upload-proof",
     *     tags={"Orders"},
     *     summary="Upload bukti pembayaran",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"bukti"},
     *                 @OA\Property(property="bukti", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Bukti pembayaran tersimpan"),
     *     @OA\Response(response=404, description="Order tidak ditemukan")
     * )
     */
    public function uploadProof(Request $request, int $orderId)
    {
        $order = Order::with('user')->findOrFail($orderId);

        $this->authorize('view', $order);

        $data = $request->validate([
            'bukti' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $path = $data['bukti']->store('bukti-pembayaran', 'public');

        $order->bukti_pembayaran = $path;
        $order->status = 'menunggu_konfirmasi';
        $order->save();

        return $this->success($order->fresh(), 'Bukti pembayaran berhasil diunggah');
    }
}
