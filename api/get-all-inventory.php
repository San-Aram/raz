<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once '../includes/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    $allItems = [];
    
    // Get all products
    $stmt = $db->query("
        SELECT id, product_name as name, quantity, expiry_date, low_stock_threshold, 'products' as category
        FROM products 
        ORDER BY product_name
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $allItems = array_merge($allItems, $products);
    
    // Get all cosmetics
    $stmt = $db->query("
        SELECT id, name, quantity, expiry_date, low_stock_threshold, 'cosmetics' as category
        FROM cosmetics 
        ORDER BY name
    ");
    $cosmetics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $allItems = array_merge($allItems, $cosmetics);
    
    // Get all dental items
    $stmt = $db->query("
        SELECT id, name, quantity, expiry_date, low_stock_threshold, 'dental' as category
        FROM dental 
        ORDER BY name
    ");
    $dental = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $allItems = array_merge($allItems, $dental);
    
    // Sort all items by name
    usort($allItems, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    echo json_encode([
        'success' => true,
        'items' => $allItems,
        'total_count' => count($allItems)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>