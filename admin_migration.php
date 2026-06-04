<?php
// Admin Role Migration Script
require_once 'includes/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "<h2>🔧 Admin Role System Migration</h2>";
    
    // 1. Update users table to support admin role
    echo "<h3>1. Updating Users Table Schema</h3>";
    
    // Check if role column exists
    $checkRole = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($checkRole->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'manager', 'seller') DEFAULT 'seller' AFTER password");
        echo "✅ Added role column to users table<br>";
    } else {
        // Update existing role column to include admin
        $db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'manager', 'seller') DEFAULT 'seller'");
        echo "✅ Updated role column to include admin<br>";
    }
    
    // Add admin-specific fields
    $adminFields = [
        'last_login' => "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER role",
        'login_attempts' => "ALTER TABLE users ADD COLUMN login_attempts INT DEFAULT 0 AFTER last_login",
        'account_locked' => "ALTER TABLE users ADD COLUMN account_locked BOOLEAN DEFAULT FALSE AFTER login_attempts",
        'created_at' => "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER account_locked",
        'updated_at' => "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
    ];
    
    foreach ($adminFields as $field => $sql) {
        $checkField = $db->query("SHOW COLUMNS FROM users LIKE '$field'");
        if ($checkField->rowCount() == 0) {
            $db->exec($sql);
            echo "✅ Added $field column<br>";
        }
    }
    
    // 2. Create admin settings table
    echo "<h3>2. Creating Admin Settings Table</h3>";
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Admin settings table created<br>";
    
    // 3. Create audit log table
    echo "<h3>3. Creating Audit Log Table</h3>";
    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            user_role ENUM('admin', 'manager', 'seller') NOT NULL,
            action VARCHAR(100) NOT NULL,
            table_affected VARCHAR(50),
            record_id INT,
            old_values JSON,
            new_values JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_action (user_id, action),
            INDEX idx_created_at (created_at),
            INDEX idx_table_record (table_affected, record_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Audit log table created<br>";
    
    // 4. Create system statistics table
    echo "<h3>4. Creating System Statistics Table</h3>";
    $db->exec("
        CREATE TABLE IF NOT EXISTS system_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            stat_date DATE NOT NULL,
            total_users INT DEFAULT 0,
            total_products INT DEFAULT 0,
            total_medications INT DEFAULT 0,
            total_sales DECIMAL(10,2) DEFAULT 0.00,
            daily_transactions INT DEFAULT 0,
            system_uptime INT DEFAULT 0,
            database_size_mb DECIMAL(8,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_date (stat_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ System statistics table created<br>";
    
    // 5. Create default admin user
    echo "<h3>5. Creating Default Admin User</h3>";
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Check if admin user exists
    $checkAdmin = $db->prepare("SELECT id FROM users WHERE username = 'admin' OR role = 'admin'");
    $checkAdmin->execute();
    
    if ($checkAdmin->rowCount() == 0) {
        $db->prepare("
            INSERT INTO users (username, password, role, created_at) 
            VALUES ('admin', ?, 'admin', NOW())
        ")->execute([$adminPassword]);
        echo "✅ Default admin user created (username: admin, password: admin123)<br>";
    } else {
        echo "ℹ️ Admin user already exists<br>";
    }
    
    // 6. Insert default admin settings
    echo "<h3>6. Configuring Default Settings</h3>";
    $defaultSettings = [
        ['system_name', 'Razology Pharmacy Management System', 'string', 'System display name'],
        ['system_version', '2.1.0', 'string', 'Current system version'],
        ['maintenance_mode', 'false', 'boolean', 'System maintenance mode'],
        ['max_login_attempts', '5', 'integer', 'Maximum login attempts before lockout'],
        ['session_timeout', '3600', 'integer', 'Session timeout in seconds'],
        ['backup_enabled', 'true', 'boolean', 'Automatic backup enabled'],
        ['notification_retention_days', '30', 'integer', 'Days to keep notifications'],
        ['audit_log_retention_days', '90', 'integer', 'Days to keep audit logs'],
        ['low_stock_threshold', '10', 'integer', 'Default low stock threshold'],
        ['currency_symbol', '$', 'string', 'Currency symbol for pricing'],
        ['timezone', 'America/New_York', 'string', 'System timezone']
    ];
    
    foreach ($defaultSettings as $setting) {
        $stmt = $db->prepare("
            INSERT INTO admin_settings (setting_key, setting_value, setting_type, description) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute($setting);
    }
    echo "✅ Default settings configured<br>";
    
    // 7. Update existing manager user
    echo "<h3>7. Updating Existing Users</h3>";
    $db->exec("UPDATE users SET role = 'manager' WHERE username != 'admin' AND role IS NULL");
    echo "✅ Updated existing users to manager role<br>";
    
    echo "<br><div style='background: #d4edda; padding: 1.5rem; border-radius: 8px; color: #155724; border: 1px solid #c3e6cb;'>";
    echo "<h4>🎉 Migration Completed Successfully!</h4>";
    echo "<p><strong>Admin System Features Added:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Three-tier role system: Admin > Manager > Seller</li>";
    echo "<li>✅ Admin user management and authentication</li>";
    echo "<li>✅ System settings and configuration</li>";
    echo "<li>✅ Comprehensive audit logging</li>";
    echo "<li>✅ System statistics tracking</li>";
    echo "<li>✅ Security enhancements and account lockout</li>";
    echo "</ul>";
    echo "<p><strong>Default Admin Credentials:</strong></p>";
    echo "<p>Username: <code>admin</code><br>Password: <code>admin123</code></p>";
    echo "<p><em>⚠️ Please change the default admin password immediately after login!</em></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
    echo "<strong>❌ Migration failed:</strong> " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    max-width: 900px; 
    margin: 0 auto; 
    padding: 2rem; 
    background: #f8f9fa; 
}
h2, h3, h4 { color: #333; }
h2 { border-bottom: 3px solid #007bff; padding-bottom: 0.5rem; }
code { background: #e9ecef; padding: 0.2rem 0.4rem; border-radius: 3px; }
</style>