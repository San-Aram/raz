<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/database.php';

// Debug mode - show detailed info
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

if ($debug) {
    echo "DEBUG MODE - Session Info:\n";
    print_r($_SESSION);
    echo "\n\n";
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'session' => $_SESSION]);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();
    
    if ($debug) {
        echo "Database connected successfully\n";
    }
    
    $productModel = new Product($db);
    $cosmeticModel = new Cosmetic($db);
    $dentalModel = new Dental($db);
    
    if ($debug) {
        echo "Models created successfully\n";
        
        // Test each model
        echo "\nTesting Product model:\n";
        $products = $productModel->getOutOfStockItems();
        echo "Out of stock products: " . count($products) . "\n";
        if (count($products) > 0) {
            echo "First product: " . print_r($products[0], true) . "\n";
        }
        
        $lowStock = $productModel->getLowStockItems();
        echo "Low stock products: " . count($lowStock) . "\n";
        
        echo "\nTesting Cosmetic model:\n";
        $cosmeticsOut = $cosmeticModel->getOutOfStockItems();
        echo "Out of stock cosmetics: " . count($cosmeticsOut) . "\n";
        
        $cosmeticsLow = $cosmeticModel->getLowStockItems();
        echo "Low stock cosmetics: " . count($cosmeticsLow) . "\n";
        
        echo "\nTesting Dental model:\n";
        $dentalOut = $dentalModel->getOutOfStockItems();
        echo "Out of stock dental: " . count($dentalOut) . "\n";
        
        $dentalLow = $dentalModel->getLowStockItems();
        echo "Low stock dental: " . count($dentalLow) . "\n";
        
        exit;
    }
    
    // Regular API response - just call the original API
    include 'notifications.php';
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Exception occurred',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>