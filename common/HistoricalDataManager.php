<?php
/**
 * Historical Data Manager
 * 
 * Centralizovaná trieda pre správu historických dát:
 * - Hľadanie previous close z predchádzajúcich obchodných dní
 * - Fallback logika pre tickery bez aktuálnych cien
 * - Konzistentné API pre obe triedy
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/api_functions.php';

class HistoricalDataManager {
    
    /**
     * Inteligentné hľadanie validného previous close z posledných obchodných dní
     * 
     * @param array $tickerData Polygon batch data
     * @param string $ticker Ticker symbol
     * @return float|null Previous close price or null if not found
     */
    public static function findValidPreviousClose($tickerData, $ticker) {
        // 1. Skúsiť aktuálny prevDay z Polygon batch
        $previousClose = $tickerData['polygon_batch']['prevDay']['c'] ?? null;
        if ($previousClose && $previousClose > 0) {
            return $previousClose;
        }
        
        // 2. Ak prevDay nie je dostupné, skúsiť nájsť dáta z predchádzajúcich dní
        echo "🔍 {$ticker}: prevDay nie je dostupné, hľadám dáta z predchádzajúcich dní...\n";
        
        // 3. Skúsiť nájsť dáta z posledných 5 dní (obídnuť víkend/holiday)
        $previousClose = self::findPreviousCloseFromLastDays($ticker, 5);
        if ($previousClose && $previousClose > 0) {
            echo "✅ {$ticker}: Našiel som previous close z predchádzajúcich dní: $" . number_format($previousClose, 3) . "\n";
            return $previousClose;
        }
        
        // 4. Fallback: skúsiť nájsť v databáze
        $previousClose = self::getHistoricalPreviousCloseFromDB($ticker);
        if ($previousClose && $previousClose > 0) {
            echo "✅ {$ticker}: Našiel som previous close z databázy: $" . number_format($previousClose, 3) . "\n";
            return $previousClose;
        }
        
        echo "❌ {$ticker}: Nepodarilo sa nájsť validný previous close\n";
        return null;
    }
    
    /**
     * Hľadanie previous close z posledných N dní
     * 
     * @param string $ticker Ticker symbol
     * @param int $maxDays Maximum number of days to look back
     * @return float|null Previous close price or null if not found
     */
    private static function findPreviousCloseFromLastDays($ticker, $maxDays = 5) {
        // Získať aktuálny dátum v NY timezone
        $timezone = new DateTimeZone('America/New_York');
        $currentDate = new DateTime('now', $timezone);
        
        // Skúsiť nájsť dáta z posledných N dní
        for ($i = 1; $i <= $maxDays; $i++) {
            $checkDate = clone $currentDate;
            $checkDate->modify("-{$i} days");
            $dateString = $checkDate->format('Y-m-d');
            
            // Skontrolovať, či je to obchodný deň (nie víkend)
            $dayOfWeek = $checkDate->format('N'); // 1 = Monday, 7 = Sunday
            if ($dayOfWeek >= 6) { // 6 = Saturday, 7 = Sunday
                echo "  ⏭️  {$ticker}: Preskakujem víkend {$dateString}\n";
                continue; // Preskočiť víkend
            }
            
            echo "  📅 {$ticker}: Kontrolujem {$dateString}...\n";
            
            // Skúsiť nájsť dáta z Polygon V2 Aggregates API
            $previousClose = self::getPolygonHistoricalClose($ticker, $dateString);
            if ($previousClose && $previousClose > 0) {
                return $previousClose;
            }
        }
        
        return null;
    }
    
    /**
     * Získanie historického previous close z databázy
     * 
     * @param string $ticker Ticker symbol
     * @return float|null Previous close price or null if not found
     */
    public static function getHistoricalPreviousCloseFromDB($ticker) {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT previous_close 
            FROM todayearningsmovements 
            WHERE ticker = ? AND previous_close > 0
            LIMIT 1
        ");
        $stmt->execute([$ticker]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (float)$result['previous_close'] : null;
    }
    
    /**
     * Získanie historického previous close z Polygon V2 Aggregates API
     * 
     * @param string $ticker Ticker symbol
     * @param string $date Date in Y-m-d format
     * @return float|null Previous close price or null if not found
     */
    private static function getPolygonHistoricalClose($ticker, $date) {
        echo "    🔍 {$ticker}: Hľadám v Polygon V2 Aggregates API pre {$date}...\n";
        
        // Convert date to timestamp for Polygon API
        $timestamp = strtotime($date);
        if ($timestamp === false) return null;
        
        // Polygon expects milliseconds timestamp
        $from = ($timestamp - (24 * 60 * 60)) * 1000; // Previous day
        $to = $timestamp * 1000; // Current day
        
        $url = POLYGON_BASE_URL . "/v2/aggs/ticker/{$ticker}/range/1/day/{$from}/{$to}";
        $url .= "?adjusted=true&sort=desc&limit=1&apikey=" . POLYGON_API_KEY;
        
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
        
        $response = file_get_contents($url, false, $context);
        if ($response === false) return null;
        
        $data = json_decode($response, true);
        
        if (isset($data['results'][0]['c'])) {
            $closePrice = (float)$data['results'][0]['c'];
            echo "    ✅ {$ticker}: Našiel som previous close z Polygon API {$date}: $" . number_format($closePrice, 3) . "\n";
            return $closePrice;
        }
        
        echo "    ❌ {$ticker}: Žiadne dáta pre {$date}\n";
        return null;
    }
    
    /**
     * Fallback logika pre current_price - použije historické dáta ak aktuálne nie sú dostupné
     * 
     * @param array $polygonData Polygon batch data
     * @param string $ticker Ticker symbol
     * @return array|null Array with 'price' and 'source' or null if no price available
     */
    public static function getCurrentPriceWithFallback($polygonData, $ticker) {
        // 1. Skúsiť získať aktuálnu cenu
        $priceData = getCurrentPrice($polygonData);
        
        if ($priceData && $priceData['price'] > 0) {
            return $priceData;
        }
        
        // 2. Fallback: použiť historické previous_close z databázy
        $historicalPrice = self::getHistoricalPreviousCloseFromDB($ticker);
        if ($historicalPrice && $historicalPrice > 0) {
            echo "⚠️  {$ticker}: Using historical price as fallback: {$historicalPrice}\n";
            return [
                'price' => $historicalPrice,
                'source' => 'historical_fallback'
            ];
        }
        
        // 3. Žiadna cena nie je dostupná
        echo "⚠️  {$ticker}: No valid current price or historical fallback available\n";
        return null;
    }
    
    /**
     * Kontrola či ticker má dostupné historické dáta
     * 
     * @param string $ticker Ticker symbol
     * @return bool True if historical data is available
     */
    public static function hasHistoricalData($ticker) {
        $previousClose = self::getHistoricalPreviousCloseFromDB($ticker);
        return $previousClose !== null && $previousClose > 0;
    }
}
?>
