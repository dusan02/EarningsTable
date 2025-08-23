<?php
/**
 * Finnhub API Wrapper with Dynamic Rate Limiting
 * Prevents 429 Too Many Requests errors
 */

class Finnhub {
    private const BASE = 'https://finnhub.io/api/v1';
    private static int $calls60 = 0;
    private static int $windowStart = 0;

    public static function get(string $path, array $q): array|null {
        $now = time();
        
        // Reset counter if 60-second window has passed
        if ($now - self::$windowStart >= 60) {
            self::$windowStart = $now;
            self::$calls60 = 0;
        }
        
        // Check if we're approaching the limit (58 calls to be safe)
        if (++self::$calls60 > 58) {
            $sleep = 60 - ($now - self::$windowStart) + 1;
            echo "⏳ Finnhub limit hit → spím {$sleep}s\n";
            sleep($sleep);
            return self::get($path, $q); // Retry after sleep
        }
        
        // Make API call
        $url = self::BASE . $path . '?' . http_build_query($q + ['token' => FINNHUB_API_KEY]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => [
                    'User-Agent: EarningsTable/1.0',
                    'Accept: application/json'
                ]
            ]
        ]);
        
        $json = file_get_contents($url, false, $context);
        
        if ($json === false) {
            return null;
        }
        
        return json_decode($json, true);
    }

    /**
     * Get shares outstanding for a ticker
     */
    public static function getSharesOutstanding(string $ticker): ?float {
        $data = self::get('/stock/profile2', ['symbol' => $ticker]);
        return $data['shareOutstanding'] ?? null;
    }

    /**
     * Get company name for a ticker
     */
    public static function getCompanyName(string $ticker): ?string {
        $data = self::get('/stock/profile2', ['symbol' => $ticker]);
        return $data['name'] ?? null;
    }

    /**
     * Get current call count for monitoring
     */
    public static function getCallCount(): int {
        return self::$calls60;
    }

    /**
     * Get earnings calendar for a ticker
     */
    public static function getEarningsCalendar(string $ticker, string $from, string $to): array|null {
        $data = self::get('/calendar/earnings', [
            'symbol' => $ticker,
            'from' => $from,
            'to' => $to
        ]);
        return $data;
    }

    /**
     * Reset call counter (for testing)
     */
    public static function resetCounter(): void {
        self::$calls60 = 0;
        self::$windowStart = 0;
    }
}
?> 