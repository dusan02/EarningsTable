<?php
/**
 * Create Database Script
 */

// Database Configuration (without database name)
$host = 'localhost';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    // Connect without database
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to MySQL successfully\n";
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS earnings_table CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);
    
    echo "✅ Database 'earnings_table' created successfully\n";
    
    // Test connection to the new database
    $pdo = new PDO("mysql:host=$host;dbname=earnings_table;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Successfully connected to 'earnings_table' database\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
