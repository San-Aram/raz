<?php
// Check if this is a seller or manager request
session_start();
$user_role = $_SESSION['user_role'] ?? '';
$is_seller = $user_role === 'seller' && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$is_manager = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['username']) && $user_role !== 'seller';

if ($is_seller) {
    // For seller, check seller authentication (uses user_id as seller identifier)
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Seller authentication required']);
        exit;
    }
} elseif ($is_manager) {
    // Manager authentication is already verified above
} else {
    // No valid authentication found
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['barcode']) || empty(trim($input['barcode']))) {
        echo json_encode(['success' => false, 'message' => 'Barcode is required']);
        exit;
    }

    $barcode = trim($input['barcode']);
    $database = new Database();
    $db = $database->connect();

    // Search for product by barcode in all product tables
    $queries = [
        // Products table (using correct column names)
        "SELECT id, product_name as name, company as brand, dose as dosage, price, quantity as stock_quantity, 'product' as type, barcode 
         FROM products 
         WHERE barcode = :barcode AND barcode IS NOT NULL AND barcode != ''",
        
        // Cosmetics table
        "SELECT id, name, company as brand, NULL as dosage, price, quantity as stock_quantity, 'cosmetic' as type, barcode 
         FROM cosmetics 
         WHERE barcode = :barcode AND barcode IS NOT NULL AND barcode != ''",
        
        // Dental table
        "SELECT id, name, company as brand, custom_size as dosage, price, quantity as stock_quantity, 'dental' as type, barcode 
         FROM dental 
         WHERE barcode = :barcode AND barcode IS NOT NULL AND barcode != ''"
    ];

    $product = null;

    foreach ($queries as $query) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':barcode', $barcode);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $product = $result;
            break;
        }
    }

    if ($product) {
        // Format the product data
        $product['price'] = floatval($product['price']);
        $product['stock_quantity'] = intval($product['stock_quantity']);
        
        // Different response format based on user type
        if ($is_seller) {
            // Seller system response format
            echo json_encode([
                'success' => true,
                'product' => $product
            ]);
        } else {
            // Manager system response format
            $detail_page = '';
            $category = '';
            
            switch ($product['type']) {
                case 'product':
                    $detail_page = 'product-detail.php';
                    $category = 'pharmaceutics';
                    break;
                case 'cosmetic':
                    $detail_page = 'cosmetics-detail.php';
                    $category = 'cosmetics';
                    break;
                case 'dental':
                    $detail_page = 'dental-detail.php';
                    $category = 'dental';
                    break;
            }
            
            echo json_encode([
                'success' => true,
                'exists' => true,
                'product' => $product,
                'detail_page' => $detail_page,
                'category' => $category
            ]);
        }
    } else {
        if ($is_seller) {
            // Seller system response format
            echo json_encode([
                'success' => false,
                'message' => 'Product not found',
                'barcode' => $barcode
            ]);
        } else {
            // Manager system response format - determine category from input or default
            $category = isset($input['category']) ? $input['category'] : 'pharmaceutics';
            
            echo json_encode([
                'success' => true,
                'exists' => false,
                'category' => $category,
                'barcode' => $barcode
            ]);
        }
    }

} catch (Exception $e) {
    error_log("Barcode check error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
?>
