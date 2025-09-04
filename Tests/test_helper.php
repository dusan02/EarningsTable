<?php
/**
 * Test Helper - Spoločné funkcie pre testy
 */

require_once 'config.php';

class TestHelper {
    
    /**
     * Načíta aktuálne tickery z databázy
     */
    public static function getCurrentTickers($limit = 5) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Načíta tickery pre dnešok
            $stmt = $pdo->prepare("
                SELECT DISTINCT ticker 
                FROM earningstickerstoday 
                WHERE report_date = CURDATE()
                ORDER BY market_cap DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tickers)) {
                // Fallback na včerajšie tickery ak dnes nie sú
                $stmt = $pdo->prepare("
                    SELECT DISTINCT ticker 
                    FROM earningstickerstoday 
                    WHERE report_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                    ORDER BY market_cap DESC 
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
                $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            return $tickers;
            
        } catch (Exception $e) {
            // Fallback na známe tickery ak databáza nefunguje
            return ['AVGO', 'CPRT', 'CIEN', 'BRC', 'BRZE'];
        }
    }
    
    /**
     * Načíta tickery s guidance dátami
     */
    public static function getTickersWithGuidance($limit = 3) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Načíta tickery ktoré majú guidance dáta
            $stmt = $pdo->prepare("
                SELECT DISTINCT bg.ticker 
                FROM benzinga_guidance bg
                INNER JOIN earningstickerstoday ett ON bg.ticker = ett.ticker
                WHERE ett.report_date = CURDATE()
                AND (bg.estimated_revenue_guidance IS NOT NULL OR bg.estimated_eps_guidance IS NOT NULL)
                ORDER BY ett.market_cap DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tickers)) {
                // Fallback na známe tickery s guidance
                return ['AVGO', 'CIEN'];
            }
            
            return $tickers;
            
        } catch (Exception $e) {
            // Fallback na známe tickery s guidance
            return ['AVGO', 'CIEN'];
        }
    }
    
    /**
     * Načíta tickery s najvyšším market cap
     */
    public static function getTopTickersByMarketCap($limit = 5) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->prepare("
                SELECT ticker 
                FROM earningstickerstoday 
                WHERE report_date = CURDATE()
                AND market_cap IS NOT NULL
                ORDER BY market_cap DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (Exception $e) {
            return ['AVGO', 'CPRT', 'CIEN', 'BRC', 'BRZE'];
        }
    }
    
    /**
     * Načíta náhodné tickery pre testovanie
     */
    public static function getRandomTickers($limit = 3) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->prepare("
                SELECT ticker 
                FROM earningstickerstoday 
                WHERE report_date = CURDATE()
                ORDER BY RAND() 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (Exception $e) {
            return ['AVGO', 'CPRT', 'CIEN'];
        }
    }
    
    /**
     * Vypíše informácie o tickeroch
     */
    public static function printTickerInfo($tickers, $title = "Test Tickers") {
        echo "📊 $title:\n";
        echo "   " . implode(', ', $tickers) . "\n";
        echo "   (Celkom: " . count($tickers) . " tickerov)\n\n";
    }
    
    /**
     * Načíta konfiguráciu pre testy
     */
    public static function getTestConfig() {
        return [
            'api_timeout' => 30,
            'max_retries' => 3,
            'test_limit' => 5,
            'guidance_limit' => 3
        ];
    }
}
?>
