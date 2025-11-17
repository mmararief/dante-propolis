<?php

namespace Tests\Feature;

use App\Console\Commands\OrdersReleaseExpiredReservations;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use App\Services\BatchAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BatchAllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    private BatchAllocationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BatchAllocationService::class);
    }

    public function test_reserve_for_order_sets_reserved_qty_and_expiration(): void
    {
        $order = $this->prepareOrderWithBatches(4, [3, 3]);

        $this->service->reserveForOrder($order, 30);

        $order->refresh();
        $this->assertNotNull($order->reservation_expires_at);

        $batches = ProductBatch::orderBy('expiry_date')->get();
        $this->assertEquals(3, $batches[0]->reserved_qty);
        $this->assertEquals(1, $batches[1]->reserved_qty);
    }

    public function test_allocate_handles_multi_batch_distribution(): void
    {
        $order = $this->prepareOrderWithBatches(4, [3, 2]);

        $this->service->reserveForOrder($order);
        $this->service->allocate($order->id);

        $order->refresh();
        $this->assertEquals('belum_dibayar', $order->status);
        $this->assertNull($order->reservation_expires_at);

        $item = $order->items()->with('batches')->first();
        $this->assertTrue($item->allocated);
        $this->assertCount(2, $item->batches);

        $batchOne = $item->batches->firstWhere('qty', 3);
        $batchTwo = $item->batches->firstWhere('qty', 1);

        $this->assertNotNull($batchOne);
        $this->assertNotNull($batchTwo);

        $remaining = ProductBatch::orderBy('created_at')->get();
        $this->assertEquals(0, $remaining[0]->qty_remaining);
        $this->assertEquals(1, $remaining[1]->qty_remaining);
    }

    public function test_release_expired_reservations_returns_stock(): void
    {
        $order = $this->prepareOrderWithBatches(2, [2]);
        $this->service->reserveForOrder($order);

        $order->update([
            'reservation_expires_at' => now()->subMinutes(5),
            'status' => 'belum_dibayar',
        ]);

        $this->artisan('orders:release-expired-reservations');

        $order->refresh();

        $this->assertEquals('expired', $order->status);
        $this->assertEquals(0, ProductBatch::first()->reserved_qty);
        $this->assertDatabaseCount('order_item_batches', 0);
    }

    private function prepareOrderWithBatches(int $itemQty, array $batches): Order
    {
        $user = User::factory()->create();
        $category = Category::firstOrCreate(['nama_kategori' => 'Test']);
        $product = Product::create([
            'kategori_id' => $category->id,
            'sku' => Str::uuid(),
            'nama_produk' => 'Produk Test',
            'deskripsi' => 'Digunakan untuk pengujian.',
            'harga_ecer' => 50000,
            'stok' => 0,
            'status' => 'aktif',
        ]);

        foreach ($batches as $index => $qty) {
            ProductBatch::create([
                'product_id' => $product->id,
                'batch_number' => 'BATCH-' . ($index + 1),
                'qty_initial' => $qty,
                'qty_remaining' => $qty,
                'expiry_date' => now()->addDays(($index + 1) * 30),
                'reserved_qty' => 0,
            ]);
        }

        $order = Order::create([
            'user_id' => $user->id,
            'subtotal' => $itemQty * 50000,
            'ongkos_kirim' => 0,
            'total' => $itemQty * 50000,
            'address' => 'Jl. Test 123',
            'phone' => '628123456789',
            'status' => 'belum_dibayar',
            'metode_pembayaran' => 'BCA',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'harga_satuan' => 50000,
            'jumlah' => $itemQty,
            'total_harga' => $itemQty * 50000,
        ]);

        return $order->fresh('items');
    }
}
