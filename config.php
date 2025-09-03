<?php
/**
 * Main Configuration File
 * Hlavný konfiguračný súbor - používa jednotný config systém
 */

// Use unified configuration system
require_once __DIR__ . '/config/config_unified.php';

// Development/Testing Configuration
define('ENABLE_MOCK_PRICE_CHANGES', true); // Set to false in production
?>
