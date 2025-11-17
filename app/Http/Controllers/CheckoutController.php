<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PriceTier;
use App\Models\Product;
use App\Services\BatchAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class CheckoutController extends Controller
{
    public function __construct(private readonly BatchAllocationService $allocationService) {}

    /**
     * @OA\Post(
     *     path="/checkout",
     *     tags={"Orders"},
     *     summary="Checkout dan reservasi batch",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CheckoutRequest")
     *     ),
     *     @OA\Response(response=200, description="Order berhasil dibuat"),
     *     @OA\Response(response=401, description="Tidak terautentikasi"),
     *     @OA\Response(response=422, description="Stok tidak mencukupi")
     * )
     */
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'courier' => ['nullable', 'string', 'max:50'],
            'courier_service' => ['nullable', 'string', 'max:50'],
            'origin_city_id' => ['nullable', 'integer'],
            'destination_city_id' => ['required', 'integer'],
            'destination_district_id' => ['nullable', 'integer'],
            'destination_subdistrict_id' => ['nullable', 'integer'],
            'address' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:20'],
            'metode_pembayaran' => ['required', 'in:BCA,BSI,gopay,dana,transfer_manual'],
            'ongkos_kirim' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.harga_tingkat_id' => ['nullable', 'integer'],
            'items.*.catatan' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        if (! $user) {
            return $this->fail('User harus login untuk checkout', 401);
        }

        try {
            $order = DB::transaction(function () use ($user, $data) {
                $pricing = $this->calculatePricing($data['items']);

                $order = Order::create([
                    'user_id' => $user->id,
                    'subtotal' => $pricing['subtotal'],
                    'ongkos_kirim' => $data['ongkos_kirim'] ?? 0,
                    'total' => $pricing['subtotal'] + ($data['ongkos_kirim'] ?? 0),
                    'courier' => $data['courier'] ?? null,
                    'courier_service' => $data['courier_service'] ?? null,
                    'origin_city_id' => $data['origin_city_id'] ?? null,
                    'destination_city_id' => $data['destination_city_id'],
                    'destination_district_id' => $data['destination_district_id'] ?? null,
                    'destination_subdistrict_id' => $data['destination_subdistrict_id'] ?? null,
                    'address' => $data['address'],
                    'phone' => $data['phone'],
                    'status' => 'belum_dibayar',
                    'metode_pembayaran' => $data['metode_pembayaran'],
                ]);

                foreach ($pricing['items'] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'harga_satuan' => $item['harga_satuan'],
                        'jumlah' => $item['jumlah'],
                        'total_harga' => $item['total'],
                        'catatan' => $item['catatan'],
                    ]);
                }

                $order->load('items');

                $this->allocationService->reserveForOrder($order);

                return $order->fresh(['items.batches.batch']);
            });
        } catch (InsufficientStockException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->success($order, 'Order berhasil dibuat dan stok sudah di-reserve');
    }

    private function calculatePricing(array $items): array
    {
        $result = [
            'subtotal' => 0,
            'items' => [],
        ];

        foreach ($items as $payload) {
            /** @var Product $product */
            $product = Product::with('priceTiers')->findOrFail($payload['product_id']);
            $qty = (int) $payload['jumlah'];
            $tier = null;

            if (! empty($payload['harga_tingkat_id'])) {
                $tier = PriceTier::where('product_id', $product->id)
                    ->where('id', $payload['harga_tingkat_id'])
                    ->first();
            }

            if (! $tier) {
                $tier = $product->priceTiers()
                    ->where('min_jumlah', '<=', $qty)
                    ->where(function ($query) use ($qty) {
                        $query->whereNull('max_jumlah')
                            ->orWhere('max_jumlah', '>=', $qty);
                    })
                    ->orderByDesc('min_jumlah')
                    ->first();
            }

            $hargaSatuan = $tier?->harga_total ?? $product->harga_ecer;
            $total = $hargaSatuan * $qty;

            $result['items'][] = [
                'product_id' => $product->id,
                'harga_satuan' => $hargaSatuan,
                'jumlah' => $qty,
                'total' => $total,
                'catatan' => $payload['catatan'] ?? null,
            ];

            $result['subtotal'] += $total;
        }

        return $result;
    }
}
