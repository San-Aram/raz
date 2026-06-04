<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/database.php';

// Check if user is logged in and has seller role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'seller') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();
    $saleModel = new Sale($db);
    
    // Get today's sales for this seller (last 10)
    $today = date('Y-m-d');
    $sales = $saleModel->getByDateRange($today, $today, $_SESSION['user_id']);
    
    // Limit to last 10 and reverse order to show most recent first
    $recentSales = array_slice($sales, 0, 10);
    
    echo json_encode($recentSales);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>