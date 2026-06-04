<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();
    
    $productModel = new Product($db);
    $cosmeticModel = new Cosmetic($db);
    $dentalModel = new Dental($db);
    
    // Check if we should ignore dismissed notifications (for testing)
    $ignoreDismissed = isset($_GET['ignore_dismissed']) && $_GET['ignore_dismissed'] === '1';
    
    // Handle dismiss actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'dismiss') {
            $notificationType = $_POST['type'] ?? '';
            $itemCategory = $_POST['category'] ?? '';
            $itemId = intval($_POST['item_id'] ?? 0);
            
            if ($notificationType && $itemCategory && $itemId) {
                $stmt = $db->prepare("
                    INSERT INTO dismissed_notifications (notification_type, item_category, item_id) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE dismissed_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$notificationType, $itemCategory, $itemId]);
                
                echo json_encode(['success' => true, 'message' => 'Notification dismissed']);
                exit;
            }
        } elseif ($action === 'dismiss_all') {
            $stmt = $db->prepare("
                UPDATE user_notification_settings 
                SET dismiss_all_until = CURRENT_TIMESTAMP 
                WHERE user_id = 1
            ");
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'All notifications dismissed']);
            exit;
        } elseif ($action === 'restock') {
            $itemCategory = $_POST['category'] ?? '';
            $itemId = intval($_POST['item_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 0);
            
            if ($itemCategory && $itemId && $quantity > 0) {
                $table = '';
                switch ($itemCategory) {
                    case 'products': $table = 'products'; break;
                    case 'cosmetics': $table = 'cosmetics'; break;
                    case 'dental': $table = 'dental'; break;
                    default:
                        echo json_encode(['success' => false, 'message' => 'Invalid category']);
                        exit;
                }
                
                $stmt = $db->prepare("UPDATE {$table} SET quantity = quantity + ? WHERE id = ?");
                if ($stmt->execute([$quantity, $itemId])) {
                    echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update stock']);
                }
                exit;
            }
        }
    }
    
    // Get dismiss all timestamp
    $stmt = $db->prepare("SELECT dismiss_all_until FROM user_notification_settings WHERE user_id = 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    $dismissAllUntil = $settings['dismiss_all_until'] ?? null;
    
    $notifications = [];
    $totalCount = 0;
    
    // Get all types of inventory alerts
    $categories = [
        'products' => ['model' => $productModel, 'name_field' => 'product_name'],
        'cosmetics' => ['model' => $cosmeticModel, 'name_field' => 'name'],
        'dental' => ['model' => $dentalModel, 'name_field' => 'name']
    ];
    
    foreach ($categories as $category => $config) {
        $model = $config['model'];
        $nameField = $config['name_field'];
        
        // Get expired items
        $expiredItems = $model->getExpiredItems();
        foreach ($expiredItems as $item) {
            // Check if this notification is dismissed
            if (!isNotificationDismissed($db, 'expired', $category, $item['id'], $dismissAllUntil)) {
                $notifications[] = [
                    'id' => uniqid(),
                    'type' => 'expired',
                    'category' => $category,
                    'item_id' => $item['id'],
                    'item_name' => $item[$nameField],
                    'message' => $item[$nameField] . ' has expired on ' . date('M j, Y', strtotime($item['expiry_date'])),
                    'severity' => 'critical',
                    'date' => $item['expiry_date'],
                    'quantity' => $item['quantity'] ?? 0,
                    'icon' => 'fas fa-exclamation-triangle',
                    'color' => '#dc3545'
                ];
                $totalCount++;
            }
        }
        
        // Get items expiring in next 30 days
        $expiringItems = $model->getExpiringItems(30);
        foreach ($expiringItems as $item) {
            // Skip already expired items
            if (strtotime($item['expiry_date']) < time()) continue;
            
            // Check if this notification is dismissed
            if (!isNotificationDismissed($db, 'expiring', $category, $item['id'], $dismissAllUntil)) {
                $daysUntilExpiry = floor((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
                $severity = $daysUntilExpiry <= 7 ? 'critical' : ($daysUntilExpiry <= 14 ? 'warning' : 'info');
                $color = $daysUntilExpiry <= 7 ? '#dc3545' : ($daysUntilExpiry <= 14 ? '#ffc107' : '#17a2b8');
                
                $notifications[] = [
                    'id' => uniqid(),
                    'type' => 'expiring',
                    'category' => $category,
                    'item_id' => $item['id'],
                    'item_name' => $item[$nameField],
                'message' => $item[$nameField] . ' expires in ' . $daysUntilExpiry . ' day' . ($daysUntilExpiry != 1 ? 's' : ''),
                'severity' => $severity,
                    'date' => $item['expiry_date'],
                    'days_until_expiry' => $daysUntilExpiry,
                    'quantity' => $item['quantity'] ?? 0,
                    'icon' => 'fas fa-clock',
                    'color' => $color
                ];
                $totalCount++;
            }
        }
        
        // Get out of stock items
        $outOfStockItems = $model->getOutOfStockItems();
        foreach ($outOfStockItems as $item) {
            // Check if this notification is dismissed (unless we're ignoring dismissals)
            if ($ignoreDismissed || !isNotificationDismissed($db, 'out_of_stock', $category, $item['id'], $dismissAllUntil)) {
                $notifications[] = [
                    'id' => uniqid(),
                    'type' => 'out_of_stock',
                    'category' => $category,
                    'item_id' => $item['id'],
                    'item_name' => $item[$nameField],
                    'message' => $item[$nameField] . ' is out of stock',
                    'severity' => 'critical',
                    'quantity' => 0,
                    'icon' => 'fas fa-times-circle',
                    'color' => '#dc3545'
                ];
                $totalCount++;
            }
        }
        
        // Get low stock items
        $lowStockItems = $model->getLowStockItems();
        foreach ($lowStockItems as $item) {
            // Check if this notification is dismissed (unless we're ignoring dismissals)
            if ($ignoreDismissed || !isNotificationDismissed($db, 'low_stock', $category, $item['id'], $dismissAllUntil)) {
                $notifications[] = [
                    'id' => uniqid(),
                    'type' => 'low_stock',
                    'category' => $category,
                    'item_id' => $item['id'],
                    'item_name' => $item[$nameField],
                    'message' => $item[$nameField] . ' is running low (only ' . $item['quantity'] . ' left)',
                    'severity' => 'warning',
                    'quantity' => $item['quantity'],
                    'threshold' => $item['low_stock_threshold'],
                    'icon' => 'fas fa-exclamation-triangle',
                    'color' => '#ffc107'
                ];
                $totalCount++;
            }
        }
    }
    
    // Sort notifications by severity and date
    usort($notifications, function($a, $b) {
        $severityOrder = ['critical' => 3, 'warning' => 2, 'info' => 1];
        
        if ($severityOrder[$a['severity']] !== $severityOrder[$b['severity']]) {
            return $severityOrder[$b['severity']] - $severityOrder[$a['severity']];
        }
        
        // If same severity, sort by date for expiry notifications
        if (isset($a['date']) && isset($b['date'])) {
            return strtotime($a['date']) - strtotime($b['date']);
        }
        
        return 0;
    });
    
    // Get action parameter for specific requests
    $action = $_GET['action'] ?? 'all';
    
    switch ($action) {
        case 'count':
            echo json_encode([
                'success' => true,
                'count' => $totalCount,
                'critical_count' => count(array_filter($notifications, fn($n) => $n['severity'] === 'critical')),
                'warning_count' => count(array_filter($notifications, fn($n) => $n['severity'] === 'warning')),
                'info_count' => count(array_filter($notifications, fn($n) => $n['severity'] === 'info'))
            ]);
            break;
            
        case 'summary':
            $summary = [
                'total' => $totalCount,
                'by_type' => [
                    'expired' => count(array_filter($notifications, fn($n) => $n['type'] === 'expired')),
                    'expiry_warning' => count(array_filter($notifications, fn($n) => $n['type'] === 'expiry_warning')),
                    'out_of_stock' => count(array_filter($notifications, fn($n) => $n['type'] === 'out_of_stock')),
                    'low_stock' => count(array_filter($notifications, fn($n) => $n['type'] === 'low_stock'))
                ],
                'by_category' => [
                    'products' => count(array_filter($notifications, fn($n) => $n['category'] === 'products')),
                    'cosmetics' => count(array_filter($notifications, fn($n) => $n['category'] === 'cosmetics')),
                    'dental' => count(array_filter($notifications, fn($n) => $n['category'] === 'dental'))
                ],
                'by_severity' => [
                    'critical' => count(array_filter($notifications, fn($n) => $n['severity'] === 'critical')),
                    'warning' => count(array_filter($notifications, fn($n) => $n['severity'] === 'warning')),  
                    'info' => count(array_filter($notifications, fn($n) => $n['severity'] === 'info'))
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'summary' => $summary
            ]);
            break;
            
        case 'all':
        default:
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'total_count' => $totalCount,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Check if a notification is dismissed
 */
function isNotificationDismissed($db, $type, $category, $itemId, $dismissAllUntil) {
    // Check if dismiss all is active
    if ($dismissAllUntil && strtotime($dismissAllUntil) > time() - 3600) { // 1 hour grace period
        return true;
    }
    
    // For stock-related notifications, check if stock has gotten worse since dismissal
    if ($type === 'low_stock' || $type === 'out_of_stock') {
        $stmt = $db->prepare("
            SELECT dismissed_at FROM dismissed_notifications 
            WHERE notification_type = ? AND item_category = ? AND item_id = ?
            AND dismissed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY dismissed_at DESC LIMIT 1
        ");
        $stmt->execute([$type, $category, $itemId]);
        $dismissed = $stmt->fetch();
        
        if ($dismissed) {
            // Get current stock level
            $currentStock = getCurrentStock($db, $category, $itemId);
            
            // If stock is critically low (≤ 5) or out of stock (0), always show notification
            // regardless of previous dismissal
            if ($currentStock <= 5) {
                return false; // Show notification even if dismissed
            }
            
            // Otherwise, respect the 24-hour dismissal period
            return true;
        }
    } else {
        // For non-stock notifications (expired, expiring), use standard 24-hour dismissal
        $stmt = $db->prepare("
            SELECT id FROM dismissed_notifications 
            WHERE notification_type = ? AND item_category = ? AND item_id = ?
            AND dismissed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$type, $category, $itemId]);
        return $stmt->fetch() !== false;
    }
    
    return false;
}

function getCurrentStock($db, $category, $itemId) {
    $table = $category === 'products' ? 'products' : $category;
    $stockField = 'quantity'; // All tables use 'quantity' column for stock
    
    $stmt = $db->prepare("SELECT $stockField FROM $table WHERE id = ?");
    $stmt->execute([$itemId]);
    $result = $stmt->fetch();
    
    return $result ? intval($result[$stockField]) : 0;
}
?>