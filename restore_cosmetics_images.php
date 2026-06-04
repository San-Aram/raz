<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pharmacy_db', 'root', '12345');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Add image_url column to cosmetics table
    try {
        $pdo->exec("ALTER TABLE cosmetics ADD COLUMN image_url VARCHAR(255) DEFAULT ''");
        echo "Added image_url column to cosmetics table\n";
    } catch (Exception $e) {
        echo "image_url column already exists in cosmetics table or error: " . $e->getMessage() . "\n";
    }
    
    echo "Cosmetics image restoration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
