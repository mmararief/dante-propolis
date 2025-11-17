<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RajaOngkirService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.rajaongkir.base_url'), '/');
        $this->apiKey = (string) config('services.rajaongkir.key');
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
        return $this->request('post', '/cost', $payload);
    }

    private function request(string $method, string $endpoint, array $payload = []): array
    {
        $options = Http::withHeaders([
            'Key' => $this->apiKey,
            'Accept' => 'application/json',
        ])->timeout(15)->retry(2, 200, function ($exception) {
            return $exception instanceof RequestException && $exception->getCode() === 429;
        });

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


