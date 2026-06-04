<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pharmacy_db', 'root', '12345');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== DETAILED PRODUCT ANALYSIS ===\n\n";
    
    // 1. Raw products table data
    echo "1. Raw products table data:\n";
    $result = $pdo->query('SELECT * FROM products ORDER BY id');
    $products = $result->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        echo "ID: {$product['id']} | Barcode: {$product['barcode']} | Name: {$product['product_name']} | Company: {$product['company']}\n";
    }
    
    // 2. Test the exact query used by Product::getAll()
    echo "\n2. Testing Product::getAll() query:\n";
    $query = "SELECT p.*, m.active_ingredient as medication_name FROM products p 
             LEFT JOIN medications m ON p.medication_id = m.id 
             WHERE 1=1 ORDER BY p.product_name ASC";
    
    $result = $pdo->query($query);
    $products = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Query returned " . count($products) . " products:\n";
    foreach ($products as $product) {
        echo "ID: {$product['id']} | Barcode: {$product['barcode']} | Name: {$product['product_name']} | Company: {$product['company']} | MedName: " . ($product['medication_name'] ?? 'NULL') . "\n";
    }
    
    // 3. Check for any data inconsistencies
    echo "\n3. Data consistency check:\n";
    
    // Check for NULL or empty essential fields
    $result = $pdo->query('SELECT id, barcode, product_name, company FROM products WHERE barcode IS NULL OR barcode = "" OR product_name IS NULL OR product_name = ""');
    $inconsistent = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($inconsistent)) {
        echo "✓ All products have valid barcode and product_name\n";
    } else {
        echo "⚠ Found " . count($inconsistent) . " products with missing data:\n";
        foreach ($inconsistent as $item) {
            echo "  ID: {$item['id']} | Barcode: '{$item['barcode']}' | Name: '{$item['product_name']}'\n";
        }
    }
    
    // 4. Check if there are any hidden characters or encoding issues
    echo "\n4. Checking for encoding issues:\n";
    $result = $pdo->query('SELECT id, barcode, product_name, LENGTH(barcode) as barcode_length, LENGTH(product_name) as name_length FROM products');
    $products = $result->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        echo "ID: {$product['id']} | Barcode: '{$product['barcode']}' (len: {$product['barcode_length']}) | Name: '{$product['product_name']}' (len: {$product['name_length']})\n";
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
