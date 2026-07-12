<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class PincodeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
    public function getLocationDetails(string $pincode): ?array
    {
        try {
            // First attempt: India Post API
            $response = Http::get("https://api.postalpincode.in/pincode/{$pincode}");

            if ($response->successful() && $response->json()[0]['Status'] === 'Success') {
                $data = $response->json()[0]['PostOffice'][0];
                return [
                    'city' => $data['District'],
                    'state' => $data['State'],
                    'country' => 'India',
                    'locality' => $data['Name'],
                ];
            }

            // Fallback: OpenStreetMap
            $nominatimResponse = Http::get("https://nominatim.openstreetmap.org/search", [
                'postalcode' => $pincode,
                'country' => 'India',
                'format' => 'json',
                'addressdetails' => 1,
            ]);

            if ($nominatimResponse->successful() && count($nominatimResponse->json()) > 0) {
                $data = $nominatimResponse->json()[0]['address'];
                return [
                    'city' => $data['city'] ?? $data['town'] ?? $data['village'] ?? '',
                    'state' => $data['state'] ?? '',
                    'country' => $data['country'] ?? 'India',
                    'locality' => $data['suburb'] ?? $data['neighbourhood'] ?? '',
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Pincode API Error: ' . $e->getMessage());
            return null;
        }
    }
}
