<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once '../includes/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    // Get month and year from query parameters
    $month = intval($_GET['month'] ?? date('m'));
    $year = intval($_GET['year'] ?? date('Y'));
    
    // Validate month and year
    if ($month < 1 || $month > 12 || $year < 2020 || $year > 2030) {
        echo json_encode(['error' => 'Invalid month or year']);
        exit;
    }
    
    // Calculate date range for the month
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate)); // Last day of the month
    
    $expirationData = [];
    
    // Get products expiring in this month
    $stmt = $db->prepare("
        SELECT id, product_name as name, expiry_date, quantity, 'products' as category
        FROM products 
        WHERE expiry_date BETWEEN ? AND ? 
        AND expiry_date IS NOT NULL
        ORDER BY expiry_date
    ");
    $stmt->execute([$startDate, $endDate]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get cosmetics expiring in this month
    $stmt = $db->prepare("
        SELECT id, name, expiry_date, quantity, 'cosmetics' as category
        FROM cosmetics 
        WHERE expiry_date BETWEEN ? AND ? 
        AND expiry_date IS NOT NULL
        ORDER BY expiry_date
    ");
    $stmt->execute([$startDate, $endDate]);
    $cosmetics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get dental items expiring in this month
    $stmt = $db->prepare("
        SELECT id, name, expiry_date, quantity, 'dental' as category
        FROM dental 
        WHERE expiry_date BETWEEN ? AND ? 
        AND expiry_date IS NOT NULL
        ORDER BY expiry_date
    ");
    $stmt->execute([$startDate, $endDate]);
    $dental = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine all items
    $allItems = array_merge($products, $cosmetics, $dental);
    
    // Group items by expiry date
    foreach ($allItems as $item) {
        $expiryDate = $item['expiry_date'];
        $day = date('j', strtotime($expiryDate)); // Day of month without leading zeros
        
        // Determine severity based on how close to expiry
        $daysUntilExpiry = floor((strtotime($expiryDate) - time()) / (60 * 60 * 24));
        $severity = 'normal';
        $color = '#28a745'; // Green for normal
        
        if ($daysUntilExpiry <= 0) {
            $severity = 'expired';
            $color = '#dc3545'; // Red for expired
        } elseif ($daysUntilExpiry <= 7) {
            $severity = 'critical';
            $color = '#dc3545'; // Red for critical
        } elseif ($daysUntilExpiry <= 30) {
            $severity = 'warning';
            $color = '#ffc107'; // Yellow for warning
        }
        
        if (!isset($expirationData[$day])) {
            $expirationData[$day] = [
                'date' => $expiryDate,
                'items' => [],
                'total_count' => 0,
                'severity_counts' => [
                    'expired' => 0,
                    'critical' => 0,
                    'warning' => 0,
                    'normal' => 0
                ],
                'dominant_color' => $color
            ];
        }
        
        $expirationData[$day]['items'][] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'category' => $item['category'],
            'quantity' => $item['quantity'],
            'severity' => $severity,
            'days_until_expiry' => $daysUntilExpiry
        ];
        
        $expirationData[$day]['total_count']++;
        $expirationData[$day]['severity_counts'][$severity]++;
        
        // Update dominant color based on most severe items
        if ($severity === 'expired' || $severity === 'critical') {
            $expirationData[$day]['dominant_color'] = '#dc3545';
        } elseif ($severity === 'warning' && $expirationData[$day]['dominant_color'] !== '#dc3545') {
            $expirationData[$day]['dominant_color'] = '#ffc107';
        }
    }
    
    // Get summary statistics
    $stats = [
        'month' => $month,
        'year' => $year,
        'total_items' => count($allItems),
        'expired_count' => 0,
        'critical_count' => 0,
        'warning_count' => 0,
        'normal_count' => 0
    ];
    
    foreach ($allItems as $item) {
        $daysUntilExpiry = floor((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
        
        if ($daysUntilExpiry <= 0) {
            $stats['expired_count']++;
        } elseif ($daysUntilExpiry <= 7) {
            $stats['critical_count']++;
        } elseif ($daysUntilExpiry <= 30) {
            $stats['warning_count']++;
        } else {
            $stats['normal_count']++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'calendar_data' => $expirationData,
        'stats' => $stats,
        'month_name' => date('F', strtotime($startDate)),
        'year' => $year
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>