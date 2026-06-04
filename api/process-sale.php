<?php
require_once '../includes/seller-auth.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    // Validate required fields
    $requiredFields = ['sale_number', 'payment_method', 'subtotal', 'tax_amount', 'total_amount', 'items'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }

    if (empty($input['items']) || !is_array($input['items'])) {
        echo json_encode(['success' => false, 'message' => 'No items in sale']);
        exit;
    }

    $database = new Database();
    $db = $database->connect();

    // Begin transaction
    $db->beginTransaction();

    try {
        // Create the sale using direct database insert (since Sale model might not match our structure)
        $stmt = $db->prepare("
            INSERT INTO sales (sale_number, seller_id, customer_name, customer_phone, 
                             payment_method, subtotal, discount_amount, tax_amount, total_amount)
            VALUES (:sale_number, :seller_id, :customer_name, :customer_phone, 
                    :payment_method, :subtotal, :discount_amount, :tax_amount, :total_amount)
        ");
        
        $saleData = [
            'sale_number' => $input['sale_number'],
            'seller_id' => $_SESSION['user_id'],
            'customer_name' => $input['customer_name'] ?? null,
            'customer_phone' => $input['customer_phone'] ?? null,
            'payment_method' => $input['payment_method'],
            'subtotal' => floatval($input['subtotal']),
            'discount_amount' => floatval($input['discount_amount'] ?? 0),
            'tax_amount' => floatval($input['tax_amount']),
            'total_amount' => floatval($input['total_amount'])
        ];
        
        if (!$stmt->execute($saleData)) {
            throw new Exception('Failed to create sale record');
        }
        
        $saleId = $db->lastInsertId();

        if (!$saleId) {
            throw new Exception('Failed to create sale record');
        }

        // Add sale items and update inventory
        foreach ($input['items'] as $item) {
            // Validate item data
            if (!isset($item['product_id']) || !isset($item['product_name']) || 
                !isset($item['price']) || !isset($item['quantity'])) {
                throw new Exception('Invalid item data');
            }

            // Create sale item using correct column names
            $productType = $item['product_type'] ?? 'product';
            // Map product types to match the ENUM values
            if ($productType === 'product') {
                $productType = 'products';
            } elseif ($productType === 'cosmetic') {
                $productType = 'cosmetics';
            } elseif ($productType === 'dental') {
                $productType = 'dental';
            }
            
            $saleItemData = [
                'sale_id' => $saleId,
                'product_type' => $productType,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'product_barcode' => $item['barcode'] ?? null,
                'unit_price' => floatval($item['price']),
                'quantity' => intval($item['quantity']),
                'line_total' => floatval($item['total'])
            ];

            $stmt = $db->prepare("
                INSERT INTO sale_items (sale_id, product_type, product_id, product_name, product_barcode, unit_price, quantity, line_total)
                VALUES (:sale_id, :product_type, :product_id, :product_name, :product_barcode, :unit_price, :quantity, :line_total)
            ");
            
            if (!$stmt->execute($saleItemData)) {
                throw new Exception('Failed to create sale item');
            }

            // Update inventory (only for non-manual items)
            if (!str_starts_with($item['product_id'], 'manual_') && $item['product_type'] !== 'manual') {
                updateInventory($db, $item['product_id'], $item['product_type'], $item['quantity']);
            }
        }

        // Commit transaction
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Sale processed successfully',
            'sale_id' => $saleId,
            'sale_number' => $input['sale_number']
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Sale processing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process sale: ' . $e->getMessage()
    ]);
}

function updateInventory($db, $productId, $productType, $quantity) {
    $table = '';
    $quantityColumn = '';
    $updateColumn = '';
    
    switch ($productType) {
        case 'product':
            $table = 'products';
            $quantityColumn = 'quantity';
            $updateColumn = 'updated_at';
            break;
        case 'cosmetic':
            $table = 'cosmetics';
            $quantityColumn = 'quantity';
            $updateColumn = 'updated_at';
            break;
        case 'dental':
            $table = 'dental';
            $quantityColumn = 'quantity';
            $updateColumn = 'updated_at';
            break;
        default:
            return; // Skip inventory update for unknown types
    }

    $stmt = $db->prepare("
        UPDATE {$table} 
        SET {$quantityColumn} = GREATEST(0, {$quantityColumn} - :quantity),
            {$updateColumn} = NOW()
        WHERE id = :product_id
    ");
    
    $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update inventory for product ID: $productId");
    }
}