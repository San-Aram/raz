<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pharmacy_db', 'root', '12345');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check current structure
    $result = $pdo->query('DESCRIBE medications');
    echo "Current medications table structure:\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\n--- Making changes ---\n";
    
    // Remove name, price, and image_url columns if they exist
    try {
        $pdo->exec('ALTER TABLE medications DROP COLUMN name');
        echo "Removed 'name' column\n";
    } catch(Exception $e) {
        echo "Column 'name' already removed or doesn't exist\n";
    }
    
    try {
        $pdo->exec('ALTER TABLE medications DROP COLUMN price');
        echo "Removed 'price' column\n";
    } catch(Exception $e) {
        echo "Column 'price' already removed or doesn't exist\n";
    }
    
    try {
        $pdo->exec('ALTER TABLE medications DROP COLUMN image_url');
        echo "Removed 'image_url' column\n";
    } catch(Exception $e) {
        echo "Column 'image_url' already removed or doesn't exist\n";
    }
    
    echo "\n--- Updated structure ---\n";
    $result = $pdo->query('DESCRIBE medications');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
