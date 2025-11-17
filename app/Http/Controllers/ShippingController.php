<?php

namespace App\Http\Controllers;

use App\Services\RajaOngkirService;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ShippingController extends Controller
{
    public function __construct(private readonly RajaOngkirService $rajaOngkir) {}

    /**
     * @OA\Get(
     *     path="/shipping/provinces",
     *     tags={"Shipping"},
     *     summary="Daftar provinsi tujuan",
     *     @OA\Response(response=200, description="Data provinsi")
     * )
     */
    public function provinces()
    {
        return $this->success($this->rajaOngkir->getProvinces());
    }

    /**
     * @OA\Get(
     *     path="/shipping/cities/{province_id}",
     *     tags={"Shipping"},
     *     summary="Daftar kota/kabupaten",
     *     @OA\Parameter(name="province_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Data kota/kabupaten")
     * )
     */
    public function cities(int $provinceId)
    {
        return $this->success($this->rajaOngkir->getCities($provinceId));
    }

    /**
     * @OA\Get(
     *     path="/shipping/districts/{city_id}",
     *     tags={"Shipping"},
     *     summary="Daftar kecamatan",
     *     @OA\Parameter(name="city_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Data kecamatan")
     * )
     */
    public function districts(int $cityId)
    {
        return $this->success($this->rajaOngkir->getDistricts($cityId));
    }

    /**
     * @OA\Get(
     *     path="/shipping/subdistricts/{district_id}",
     *     tags={"Shipping"},
     *     summary="Daftar kelurahan",
     *     @OA\Parameter(name="district_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Data kelurahan")
     * )
     */
    public function subdistricts(int $districtId)
    {
        return $this->success($this->rajaOngkir->getSubdistricts($districtId));
    }

    /**
     * @OA\Post(
     *     path="/shipping/cost",
     *     tags={"Shipping"},
     *     summary="Hitung biaya pengiriman",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"weight","courier"},
     *             @OA\Property(property="origin", type="integer", nullable=true),
     *             @OA\Property(property="destination", type="integer", nullable=true),
     *             @OA\Property(property="origin_district_id", type="integer", nullable=true),
     *             @OA\Property(property="destination_district_id", type="integer", nullable=true),
     *             @OA\Property(property="weight", type="integer", description="Dalam gram"),
     *             @OA\Property(property="courier", type="string", example="jne"),
     *             @OA\Property(property="price", type="string", example="lowest")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Daftar layanan & biaya")
     * )
     */
    public function cost(Request $request)
    {
        $data = $request->validate([
            'origin' => ['nullable', 'integer'],
            'destination' => ['nullable', 'integer'],
            'weight' => ['required', 'integer', 'min:1'],
            'courier' => ['required', 'string'],
            'origin_district_id' => ['nullable', 'integer'],
            'destination_district_id' => ['nullable', 'integer'],
            'price' => ['nullable', 'string'],
        ]);

        $hasDistrict = ($data['origin_district_id'] ?? config('services.rajaongkir.origin_district_id')) && $data['destination_district_id'];

        if (! $hasDistrict) {
            if (empty($data['origin']) || empty($data['destination'])) {
                return $this->fail('Origin / destination tidak lengkap.', 422);
            }
        }

        return $this->success($this->rajaOngkir->getCost($data));
    }
}
