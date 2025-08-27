<?php
/**
 * Page Checker Script
 * Kontroluje, či sa stránky zobrazujú správne
 */

echo "<h1>Page Checker</h1>\n";
echo "<p>Kontrolujem dostupnosť stránok...</p>\n";

$pages = [
    'earnings-dashboard-adminlte.html',
    'earnings-dashboard-clean.html',
    'earnings-dashboard-NEW.html',
    'public/earnings-dashboard-adminlte.html',
    'public/earnings-dashboard-clean.html',
    'public/earnings-dashboard-NEW.html'
];

foreach ($pages as $page) {
    echo "<h3>Kontrolujem: $page</h3>\n";
    
    // Skontroluj, či súbor existuje
    if (file_exists($page)) {
        echo "✅ Súbor existuje<br>\n";
        
        // Skús načítať obsah
        $content = file_get_contents($page);
        if ($content !== false) {
            echo "✅ Súbor sa dá načítať (" . strlen($content) . " bajtov)<br>\n";
            
            // Skontroluj, či obsahuje základné HTML elementy
            if (strpos($content, '<!DOCTYPE html>') !== false) {
                echo "✅ Obsahuje DOCTYPE<br>\n";
            } else {
                echo "❌ Chýba DOCTYPE<br>\n";
            }
            
            if (strpos($content, '<title>') !== false) {
                echo "✅ Obsahuje title tag<br>\n";
                // Extrahuj title
                preg_match('/<title>(.*?)<\/title>/', $content, $matches);
                if (isset($matches[1])) {
                    echo "   Title: " . htmlspecialchars($matches[1]) . "<br>\n";
                }
            } else {
                echo "❌ Chýba title tag<br>\n";
            }
            
            if (strpos($content, '<h1>') !== false) {
                echo "✅ Obsahuje H1 nadpis<br>\n";
                // Extrahuj H1
                preg_match('/<h1[^>]*>(.*?)<\/h1>/', $content, $matches);
                if (isset($matches[1])) {
                    echo "   H1: " . htmlspecialchars(strip_tags($matches[1])) . "<br>\n";
                }
            } else {
                echo "❌ Chýba H1 nadpis<br>\n";
            }
            
            // Skontroluj, či obsahuje tabuľku
            if (strpos($content, '<table') !== false) {
                echo "✅ Obsahuje tabuľku<br>\n";
            } else {
                echo "❌ Chýba tabuľka<br>\n";
            }
            
            // Skontroluj, či obsahuje JavaScript
            if (strpos($content, '<script>') !== false) {
                echo "✅ Obsahuje JavaScript<br>\n";
            } else {
                echo "❌ Chýba JavaScript<br>\n";
            }
            
        } else {
            echo "❌ Súbor sa nedá načítať<br>\n";
        }
    } else {
        echo "❌ Súbor neexistuje<br>\n";
    }
    
    echo "<hr>\n";
}

// Skontroluj API endpoint
echo "<h3>Kontrolujem API endpoint</h3>\n";
$api_url = 'http://localhost/api/earnings-tickers-today.php';
$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'method' => 'GET'
    ]
]);

$api_response = @file_get_contents($api_url, false, $context);
if ($api_response !== false) {
    echo "✅ API endpoint funguje<br>\n";
    $api_data = json_decode($api_response, true);
    if ($api_data && isset($api_data['total'])) {
        echo "   Počet tickerov: " . $api_data['total'] . "<br>\n";
    }
} else {
    echo "❌ API endpoint nefunguje<br>\n";
}

echo "<h3>Test HTTP požiadaviek</h3>\n";
$test_urls = [
    'http://localhost/earnings-dashboard-adminlte.html',
    'http://localhost/earnings-dashboard-clean.html',
    'http://localhost/public/earnings-dashboard-adminlte.html'
];

foreach ($test_urls as $url) {
    echo "Testujem: $url<br>\n";
    
    $headers = @get_headers($url);
    if ($headers !== false) {
        $status_line = $headers[0];
        echo "   Status: $status_line<br>\n";
        
        if (strpos($status_line, '200') !== false) {
            echo "   ✅ Stránka je dostupná<br>\n";
            
            // Skús načítať obsah
            $page_content = @file_get_contents($url);
            if ($page_content !== false) {
                echo "   ✅ Obsah sa dá načítať (" . strlen($page_content) . " bajtov)<br>\n";
                
                // Skontroluj, či obsahuje očakávaný text
                if (strpos($page_content, 'Earnings Dashboard') !== false) {
                    echo "   ✅ Obsahuje 'Earnings Dashboard'<br>\n";
                } else {
                    echo "   ❌ Neobsahuje 'Earnings Dashboard'<br>\n";
                }
            } else {
                echo "   ❌ Obsah sa nedá načítať<br>\n";
            }
        } else {
            echo "   ❌ Stránka nie je dostupná<br>\n";
        }
    } else {
        echo "   ❌ Nepodarilo sa pripojiť<br>\n";
    }
    echo "<br>\n";
}

echo "<h3>Záver</h3>\n";
echo "<p>Ak vidíte '❌' chyby, potom je problém s Apache konfiguráciou alebo cestami k súborom.</p>\n";
echo "<p>Skúste otvoriť: <a href='http://localhost/earnings-dashboard-adminlte.html' target='_blank'>http://localhost/earnings-dashboard-adminlte.html</a></p>\n";
?>
