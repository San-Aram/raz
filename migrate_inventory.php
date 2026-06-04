<?php
// Database migration to add expiry_date and quantity fields to product tables
require_once 'includes/database.php';

// Function to safely add columns if they don't exist
function addColumnIfNotExists($db, $table, $column, $definition) {
    try {
        $checkQuery = "SHOW COLUMNS FROM `$table` LIKE '$column'";
        $stmt = $db->query($checkQuery);
        if ($stmt->rowCount() == 0) {
            $alterQuery = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
            $db->exec($alterQuery);
            return true;
        }
        return "exists";
    } catch (Exception $e) {
        throw new Exception("Error adding column $column to table $table: " . $e->getMessage());
    }
}

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "<h2>Adding Expiry Date and Quantity Fields to Product Tables</h2>";
    
    // Add fields to products table
    echo "<h3>Updating products table...</h3>";
    $result1 = addColumnIfNotExists($db, 'products', 'expiry_date', 'DATE NULL AFTER price');
    $result2 = addColumnIfNotExists($db, 'products', 'quantity', 'INT DEFAULT 0 AFTER price');
    $result3 = addColumnIfNotExists($db, 'products', 'low_stock_threshold', 'INT DEFAULT 10 AFTER price');
    echo "✅ Products table updated - expiry_date: " . ($result1 === "exists" ? "already exists" : "added") . 
         ", quantity: " . ($result2 === "exists" ? "already exists" : "added") . 
         ", low_stock_threshold: " . ($result3 === "exists" ? "already exists" : "added") . "<br>";
    
    // Add fields to cosmetics table
    echo "<h3>Updating cosmetics table...</h3>";
    $result4 = addColumnIfNotExists($db, 'cosmetics', 'expiry_date', 'DATE NULL AFTER price');
    $result5 = addColumnIfNotExists($db, 'cosmetics', 'quantity', 'INT DEFAULT 0 AFTER price');
    $result6 = addColumnIfNotExists($db, 'cosmetics', 'low_stock_threshold', 'INT DEFAULT 10 AFTER price');
    echo "✅ Cosmetics table updated - expiry_date: " . ($result4 === "exists" ? "already exists" : "added") . 
         ", quantity: " . ($result5 === "exists" ? "already exists" : "added") . 
         ", low_stock_threshold: " . ($result6 === "exists" ? "already exists" : "added") . "<br>";
    
    // Add fields to dental table
    echo "<h3>Updating dental table...</h3>";
    $result7 = addColumnIfNotExists($db, 'dental', 'expiry_date', 'DATE NULL AFTER price');
    $result8 = addColumnIfNotExists($db, 'dental', 'quantity', 'INT DEFAULT 0 AFTER price');
    $result9 = addColumnIfNotExists($db, 'dental', 'low_stock_threshold', 'INT DEFAULT 10 AFTER price');
    echo "✅ Dental table updated - expiry_date: " . ($result7 === "exists" ? "already exists" : "added") . 
         ", quantity: " . ($result8 === "exists" ? "already exists" : "added") . 
         ", low_stock_threshold: " . ($result9 === "exists" ? "already exists" : "added") . "<br>";
    
    // Create notifications table
    echo "<h3>Creating notifications table...</h3>";
    $db->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type ENUM('low_stock', 'out_of_stock', 'expiry_warning', 'expired') NOT NULL,
            category ENUM('products', 'cosmetics', 'dental') NOT NULL,
            item_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            severity ENUM('info', 'warning', 'critical') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            INDEX idx_type (type),
            INDEX idx_category (category),
            INDEX idx_severity (severity),
            INDEX idx_read (is_read),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Notifications table created<br>";
    
    echo "<div style='background: #d4edda; padding: 1rem; border-radius: 8px; margin-top: 1rem;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>Migration Completed Successfully!</h3>";
    echo "<p style='color: #155724; margin-bottom: 0;'>All product tables now have expiry_date, quantity, and low_stock_threshold fields.</p>";
    echo "<p style='color: #155724; margin-bottom: 0;'>Notifications table has been created for inventory alerts.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 8px; margin-top: 1rem;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>Migration Failed!</h3>";
    echo "<p style='color: #721c24; margin-bottom: 0;'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Migration - Add Inventory Management</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 2rem; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 0.5rem; }
        h3 { color: #007bff; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <a href="index.php" style="display: inline-block; margin-top: 2rem; padding: 0.5rem 1rem; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">Back to Dashboard</a>
</body>
</html>