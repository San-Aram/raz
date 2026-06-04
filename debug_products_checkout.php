<?php
require_once 'includes/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "=== DATABASE PRODUCT COUNTS ===\n";
    
    // Check products table
    $stmt = $db->query('SELECT COUNT(*) as count FROM products');
    $count = $stmt->fetch()['count'];
    echo "Products table: $count items\n";
    
    // Check medications table
    $stmt = $db->query('SELECT COUNT(*) as count FROM medications');
    $count = $stmt->fetch()['count'];
    echo "Medications table: $count items\n";
    
    // Check cosmetics table
    $stmt = $db->query('SELECT COUNT(*) as count FROM cosmetics');
    $count = $stmt->fetch()['count'];
    echo "Cosmetics table: $count items\n";
    
    // Check dental table
    $stmt = $db->query('SELECT COUNT(*) as count FROM dental');
    $count = $stmt->fetch()['count'];
    echo "Dental table: $count items\n";
    
    echo "\n=== SAMPLE PRODUCTS (First 5 from each table) ===\n";
    
    // Sample products
    echo "\n--- Products ---\n";
    $stmt = $db->query('SELECT id, name, brand, price, stock_quantity, barcode FROM products LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Brand: {$row['brand']}, Price: {$row['price']}, Stock: {$row['stock_quantity']}, Barcode: {$row['barcode']}\n";
    }
    
    // Sample medications
    echo "\n--- Medications ---\n";
    $stmt = $db->query('SELECT id, name, brand, dosage, price, stock_quantity, barcode FROM medications LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Brand: {$row['brand']}, Dosage: {$row['dosage']}, Price: {$row['price']}, Stock: {$row['stock_quantity']}, Barcode: {$row['barcode']}\n";
    }
    
    // Sample cosmetics
    echo "\n--- Cosmetics ---\n";
    $stmt = $db->query('SELECT id, name, brand, size, price, stock_quantity, barcode FROM cosmetics LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Brand: {$row['brand']}, Size: {$row['size']}, Price: {$row['price']}, Stock: {$row['stock_quantity']}, Barcode: {$row['barcode']}\n";
    }
    
    // Sample dental
    echo "\n--- Dental ---\n";
    $stmt = $db->query('SELECT id, name, brand, price, stock_quantity, barcode FROM dental LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Brand: {$row['brand']}, Price: {$row['price']}, Stock: {$row['stock_quantity']}, Barcode: {$row['barcode']}\n";
    }
    
    echo "\n=== BARCODE ANALYSIS ===\n";
    
    // Check barcode data
    $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE barcode IS NOT NULL AND barcode != ''");
    $count = $stmt->fetch()['count'];
    echo "Products with barcodes: $count\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM medications WHERE barcode IS NOT NULL AND barcode != ''");
    $count = $stmt->fetch()['count'];
    echo "Medications with barcodes: $count\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM cosmetics WHERE barcode IS NOT NULL AND barcode != ''");
    $count = $stmt->fetch()['count'];
    echo "Cosmetics with barcodes: $count\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM dental WHERE barcode IS NOT NULL AND barcode != ''");
    $count = $stmt->fetch()['count'];
    echo "Dental with barcodes: $count\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>