<?php
// Database migration to add dismissed notifications functionality
require_once 'includes/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "<h2>Adding Dismissed Notifications Table</h2>";
    
    // Create dismissed_notifications table
    echo "<h3>Creating dismissed_notifications table...</h3>";
    $db->exec("
        CREATE TABLE IF NOT EXISTS dismissed_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT 1,
            notification_type ENUM('expired', 'expiring', 'out_of_stock', 'low_stock') NOT NULL,
            item_category ENUM('products', 'cosmetics', 'dental') NOT NULL,
            item_id INT NOT NULL,
            dismissed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_notification (user_id, notification_type, item_category, item_id),
            INDEX idx_dismissed_at (dismissed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ dismissed_notifications table created successfully<br>";
    
    // Create a general dismiss all function by adding a dismiss_all_until timestamp
    echo "<h3>Adding dismiss all functionality...</h3>";
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_notification_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT 1,
            dismiss_all_until TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ user_notification_settings table created successfully<br>";
    
    // Insert default setting for user
    $db->exec("
        INSERT IGNORE INTO user_notification_settings (user_id) VALUES (1)
    ");
    echo "✅ Default notification settings created<br>";
    
    echo "<br><div style='background: #d4edda; padding: 1rem; border-radius: 4px; color: #155724;'>";
    echo "<strong>✅ Migration completed successfully!</strong><br>";
    echo "Added dismissed notifications tracking and dismiss all functionality.";
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
    <title>Notification System Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 2rem; }
        h2, h3 { color: #333; }
    </style>
</head>
<body>
    <p><a href="index.php" style="color: #007bff; text-decoration: none;">← Back to Dashboard</a></p>
</body>
</html>