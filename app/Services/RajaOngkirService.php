<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RajaOngkirService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('RAJAONGKIR_API_KEY');
        $this->baseUrl = env('RAJAONGKIR_BASE_URL');
    }

    /**
     * Mencari lokasi berdasarkan keyword (Autocomplete).
     * @param string $keyword
     * @return array
     */
    public function searchLocation(string $keyword)
    {
        $response = Http::withHeaders(['key' => $this->apiKey])
            ->get($this->baseUrl . '/destination/domestic-destination', [
                'search' => $keyword
            ]);
            
        return $this->handleResponse($response);
    }

    /**
     * Hitung ongkos kirim.
     */
    public function getCost($origin, $destination, $weight, $courier)
    {
        $response = Http::withHeaders(['key' => $this->apiKey])
            ->asForm()
            ->post($this->baseUrl . '/calculate/domestic-cost', [
                'origin' => (int) $origin,
                'destination' => (int) $destination,
                'weight' => (int) $weight,
                'courier' => strtolower($courier)
            ]);
            
        return $this->handleResponse($response);
    }

    /**
     * Helper untuk handle response API.
     * Clean Code: Memisahkan logic parsing error.
     */
    private function handleResponse($response)
    {
        if ($response->failed()) {
            // Throw exception biar bisa ditangkap Controller
            throw new \Exception("Komerce API Error: " . $response->body());
        }

        $json = $response->json();

        // Standardisasi output: Selalu return isi 'data'
        return $json['data'] ?? $json;
    }
}