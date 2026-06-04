<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pharmacy_db', 'root', '12345');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Add age_group column to dental table
    try {
        $pdo->exec("ALTER TABLE dental ADD COLUMN age_group ENUM('kids', 'adults', 'both') DEFAULT 'both'");
        echo "Added age_group column to dental table\n";
    } catch (Exception $e) {
        echo "age_group column already exists in dental table or error: " . $e->getMessage() . "\n";
    }
    
    // Add contains_fluoride column to dental table
    try {
        $pdo->exec("ALTER TABLE dental ADD COLUMN contains_fluoride BOOLEAN DEFAULT FALSE");
        echo "Added contains_fluoride column to dental table\n";
    } catch (Exception $e) {
        echo "contains_fluoride column already exists in dental table or error: " . $e->getMessage() . "\n";
    }
    
    echo "Dental table updates completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
