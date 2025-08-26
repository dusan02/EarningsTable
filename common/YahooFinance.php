<?php

class YahooFinance {
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    
    /**
     * Get earnings calendar for a specific date
     */
    public function getEarningsCalendar($date) {
        try {
            // Yahoo Finance earnings calendar URL
            $url = "https://finance.yahoo.com/calendar/earnings?day={$date}";
            
            // Get the page content
            $content = $this->makeRequest($url);
            
            if (!$content) {
                return ['error' => 'Failed to fetch Yahoo Finance data'];
            }
            
            // Extract earnings data from the page
            $earnings = $this->parseEarningsPage($content, $date);
            
            return [
                'success' => true,
                'date' => $date,
                'earnings' => $earnings,
                'count' => count($earnings)
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Make HTTP request with proper headers
     */
    private function makeRequest($url) {
        // Use cURL for better control
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: identity',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
        ]);
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($content === false || $httpCode !== 200) {
            return null;
        }
        
        return $content;
    }
    
    /**
     * Parse earnings data from Yahoo Finance page
     */
    private function parseEarningsPage($content, $date) {
        $earnings = [];
        
        // Look for JSON data in the page
        if (preg_match('/"earningsCalendar":\s*(\[.*?\])/s', $content, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if ($jsonData) {
                foreach ($jsonData as $earning) {
                    $earnings[] = [
                        'symbol' => $earning['symbol'] ?? '',
                        'company_name' => $earning['companyName'] ?? '',
                        'eps_estimate' => $earning['epsEstimate'] ?? null,
                        'revenue_estimate' => $earning['revenueEstimate'] ?? null,
                        'report_time' => $earning['reportTime'] ?? 'TNS',
                        'market_cap' => $earning['marketCap'] ?? null
                    ];
                }
            }
        }
        
        // If no JSON data found, try to parse HTML table
        if (empty($earnings)) {
            $earnings = $this->parseHTMLTable($content);
        }
        
        return $earnings;
    }
    
    /**
     * Parse HTML table as fallback
     */
    private function parseHTMLTable($content) {
        $earnings = [];
        
        // Look for table rows with earnings data
        if (preg_match_all('/<tr[^>]*class="[^"]*Py\([^"]*\)[^"]*"[^>]*>(.*?)<\/tr>/s', $content, $matches)) {
            foreach ($matches[1] as $row) {
                // Extract ticker symbol
                if (preg_match('/<td[^>]*>([A-Z]{1,5})<\/td>/', $row, $symbolMatch)) {
                    $symbol = trim($symbolMatch[1]);
                    
                    // Extract company name
                    $companyName = '';
                    if (preg_match('/<td[^>]*>([^<]+)<\/td>/', $row, $nameMatch)) {
                        $companyName = trim($nameMatch[1]);
                    }
                    
                    // Extract EPS estimate
                    $epsEstimate = null;
                    if (preg_match('/<td[^>]*>([0-9.-]+)<\/td>/', $row, $epsMatch)) {
                        $epsEstimate = floatval($epsMatch[1]);
                    }
                    
                    $earnings[] = [
                        'symbol' => $symbol,
                        'company_name' => $companyName,
                        'eps_estimate' => $epsEstimate,
                        'revenue_estimate' => null,
                        'report_time' => 'TNS',
                        'market_cap' => null
                    ];
                }
            }
        }
        
        return $earnings;
    }
    
    /**
     * Get specific ticker earnings data
     */
    public function getTickerEarnings($ticker, $date) {
        try {
            $url = "https://finance.yahoo.com/quote/{$ticker}/earnings";
            $content = $this->makeRequest($url);
            
            if (!$content) {
                return null;
            }
            
            // Look for earnings data in the page
            if (preg_match('/"earningsTimestamp":\s*"([^"]+)"/', $content, $matches)) {
                $earningsDate = $matches[1];
                if (strpos($earningsDate, $date) !== false) {
                    return [
                        'symbol' => $ticker,
                        'has_earnings' => true,
                        'date' => $earningsDate
                    ];
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if ticker has earnings on specific date
     */
    public function hasEarningsOnDate($ticker, $date) {
        $earnings = $this->getTickerEarnings($ticker, $date);
        return $earnings && $earnings['has_earnings'];
    }
    
    /**
     * Get actual values for a specific ticker and date
     */
    public function getActualValues($ticker, $date) {
        try {
            $url = "https://finance.yahoo.com/quote/{$ticker}/earnings";
            $content = $this->makeRequest($url);
            
            if (!$content) {
                return null;
            }
            
            // Look for actual earnings data in the page
            $epsActual = null;
            $revenueActual = null;
            
            // Try to find actual EPS value
            if (preg_match('/"actualEPS":\s*([0-9.-]+)/', $content, $matches)) {
                $epsActual = floatval($matches[1]);
            }
            
            // Try to find actual revenue value
            if (preg_match('/"actualRevenue":\s*([0-9]+)/', $content, $matches)) {
                $revenueActual = intval($matches[1]);
            }
            
            // Alternative patterns for actual values
            if ($epsActual === null && preg_match('/"epsActual":\s*([0-9.-]+)/', $content, $matches)) {
                $epsActual = floatval($matches[1]);
            }
            
            if ($revenueActual === null && preg_match('/"revenueActual":\s*([0-9]+)/', $content, $matches)) {
                $revenueActual = intval($matches[1]);
            }
            
            // Return data only if we found something
            if ($epsActual !== null || $revenueActual !== null) {
                return [
                    'eps_actual' => $epsActual,
                    'revenue_actual' => $revenueActual,
                    'ticker' => $ticker,
                    'date' => $date
                ];
            }
            
            return null;
            
        } catch (Exception $e) {
            return null;
        }
    }
}

?>
