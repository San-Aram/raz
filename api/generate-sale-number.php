<?php
require_once '../includes/seller-auth.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();

    // Generate new sale number
    $today = date('Ymd');
    $stmt = $db->prepare("SELECT COUNT(*) + 1 as next_number FROM sales WHERE DATE(sale_date) = CURDATE()");
    $stmt->execute();
    $result = $stmt->fetch();
    $nextNumber = $result['next_number'];
    
    $saleNumber = $today . sprintf('%04d', $nextNumber);

    echo json_encode([
        'success' => true,
        'sale_number' => $saleNumber
    ]);

} catch (Exception $e) {
    error_log("Generate sale number error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate sale number'
    ]);
}