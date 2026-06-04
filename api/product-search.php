<?php
require_once '../includes/seller-auth.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (empty($query) || strlen($query) < 2) {
        echo json_encode(['success' => false, 'message' => 'Query too short']);
        exit;
    }

    $database = new Database();
    $db = $database->connect();

    // Search across all product tables
    $searchTerm = '%' . $query . '%';
    $products = [];

    // Search products (using correct column names)
    $stmt = $db->prepare("
        SELECT id, product_name as name, company as brand, dose as dosage, price, quantity as stock_quantity, 'product' as type, barcode
        FROM products 
        WHERE (product_name LIKE :search OR company LIKE :search OR active_ingredient LIKE :search)
        ORDER BY product_name ASC
        LIMIT 10
    ");
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();
    $products = array_merge($products, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Search cosmetics
    $stmt = $db->prepare("
        SELECT id, name, company as brand, NULL as dosage, price, quantity as stock_quantity, 'cosmetic' as type, barcode
        FROM cosmetics 
        WHERE (name LIKE :search OR company LIKE :search OR notes LIKE :search)
        ORDER BY name ASC
        LIMIT 10
    ");
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();
    $products = array_merge($products, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Search dental
    $stmt = $db->prepare("
        SELECT id, name, company as brand, custom_size as dosage, price, quantity as stock_quantity, 'dental' as type, barcode
        FROM dental 
        WHERE (name LIKE :search OR company LIKE :search OR notes LIKE :search)
        ORDER BY name ASC
        LIMIT 10
    ");
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();
    $products = array_merge($products, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Format the results
    foreach ($products as &$product) {
        $product['price'] = floatval($product['price']);
        $product['stock_quantity'] = intval($product['stock_quantity']);
    }

    // Sort by relevance (name matches first, then brand matches)
    usort($products, function($a, $b) use ($query) {
        $queryLower = strtolower($query);
        $aNameMatch = stripos($a['name'], $query) !== false;
        $bNameMatch = stripos($b['name'], $query) !== false;
        
        if ($aNameMatch && !$bNameMatch) return -1;
        if (!$aNameMatch && $bNameMatch) return 1;
        
        return strcasecmp($a['name'], $b['name']);
    });

    // Limit to top 20 results
    $products = array_slice($products, 0, 20);

    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => count($products)
    ]);

} catch (Exception $e) {
    error_log("Product search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}