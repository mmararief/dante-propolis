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
     *             required={"origin","destination","weight","courier"},
     *             @OA\Property(property="origin", type="integer"),
     *             @OA\Property(property="destination", type="integer"),
     *             @OA\Property(property="weight", type="integer", description="Dalam gram"),
     *             @OA\Property(property="courier", type="string", example="jne")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Daftar layanan & biaya")
     * )
     */
    public function cost(Request $request)
    {
        $data = $request->validate([
            'origin' => ['required', 'integer'],
            'destination' => ['required', 'integer'],
            'weight' => ['required', 'integer', 'min:1'],
            'courier' => ['required', 'string'],
        ]);

        return $this->success($this->rajaOngkir->getCost($data));
    }
}
