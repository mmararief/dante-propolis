<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Propolis Fulfillment API",
 *     version="1.0.0",
 *     description="Dokumentasi resmi layanan backend Propolis. Semua response menggunakan format {success, data, message}.",
 *     @OA\Contact(
 *         name="Tim Propolis",
 *         email="support@example.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Root"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Masukkan token Sanctum dengan format Bearer {token}."
 * )
 *
 * @OA\Tag(
 *     name="Products",
 *     description="Katalog produk dan stok batch."
 * )
 * @OA\Tag(
 *     name="Shipping",
 *     description="Integrasi RajaOngkir."
 * )
 * @OA\Tag(
 *     name="Orders",
 *     description="Checkout & order pelanggan."
 * )
 * @OA\Tag(
 *     name="Admin",
 *     description="Operasi administratif."
 * )
 * @OA\Tag(
 *     name="Auth",
 *     description="Registrasi, login, logout."
 * )
 */
class ApiDoc {}

/**
 * @OA\Schema(
 *     schema="CheckoutItem",
 *     required={"product_id","jumlah"},
 *     @OA\Property(property="product_id", type="integer", example=1),
 *     @OA\Property(property="jumlah", type="integer", example=3),
 *     @OA\Property(property="harga_tingkat_id", type="integer", nullable=true),
 *     @OA\Property(property="catatan", type="string", nullable=true)
 * )
 */
class CheckoutItemSchema {}

/**
 * @OA\Schema(
 *     schema="CheckoutRequest",
 *     required={"metode_pembayaran","items"},
 *     @OA\Property(property="courier", type="string", example="jne"),
 *     @OA\Property(property="courier_service", type="string", example="REG"),
 *     @OA\Property(property="origin_city_id", type="integer", example=149),
 *     @OA\Property(property="destination_city_id", type="integer", nullable=true, example=501, description="Isi jika ingin override alamat default pengguna"),
 *     @OA\Property(property="destination_district_id", type="integer", nullable=true),
 *     @OA\Property(property="destination_subdistrict_id", type="integer", nullable=true),
 *     @OA\Property(property="address", type="string", example="Jl. Mawar no 1"),
 *     @OA\Property(property="phone", type="string", example="628123456789"),
 *     @OA\Property(property="metode_pembayaran", type="string", enum={"BCA","BSI","gopay","dana","transfer_manual"}),
 *     @OA\Property(property="ongkos_kirim", type="number", format="float", example=20000),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/CheckoutItem")
 *     )
 * )
 */
class CheckoutRequestSchema {}
