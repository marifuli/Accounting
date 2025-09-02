<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyExchangeService
{
    private const API_URL = 'https://api.exchangeratesapi.io/v1/latest';
    private const CACHE_TTL = 3600 * 24; // Cache for 1 hour
    private const PRIMARY_CURRENCY = 'BDT';

    /**
     * Get exchange rate from one currency to another
     *
     * @param string $from
     * @param string $to
     * @return float
     */
    public function getExchangeRate(string $from, string $to = self::PRIMARY_CURRENCY): float
    {
        // If converting from the same currency, return 1
        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$from}_to_{$to}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($from, $to) {
            try {
                $apiKey = config('services.exchange_api');

                if (!$apiKey) {
                    Log::warning('Exchange rates API key not configured');
                    return $this->getFallbackRate($from, $to);
                }

                $response = Http::timeout(10)->get(self::API_URL, [
                    'access_key' => $apiKey,
                    'base' => 'EUR', // exchangeratesapi.io uses EUR as base for free tier
                    'symbols' => implode(',', [$from, $to])
                ]);

                if (!$response->successful()) {
                    Log::error('Failed to fetch exchange rates', [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    return $this->getFallbackRate($from, $to);
                }

                $data = $response->json();

                if (!isset($data['rates'][$from]) || !isset($data['rates'][$to])) {
                    Log::error('Currency rates not found in response', [
                        'from' => $from,
                        'to' => $to,
                        'available_rates' => array_keys($data['rates'] ?? [])
                    ]);
                    return $this->getFallbackRate($from, $to);
                }

                // Convert from EUR-based rates to direct conversion
                $fromRate = $data['rates'][$from];
                $toRate = $data['rates'][$to];

                return $toRate / $fromRate;

            } catch (\Exception $e) {
                Log::error('Exception while fetching exchange rates', [
                    'message' => $e->getMessage(),
                    'from' => $from,
                    'to' => $to
                ]);
                return $this->getFallbackRate($from, $to);
            }
        });
    }

    /**
     * Convert amount from one currency to another
     *
     * @param float $amount
     * @param string $from
     * @param string $to
     * @return float
     */
    public function convertAmount(float $amount, string $from, string $to = self::PRIMARY_CURRENCY): float
    {
        $rate = $this->getExchangeRate($from, $to);
        return $amount * $rate;
    }

    /**
     * Convert amount to primary currency (BDT)
     *
     * @param float $amount
     * @param string $currency
     * @return float
     */
    public function convertToPrimaryCurrency(float $amount, string $currency): float
    {
        return $this->convertAmount($amount, $currency, self::PRIMARY_CURRENCY);
    }

    /**
     * Get fallback exchange rates when API is unavailable
     *
     * @param string $from
     * @param string $to
     * @return float
     */
    private function getFallbackRate(string $from, string $to): float
    {
        // Fallback rates - these should be updated periodically
        $fallbackRates = [
            'USD' => ['BDT' => 120.0],
            'EUR' => ['BDT' => 130.0],
            'GBP' => ['BDT' => 145.0],
            'JPY' => ['BDT' => 0.8],
            'INR' => ['BDT' => 1.45],
            'CAD' => ['BDT' => 88.0],
            'AUD' => ['BDT' => 78.0],
        ];

        if (isset($fallbackRates[$from][$to])) {
            return $fallbackRates[$from][$to];
        }

        if (isset($fallbackRates[$to][$from])) {
            return 1 / $fallbackRates[$to][$from];
        }

        // If no fallback rate available, log warning and return 1
        Log::warning('No fallback rate available', [
            'from' => $from,
            'to' => $to
        ]);

        return 1.0;
    }

    /**
     * Get the primary currency
     *
     * @return string
     */
    public function getPrimaryCurrency(): string
    {
        return self::PRIMARY_CURRENCY;
    }
}
