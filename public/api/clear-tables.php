<?php
require_once '../../config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $results = [];
    
    // Clear all tables
    $tables = [
        'earnings_tickers',
        'company_names', 
        'earnings_eps_revenues',
        'current_prices_mcaps',
        'shares_outstanding'
    ];
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table");
        $stmt->execute();
        $count = $stmt->rowCount();
        $results['cleared'][$table] = $count;
    }
    
    $results['status'] = 'success';
    $results['message'] = 'All tables cleared successfully';
    
    echo json_encode($results, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error clearing tables: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
