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
    $saleId = intval($_GET['id'] ?? 0);
    
    if (!$saleId) {
        echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
        exit;
    }

    $database = new Database();
    $db = $database->connect();

    // Get sale details
    $stmt = $db->prepare("
        SELECT s.*, u.username as seller_name
        FROM sales s
        LEFT JOIN users u ON s.seller_id = u.id
        WHERE s.id = :sale_id
    ");
    $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
    $stmt->execute();
    
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
        exit;
    }

    // Get sale items
    $stmt = $db->prepare("
        SELECT *
        FROM sale_items
        WHERE sale_id = :sale_id
        ORDER BY id
    ");
    $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
    $stmt->execute();
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'sale' => $sale,
        'items' => $items
    ]);

} catch (Exception $e) {
    error_log("Sale details error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}