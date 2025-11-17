<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi API Propolis</title>
    <style>
        :root {
            color-scheme: light dark;
        }
        html, body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8fafc;
            color: #0f172a;
        }
        body {
            padding: 2rem;
        }
        h1, h2, h3 {
            color: #0f172a;
        }
        code {
            background: #e2e8f0;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }
        pre {
            background: #0f172a;
            color: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        th, td {
            border: 1px solid #cbd5f5;
            padding: 0.75rem;
            text-align: left;
        }
        th {
            background: #e2e8f0;
        }
        .badge {
            padding: 0.1rem 0.45rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        .badge.get { background: #059669; }
        .badge.post { background: #2563eb; }
        .badge.put { background: #d97706; }
        .badge.delete { background: #dc2626; }
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #f8fafc; }
            code { background: #1f2937; }
            table, th, td { border-color: #1f2937; }
            th { background: #1f2937; }
        }
    </style>
</head>
<body>
    <header>
        <h1>Dokumentasi API Propolis Fulfillment</h1>
        <p>Semua response mengikuti format: <code>{ "success": true, "data": ..., "message": "opsional" }</code></p>
        <p>Gunakan header <code>Authorization: Bearer &lt;token_sanctum&gt;</code> untuk endpoint yang memerlukan autentikasi.</p>
    </header>

    <section>
        <h2>Autentikasi</h2>
        <ul>
            <li>Menggunakan Laravel Sanctum (token-based).</li>
            <li>Dapat membuat token melalui endpoint login (implementasi menyesuaikan kebutuhan Anda).</li>
        </ul>
    </section>

    <section>
        <h2>Endpoint Publik</h2>
        <table>
            <thead>
            <tr>
                <th>Method</th>
                <th>Endpoint</th>
                <th>Deskripsi</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><span class="badge get">GET</span></td>
                <td><code>/api/products</code></td>
                <td>Daftar produk + ringkasan stok batch.</td>
            </tr>
            <tr>
                <td><span class="badge get">GET</span></td>
                <td><code>/api/products/{id}</code></td>
                <td>Detail produk, batch, harga tingkat.</td>
            </tr>
            <tr>
                <td><span class="badge get">GET</span></td>
                <td><code>/api/shipping/provinces</code></td>
                <td>Daftar provinsi (cache RajaOngkir).</td>
            </tr>
            <tr>
                <td><span class="badge get">GET</span></td>
                <td><code>/api/shipping/cities/{province}</code></td>
                <td>Daftar kota/kabupaten.</td>
            </tr>
            <tr>
                <td><span class="badge get">GET</span></td>
                <td><code>/api/shipping/districts/{city}</code></td>
                <td>Daftar kecamatan.</td>
            </tr>
            <tr>
                <td><span class="badge get">GET</span></td>
                <td><code>/api/shipping/subdistricts/{district}</code></td>
                <td>Daftar kelurahan.</td>
            </tr>
            <tr>
                <td><span class="badge post">POST</span></td>
                <td><code>/api/shipping/cost</code></td>
                <td>Hitung ongkir (body: origin, destination, weight, courier).</td>
            </tr>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Endpoint Pelanggan (Sanctum)</h2>
        <table>
            <thead>
            <tr>
                <th>Method</th>
                <th>Endpoint</th>
                <th>Deskripsi</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><span class="badge post">POST</span></td>
                <td><code>/api/checkout</code></td>
                <td>Buat order, reserve stok batch (body items, alamat, ongkir).</td>
            </tr>
            <tr>
                <td><span class="badge post">POST</span></td>
                <td><code>/api/orders/{id}/upload-proof</code></td>
                <td>Upload bukti pembayaran (multipart).</td>
            </tr>
            <tr>
                <td><span class="badge get">GET</span></td>
                <td><code>/api/orders/{id}</code></td>
                <td>Detail order + alokasi batch (policy owner/admin).</td>
            </tr>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Endpoint Admin</h2>
        <p>Semua endpoint di bawah wajib bearer token + Gate <code>admin</code>.</p>
        <table>
            <thead>
            <tr>
                <th>Method</th>
                <th>Endpoint</th>
                <th>Deskripsi</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><span class="badge post">POST</span></td>
                <td><code>/api/products</code></td>
                <td>Tambah produk baru.</td>
            </tr>
            <tr>
                <td><span class="badge put">PUT</span></td>
                <td><code>/api/products/{id}</code></td>
                <td>Perbarui produk.</td>
            </tr>
            <tr>
                <td><span class="badge post">POST</span></td>
                <td><code>/api/products/{id}/batches</code></td>
                <td>Tambah batch stok baru.</td>
            </tr>
            <tr>
                <td><span class="badge get">GET</span></td>
                <td><code>/api/products/{id}/batches</code></td>
                <td>Lihat daftar batch produk.</td>
            </tr>
            <tr>
                <td><span class="badge post">POST</span></td>
                <td><code>/api/products/{id}/price-tiers</code></td>
                <td>Kelola harga tingkat.</td>
            </tr>
            <tr>
                <td><span class="badge get">GET</span></td>
                <td><code>/api/admin/orders</code></td>
                <td>Daftar order (dengan filter status).</td>
            </tr>
            <tr>
                <td><span class="badge get">GET</span></td>
                <td><code>/api/admin/orders/{id}</code></td>
                <td>Detail order lengkap.</td>
            </tr>
            <tr>
                <td><span class="badge post">POST</span></td>
                <td><code>/api/admin/orders/{id}/verify-payment</code></td>
                <td>Verifikasi pembayaran + alokasi batch (panggil BatchAllocationService::allocate).</td>
            </tr>
            <tr>
                <td><span class="badge post">POST</span></td>
                <td><code>/api/admin/orders/{id}/ship</code></td>
                <td>Input resi & ubah status ke dikirim (dispatch job notifikasi).</td>
            </tr>
            <tr>
                <td><span class="badge post">POST</span></td>
                <td><code>/api/admin/run-reservation-release</code></td>
                <td>Trigger job pelepasan reservasi stok.</td>
            </tr>
            <tr>
                <td><span class="badge get">GET</span></td>
                <td><code>/api/reports/batch-stock</code></td>
                <td>Laporan stok per batch.</td>
            </tr>
            <tr>
                <td><span class="badge get">GET</span></td>
                <td><code>/api/reports/batch-sales</code></td>
                <td>Laporan penjualan per batch (param optional <code>from</code>, <code>to</code>).</td>
            </tr>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Contoh Request</h2>
        <h3>Checkout</h3>
        <pre><code>curl -X POST http://localhost:8000/api/checkout \
  -H "Authorization: Bearer &lt;TOKEN&gt;" \
  -H "Content-Type: application/json" \
  -d '{
    "destination_city_id": 501,
    "address": "Jl. Mawar 1",
    "phone": "628123456789",
    "metode_pembayaran": "BCA",
    "items": [
      { "product_id": 1, "jumlah": 3 }
    ]
  }'</code></pre>

        <h3>Upload Bukti Pembayaran</h3>
        <pre><code>curl -X POST http://localhost:8000/api/orders/1/upload-proof \
  -H "Authorization: Bearer &lt;TOKEN&gt;" \
  -F "bukti=@/path/bukti-transfer.jpg"</code></pre>

        <h3>Verifikasi Pembayaran (Admin)</h3>
        <pre><code>curl -X POST http://localhost:8000/api/admin/orders/1/verify-payment \
  -H "Authorization: Bearer &lt;ADMIN_TOKEN&gt;"</code></pre>
    </section>

    <section>
        <h2>Catatan Batch Allocation</h2>
        <ul>
            <li>Checkout memanggil <code>BatchAllocationService::reserveForOrder()</code> → mengisi <code>reserved_qty</code>, <code>order_item_batches</code>, dan <code>reservation_expires_at</code>.</li>
            <li>Admin verifikasi memanggil <code>BatchAllocationService::allocate()</code> → menurunkan <code>qty_remaining</code>, mencatat movement <code>sold</code>, menandai item sebagai <code>allocated</code>.</li>
            <li>Command <code>orders:release-expired-reservations</code> melepas reservasi yang sudah lewat 30 menit.</li>
        </ul>
    </section>
</body>
</html>

