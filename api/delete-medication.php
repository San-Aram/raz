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
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        echo json_encode([
            'success' => false,
            'message' => 'Medication ID is required'
        ]);
        exit;
    }
    
    $database = new Database();
    $db = $database->connect();
    $medication = new Medication($db);
    
    // Check if medication exists
    $existingMedication = $medication->getById($id);
    if (!$existingMedication) {
        echo json_encode([
            'success' => false,
            'message' => 'Medication not found'
        ]);
        exit;
    }
    
    if ($medication->delete($id)) {
        echo json_encode([
            'success' => true,
            'message' => 'Medication deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting medication'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
