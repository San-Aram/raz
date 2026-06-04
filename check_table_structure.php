<?php
require_once 'includes/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "=== TABLE STRUCTURES ===\n";
    
    // Check products table structure
    echo "\n--- Products Table ---\n";
    $stmt = $db->query('DESCRIBE products');
    while ($row = $stmt->fetch()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
    // Check medications table structure
    echo "\n--- Medications Table ---\n";
    $stmt = $db->query('DESCRIBE medications');
    while ($row = $stmt->fetch()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
    // Check cosmetics table structure
    echo "\n--- Cosmetics Table ---\n";
    $stmt = $db->query('DESCRIBE cosmetics');
    while ($row = $stmt->fetch()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
    // Check dental table structure
    echo "\n--- Dental Table ---\n";
    $stmt = $db->query('DESCRIBE dental');
    while ($row = $stmt->fetch()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>