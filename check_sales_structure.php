<?php
require_once 'includes/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "=== SALES TABLE STRUCTURE ===\n";
    $stmt = $db->query('DESCRIBE sales');
    while ($row = $stmt->fetch()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
    echo "\n=== SALE_ITEMS TABLE STRUCTURE ===\n";
    $stmt = $db->query('DESCRIBE sale_items');
    while ($row = $stmt->fetch()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>