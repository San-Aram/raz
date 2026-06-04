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
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        echo json_encode([
            'success' => false,
            'message' => 'Product ID is required'
        ]);
        exit;
    }
    
    $database = new Database();
    $db = $database->connect();
    $dental = new Dental($db);
    
    // Get the product first to check if it exists
    $product = $dental->getById($id);
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Dental product not found'
        ]);
        exit;
    }
    
    // Delete the product
    if ($dental->delete($id)) {
        // If there was an image, you might want to delete it from the filesystem too
        if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
            unlink('../' . $product['image_url']);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Dental product deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting dental product'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting dental product: ' . $e->getMessage()
    ]);
}
?>
