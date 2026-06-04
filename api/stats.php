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
    $database = new Database();
    $db = $database->connect();
    
    $type = $_GET['type'] ?? 'overview';
    
    switch ($type) {
        case 'overview':
            // Get basic counts
            $data = [
                'medications' => $db->query("SELECT COUNT(*) FROM medications")->fetchColumn(),
                'products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
                'cosmetics' => $db->query("SELECT COUNT(*) FROM cosmetics")->fetchColumn(),
                'dental' => $db->query("SELECT COUNT(*) FROM dental")->fetchColumn()
            ];
            break;
            
        case 'medications':
            // Medication statistics by class
            $data = $db->query("
                SELECT 
                    class,
                    COUNT(*) as count,
                    SUM(CASE WHEN pregnancy_safe = 1 THEN 1 ELSE 0 END) as pregnancy_safe_count,
                    SUM(CASE WHEN lactation_safe = 1 THEN 1 ELSE 0 END) as lactation_safe_count
                FROM medications 
                GROUP BY class
                ORDER BY count DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'products':
            // Product price distribution
            $data = $db->query("
                SELECT 
                    CASE 
                        WHEN price = 0 THEN 'No Price Set'
                        WHEN price <= 10 THEN '$0-$10'
                        WHEN price <= 25 THEN '$10-$25'
                        WHEN price <= 50 THEN '$25-$50'
                        WHEN price <= 100 THEN '$50-$100'
                        ELSE '$100+'
                    END as price_range,
                    COUNT(*) as count
                FROM products 
                GROUP BY price_range
                ORDER BY 
                    CASE 
                        WHEN price = 0 THEN 0
                        WHEN price <= 10 THEN 1
                        WHEN price <= 25 THEN 2
                        WHEN price <= 50 THEN 3
                        WHEN price <= 100 THEN 4
                        ELSE 5
                    END
            ")->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'cosmetics':
            // Cosmetics by class
            $data = $db->query("
                SELECT 
                    class,
                    COUNT(*) as count
                FROM cosmetics 
                GROUP BY class
                ORDER BY count DESC
                LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'dental':
            // Dental products analysis
            $data = [
                'by_class' => $db->query("
                    SELECT 
                        class,
                        COUNT(*) as count
                    FROM dental 
                    GROUP BY class
                    ORDER BY count DESC
                ")->fetchAll(PDO::FETCH_ASSOC),
                'by_age_group' => $db->query("
                    SELECT 
                        age_group,
                        COUNT(*) as count
                    FROM dental 
                    GROUP BY age_group
                ")->fetchAll(PDO::FETCH_ASSOC),
                'fluoride_stats' => $db->query("
                    SELECT 
                        CASE WHEN contains_fluoride = 1 THEN 'Contains Fluoride' ELSE 'No Fluoride' END as fluoride_status,
                        COUNT(*) as count
                    FROM dental 
                    GROUP BY contains_fluoride
                ")->fetchAll(PDO::FETCH_ASSOC)
            ];
            break;
            
        case 'recent':
            // Recent additions (last 30 days) - fallback if no created_at columns
            $data = [
                'medications' => 0, // Set to 0 since created_at might not exist
                'products' => 0,
                'cosmetics' => 0,
                'dental' => 0
            ];
            break;
            
        default:
            // Return basic stats as default
            $data = [
                'medications' => (int)$db->query("SELECT COUNT(*) FROM medications")->fetchColumn(),
                'products' => (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
                'cosmetics' => (int)$db->query("SELECT COUNT(*) FROM cosmetics")->fetchColumn(),
                'dental' => (int)$db->query("SELECT COUNT(*) FROM dental")->fetchColumn(),
                'total_medications' => (int)$db->query("SELECT COUNT(*) FROM medications")->fetchColumn(),
                'total_products' => (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
                'total_cosmetics' => (int)$db->query("SELECT COUNT(*) FROM cosmetics")->fetchColumn(),
                'total_dental' => (int)$db->query("SELECT COUNT(*) FROM dental")->fetchColumn(),
                'pregnancy_safe' => (int)$db->query("SELECT COUNT(*) FROM medications WHERE pregnancy_safe = 1")->fetchColumn(),
                'lactation_safe' => (int)$db->query("SELECT COUNT(*) FROM medications WHERE lactation_safe = 1")->fetchColumn()
            ];
    }
    
    // Debug: Log the actual values
    error_log('Stats API - Type: ' . $type);
    if (isset($data['pregnancy_safe'])) {
        error_log('Stats API - Pregnancy safe count: ' . $data['pregnancy_safe']);
        error_log('Stats API - Lactation safe count: ' . $data['lactation_safe']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s'),
        'debug_type' => $type
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching statistics: ' . $e->getMessage()
    ]);
}
?>
