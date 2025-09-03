<?php
/**
 * 🛡️ UNIFIED VALIDATOR
 * 
 * Konsoliduje všetky validácie do jednej triedy:
 * - Eliminuje duplicitný kód
 * - Centralizuje validačnú logiku
 * - Zjednodušuje údržbu
 * - Poskytuje konzistentné rozhranie
 */

class UnifiedValidator {
    
    // ========================================
    // NUMERIC VALIDATIONS - UNIFIED
    // ========================================
    
    /**
     * Validuje numerickú hodnotu s rozsahom
     */
    public static function validateNumeric($value, $min = null, $max = null, $allowNull = false) {
        if ($allowNull && $value === null) {
            return ['valid' => true, 'issues' => []];
        }
        
        if ($value === null || $value === '') {
            return ['valid' => false, 'issues' => ['Value cannot be null or empty']];
        }
        
        if (!is_numeric($value)) {
            return ['valid' => false, 'issues' => ['Value must be numeric']];
        }
        
        $numericValue = (float)$value;
        $issues = [];
        
        if ($min !== null && $numericValue < $min) {
            $issues[] = "Value {$numericValue} below minimum {$min}";
        }
        
        if ($max !== null && $numericValue > $max) {
            $issues[] = "Value {$numericValue} above maximum {$max}";
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'value' => $numericValue
        ];
    }
    
    /**
     * Validuje EPS guidance (-100 až +100)
     */
    public static function validateEpsGuidance($eps, $minEps = null, $maxEps = null) {
        $validation = self::validateNumeric($eps, -100, 100, true);
        
        if (!$validation['valid']) {
            return $validation;
        }
        
        if ($eps === null) {
            return ['valid' => true, 'issues' => []];
        }
        
        $issues = $validation['issues'];
        
        // Kontrola min/max rozsahov ak sú nastavené
        if ($minEps !== null && $eps < $minEps) {
            $issues[] = "EPS guidance {$eps} below min_eps_guidance {$minEps}";
        }
        
        if ($maxEps !== null && $eps > $maxEps) {
            $issues[] = "EPS guidance {$eps} above max_eps_guidance {$maxEps}";
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'value' => $eps
        ];
    }
    
    /**
     * Validuje Revenue guidance (pozitívne hodnoty)
     */
    public static function validateRevenueGuidance($revenue, $minRevenue = null, $maxRevenue = null) {
        $validation = self::validateNumeric($revenue, 0, null, true);
        
        if (!$validation['valid']) {
            return $validation;
        }
        
        if ($revenue === null) {
            return ['valid' => true, 'issues' => []];
        }
        
        $issues = $validation['issues'];
        
        // Kontrola min/max rozsahov ak sú nastavené
        if ($minRevenue !== null && $revenue < $minRevenue) {
            $issues[] = "Revenue guidance {$revenue} below min_revenue_guidance {$minRevenue}";
        }
        
        if ($maxRevenue !== null && $revenue > $maxRevenue) {
            $issues[] = "Revenue guidance {$revenue} above max_revenue_guidance {$maxRevenue}";
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'value' => $revenue
        ];
    }
    
    /**
     * Vypočíta percentuálny rozdiel medzi guidance a konsenzom
     */
    public static function calculateDeltaPercent($guide, $consensus) {
        if ($guide === null || $consensus === null || $consensus == 0) {
            return null;
        }
        
        $difference = (($guide - $consensus) / abs($consensus)) * 100;
        return round($difference, 4);
    }
    
    // ========================================
    // TICKER VALIDATIONS - UNIFIED
    // ========================================
    
    /**
     * Validuje ticker symbol
     */
    public static function validateTicker($ticker) {
        if (empty($ticker)) {
            return ['valid' => false, 'issues' => ['Ticker cannot be empty']];
        }
        
        // Basic validation: 1-5 characters, alphanumeric
        if (!preg_match('/^[A-Z]{1,5}$/', $ticker)) {
            return ['valid' => false, 'issues' => ['Ticker must be 1-5 uppercase letters']];
        }
        
        return ['valid' => true, 'issues' => [], 'value' => $ticker];
    }
    
    /**
     * Vyčistí názov spoločnosti
     */
    public static function sanitizeCompanyName($companyName) {
        if (empty($companyName)) {
            return '';
        }
        
        // Remove common suffixes and clean up
        $cleanName = preg_replace('/\s+(Inc\.?|Corp\.?|Corporation|Company|Co\.?|Ltd\.?|Limited|Group|Holdings?|International|Technologies|Technology|Tech|Systems|Solutions|Services|Enterprises|Industries|Partners|Management|Capital|Acquisition|American Depositary.*|Common Stock|Class [A-Z].*|each.*)/i', '', $companyName);
        $cleanName = preg_replace('/\s*,.*$/', '', $cleanName);
        $cleanName = preg_replace('/\s+/', ' ', $cleanName);
        
        return trim($cleanName);
    }
    
    // ========================================
    // SECURITY PATTERN DETECTION - UNIFIED
    // ========================================
    
    /**
     * Detekcia SQL injection pattern
     */
    public static function detectSqlPattern($query) {
        $patterns = [
            'union' => '/union\s+select/i',
            'drop' => '/drop\s+table/i',
            'delete' => '/delete\s+from/i',
            'insert' => '/insert\s+into/i',
            'update' => '/update\s+.+\s+set/i',
            'alter' => '/alter\s+table/i',
            'create' => '/create\s+table/i',
            'truncate' => '/truncate\s+table/i'
        ];
        
        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $query)) {
                return [
                    'detected' => true,
                    'type' => $type,
                    'pattern' => $pattern
                ];
            }
        }
        
        return ['detected' => false, 'type' => null, 'pattern' => null];
    }
    
    /**
     * Detekcia XSS pattern
     */
    public static function detectXssPattern($input) {
        $patterns = [
            'script' => '/<script[^>]*>/i',
            'javascript' => '/javascript:/i',
            'onload' => '/onload\s*=/i',
            'onclick' => '/onclick\s*=/i',
            'onmouseover' => '/onmouseover\s*=/i',
            'onerror' => '/onerror\s*=/i',
            'iframe' => '/<iframe[^>]*>/i',
            'object' => '/<object[^>]*>/i',
            'embed' => '/<embed[^>]*>/i'
        ];
        
        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $input)) {
                return [
                    'detected' => true,
                    'type' => $type,
                    'pattern' => $pattern
                ];
            }
        }
        
        return ['detected' => false, 'type' => null, 'pattern' => null];
    }
    
    /**
     * Detekcia path traversal pattern
     */
    public static function detectPathTraversalPattern($input) {
        $patterns = [
            'unix' => '/\.\.\//',
            'windows' => '/\.\.\\\\/',
            'absolute' => '/^\/(etc|var|usr|home|root)/i',
            'drive' => '/^[a-z]:\\/i/'
        ];
        
        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $input)) {
                return [
                    'detected' => true,
                    'type' => $type,
                    'pattern' => $pattern
                ];
            }
        }
        
        return ['detected' => false, 'type' => null, 'pattern' => null];
    }
    
    // ========================================
    // GUIDANCE SPECIFIC VALIDATIONS - UNIFIED
    // ========================================
    
    /**
     * Validuje kompletný guidance record
     */
    public static function validateGuidanceRecord($guidance) {
        $issues = [];
        
        // Validácia ticker
        $tickerValidation = self::validateTicker($guidance['ticker'] ?? '');
        if (!$tickerValidation['valid']) {
            $issues = array_merge($issues, $tickerValidation['issues']);
        }
        
        // Validácia EPS guidance
        if (isset($guidance['estimated_eps_guidance'])) {
            $epsValidation = self::validateEpsGuidance(
                $guidance['estimated_eps_guidance'],
                $guidance['min_eps_guidance'] ?? null,
                $guidance['max_eps_guidance'] ?? null
            );
            if (!$epsValidation['valid']) {
                $issues = array_merge($issues, $epsValidation['issues']);
            }
        }
        
        // Validácia Revenue guidance
        if (isset($guidance['estimated_revenue_guidance'])) {
            $revenueValidation = self::validateRevenueGuidance(
                $guidance['estimated_revenue_guidance'],
                $guidance['min_revenue_guidance'] ?? null,
                $guidance['max_revenue_guidance'] ?? null
            );
            if (!$revenueValidation['valid']) {
                $issues = array_merge($issues, $revenueValidation['issues']);
            }
        }
        
        // Validácia fiscal period
        if (isset($guidance['fiscal_period'])) {
            $validPeriods = ['Q1', 'Q2', 'Q3', 'Q4', 'Y'];
            if (!in_array($guidance['fiscal_period'], $validPeriods)) {
                $issues[] = "Invalid fiscal period: {$guidance['fiscal_period']}";
            }
        }
        
        // Validácia fiscal year
        if (isset($guidance['fiscal_year'])) {
            $yearValidation = self::validateNumeric($guidance['fiscal_year'], 2000, 2100);
            if (!$yearValidation['valid']) {
                $issues = array_merge($issues, $yearValidation['issues']);
            }
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues
        ];
    }
    
    // ========================================
    // UTILITY VALIDATIONS - UNIFIED
    // ========================================
    
    /**
     * Validuje email adresu
     */
    public static function validateEmail($email) {
        if (empty($email)) {
            return ['valid' => false, 'issues' => ['Email cannot be empty']];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'issues' => ['Invalid email format']];
        }
        
        return ['valid' => true, 'issues' => [], 'value' => $email];
    }
    
    /**
     * Validuje URL
     */
    public static function validateUrl($url) {
        if (empty($url)) {
            return ['valid' => false, 'issues' => ['URL cannot be empty']];
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'issues' => ['Invalid URL format']];
        }
        
        return ['valid' => true, 'issues' => [], 'value' => $url];
    }
    
    /**
     * Validuje IP adresu
     */
    public static function validateIpAddress($ip) {
        if (empty($ip)) {
            return ['valid' => false, 'issues' => ['IP address cannot be empty']];
        }
        
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['valid' => false, 'issues' => ['Invalid IP address format']];
        }
        
        return ['valid' => true, 'issues' => [], 'value' => $ip];
    }
}
?>
