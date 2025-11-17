<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ReleaseExpiredReservationJob;
use App\Jobs\SendOrderShippedNotificationJob;
use App\Models\Order;
use App\Services\BatchAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class AdminOrderController extends Controller
{
    public function __construct(private readonly BatchAllocationService $allocationService) {}

    /**
     * @OA\Get(
     *     path="/admin/orders",
     *     tags={"Admin"},
     *     summary="Daftar order",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Daftar order")
     * )
     */
    public function index(Request $request)
    {
        $this->authorize('admin');

        $orders = Order::with('user')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success($orders);
    }

    /**
     * @OA\Get(
     *     path="/admin/orders/{id}",
     *     tags={"Admin"},
     *     summary="Detail order untuk admin",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detail order"),
     *     @OA\Response(response=404, description="Order tidak ditemukan")
     * )
     */
    public function show(int $id)
    {
        $this->authorize('admin');

        $order = Order::with(['user', 'items.product', 'items.batches.batch'])->findOrFail($id);

        return $this->success($order);
    }

    /**
     * @OA\Post(
     *     path="/admin/orders/{id}/verify-payment",
     *     tags={"Admin"},
     *     summary="Verifikasi pembayaran dan alokasi batch",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Order dialokasikan"),
     *     @OA\Response(response=422, description="Status tidak valid")
     * )
     */
    public function verifyPayment(Request $request, int $orderId)
    {
        $this->authorize('admin');

        $order = Order::with('items')->findOrFail($orderId);

        if (! in_array($order->status, ['belum_dibayar', 'menunggu_konfirmasi'], true)) {
            return $this->fail('Status order tidak valid untuk verifikasi', 422);
        }

        DB::transaction(function () use ($order) {
            $this->allocationService->allocate($order->id);
            $order->status = 'diproses';
            $order->save();
        });

        return $this->success($order->fresh(['items.batches.batch']), 'Order verified and allocated');
    }

    /**
     * @OA\Post(
     *     path="/admin/orders/{id}/ship",
     *     tags={"Admin"},
     *     summary="Input resi pengiriman",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"resi"},
     *             @OA\Property(property="resi", type="string", example="JNE123456")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Order dikirim")
     * )
     */
    public function ship(Request $request, int $orderId)
    {
        $this->authorize('admin');

        $order = Order::findOrFail($orderId);

        $data = $request->validate([
            'resi' => ['required', 'string', 'max:100'],
        ]);

        $order->resi = $data['resi'];
        $order->status = 'dikirim';
        $order->save();

        SendOrderShippedNotificationJob::dispatch($order->id);

        return $this->success($order, 'Order diperbarui menjadi dikirim');
    }

    /**
     * @OA\Post(
     *     path="/admin/run-reservation-release",
     *     tags={"Admin"},
     *     summary="Trigger job pelepasan reservasi stok",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Job dikirim ke antrean")
     * )
     */
    public function runReservationRelease()
    {
        $this->authorize('admin');

        ReleaseExpiredReservationJob::dispatch();

        return $this->success(null, 'Job release reservasi sudah dijalankan');
    }
}
