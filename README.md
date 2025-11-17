## Backend Propolis Fulfillment (Laravel 11 + Sanctum)

Implementasi backend lengkap sesuai SDD + batch tracking + integrasi RajaOngkir.

### 1. Prasyarat

-   PHP 8.2+, Composer
-   MySQL 8, Redis (Predis client)
-   Eksternal API key RajaOngkir (`https://rajaongkir.komerce.id/api/v1`)

### 2. Setup Cepat

```bash
cp .env.example .env        # sesuaikan DB & RAJAONGKIR_KEY
composer install
php artisan key:generate
php artisan migrate --seed  # membuat struktur + data contoh
php artisan storage:link
```

> Seeder membuat 1 user admin (`admin@example.com` / `password`), 3 kategori, 5 produk, batch sample dan cache provinsi.

### 3. Menjalankan Aplikasi

-   HTTP server lokal: `php artisan serve`
-   Queue worker: `php artisan queue:work`
-   Scheduler (cron): jalankan `php artisan schedule:run` tiap menit, atau crontab
-   Perintah penting:
    -   `php artisan orders:release-expired-reservations`
    -   `php artisan batch:report-expiring --days=30`

### 4. Struktur Fitur Inti

-   **Autentikasi**: Laravel Sanctum (token bearer).
-   **Batch & stok**: tabel `product_batches`, `batch_stock_movements`, service `BatchAllocationService`.
    -   Reservasi ketika checkout → `reserved_qty` + `order_item_batches`.
    -   Finalisasi saat admin verifikasi pembayaran → `qty_remaining` turun, log movement `sold`.
-   **Integrasi RajaOngkir**: `App\Services\RajaOngkirService` (caching provinsi/kota).
-   **Jobs & Commands**:
    -   `ReleaseExpiredReservationJob` + command `orders:release-expired-reservations`.
    -   `SendOrderShippedNotificationJob` (placeholder log).
    -   `batch:report-expiring --days=30` menampilkan batch mendekati expiry.
-   **Reports**: `GET /api/reports/batch-stock`, `GET /api/reports/batch-sales?from=&to=` memakai query SQL pada doc.

### 5. API Overview

Semua response mengikuti format `{ success, data, message }`.

Public / umum:

| Method | Endpoint                                | Deskripsi                                                  |
| ------ | --------------------------------------- | ---------------------------------------------------------- |
| GET    | `/api/products`                         | List produk + ringkasan batch                              |
| GET    | `/api/products/{id}`                    | Detail + batch & harga tingkat                             |
| GET    | `/api/shipping/provinces`               | Proxy RajaOngkir (cache)                                   |
| GET    | `/api/shipping/cities/{province}`       | 〃                                                         |
| GET    | `/api/shipping/districts/{city}`        | 〃                                                         |
| GET    | `/api/shipping/subdistricts/{district}` | 〃                                                         |
| POST   | `/api/shipping/cost`                    | Hitung ongkir (body: origin, destination, weight, courier) |

Auth (Sanctum):

| Method | Endpoint                        | Deskripsi                         |
| ------ | ------------------------------- | --------------------------------- |
| POST   | `/api/checkout`                 | Buat order + reserve batch        |
| POST   | `/api/orders/{id}/upload-proof` | Upload bukti (pelanggan)          |
| GET    | `/api/orders/{id}`              | Detail order (policy owner/admin) |

Admin (Sanctum + Gate `admin`):

| Method | Endpoint                                | Deskripsi                     |
| ------ | --------------------------------------- | ----------------------------- |
| POST   | `/api/products`                         | Tambah produk                 |
| PUT    | `/api/products/{id}`                    | Ubah produk                   |
| POST   | `/api/products/{id}/batches`            | Restock batch                 |
| GET    | `/api/products/{id}/batches`            | List batch per produk         |
| POST   | `/api/products/{id}/price-tiers`        | Kelola harga tingkat          |
| GET    | `/api/admin/orders`                     | List order + filter status    |
| GET    | `/api/admin/orders/{id}`                | Detail order lengkap          |
| POST   | `/api/admin/orders/{id}/verify-payment` | Finalisasi + alokasi batch    |
| POST   | `/api/admin/orders/{id}/ship`           | Input resi + kirim notifikasi |
| POST   | `/api/admin/run-reservation-release`    | Trigger job rilis reservasi   |
| GET    | `/api/reports/batch-stock`              | Laporan stok batch            |
| GET    | `/api/reports/batch-sales`              | Laporan penjualan batch       |

### 6. Contoh Curl

```bash
# Ambil provinsi via RajaOngkir proxy
curl -H "Accept: application/json" \
     -H "Authorization: Bearer {TOKEN}" \
     http://localhost:8000/api/shipping/provinces

# Checkout (token user)
curl -X POST http://localhost:8000/api/checkout \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{ "destination_city_id": 501, "address":"Jl. Mawar 1", "phone":"62812",
        "metode_pembayaran":"BCA", "items":[{ "product_id":1,"jumlah":3 }] }'

# Upload bukti pembayaran
curl -X POST http://localhost:8000/api/orders/1/upload-proof \
  -H "Authorization: Bearer {TOKEN}" \
  -F "bukti=@/path/bukti-transfer.jpg"

# Admin verifikasi pembayaran
curl -X POST http://localhost:8000/api/admin/orders/1/verify-payment \
  -H "Authorization: Bearer {ADMIN_TOKEN}"
```

### 7. SQL Referensi

```sql
-- Laporan stok batch
SELECT p.id, p.nama_produk, pb.batch_number, pb.qty_initial, pb.qty_remaining, pb.reserved_qty, pb.expiry_date
FROM product_batches pb
JOIN products p ON p.id = pb.product_id
ORDER BY p.nama_produk, pb.expiry_date;

-- Penjualan per batch
SELECT p.id, p.nama_produk, pb.batch_number, SUM(oib.qty) AS qty_sold
FROM order_item_batches oib
JOIN product_batches pb ON pb.id = oib.batch_id
JOIN order_items oi ON oi.id = oib.order_item_id
JOIN orders o ON o.id = oi.order_id
JOIN products p ON p.id = oi.product_id
WHERE o.status IN ('diproses','dikirim','selesai')
GROUP BY p.id, pb.batch_number;
```

### 8. Testing

```
php artisan test --filter=BatchAllocationServiceTest
```

Test meliputi:

1. Reservasi menambah `reserved_qty` + set `reservation_expires_at`.
2. Final allocate mendukung multi batch (3+1).
3. Command `orders:release-expired-reservations` mengembalikan stok & ubah status `expired`.

### 9. Dokumentasi API

-   Halaman ringkas manual: `http://localhost:8000/docs/api` (ringkasan endpoint + curl).
-   Swagger UI interaktif: `http://localhost:8000/docs/openapi`
    -   Regenerasi spesifikasi: `php artisan l5-swagger:generate`
    -   File JSON tersimpan di `storage/api-docs/api-docs.json`

### 10. Catatan Tambahan

-   Jalankan queue worker agar job `SendOrderShippedNotificationJob` & rilis reservasi otomatis dieksekusi.
-   `laporan_transaksi` disiapkan untuk materialized summary—jalankan proses ETL terjadwal sesuai kebutuhan bisnis.
-   Simpan `RAJAONGKIR_KEY` di `.env`, jangan hardcode.
