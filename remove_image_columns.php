<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pharmacy_db', 'root', '12345');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Remove image_url column from cosmetics table if it exists
    try {
        $pdo->exec("ALTER TABLE cosmetics DROP COLUMN image_url");
        echo "Removed image_url column from cosmetics table\n";
    } catch (Exception $e) {
        echo "image_url column doesn't exist in cosmetics table or error: " . $e->getMessage() . "\n";
    }
    
    // Remove image_url column from dental table if it exists
    try {
        $pdo->exec("ALTER TABLE dental DROP COLUMN image_url");
        echo "Removed image_url column from dental table\n";
    } catch (Exception $e) {
        echo "image_url column doesn't exist in dental table or error: " . $e->getMessage() . "\n";
    }
    
    echo "Image columns removal completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
