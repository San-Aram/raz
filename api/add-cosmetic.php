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
require_once '../includes/upload.php';

try {
    $data = [
        'barcode' => trim($_POST['barcode'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'company' => trim($_POST['company'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
        'class' => trim($_POST['class'] ?? ''),
        'custom_class' => trim($_POST['customClass'] ?? ''),
        'price' => floatval($_POST['price'] ?? 0),
        'image_url' => '',
        'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
        'quantity' => intval($_POST['quantity'] ?? 0),
        'low_stock_threshold' => intval($_POST['low_stock_threshold'] ?? 10)
    ];
    
    // Validate required fields - only barcode, name, company, and class are required
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
    
    // Handle custom class - if class is "Custom", use custom_class value
    if ($data['class'] === 'Custom') {
        if (empty($data['custom_class'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Custom class is required when "Custom" is selected'
            ]);
            exit;
        }
        $data['class'] = $data['custom_class'];
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
    $cosmetic = new Cosmetic($db);
    
    // Start transaction to prevent race conditions
    $db->beginTransaction();
    
    try {
        // Check for existing barcode within transaction
        $existingProduct = $cosmetic->getByBarcode($data['barcode']);
        if ($existingProduct) {
            $db->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'A cosmetic product with this barcode already exists'
            ]);
            exit;
        }
        
        // Handle image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleImageUpload($_FILES['image']);
            if ($uploadResult['success']) {
                $data['image_url'] = 'uploads/' . $uploadResult['filename'];
            } else {
                $db->rollback();
                echo json_encode([
                    'success' => false,
                    'message' => 'Image upload failed: ' . $uploadResult['message']
                ]);
                exit;
            }
        }
        
        // Create the cosmetic product
        if ($cosmetic->create($data)) {
            // Get the created product ID
            $productId = $db->lastInsertId();
            
            // Commit transaction
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Cosmetic product added successfully',
                'product_id' => $productId
            ]);
        } else {
            $db->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'Error adding cosmetic product to database'
            ]);
        }
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error adding cosmetic product: ' . $e->getMessage()
    ]);
}
?>
