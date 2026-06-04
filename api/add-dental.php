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

try {
    $data = [
        'barcode' => trim($_POST['barcode'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'company' => trim($_POST['company'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
        'class' => trim($_POST['class'] ?? ''),
        'subcategory' => trim($_POST['subcategory'] ?? ''),
        'custom_size' => trim($_POST['customSize'] ?? ''),
        'custom_class' => trim($_POST['customClass'] ?? ''),
        'price' => floatval($_POST['price'] ?? 0),
        'age_group' => $_POST['age_group'] ?? 'both',
        'contains_fluoride' => isset($_POST['contains_fluoride']),
        'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
        'quantity' => intval($_POST['quantity'] ?? 0),
        'low_stock_threshold' => intval($_POST['low_stock_threshold'] ?? 10)
    ];
    
    // Validate required fields
    $requiredFields = ['barcode', 'name', 'company', 'class'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            echo json_encode([
                'success' => false,
                'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
            ]);
            exit;
        }
    }
    
    // Validate inventory fields
    if ($data['quantity'] < 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Quantity cannot be negative'
        ]);
        exit;
    }
    
    if ($data['low_stock_threshold'] < 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Low stock threshold cannot be negative'
        ]);
        exit;
    }
    
    if ($data['price'] < 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Price cannot be negative'
        ]);
        exit;
    }
    
    // Validate expiry date format if provided
    if ($data['expiry_date'] && !DateTime::createFromFormat('Y-m-d', $data['expiry_date'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid expiry date format. Use YYYY-MM-DD'
        ]);
        exit;
    }
    
    $database = new Database();
    $db = $database->connect();
    $dental = new Dental($db);
    
    // Start transaction to prevent race conditions
    $db->beginTransaction();
    
    try {
        // Check for existing barcode within transaction
        $existingProduct = $dental->getByBarcode($data['barcode']);
        if ($existingProduct) {
            $db->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'A dental product with this barcode already exists'
            ]);
            exit;
        }
        
        // Create the dental product
        if ($dental->create($data)) {
            // Get the created product ID
            $productId = $db->lastInsertId();
            
            // Commit transaction
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Dental product added successfully',
                'product_id' => $productId
            ]);
        } else {
            $db->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'Error adding dental product to database'
            ]);
        }
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error adding dental product: ' . $e->getMessage()
    ]);
}
?>
