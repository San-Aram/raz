<?php
require_once 'includes/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "=== SAMPLE PRODUCTS FOR CHECKOUT TESTING ===\n";
    
    // Sample products with correct column names
    echo "\n--- Products ---\n";
    $stmt = $db->query('SELECT id, product_name, company, dose, price, quantity, barcode FROM products WHERE quantity > 0 LIMIT 10');
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['product_name']}, Brand: {$row['company']}, Dose: {$row['dose']}, Price: ₱{$row['price']}, Stock: {$row['quantity']}, Barcode: {$row['barcode']}\n";
    }
    
    // Sample cosmetics
    echo "\n--- Cosmetics ---\n";
    $stmt = $db->query('SELECT id, name, company, price, quantity, barcode FROM cosmetics WHERE quantity > 0 LIMIT 10');
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Brand: {$row['company']}, Price: ₱{$row['price']}, Stock: {$row['quantity']}, Barcode: {$row['barcode']}\n";
    }
    
    // Sample dental
    echo "\n--- Dental ---\n";
    $stmt = $db->query('SELECT id, name, company, custom_size, price, quantity, barcode FROM dental WHERE quantity > 0 LIMIT 10');
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Brand: {$row['company']}, Size: {$row['custom_size']}, Price: ₱{$row['price']}, Stock: {$row['quantity']}, Barcode: {$row['barcode']}\n";
    }
    
    echo "\n=== TEST SEARCH QUERIES ===\n";
    
    // Test search for common terms
    $searchTerms = ['paracetamol', 'aspirin', 'toothpaste', 'lotion', 'tablet'];
    
    foreach ($searchTerms as $term) {
        echo "\nSearching for '$term':\n";
        $searchTerm = '%' . $term . '%';
        
        // Search products
        $stmt = $db->prepare("SELECT product_name, company, price FROM products WHERE product_name LIKE :search LIMIT 3");
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        if ($results) {
            foreach ($results as $row) {
                echo "  Product: {$row['product_name']} by {$row['company']} - ₱{$row['price']}\n";
            }
        }
        
        // Search cosmetics
        $stmt = $db->prepare("SELECT name, company, price FROM cosmetics WHERE name LIKE :search LIMIT 3");
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        if ($results) {
            foreach ($results as $row) {
                echo "  Cosmetic: {$row['name']} by {$row['company']} - ₱{$row['price']}\n";
            }
        }
        
        // Search dental
        $stmt = $db->prepare("SELECT name, company, price FROM dental WHERE name LIKE :search LIMIT 3");
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        if ($results) {
            foreach ($results as $row) {
                echo "  Dental: {$row['name']} by {$row['company']} - ₱{$row['price']}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>