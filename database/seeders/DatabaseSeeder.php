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
            ->map(fn($name) => Category::firstOrCreate(['nama_kategori' => $name]));

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
                'batch_number' => 'BATCH-' . $product->id . '-' . now()->format('Ym'),
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

        // Cache::put('rajaongkir:provinces', [
        //     ['province_id' => 1, 'province' => 'NUSA TENGGARA BARAT (NTB)'],
        //     ['province_id' => 2, 'province' => 'NUSA TENGGARA BARAT'],
        //     ['province_id' => 3, 'province' => 'MALUKU'],
        //     ['province_id' => 4, 'province' => 'KALIMANTAN SELATAN'],
        //     ['province_id' => 5, 'province' => 'KALIMANTAN TENGAH'],
        //     ['province_id' => 6, 'province' => 'JAWA BARAT'],
        //     ['province_id' => 7, 'province' => 'BENGKULU'],
        //     ['province_id' => 8, 'province' => 'KALIMANTAN TIMUR'],
        //     ['province_id' => 9, 'province' => 'KEPULAUAN RIAU'],
        //     ['province_id' => 10, 'province' => 'NANGGROE ACEH DARUSSALAM (NAD)'],
        //     ['province_id' => 11, 'province' => 'DKI JAKARTA'],
        //     ['province_id' => 12, 'province' => 'BANTEN'],
        //     ['province_id' => 13, 'province' => 'JAWA TENGAH'],
        //     ['province_id' => 14, 'province' => 'JAMBI'],
        //     ['province_id' => 15, 'province' => 'PAPUA'],
        //     ['province_id' => 16, 'province' => 'BALI'],
        //     ['province_id' => 17, 'province' => 'SUMATERA UTARA'],
        //     ['province_id' => 18, 'province' => 'GORONTALO'],
        //     ['province_id' => 19, 'province' => 'JAWA TIMUR'],
        //     ['province_id' => 20, 'province' => 'DI YOGYAKARTA'],
        //     ['province_id' => 21, 'province' => 'SULAWESI TENGGARA'],
        //     ['province_id' => 22, 'province' => 'NUSA TENGGARA TIMUR (NTT)'],
        //     ['province_id' => 23, 'province' => 'SULAWESI UTARA'],
        //     ['province_id' => 24, 'province' => 'SUMATERA BARAT'],
        //     ['province_id' => 25, 'province' => 'BANGKA BELITUNG'],
        //     ['province_id' => 26, 'province' => 'RIAU'],
        //     ['province_id' => 27, 'province' => 'SUMATERA SELATAN'],
        //     ['province_id' => 28, 'province' => 'SULAWESI TENGAH'],
        //     ['province_id' => 29, 'province' => 'KALIMANTAN BARAT'],
        //     ['province_id' => 30, 'province' => 'PAPUA BARAT'],
        //     ['province_id' => 31, 'province' => 'LAMPUNG'],
        //     ['province_id' => 32, 'province' => 'KALIMANTAN UTARA'],
        //     ['province_id' => 33, 'province' => 'MALUKU UTARA'],
        //     ['province_id' => 34, 'province' => 'SULAWESI SELATAN'],
        //     ['province_id' => 35, 'province' => 'SULAWESI BARAT'],
        // ], now()->addDay());
    }
}
