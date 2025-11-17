<?php

namespace Database\Seeders;

use App\Models\BatchStockMovement;
use App\Models\Category;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'nama_lengkap' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'no_hp' => '628123456789',
                'alamat_lengkap' => 'Jl. Propolis No. 1 Jakarta',
            ]
        );

        $categories = collect(['Propolis', 'Perawatan', 'Bundling'])
            ->map(fn ($name) => Category::firstOrCreate(['nama_kategori' => $name]));

        $products = collect([
            ['sku' => 'PRP-001', 'nama' => 'Propolis 10ml', 'harga' => 75000],
            ['sku' => 'PRP-002', 'nama' => 'Propolis 20ml', 'harga' => 135000],
            ['sku' => 'PRP-003', 'nama' => 'Face Serum', 'harga' => 99000],
            ['sku' => 'PRP-004', 'nama' => 'Bundling Hemat', 'harga' => 210000],
            ['sku' => 'PRP-005', 'nama' => 'Honey Boost', 'harga' => 68000],
        ])->map(function ($item, $index) use ($categories) {
            return Product::updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'kategori_id' => $categories[$index % $categories->count()]->id,
                    'nama_produk' => $item['nama'],
                    'deskripsi' => 'Produk sampel untuk pengujian backend.',
                    'harga_ecer' => $item['harga'],
                    'stok' => 0,
                    'status' => 'aktif',
                ]
            );
        });

        foreach ($products as $product) {
            PriceTier::updateOrCreate(
                ['product_id' => $product->id, 'min_jumlah' => 5],
                ['max_jumlah' => 9, 'harga_total' => $product->harga_ecer * 0.95, 'label' => 'Reseller Lite']
            );

            PriceTier::updateOrCreate(
                ['product_id' => $product->id, 'min_jumlah' => 10],
                ['max_jumlah' => null, 'harga_total' => $product->harga_ecer * 0.9, 'label' => 'Reseller Pro']
            );

            $batch = ProductBatch::create([
                'product_id' => $product->id,
                'batch_number' => 'BATCH-'.$product->id.'-'.now()->format('Ym'),
                'qty_initial' => 100,
                'qty_remaining' => 100,
                'expiry_date' => now()->addMonths(6),
                'purchase_price' => $product->harga_ecer * 0.6,
            ]);

            BatchStockMovement::create([
                'batch_id' => $batch->id,
                'change_qty' => 100,
                'reason' => 'restock',
                'reference_table' => 'seeders',
                'reference_id' => $batch->id,
                'note' => 'Seed data restock',
            ]);

            $product->refreshStockCache();
        }

        Cache::put('rajaongkir:provinces', [
            ['province_id' => 1, 'province' => 'DKI Jakarta'],
            ['province_id' => 9, 'province' => 'Jawa Barat'],
        ], now()->addDay());
    }
}
