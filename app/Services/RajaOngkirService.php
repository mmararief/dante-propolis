<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RajaOngkirService
{
    private string $baseUrl;
    private string $apiKey;
    private ?string $originDistrictId;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.rajaongkir.base_url'), '/');
        $this->apiKey = (string) config('services.rajaongkir.key');
        $this->originDistrictId = config('services.rajaongkir.origin_district_id');
    }

    public function getProvinces(): array
    {
        return Cache::remember(
            'rajaongkir:provinces',
            now()->addHours((int) config('services.rajaongkir.cache_hours', 12)),
            fn () => $this->request('get', '/destination/province')
        );
    }

    public function getCities(int $provinceId): array
    {
        return Cache::remember(
            "rajaongkir:cities:{$provinceId}",
            now()->addHours((int) config('services.rajaongkir.cache_hours', 12)),
            fn () => $this->request('get', "/destination/city/{$provinceId}")
        );
    }

    public function getDistricts(int $cityId): array
    {
        return Cache::remember(
            "rajaongkir:districts:{$cityId}",
            now()->addHours((int) config('services.rajaongkir.cache_hours', 12)),
            fn () => $this->request('get', "/destination/district/{$cityId}")
        );
    }

    public function getSubdistricts(int $districtId): array
    {
        return Cache::remember(
            "rajaongkir:subdistricts:{$districtId}",
            now()->addHours((int) config('services.rajaongkir.cache_hours', 12)),
            fn () => $this->request('get', "/destination/sub-district/{$districtId}")
        );
    }

    public function getCost(array $payload): array
    {
        $originDistrict = $payload['origin_district_id'] ?? $this->originDistrictId;
        $destinationDistrict = $payload['destination_district_id'] ?? null;

        if ($originDistrict && $destinationDistrict) {
            $body = [
                'origin' => $originDistrict,
                'destination' => $destinationDistrict,
                'weight' => $payload['weight'] ?? 1000,
                'courier' => $payload['courier'] ?? 'jne',
                'price' => $payload['price'] ?? 'lowest',
            ];

            return $this->request('post', '/calculate/district/domestic-cost', $body, true);
        }

        return $this->request('post', '/cost', [
            'origin' => $payload['origin'] ?? $payload['origin_city_id'] ?? null,
            'destination' => $payload['destination'] ?? $payload['destination_city_id'] ?? null,
            'weight' => $payload['weight'] ?? 1000,
            'courier' => $payload['courier'] ?? 'jne',
        ]);
    }

    private function request(string $method, string $endpoint, array $payload = [], bool $asForm = false): array
    {
        $options = Http::withHeaders([
            'Key' => $this->apiKey,
            'Accept' => 'application/json',
        ])->timeout(15)->retry(2, 200, function ($exception) {
            return $exception instanceof RequestException && $exception->getCode() === 429;
        });

        if ($asForm) {
            $options = $options->asForm();
        }

        $url = $this->baseUrl.$endpoint;

        $response = $method === 'get'
            ? $options->get($url, $payload)
            : $options->post($url, $payload);

        if ($response->status() === 429) {
            throw new RequestException($response);
        }

        $response->throw();

        return $response->json('data') ?? $response->json();
    }
}


