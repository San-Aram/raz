<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

header('Content-Type: application/json');
require_once '../includes/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }
    
    $action = $input['action'] ?? '';
    $items = $input['items'] ?? [];
    $values = $input['values'] ?? [];
    
    if (empty($action) || empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Action and items are required']);
        exit;
    }
    
    $database = new Database();
    $db = $database->connect();
    
    $updatedCount = 0;
    $errors = [];
    
    switch ($action) {
        case 'update_quantity':
            $newQuantity = intval($values['quantity'] ?? 0);
            if ($newQuantity < 0) {
                echo json_encode(['success' => false, 'message' => 'Quantity must be non-negative']);
                exit;
            }
            
            foreach ($items as $item) {
                $category = $item['category'];
                $itemId = intval($item['id']);
                
                try {
                    if ($category === 'products') {
                        $stmt = $db->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                    } elseif ($category === 'cosmetics') {
                        $stmt = $db->prepare("UPDATE cosmetics SET quantity = ? WHERE id = ?");
                    } elseif ($category === 'dental') {
                        $stmt = $db->prepare("UPDATE dental SET quantity = ? WHERE id = ?");
                    } else {
                        continue;
                    }
                    
                    $stmt->execute([$newQuantity, $itemId]);
                    $updatedCount++;
                } catch (Exception $e) {
                    $errors[] = "Error updating {$category} item {$itemId}: " . $e->getMessage();
                }
            }
            break;
            
        case 'adjust_quantity':
            $adjustment = intval($values['adjustment'] ?? 0);
            
            foreach ($items as $item) {
                $category = $item['category'];
                $itemId = intval($item['id']);
                
                try {
                    if ($category === 'products') {
                        $stmt = $db->prepare("UPDATE products SET quantity = GREATEST(0, quantity + ?) WHERE id = ?");
                    } elseif ($category === 'cosmetics') {
                        $stmt = $db->prepare("UPDATE cosmetics SET quantity = GREATEST(0, quantity + ?) WHERE id = ?");
                    } elseif ($category === 'dental') {
                        $stmt = $db->prepare("UPDATE dental SET quantity = GREATEST(0, quantity + ?) WHERE id = ?");
                    } else {
                        continue;
                    }
                    
                    $stmt->execute([$adjustment, $itemId]);
                    $updatedCount++;
                } catch (Exception $e) {
                    $errors[] = "Error adjusting {$category} item {$itemId}: " . $e->getMessage();
                }
            }
            break;

        case 'update_price':
            $newPrice = floatval($values['price'] ?? -1);
            if ($newPrice < 0) {
                echo json_encode(['success' => false, 'message' => 'Price must be non-negative']);
                exit;
            }

            foreach ($items as $item) {
                $category = $item['category'];
                $itemId = intval($item['id']);

                try {
                    if ($category === 'products') {
                        $stmt = $db->prepare("UPDATE products SET price = ? WHERE id = ?");
                    } elseif ($category === 'cosmetics') {
                        $stmt = $db->prepare("UPDATE cosmetics SET price = ? WHERE id = ?");
                    } elseif ($category === 'dental') {
                        $stmt = $db->prepare("UPDATE dental SET price = ? WHERE id = ?");
                    } else {
                        continue;
                    }

                    $stmt->execute([$newPrice, $itemId]);
                    $updatedCount++;
                } catch (Exception $e) {
                    $errors[] = "Error updating price for {$category} item {$itemId}: " . $e->getMessage();
                }
            }
            break;

        case 'update_expiry':
            $newExpiryDate = $values['expiry_date'] ?? '';
            if (empty($newExpiryDate)) {
                echo json_encode(['success' => false, 'message' => 'Expiry date is required']);
                exit;
            }
            
            foreach ($items as $item) {
                $category = $item['category'];
                $itemId = intval($item['id']);
                
                try {
                    if ($category === 'products') {
                        $stmt = $db->prepare("UPDATE products SET expiry_date = ? WHERE id = ?");
                    } elseif ($category === 'cosmetics') {
                        $stmt = $db->prepare("UPDATE cosmetics SET expiry_date = ? WHERE id = ?");
                    } elseif ($category === 'dental') {
                        $stmt = $db->prepare("UPDATE dental SET expiry_date = ? WHERE id = ?");
                    } else {
                        continue;
                    }
                    
                    $stmt->execute([$newExpiryDate, $itemId]);
                    $updatedCount++;
                } catch (Exception $e) {
                    $errors[] = "Error updating expiry for {$category} item {$itemId}: " . $e->getMessage();
                }
            }
            break;
            
        case 'update_threshold':
            $newThreshold = intval($values['threshold'] ?? 10);
            if ($newThreshold < 0) {
                echo json_encode(['success' => false, 'message' => 'Threshold must be non-negative']);
                exit;
            }
            
            foreach ($items as $item) {
                $category = $item['category'];
                $itemId = intval($item['id']);
                
                try {
                    if ($category === 'products') {
                        $stmt = $db->prepare("UPDATE products SET low_stock_threshold = ? WHERE id = ?");
                    } elseif ($category === 'cosmetics') {
                        $stmt = $db->prepare("UPDATE cosmetics SET low_stock_threshold = ? WHERE id = ?");
                    } elseif ($category === 'dental') {
                        $stmt = $db->prepare("UPDATE dental SET low_stock_threshold = ? WHERE id = ?");
                    } else {
                        continue;
                    }
                    
                    $stmt->execute([$newThreshold, $itemId]);
                    $updatedCount++;
                } catch (Exception $e) {
                    $errors[] = "Error updating threshold for {$category} item {$itemId}: " . $e->getMessage();
                }
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
    
    if ($updatedCount > 0) {
        echo json_encode([
            'success' => true,
            'message' => "Successfully updated {$updatedCount} items",
            'updated_count' => $updatedCount,
            'errors' => $errors
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No items were updated',
            'errors' => $errors
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>