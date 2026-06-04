<?php
// Database migration to create seller/cashier system
require_once 'includes/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "<h2>Creating Seller/Cashier System Database Structure</h2>";
    
    // Update users table to include user roles
    echo "<h3>Creating users table with roles system...</h3>";
    
    // Create users table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('manager', 'seller') DEFAULT 'manager',
            full_name VARCHAR(100) DEFAULT NULL,
            email VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE,
            INDEX idx_username (username),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Users table created successfully<br>";
    
    // Create default manager account if it doesn't exist
    $stmt = $db->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, role, full_name) VALUES ('admin', ?, 'manager', 'System Administrator')");
        $stmt->execute([$hashedPassword]);
        echo "✅ Created manager user account (username: admin, password: admin)<br>";
    } else {
        echo "✅ Manager user account already exists<br>";
    }
    
    // Create seller user account
    $stmt = $db->prepare("SELECT id FROM users WHERE username = 'seller'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hashedPassword = password_hash('seller123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES ('seller', ?, 'seller')");
        $stmt->execute([$hashedPassword]);
        echo "✅ Created seller user account (username: seller, password: seller123)<br>";
    } else {
        echo "✅ Seller user account already exists<br>";
    }
    
    // Create sales table
    echo "<h3>Creating sales tables...</h3>";
    $db->exec("
        CREATE TABLE IF NOT EXISTS sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sale_number VARCHAR(20) UNIQUE NOT NULL,
            seller_id INT NOT NULL,
            customer_name VARCHAR(100) DEFAULT NULL,
            customer_phone VARCHAR(20) DEFAULT NULL,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payment_method ENUM('cash', 'card', 'mobile', 'insurance') DEFAULT 'cash',
            payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'paid',
            sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notes TEXT DEFAULT NULL,
            INDEX idx_seller_id (seller_id),
            INDEX idx_sale_date (sale_date),
            INDEX idx_sale_number (sale_number),
            FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Sales table created successfully<br>";
    
    // Create sale_items table
    $db->exec("
        CREATE TABLE IF NOT EXISTS sale_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sale_id INT NOT NULL,
            product_type ENUM('products', 'cosmetics', 'dental') NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            product_barcode VARCHAR(100) DEFAULT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            line_total DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sale_id (sale_id),
            INDEX idx_product_type_id (product_type, product_id),
            FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Sale items table created successfully<br>";
    
    // Create sales_sessions table for cart management
    $db->exec("
        CREATE TABLE IF NOT EXISTS sales_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(255) NOT NULL,
            seller_id INT NOT NULL,
            cart_data TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_session_id (session_id),
            INDEX idx_seller_id (seller_id),
            FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Sales sessions table created successfully<br>";
    
    // Create daily_sales_summary table
    $db->exec("
        CREATE TABLE IF NOT EXISTS daily_sales_summary (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sale_date DATE NOT NULL,
            seller_id INT NOT NULL,
            total_sales DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_transactions INT NOT NULL DEFAULT 0,
            cash_sales DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            card_sales DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_seller_date (sale_date, seller_id),
            INDEX idx_seller_date (seller_id, sale_date),
            FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Daily sales summary table created successfully<br>";
    
    // Update existing manager user
    $stmt = $db->prepare("UPDATE users SET role = 'manager' WHERE username != 'seller'");
    $stmt->execute();
    echo "✅ Updated existing users to manager role<br>";
    
    echo "<br><div style='background: #d4edda; padding: 1rem; border-radius: 4px; color: #155724;'>";
    echo "<strong>✅ Seller System Migration Completed Successfully!</strong><br><br>";
    echo "<strong>User Accounts Created:</strong><br>";
    echo "• Manager Login: admin / admin (existing)<br>";
    echo "• Seller Login: seller / seller123 (new)<br><br>";
    echo "<strong>Database Tables Created:</strong><br>";
    echo "• sales - Main sales transactions<br>";
    echo "• sale_items - Individual items in each sale<br>";
    echo "• sales_sessions - Shopping cart management<br>";
    echo "• daily_sales_summary - Daily sales reporting<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
    echo "<strong>❌ Migration failed:</strong> " . $e->getMessage();
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Seller System Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 2rem; }
        h2, h3 { color: #333; }
    </style>
</head>
<body>
    <p><a href="index.php" style="color: #007bff; text-decoration: none;">← Back to Manager Dashboard</a></p>
    <p><a href="seller-login.php" style="color: #28a745; text-decoration: none;">→ Go to Seller Login</a></p>
</body>
</html>