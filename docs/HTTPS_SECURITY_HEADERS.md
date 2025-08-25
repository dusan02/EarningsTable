# 🌐 HTTPS & Security Headers

## 📋 **Prehľad**

HTTPS & Security Headers je komplexný systém pre zabezpečenie bezpečnej komunikácie a ochranu pred rôznymi webovými útokmi. Poskytuje automatické presmerovanie na HTTPS a nastavenie všetkých potrebných bezpečnostných hlavičiek.

### **✅ Výhody:**

- **HTTPS Enforcement** - automatické presmerovanie na bezpečné spojenie
- **Content Security Policy (CSP)** - ochrana pred XSS útokmi
- **HTTP Strict Transport Security (HSTS)** - vynútenie HTTPS
- **XSS Protection** - dodatočná ochrana pred XSS
- **Clickjacking Protection** - ochrana pred clickjacking útokmi
- **MIME Sniffing Protection** - ochrana pred MIME sniffing útokmi
- **Information Disclosure Protection** - kontrola referrer informácií
- **Permissions Policy** - kontrola prístupu k API

---

## 🚀 **Rýchle použitie**

### **1. Automatické nastavenie hlavičiek:**

```php
require_once 'config/security_headers.php';

// Automaticky nastaví všetky bezpečnostné hlavičky
$securityHeaders = SecurityHeaders::getInstance();
$securityHeaders->setSecurityHeaders();
```

### **2. Použitie middleware:**

```php
$middleware = new HTTPSMiddleware();
$middleware->process(); // Nastaví hlavičky a validuje požiadavku
```

### **3. Spracovanie CSP violation:**

```php
$handler = new CSPViolationHandler();
$handler->handleViolation(); // Spracuje CSP violation reporty
```

---

## 📁 **Štruktúra súborov**

```
config/
├── security_headers.php     # SecurityHeaders, HTTPSMiddleware, CSPViolationHandler
└── config.php              # Hlavná konfigurácia

Tests/
└── test_security_headers.php # Test security headers

.htaccess                   # Apache konfigurácia
web.config                  # IIS konfigurácia

docs/
└── HTTPS_SECURITY_HEADERS.md # Táto dokumentácia
```

---

## 🔧 **Konfigurácia**

### **Automatické načítanie:**

```php
require_once 'config/security_headers.php';

// Použitie
$securityHeaders = SecurityHeaders::getInstance();
$middleware = new HTTPSMiddleware();
$handler = new CSPViolationHandler();
```

### **Konfigurácia hlavičiek:**

```php
// V SecurityHeaders triede
private $config = [
    'https_enforcement' => true,      // Vynúti HTTPS
    'hsts_enabled' => true,           // Povolí HSTS
    'csp_enabled' => true,            // Povolí CSP
    'xss_protection' => true,         // Povolí XSS Protection
    'content_type_options' => true,   // Povolí Content Type Options
    'frame_options' => true,          // Povolí Frame Options
    'referrer_policy' => true,        // Povolí Referrer Policy
    'permissions_policy' => true      // Povolí Permissions Policy
];
```

---

## 🧪 **Testovanie**

### **Test security headers:**

```bash
make test-headers

# Alebo priamo
php Tests/test_security_headers.php
```

---

## 🔧 **Používanie v kóde**

### **1. SecurityHeaders - Základné nastavenie:**

#### **Nastavenie všetkých hlavičiek:**

```php
$securityHeaders = SecurityHeaders::getInstance();

// Nastav všetky bezpečnostné hlavičky
$securityHeaders->setSecurityHeaders();
```

#### **Kontrola HTTPS:**

```php
$securityInfo = $securityHeaders->getSecurityInfo();

if ($securityInfo['https_enabled']) {
    echo "Požiadavka je cez HTTPS";
} else {
    echo "Požiadavka nie je cez HTTPS";
}
```

#### **Validácia požiadavky:**

```php
$issues = $securityHeaders->validateRequest();

if (!empty($issues)) {
    foreach ($issues as $issue) {
        echo "Bezpečnostný problém: $issue\n";
    }
}
```

#### **Test hlavičiek:**

```php
$tests = $securityHeaders->testHeaders();

if ($tests['hsts']) {
    echo "HSTS hlavička je nastavená\n";
}

if ($tests['csp']) {
    echo "CSP hlavička je nastavená\n";
}
```

#### **Custom hlavičky:**

```php
$customHeaders = [
    'X-Custom-Header' => 'CustomValue',
    'X-Test-Header' => 'TestValue'
];

$securityHeaders->setCustomHeaders($customHeaders);
```

### **2. HTTPSMiddleware - Automatické spracovanie:**

#### **Základné použitie:**

```php
$middleware = new HTTPSMiddleware();

// Spracuje požiadavku (nastaví hlavičky, validuje, loguje problémy)
$result = $middleware->process();

if ($result) {
    echo "Požiadavka je bezpečná";
} else {
    echo "Požiadavka má bezpečnostné problémy";
}
```

#### **Získanie informácií:**

```php
$securityInfo = $middleware->getSecurityInfo();
print_r($securityInfo);
```

### **3. CSPViolationHandler - Spracovanie CSP violation:**

#### **Spracovanie violation reportu:**

```php
$handler = new CSPViolationHandler();

// Automaticky spracuje CSP violation report
$handler->handleViolation();
```

---

## 🌐 **HTTPS Enforcement**

### **Automatické presmerovanie:**

- **HTTP → HTTPS** - všetky HTTP požiadavky sa automaticky presmerujú na HTTPS
- **301 Permanent Redirect** - prehliadače si zapamätajú presmerovanie
- **HSTS** - prehliadače budú automaticky používať HTTPS

### **Detekcia HTTPS:**

```php
// Kontroluje rôzne spôsoby detekcie HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    // HTTPS cez $_SERVER['HTTPS']
}

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    // HTTPS cez proxy
}

if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') {
    // HTTPS cez port
}
```

---

## 🛡️ **Security Headers**

### **1. HSTS (HTTP Strict Transport Security):**

```
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

- **max-age=31536000** - 1 rok
- **includeSubDomains** - platí aj pre subdomény
- **preload** - zahrnuté v preload list

### **2. CSP (Content Security Policy):**

```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https: http:; connect-src 'self' https://api.polygon.io https://finnhub.io https://query1.finance.yahoo.com; frame-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; upgrade-insecure-requests
```

**Direktívy:**

- **default-src 'self'** - predvolené zdroje len z vlastnej domény
- **script-src** - povolené zdroje pre JavaScript
- **style-src** - povolené zdroje pre CSS
- **font-src** - povolené zdroje pre fonty
- **img-src** - povolené zdroje pre obrázky
- **connect-src** - povolené API volania
- **frame-src** - povolené iframe
- **object-src 'none'** - zakázané objekty
- **base-uri 'self'** - base URL len z vlastnej domény
- **form-action 'self'** - formuláre len na vlastnú doménu
- **frame-ancestors 'self'** - ochrana pred clickjacking
- **upgrade-insecure-requests** - automatické presmerovanie na HTTPS

### **3. XSS Protection:**

```
X-XSS-Protection: 1; mode=block
```

- **1** - povolené
- **mode=block** - blokuje útoky

### **4. Content Type Options:**

```
X-Content-Type-Options: nosniff
```

- **nosniff** - zakazuje MIME sniffing

### **5. Frame Options:**

```
X-Frame-Options: SAMEORIGIN
```

- **SAMEORIGIN** - iframe len z rovnakej domény

### **6. Referrer Policy:**

```
Referrer-Policy: strict-origin-when-cross-origin
```

- **strict-origin-when-cross-origin** - referrer len pre HTTPS

### **7. Permissions Policy:**

```
Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()
```

- Zakazuje prístup k citlivým API

---

## 🔧 **Server Konfigurácia**

### **Apache (.htaccess):**

```apache
# HTTPS Enforcement
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security Headers
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set Content-Security-Policy "..."
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "..."
</IfModule>
```

### **IIS (web.config):**

```xml
<rewrite>
    <rules>
        <rule name="HTTPS Redirect" stopProcessing="true">
            <match url="(.*)" />
            <conditions>
                <add input="{HTTPS}" pattern="off" ignoreCase="true" />
            </conditions>
            <action type="Redirect" url="https://{HTTP_HOST}/{R:1}" redirectType="Permanent" />
        </rule>
    </rules>
</rewrite>

<httpProtocol>
    <customHeaders>
        <add name="Strict-Transport-Security" value="max-age=31536000; includeSubDomains; preload" />
        <add name="Content-Security-Policy" value="..." />
        <add name="X-XSS-Protection" value="1; mode=block" />
        <add name="X-Content-Type-Options" value="nosniff" />
        <add name="X-Frame-Options" value="SAMEORIGIN" />
        <add name="Referrer-Policy" value="strict-origin-when-cross-origin" />
        <add name="Permissions-Policy" value="..." />
    </customHeaders>
</httpProtocol>
```

---

## 📊 **Monitoring a reporty**

### **CSP Violation Reporty:**

```php
// Automaticky sa spracovávajú cez CSPViolationHandler
// Logujú sa do security logov
{
    "csp-report": {
        "document-uri": "https://example.com/page",
        "violated-directive": "script-src",
        "blocked-uri": "https://evil.com/script.js",
        "source-file": "https://example.com/page",
        "line-number": 10
    }
}
```

### **Bezpečnostné informácie:**

```php
$securityInfo = $securityHeaders->getSecurityInfo();

// Výstup:
[
    'https_enabled' => true,
    'headers_set' => [
        'https_enforcement' => true,
        'hsts_enabled' => true,
        'csp_enabled' => true,
        // ...
    ],
    'request_validation' => []
]
```

---

## 🚨 **Alerting systém**

### **Automatické alerty:**

- **HTTP požiadavky** - automatické presmerovanie na HTTPS
- **CSP violation** - logovanie do security logov
- **Podozrivé User-Agent** - detekcia botov a crawlerov
- **Neautorizované Origin** - CORS kontrola

### **Logovanie problémov:**

```php
$issues = [
    'Request not using HTTPS',
    'Suspicious User-Agent detected: EvilBot/1.0',
    'Unauthorized Origin: https://evil.com'
];

$securityHeaders->logSecurityIssues($issues);
```

---

## 🔍 **Validácia požiadavky**

### **Kontrolované aspekty:**

- **HTTPS** - či je požiadavka cez HTTPS
- **User-Agent** - detekcia podozrivých botov
- **Origin/Referer** - kontrola CORS
- **Request Headers** - validácia hlavičiek

### **Podozrivé vzory:**

```php
$suspiciousPatterns = [
    '/bot/i',
    '/crawler/i',
    '/spider/i',
    '/scraper/i',
    '/curl/i',
    '/wget/i'
];
```

---

## 📞 **Troubleshooting**

### **Časté problémy:**

#### **1. Hlavičky sa nenastavujú:**

```bash
# Riešenie: Skontroluj oprávnenia
chmod 644 .htaccess
chmod 644 web.config
```

#### **2. HTTPS presmerovanie nefunguje:**

```bash
# Riešenie: Skontroluj SSL certifikát
openssl s_client -connect yourdomain.com:443
```

#### **3. CSP blokuje legitímny obsah:**

```php
// Riešenie: Uprav CSP direktívy
$cspDirectives = [
    "script-src 'self' 'unsafe-inline' https://trusted-cdn.com",
    // ...
];
```

#### **4. HSTS nefunguje:**

```bash
# Riešenie: Skontroluj hlavičku
curl -I https://yourdomain.com | grep Strict-Transport-Security
```

---

## 🎯 **Best Practices**

### **✅ Odporúčania:**

1. **Vždy používaj HTTPS** - pre všetku komunikáciu
2. **Nastav CSP** - chráni pred XSS útokmi
3. **Používaj HSTS** - vynúti HTTPS
4. **Monitoruj CSP violation** - sleduj pokusy o útoky
5. **Pravidelne testuj hlavičky** - overuj funkčnosť
6. **Loguj bezpečnostné problémy** - pre audit
7. **Aktualizuj CSP** - podľa potrieb aplikácie

### **❌ Čo sa vyhnúť:**

1. **'unsafe-inline' v CSP** - ak je to možné
2. **'unsafe-eval' v CSP** - ak je to možné
3. **Príliš široké CSP** - používaj minimálne oprávnenia
4. **Ignorovanie CSP violation** - vždy reaguj
5. **HTTP komunikácia** - vždy používaj HTTPS

---

## 🔒 **Bezpečnostné aspekty**

### **Ochrana pred útokmi:**

- **XSS** - CSP, XSS Protection
- **Clickjacking** - Frame Options
- **MIME Sniffing** - Content Type Options
- **Man-in-the-Middle** - HTTPS, HSTS
- **Information Disclosure** - Referrer Policy
- **Unauthorized API Access** - Permissions Policy

### **Compliance:**

- **OWASP Top 10** - pokrýva viacero kategórií
- **GDPR** - ochrana osobných údajov
- **PCI DSS** - bezpečnosť finančných údajov
- **ISO 27001** - informačná bezpečnosť

---

## 🎉 **Záver**

HTTPS & Security Headers zabezpečuje:

- **Bezpečnosť** - ochrana pred webovými útokmi
- **Dôveryhodnosť** - HTTPS komunikácia
- **Compliance** - splnenie bezpečnostných štandardov
- **Monitoring** - sledovanie pokusov o útoky
- **Transparentnosť** - jasné bezpečnostné politiky

**Teraz máš kompletnú ochranu webovej aplikácie!** 🌐
