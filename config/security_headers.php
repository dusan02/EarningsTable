<?php
/**
 * HTTPS & Security Headers
 * Bezpečnostné hlavičky a HTTPS enforcement
 */

class SecurityHeaders {
    private static $instance = null;
    private $config;
    
    /**
     * Konštruktor
     */
    private function __construct() {
        $this->config = [
            'https_enforcement' => true,
            'hsts_enabled' => true,
            'csp_enabled' => true,
            'xss_protection' => true,
            'content_type_options' => true,
            'frame_options' => true,
            'referrer_policy' => true,
            'permissions_policy' => true
        ];
    }
    
    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Nastaví všetky bezpečnostné hlavičky
     */
    public function setSecurityHeaders() {
        // HTTPS Enforcement
        if ($this->config['https_enforcement']) {
            $this->enforceHTTPS();
        }
        
        // HSTS (HTTP Strict Transport Security)
        if ($this->config['hsts_enabled']) {
            $this->setHSTS();
        }
        
        // CSP (Content Security Policy)
        if ($this->config['csp_enabled']) {
            $this->setCSP();
        }
        
        // XSS Protection
        if ($this->config['xss_protection']) {
            $this->setXSSProtection();
        }
        
        // Content Type Options
        if ($this->config['content_type_options']) {
            $this->setContentTypeOptions();
        }
        
        // Frame Options
        if ($this->config['frame_options']) {
            $this->setFrameOptions();
        }
        
        // Referrer Policy
        if ($this->config['referrer_policy']) {
            $this->setReferrerPolicy();
        }
        
        // Permissions Policy
        if ($this->config['permissions_policy']) {
            $this->setPermissionsPolicy();
        }
        
        return true;
    }
    
    /**
     * Vynúti HTTPS
     */
    private function enforceHTTPS() {
        // Kontrola, či je požiadavka cez HTTPS
        if (!$this->isHTTPS()) {
            $this->redirectToHTTPS();
        }
        
        // Nastav hlavičku pre HTTPS
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    
    /**
     * Skontroluje, či je požiadavka cez HTTPS
     */
    private function isHTTPS() {
        // Kontrola rôznych spôsobov detekcie HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            return true;
        }
        
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        
        if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Presmeruje na HTTPS
     */
    private function redirectToHTTPS() {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        $httpsUrl = 'https://' . $host . $uri;
        
        // 301 Permanent Redirect
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $httpsUrl);
        exit();
    }
    
    /**
     * Nastaví HSTS hlavičku
     */
    private function setHSTS() {
        $hstsHeader = 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload';
        header($hstsHeader);
    }
    
    /**
     * Nastaví CSP hlavičku
     */
    private function setCSP() {
        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "img-src 'self' data: https: http:",
            "connect-src 'self' https://api.polygon.io https://finnhub.io https://query1.finance.yahoo.com",
            "frame-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "upgrade-insecure-requests"
        ];
        
        $cspHeader = 'Content-Security-Policy: ' . implode('; ', $cspDirectives);
        header($cspHeader);
        
        // Report-Only verzia pre testovanie
        // header('Content-Security-Policy-Report-Only: ' . implode('; ', $cspDirectives));
    }
    
    /**
     * Nastaví XSS Protection hlavičku
     */
    private function setXSSProtection() {
        header('X-XSS-Protection: 1; mode=block');
    }
    
    /**
     * Nastaví Content Type Options hlavičku
     */
    private function setContentTypeOptions() {
        header('X-Content-Type-Options: nosniff');
    }
    
    /**
     * Nastaví Frame Options hlavičku
     */
    private function setFrameOptions() {
        header('X-Frame-Options: SAMEORIGIN');
    }
    
    /**
     * Nastaví Referrer Policy hlavičku
     */
    private function setReferrerPolicy() {
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    /**
     * Nastaví Permissions Policy hlavičku
     */
    private function setPermissionsPolicy() {
        $permissions = [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()'
        ];
        
        $permissionsHeader = 'Permissions-Policy: ' . implode(', ', $permissions);
        header($permissionsHeader);
    }
    
    /**
     * Nastaví custom hlavičky
     */
    public function setCustomHeaders($headers = []) {
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
    }
    
    /**
     * Skontroluje bezpečnosť požiadavky
     */
    public function validateRequest() {
        $issues = [];
        
        // Kontrola HTTPS
        if (!$this->isHTTPS()) {
            $issues[] = 'Request not using HTTPS';
        }
        
        // Kontrola User-Agent
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            
            // Detekcia podozrivých User-Agent
            $suspiciousPatterns = [
                '/bot/i',
                '/crawler/i',
                '/spider/i',
                '/scraper/i',
                '/curl/i',
                '/wget/i'
            ];
            
            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $userAgent)) {
                    $issues[] = 'Suspicious User-Agent detected: ' . $userAgent;
                    break;
                }
            }
        }
        
        // Kontrola Origin/Referer
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $allowedOrigins = [
                'https://localhost',
                'https://127.0.0.1',
                'https://yourdomain.com' // Pridaj svoju doménu
            ];
            
            $origin = $_SERVER['HTTP_ORIGIN'];
            if (!in_array($origin, $allowedOrigins)) {
                $issues[] = 'Unauthorized Origin: ' . $origin;
            }
        }
        
        return $issues;
    }
    
    /**
     * Loguje bezpečnostné problémy
     */
    public function logSecurityIssues($issues) {
        if (!empty($issues)) {
            require_once __DIR__ . '/logger.php';
            $logger = SecurityLogger::getInstance();
            
            foreach ($issues as $issue) {
                $logger->log('warning', 'security_header_violation', [
                    'issue' => $issue,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
            }
        }
    }
    
    /**
     * Vráti informácie o bezpečnostných hlavičkách
     */
    public function getSecurityInfo() {
        return [
            'https_enabled' => $this->isHTTPS(),
            'headers_set' => $this->config,
            'request_validation' => $this->validateRequest()
        ];
    }
    
    /**
     * Testuje bezpečnostné hlavičky
     */
    public function testHeaders() {
        $tests = [];
        
        // Test HTTPS
        $tests['https'] = $this->isHTTPS();
        
        // Test hlavičky
        $headers = headers_list();
        $tests['hsts'] = $this->hasHeader($headers, 'Strict-Transport-Security');
        $tests['csp'] = $this->hasHeader($headers, 'Content-Security-Policy');
        $tests['xss'] = $this->hasHeader($headers, 'X-XSS-Protection');
        $tests['content_type'] = $this->hasHeader($headers, 'X-Content-Type-Options');
        $tests['frame'] = $this->hasHeader($headers, 'X-Frame-Options');
        $tests['referrer'] = $this->hasHeader($headers, 'Referrer-Policy');
        $tests['permissions'] = $this->hasHeader($headers, 'Permissions-Policy');
        
        return $tests;
    }
    
    /**
     * Skontroluje, či hlavička existuje
     */
    private function hasHeader($headers, $name) {
        foreach ($headers as $header) {
            if (stripos($header, $name) === 0) {
                return true;
            }
        }
        return false;
    }
}

/**
 * HTTPS Middleware
 * Automatické presmerovanie na HTTPS
 */
class HTTPSMiddleware {
    private $securityHeaders;
    
    public function __construct() {
        $this->securityHeaders = SecurityHeaders::getInstance();
    }
    
    /**
     * Spracuje požiadavku
     */
    public function process() {
        // Nastav bezpečnostné hlavičky
        $this->securityHeaders->setSecurityHeaders();
        
        // Validuj požiadavku
        $issues = $this->securityHeaders->validateRequest();
        
        // Loguj problémy
        if (!empty($issues)) {
            $this->securityHeaders->logSecurityIssues($issues);
        }
        
        return empty($issues);
    }
    
    /**
     * Vráti informácie o bezpečnosti
     */
    public function getSecurityInfo() {
        return $this->securityHeaders->getSecurityInfo();
    }
}

/**
 * CSP Violation Handler
 * Spracováva CSP violation reporty
 */
class CSPViolationHandler {
    private $logger;
    
    public function __construct() {
        require_once __DIR__ . '/logger.php';
        $this->logger = SecurityLogger::getInstance();
    }
    
    /**
     * Spracuje CSP violation report
     */
    public function handleViolation() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
            isset($_SERVER['CONTENT_TYPE']) && 
            strpos($_SERVER['CONTENT_TYPE'], 'application/csp-report') !== false) {
            
            $input = file_get_contents('php://input');
            $report = json_decode($input, true);
            
            if ($report) {
                $this->logger->log('warning', 'csp_violation', [
                    'document_uri' => $report['csp-report']['document-uri'] ?? 'unknown',
                    'violated_directive' => $report['csp-report']['violated-directive'] ?? 'unknown',
                    'blocked_uri' => $report['csp-report']['blocked-uri'] ?? 'unknown',
                    'source_file' => $report['csp-report']['source-file'] ?? 'unknown',
                    'line_number' => $report['csp-report']['line-number'] ?? 'unknown'
                ]);
            }
        }
        
        // Vráť 204 No Content
        http_response_code(204);
        exit();
    }
}
?>
