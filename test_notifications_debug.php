<?php
session_start();
// Simulate logged in session
$_SESSION['logged_in'] = true;
$_SESSION['username'] = 'admin';

require_once 'includes/database.php';

echo "<h2>Notification System Debug</h2>";

try {
    $database = new Database();
    $db = $database->connect();
    
    // Check if tables exist
    echo "<h3>1. Database Tables Check</h3>";
    $tables = ['dismissed_notifications', 'user_notification_settings', 'products', 'cosmetics', 'dental'];
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            $count = $db->query("SELECT COUNT(*) as count FROM $table")->fetch();
            echo "✅ $table table exists - Record count: " . $count['count'] . "<br>";
        } else {
            echo "❌ $table table does NOT exist<br>";
        }
    }
    
    // Check dismissed notifications
    echo "<h3>2. Dismissed Notifications Check</h3>";
    $dismissed = $db->query("SELECT * FROM dismissed_notifications ORDER BY dismissed_at DESC LIMIT 5")->fetchAll();
    if (empty($dismissed)) {
        echo "No dismissed notifications found<br>";
    } else {
        echo "Recent dismissed notifications:<br>";
        foreach ($dismissed as $d) {
            echo "- Type: {$d['notification_type']}, Category: {$d['item_category']}, Item ID: {$d['item_id']}, Dismissed: {$d['dismissed_at']}<br>";
        }
    }
    
    // Check user notification settings
    echo "<h3>3. User Notification Settings</h3>";
    $settings = $db->query("SELECT * FROM user_notification_settings WHERE user_id = 1")->fetch();
    if ($settings) {
        echo "Dismiss all until: " . ($settings['dismiss_all_until'] ?: 'Not set') . "<br>";
    } else {
        echo "No user notification settings found<br>";
    }
    
    // Test the notification API
    echo "<h3>4. API Test</h3>";
    echo "Testing notifications API...<br>";
    
    // Include the notification models
    $productModel = new Product($db);
    $cosmeticModel = new Cosmetic($db);
    $dentalModel = new Dental($db);
    
    // Check raw data counts
    echo "<h4>Raw Inventory Data:</h4>";
    
    // Out of stock items
    $outOfStock = $productModel->getOutOfStockItems();
    echo "Products out of stock: " . count($outOfStock) . "<br>";
    
    $outOfStockCosmetics = $cosmeticModel->getOutOfStockItems();
    echo "Cosmetics out of stock: " . count($outOfStockCosmetics) . "<br>";
    
    $outOfStockDental = $dentalModel->getOutOfStockItems();
    echo "Dental out of stock: " . count($outOfStockDental) . "<br>";
    
    // Low stock items
    $lowStock = $productModel->getLowStockItems();
    echo "Products low stock: " . count($lowStock) . "<br>";
    
    $lowStockCosmetics = $cosmeticModel->getLowStockItems();
    echo "Cosmetics low stock: " . count($lowStockCosmetics) . "<br>";
    
    $lowStockDental = $dentalModel->getLowStockItems();
    echo "Dental low stock: " . count($lowStockDental) . "<br>";
    
    // Expired items
    $expired = $productModel->getExpiredItems();
    echo "Products expired: " . count($expired) . "<br>";
    
    $expiredCosmetics = $cosmeticModel->getExpiredItems();
    echo "Cosmetics expired: " . count($expiredCosmetics) . "<br>";
    
    $expiredDental = $dentalModel->getExpiredItems();
    echo "Dental expired: " . count($expiredDental) . "<br>";
    
    // Test the dismiss function
    echo "<h4>Testing Dismissal Logic:</h4>";
    
    if (!empty($outOfStock)) {
        $testItem = $outOfStock[0];
        echo "Testing dismissal for: " . $testItem['product_name'] . " (ID: {$testItem['id']})<br>";
        
        // Check if it's dismissed
        $isNotificationDismissedFunction = file_get_contents('api/notifications.php');
        if (strpos($isNotificationDismissedFunction, 'function isNotificationDismissed') !== false) {
            echo "Dismissal function exists in API<br>";
        } else {
            echo "❌ Dismissal function not found in API<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 2rem; }
h2, h3, h4 { color: #333; }
</style>