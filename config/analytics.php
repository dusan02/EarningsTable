<?php
/**
 * Google Analytics Configuration
 * 
 * Tento súbor obsahuje konfiguráciu pre Google Analytics
 * Nahraďte 'GA_MEASUREMENT_ID' s vašim skutočným Google Analytics ID
 */

// Google Analytics Measurement ID
// Formát: G-XXXXXXXXXX alebo UA-XXXXXXXXX-X
define('GA_MEASUREMENT_ID', 'G-E6DJ7N6W1L'); // Váš skutočný ID

// Google Analytics nastavenia
define('GA_ENABLED', true); // Povoliť/zakázať Google Analytics
define('GA_DEBUG_MODE', false); // Debug mód (len pre vývoj)

// E-commerce tracking (ak budete potrebovať)
define('GA_ENHANCED_ECOMMERCE', false);

// Custom events tracking
define('GA_TRACK_EVENTS', true);

// Privacy settings
define('GA_ANONYMIZE_IP', true); // Anonymizovať IP adresy (GDPR compliance)
define('GA_COOKIE_CONSENT', true); // Cookie consent (GDPR compliance)

/**
 * Získa Google Analytics kód pre vloženie do HTML
 * 
 * @return string HTML kód pre Google Analytics
 */
function getGoogleAnalyticsCode() {
    // Check if analytics is enabled and has valid measurement ID
    $enabled = constant('GA_ENABLED');
    $measurementId = constant('GA_MEASUREMENT_ID');
    
    if (!$enabled || empty($measurementId)) {
        return '';
    }
    
    if ($measurementId === 'GA_MEASUREMENT_ID') {
        return '';
    }
    
    $debugMode = GA_DEBUG_MODE ? '?debug_mode=true' : '';
    $anonymizeIp = GA_ANONYMIZE_IP ? 'true' : 'false';
    
    $code = "
    <!-- Google Analytics -->
    <script async src=\"https://www.googletagmanager.com/gtag/js?id=" . GA_MEASUREMENT_ID . $debugMode . "\"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        
        // Základná konfigurácia
        gtag('config', '" . GA_MEASUREMENT_ID . "', {
            'anonymize_ip': " . $anonymizeIp . ",
            'cookie_flags': 'SameSite=None;Secure'
        });";
    
    // E-commerce tracking
    if (GA_ENHANCED_ECOMMERCE) {
        $code .= "
        // Enhanced E-commerce
        gtag('config', '" . GA_MEASUREMENT_ID . "', {
            'enhanced_ecommerce': true
        });";
    }
    
    // Custom events
    if (GA_TRACK_EVENTS) {
        $code .= "
        
        // Custom events tracking
        function trackEvent(eventName, parameters = {}) {
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, parameters);
            }
        }
        
        // Track page views with custom parameters
        function trackPageView(pageTitle, pageLocation) {
            if (typeof gtag !== 'undefined') {
                gtag('config', '" . GA_MEASUREMENT_ID . "', {
                    'page_title': pageTitle,
                    'page_location': pageLocation
                });
            }
        }";
    }
    
    $code .= "
    </script>
    <!-- End Google Analytics -->";
    
    return $code;
}

/**
 * Vytvorí Google Analytics event tracking kód
 * 
 * @param string $eventName Názov eventu
 * @param array $parameters Parametre eventu
 * @return string JavaScript kód
 */
function getGoogleAnalyticsEvent($eventName, $parameters = []) {
    // Check if analytics is enabled and event tracking is enabled
    $enabled = constant('GA_ENABLED');
    $trackEvents = constant('GA_TRACK_EVENTS');
    
    if (!$enabled || !$trackEvents) {
        return '';
    }
    
    $params = json_encode($parameters);
    return "trackEvent('$eventName', $params);";
}

/**
 * Vytvorí Google Analytics page view tracking kód
 * 
 * @param string $pageTitle Názov stránky
 * @param string $pageLocation URL stránky
 * @return string JavaScript kód
 */
function getGoogleAnalyticsPageView($pageTitle, $pageLocation) {
    // Check if analytics is enabled
    $enabled = constant('GA_ENABLED');
    
    if (!$enabled) {
        return '';
    }
    
    return "trackPageView('$pageTitle', '$pageLocation');";
}
?>
