<?php
/**
 * Clean Polygon API - Essential Functions Only
 * Optimized for minimal code footprint - now uses ApiWrapper
 */

require_once __DIR__ . '/../config/api_wrapper.php';

/**
 * Get batch snapshot for all tickers
 * Single API call instead of individual calls
 */
function getBatchSnapshot($tickers) {
    $polygonApi = new PolygonApiWrapper();
    return $polygonApi->getBatchSnapshot($tickers);
}

/**
 * Get accurate market cap and shares outstanding from Polygon V3 Reference
 */
function getAccurateMarketCap($ticker) {
    $polygonApi = new PolygonApiWrapper();
    return $polygonApi->getAccurateMarketCap($ticker);
}

/**
 * Get accurate market cap data for multiple tickers using batch approach
 * Uses enhanced shares outstanding with multiple fallbacks
 */
function getAccurateMarketCapBatch($tickers) {
    $polygonApi = new PolygonApiWrapper();
    return $polygonApi->getAccurateMarketCapBatch($tickers);
}

/**
 * Get shares outstanding from IEX Cloud (alternative to Finnhub)
 */
function getSharesOutstandingIEX($ticker) {
    // Note: IEX Cloud requires API key, but provides 50,000 free calls/month
    // For now, return null - would need IEX API key to implement
    return null;
}

/**
 * Get shares outstanding from Financial Model Prep (alternative to Finnhub)
 */
function getSharesOutstandingFMP($ticker) {
    // Note: FMP provides 250 free calls/day
    // For now, return null - would need FMP API key to implement
    return null;
}

/**
 * Enhanced function to get shares outstanding with multiple fallbacks
 */
function getSharesOutstandingEnhanced($ticker) {
    $polygonApi = new PolygonApiWrapper();
    return $polygonApi->getSharesOutstandingEnhanced($ticker);
}

if (!function_exists('processTickerDataWithAccurateMC')) {
    /**
     * Normalizuje snapshot pre jeden ticker a vráti, čo vieme zapísať.
     * - cena: lastTrade.p alebo fallback na prevDay.c
     * - % zmeny: iba ak máme lastTrade aj prevClose (inak NULL, aby nepadalo -100%)
     * - mc: len ak máme shares_outstanding > 0
     */
    function processTickerDataWithAccurateMC(array $snapshot, string $ticker, ?array $accurate = null): array
    {
        $polygonApi = new PolygonApiWrapper();
        return $polygonApi->processTickerDataWithAccurateMC($snapshot, $ticker, $accurate);
    }
}
?> 