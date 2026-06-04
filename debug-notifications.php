<?php
session_start();
require_once 'includes/database.php';

echo "<h2>Notification Debug</h2>";

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "<h3>1. Check Database Structure</h3>";
    
    // First, check what columns exist in products table
    $stmt = $db->prepare("DESCRIBE products");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Products table columns:</strong></p>";
    foreach ($columns as $col) {
        echo "{$col['Field']} ({$col['Type']}), ";
    }
    echo "<br><br>";
    
    echo "<h3>2. Check Low Stock Items & Thresholds</h3>";
    
    // Check products with their thresholds
    $stmt = $db->prepare("SELECT id, product_name, quantity, low_stock_threshold FROM products WHERE quantity <= 10 ORDER BY quantity ASC LIMIT 20");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Product Name</th><th>Stock</th><th>Low Stock Threshold</th><th>Should Show Alert?</th><th>API Method Will Show?</th></tr>";
    
    foreach ($products as $product) {
        $shouldShow = $product['quantity'] <= 5 ? "YES (Critical)" : "YES (Low Stock)";
        
        // Check what the API method will return
        $apiWillShow = "NO";
        if ($product['quantity'] == 0) {
            $apiWillShow = "YES (Out of Stock)";
        } elseif ($product['quantity'] <= $product['low_stock_threshold']) {
            $apiWillShow = "YES (Low Stock)";
        }
        
        echo "<tr>";
        echo "<td>{$product['id']}</td>";
        echo "<td>{$product['product_name']}</td>";
        echo "<td>{$product['quantity']}</td>";
        echo "<td>{$product['low_stock_threshold']}</td>";
        echo "<td style='color: " . ($product['quantity'] <= 5 ? 'red' : 'orange') . ";'>{$shouldShow}</td>";
        echo "<td style='color: " . ($apiWillShow == "NO" ? 'red' : 'green') . ";'>{$apiWillShow}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>3. Fix Low Stock Thresholds</h3>";
    echo "<p>Many products may have low_stock_threshold = 0 or NULL, which means they won't trigger notifications.</p>";
    echo "<p><a href='?fix_thresholds=1' style='color: red;'>Click here to set all low_stock_threshold to 10</a></p>";
    
    if (isset($_GET['fix_thresholds'])) {
        $stmt = $db->prepare("UPDATE products SET low_stock_threshold = 10 WHERE low_stock_threshold IS NULL OR low_stock_threshold = 0");
        $stmt->execute();
        $affected = $stmt->rowCount();
        echo "<p style='color: green;'>✅ Updated {$affected} products to have low_stock_threshold = 10!</p>";
        echo "<p><a href='?'>Refresh page</a></p>";
    }
    
    echo "<h3>2. Check Dismissed Notifications</h3>";
    
    // Check dismissed notifications
    $stmt = $db->prepare("
        SELECT notification_type, item_category, item_id, dismissed_at,
               TIMESTAMPDIFF(HOUR, dismissed_at, NOW()) as hours_ago
        FROM dismissed_notifications 
        WHERE dismissed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY dismissed_at DESC
    ");
    $stmt->execute();
    $dismissed = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($dismissed)) {
        echo "<p>No dismissed notifications in the last 24 hours.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Type</th><th>Category</th><th>Item ID</th><th>Dismissed At</th><th>Hours Ago</th></tr>";
        
        foreach ($dismissed as $d) {
            echo "<tr>";
            echo "<td>{$d['notification_type']}</td>";
            echo "<td>{$d['item_category']}</td>";
            echo "<td>{$d['item_id']}</td>";
            echo "<td>{$d['dismissed_at']}</td>";
            echo "<td>{$d['hours_ago']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>3. Test Notification Logic</h3>";
    
    // Test the notification API directly
    echo "<p>Testing notification API response...</p>";
    
    // Make a proper HTTP request to the API instead of including it
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Cookie: ' . http_build_query($_COOKIE, '', '; ')
        ]
    ]);
    
    $apiOutput = @file_get_contents('http://localhost/api/notifications.php', false, $context);
    
    if ($apiOutput === false) {
        echo "<p style='color: red;'>Failed to call API - trying direct include method...</p>";
        
        // Fallback: direct call to notification logic
        echo "<p>Creating notifications manually...</p>";
        
        // Test just the low stock logic manually
        $notifications = [];
        $totalCount = 0;
        
        foreach ($products as $product) {
            if ($product['quantity'] <= 10) {
                $severity = $product['quantity'] == 0 ? 'critical' : ($product['quantity'] <= 5 ? 'critical' : 'warning');
                $message = $product['quantity'] == 0 ? 
                    $product['product_name'] . ' is out of stock' : 
                    $product['product_name'] . ' has low stock (' . $product['quantity'] . ' remaining)';
                
                $notifications[] = [
                    'type' => $product['quantity'] == 0 ? 'out_of_stock' : 'low_stock',
                    'item_name' => $product['product_name'],
                    'message' => $message,
                    'severity' => $severity,
                    'quantity' => $product['quantity']
                ];
                $totalCount++;
            }
        }
        
        $apiOutput = json_encode([
            'success' => true,
            'notifications' => $notifications,
            'totalCount' => $totalCount
        ]);
        
        echo "<p style='color: green;'>Generated {$totalCount} notifications manually</p>";
    }
    
    echo "<p><strong>API Response:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($apiOutput);
    echo "</pre>";
    
    // Try to decode JSON
    $data = json_decode($apiOutput, true);
    if ($data) {
        echo "<p><strong>Parsed Notifications:</strong></p>";
        echo "<p>Total Count: " . $data['totalCount'] . "</p>";
        
        if (!empty($data['notifications'])) {
            foreach ($data['notifications'] as $notif) {
                echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 10px; background: #f9f9f9;'>";
                echo "<strong>{$notif['type']}</strong> - {$notif['item_name']}<br>";
                echo "Message: {$notif['message']}<br>";
                echo "Severity: {$notif['severity']}<br>";
                if (isset($notif['quantity'])) {
                    echo "Stock: {$notif['quantity']}<br>";
                }
                echo "</div>";
            }
        } else {
            echo "<p>No notifications returned by API.</p>";
        }
    } else {
        echo "<p style='color: red;'>Failed to parse API response as JSON.</p>";
    }
    
    echo "<h3>4. Clear All Dismissed Notifications (for testing)</h3>";
    echo "<p><a href='?clear=1' style='color: red;'>Click here to clear all dismissed notifications</a></p>";
    
    if (isset($_GET['clear'])) {
        $stmt = $db->prepare("DELETE FROM dismissed_notifications");
        $stmt->execute();
        echo "<p style='color: green;'>✅ All dismissed notifications cleared!</p>";
        echo "<p><a href='?'>Refresh page</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>