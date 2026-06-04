<?php
require_once 'includes/seller-auth.php';
require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();

// Get date range (default last 30 days)
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Get overall statistics
$statsQuery = "
    SELECT 
        COUNT(DISTINCT s.id) as total_sales,
        SUM(s.total_amount) as total_revenue,
        AVG(s.total_amount) as avg_transaction,
        COUNT(DISTINCT s.customer_name) as unique_customers,
        SUM(si.quantity) as total_items_sold
    FROM sales s
    LEFT JOIN sale_items si ON s.id = si.sale_id
    WHERE DATE(s.sale_date) BETWEEN :date_from AND :date_to
";

$stmt = $db->prepare($statsQuery);
$stmt->bindParam(':date_from', $dateFrom);
$stmt->bindParam(':date_to', $dateTo);
$stmt->execute();
$overallStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get top-selling products
$topProductsQuery = "
    SELECT 
        si.product_name,
        si.product_type,
        SUM(si.quantity) as total_quantity,
        SUM(si.line_total) as total_revenue,
        COUNT(DISTINCT s.id) as transaction_count,
        AVG(si.unit_price) as avg_price
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.sale_date) BETWEEN :date_from AND :date_to
    GROUP BY si.product_name, si.product_type
    ORDER BY total_quantity DESC
    LIMIT 20
";

$stmt = $db->prepare($topProductsQuery);
$stmt->bindParam(':date_from', $dateFrom);
$stmt->bindParam(':date_to', $dateTo);
$stmt->execute();
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get sales by product type
$categoryQuery = "
    SELECT 
        si.product_type,
        COUNT(DISTINCT si.id) as item_count,
        SUM(si.quantity) as total_quantity,
        SUM(si.line_total) as total_revenue,
        AVG(si.unit_price) as avg_price
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.sale_date) BETWEEN :date_from AND :date_to
    GROUP BY si.product_type
    ORDER BY total_revenue DESC
";

$stmt = $db->prepare($categoryQuery);
$stmt->bindParam(':date_from', $dateFrom);
$stmt->bindParam(':date_to', $dateTo);
$stmt->execute();
$categoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily sales trend
$dailyTrendQuery = "
    SELECT 
        DATE(s.sale_date) as sale_date,
        COUNT(s.id) as transaction_count,
        SUM(s.total_amount) as daily_revenue,
        SUM(si.quantity) as items_sold
    FROM sales s
    LEFT JOIN sale_items si ON s.id = si.sale_id
    WHERE DATE(s.sale_date) BETWEEN :date_from AND :date_to
    GROUP BY DATE(s.sale_date)
    ORDER BY sale_date DESC
    LIMIT 30
";

$stmt = $db->prepare($dailyTrendQuery);
$stmt->bindParam(':date_from', $dateFrom);
$stmt->bindParam(':date_to', $dateTo);
$stmt->execute();
$dailyTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment method statistics
$paymentQuery = "
    SELECT 
        payment_method,
        COUNT(*) as transaction_count,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_transaction
    FROM sales
    WHERE DATE(sale_date) BETWEEN :date_from AND :date_to
    GROUP BY payment_method
    ORDER BY total_revenue DESC
";

$stmt = $db->prepare($paymentQuery);
$stmt->bindParam(':date_from', $dateFrom);
$stmt->bindParam(':date_to', $dateTo);
$stmt->execute();
$paymentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate AI recommendations
function generateAIRecommendations($topProducts, $categoryStats, $dailyTrend, $overallStats) {
    $recommendations = [];
    
    // Analyze product performance
    if (!empty($topProducts)) {
        $topProduct = $topProducts[0];
        $recommendations[] = [
            'title' => 'Top Performer',
            'type' => 'success',
            'icon' => 'trophy',
            'message' => "'{$topProduct['product_name']}' is your best seller with {$topProduct['total_quantity']} units sold. Consider increasing stock levels and promoting similar products."
        ];
        
        // Check for declining products
        $lowPerformers = array_slice($topProducts, -5);
        if (count($topProducts) > 10) {
            $recommendations[] = [
                'title' => 'Inventory Optimization',
                'type' => 'warning',
                'icon' => 'chart-line',
                'message' => "Consider reviewing slow-moving items. Focus marketing efforts on underperforming products or consider discontinuing them."
            ];
        }
    }
    
    // Category analysis
    if (!empty($categoryStats)) {
        $topCategory = $categoryStats[0];
        $categoryName = ucfirst($topCategory['product_type']);
        $recommendations[] = [
            'title' => 'Category Focus',
            'type' => 'info',
            'icon' => 'tags',
            'message' => "{$categoryName} products generate the highest revenue (₱" . number_format($topCategory['total_revenue'], 2) . "). Consider expanding this category."
        ];
        
        // Check category diversity
        $categoryCount = count($categoryStats);
        if ($categoryCount < 3) {
            $recommendations[] = [
                'title' => 'Diversification Opportunity',
                'type' => 'info',
                'icon' => 'plus-circle',
                'message' => "Your sales are concentrated in {$categoryCount} categories. Consider diversifying your product range to attract more customers."
            ];
        }
    }
    
    // Sales trend analysis
    if (count($dailyTrend) >= 7) {
        $recentWeek = array_slice($dailyTrend, 0, 7);
        $previousWeek = array_slice($dailyTrend, 7, 7);
        
        if (!empty($previousWeek)) {
            $recentAvg = array_sum(array_column($recentWeek, 'daily_revenue')) / count($recentWeek);
            $previousAvg = array_sum(array_column($previousWeek, 'daily_revenue')) / count($previousWeek);
            
            $change = (($recentAvg - $previousAvg) / $previousAvg) * 100;
            
            if ($change > 10) {
                $recommendations[] = [
                    'title' => 'Growing Sales',
                    'type' => 'success',
                    'icon' => 'arrow-up',
                    'message' => "Sales are up " . number_format($change, 1) . "% compared to last week. Great job! Maintain current strategies."
                ];
            } elseif ($change < -10) {
                $recommendations[] = [
                    'title' => 'Sales Decline',
                    'type' => 'danger',
                    'icon' => 'arrow-down',
                    'message' => "Sales decreased " . number_format(abs($change), 1) . "% compared to last week. Consider promotional activities or review pricing strategy."
                ];
            }
        }
    }
    
    // Customer analysis
    if ($overallStats['unique_customers'] > 0) {
        $avgItemsPerCustomer = $overallStats['total_items_sold'] / $overallStats['unique_customers'];
        if ($avgItemsPerCustomer < 2) {
            $recommendations[] = [
                'title' => 'Cross-Selling Opportunity',
                'type' => 'info',
                'icon' => 'shopping-basket',
                'message' => "Customers buy an average of " . number_format($avgItemsPerCustomer, 1) . " items per visit. Try bundle offers or product recommendations to increase basket size."
            ];
        }
    }
    
    // AI-powered seasonal recommendations
    $currentMonth = date('n');
    $seasonalTips = [
        'winter' => [
            'months' => [1,2,3],
            'tip' => "Winter season: Promote cold remedies, cough syrups, and vitamin supplements."
        ],
        'spring' => [
            'months' => [4,5,6],
            'tip' => "Spring season: Focus on allergy medications, skin care products, and health supplements."
        ],
        'summer' => [
            'months' => [7,8,9],
            'tip' => "Summer season: Highlight sunscreen, hydration products, and travel health items."
        ],
        'autumn' => [
            'months' => [10,11,12],
            'tip' => "Autumn/Holiday season: Promote flu vaccines, immune boosters, and gift sets."
        ]
    ];
    
    foreach ($seasonalTips as $season => $data) {
        if (in_array($currentMonth, $data['months'])) {
            $recommendations[] = [
                'title' => 'Seasonal Strategy',
                'type' => 'info',
                'icon' => 'calendar-alt',
                'message' => $data['tip']
            ];
            break;
        }
    }
    
    return $recommendations;
}

$aiRecommendations = generateAIRecommendations($topProducts, $categoryStats, $dailyTrend, $overallStats);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Statistics - Razology POS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="header seller-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-cash-register"></i>
                    <h1>Razology POS</h1>
                </div>
                <nav class="nav">
                    <a href="seller-dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="checkout.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i> Checkout
                    </a>
                    <a href="sales-history.php" class="nav-link">
                        <i class="fas fa-receipt"></i> Sales
                    </a>
                    <a href="sales-stats.php" class="nav-link active">
                        <i class="fas fa-chart-bar"></i> Stats
                    </a>
                    <a href="product-lookup.php" class="nav-link">
                        <i class="fas fa-search"></i> Products
                    </a>
                    <div class="user-info">
                        <span class="user-welcome">
                            <i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </span>
                        <a href="seller-logout.php" class="nav-link logout-link">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <main class="main stats-main">
        <div class="container">
            <div class="page-header">
                <h2><i class="fas fa-chart-bar"></i> Sales Statistics & Analytics</h2>
                <div class="header-actions">
                    <a href="sales-history.php" class="btn btn-outline">
                        <i class="fas fa-history"></i> Sales History
                    </a>
                    <a href="checkout.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Sale
                    </a>
                </div>
            </div>

            <!-- Date Filter -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="date_from">From Date</label>
                            <input type="date" id="date_from" name="date_from" class="form-control" 
                                   value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_to">To Date</label>
                            <input type="date" id="date_to" name="date_to" class="form-control" 
                                   value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-chart-line"></i> Update Stats
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Overview Statistics -->
            <div class="overview-stats">
                <h3><i class="fas fa-chart-pie"></i> Overview Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($overallStats['total_sales']); ?></h3>
                            <p>Total Sales</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                        <div class="stat-content">
                            <h3>₱<?php echo number_format($overallStats['total_revenue'] ?? 0, 2); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($overallStats['total_items_sold'] ?? 0); ?></h3>
                            <p>Items Sold</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($overallStats['unique_customers'] ?? 0); ?></h3>
                            <p>Unique Customers</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <h3>₱<?php echo number_format($overallStats['avg_transaction'] ?? 0, 2); ?></h3>
                            <p>Avg Transaction</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="charts-section">
                <!-- Sales Trend Chart -->
                <div class="chart-card">
                    <h3><i class="fas fa-chart-line"></i> Daily Sales Trend</h3>
                    <canvas id="salesTrendChart"></canvas>
                </div>

                <!-- Category Performance Chart -->
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie"></i> Category Performance</h3>
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- AI Recommendations -->
            <div class="ai-recommendations">
                <h3><i class="fas fa-robot"></i> AI-Powered Recommendations</h3>
                <div class="recommendations-grid">
                    <?php foreach ($aiRecommendations as $rec): ?>
                        <div class="recommendation-card recommendation-<?php echo $rec['type']; ?>">
                            <div class="rec-icon">
                                <i class="fas fa-<?php echo $rec['icon']; ?>"></i>
                            </div>
                            <div class="rec-content">
                                <h4><?php echo htmlspecialchars($rec['title']); ?></h4>
                                <p><?php echo htmlspecialchars($rec['message']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="data-tables">
                <!-- Top Products -->
                <div class="table-section">
                    <h3><i class="fas fa-star"></i> Top Selling Products</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th>Avg Price</th>
                                    <th>Transactions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $index => $product): ?>
                                    <tr>
                                        <td>
                                            <span class="rank-badge rank-<?php echo $index + 1; ?>">
                                                #<?php echo $index + 1; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="category-badge category-<?php echo $product['product_type']; ?>">
                                                <?php echo ucfirst($product['product_type']); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo number_format($product['total_quantity']); ?></strong></td>
                                        <td><strong>₱<?php echo number_format($product['total_revenue'], 2); ?></strong></td>
                                        <td>₱<?php echo number_format($product['avg_price'], 2); ?></td>
                                        <td><?php echo number_format($product['transaction_count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="table-section">
                    <h3><i class="fas fa-credit-card"></i> Payment Method Analysis</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th>Transactions</th>
                                    <th>Revenue</th>
                                    <th>Avg Transaction</th>
                                    <th>Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalTransactions = array_sum(array_column($paymentStats, 'transaction_count'));
                                foreach ($paymentStats as $payment): 
                                    $share = $totalTransactions > 0 ? ($payment['transaction_count'] / $totalTransactions) * 100 : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <span class="payment-method payment-<?php echo $payment['payment_method']; ?>">
                                                <i class="fas fa-<?php echo $payment['payment_method'] === 'cash' ? 'money-bill-wave' : 
                                                    ($payment['payment_method'] === 'card' ? 'credit-card' : 
                                                    ($payment['payment_method'] === 'mobile' ? 'mobile-alt' : 'shield-alt')); ?>"></i>
                                                <?php echo ucfirst($payment['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($payment['transaction_count']); ?></td>
                                        <td><strong>₱<?php echo number_format($payment['total_revenue'], 2); ?></strong></td>
                                        <td>₱<?php echo number_format($payment['avg_transaction'], 2); ?></td>
                                        <td>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $share; ?>%"></div>
                                                <span class="progress-text"><?php echo number_format($share, 1); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer seller-footer">
        <div class="container">
            <p>&copy; 2025 Razology POS - Sales Analytics</p>
        </div>
    </footer>

    <script>
        // Prepare data for charts
        const dailyTrendData = <?php echo json_encode(array_reverse($dailyTrend)); ?>;
        const categoryData = <?php echo json_encode($categoryStats); ?>;

        // Sales Trend Chart
        const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
        new Chart(salesTrendCtx, {
            type: 'line',
            data: {
                labels: dailyTrendData.map(d => new Date(d.sale_date).toLocaleDateString()),
                datasets: [{
                    label: 'Daily Revenue',
                    data: dailyTrendData.map(d => parseFloat(d.daily_revenue)),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Transactions',
                    data: dailyTrendData.map(d => parseInt(d.transaction_count)),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (₱)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Transactions'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Sales Performance Over Time'
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(c => c.product_type.charAt(0).toUpperCase() + c.product_type.slice(1)),
                datasets: [{
                    data: categoryData.map(c => parseFloat(c.total_revenue)),
                    backgroundColor: [
                        '#28a745',
                        '#007bff',
                        '#ffc107',
                        '#dc3545',
                        '#6f42c1',
                        '#fd7e14'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Revenue by Product Category'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>

    <style>
        /* Base styles - ensure CSS variables are available */
        :root {
            --primary-color: #28a745;
            --secondary-color: #20c997;
            --white: #ffffff;
            --dark-color: #212529;
            --gray-50: #f8f9fa;
            --gray-100: #e9ecef;
            --gray-200: #dee2e6;
            --gray-300: #ced4da;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --border-radius: 8px;
            --box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .stats-main {
            padding: 2rem 0;
            background: #f8f9fa;
            min-height: calc(100vh - 140px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .page-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--dark-color);
            font-size: 1.75rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .filters-section {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1.5rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        .form-control {
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #218838;
            border-color: #1e7e34;
            color: var(--white);
        }

        .btn-outline {
            background: transparent;
            border-color: var(--gray-300);
            color: var(--gray-700);
        }

        .btn-outline:hover {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .ai-recommendations {
            margin-bottom: 2rem;
        }

        .ai-recommendations h3 {
            margin: 0 0 1rem 0;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .recommendation-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            display: flex;
            gap: 1rem;
            border-left: 4px solid;
        }

        .recommendation-success {
            border-left-color: #28a745;
        }

        .recommendation-info {
            border-left-color: #17a2b8;
        }

        .recommendation-warning {
            border-left-color: #ffc107;
        }

        .recommendation-danger {
            border-left-color: #dc3545;
        }

        .rec-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
            flex-shrink: 0;
        }

        .recommendation-success .rec-icon {
            background: #28a745;
        }

        .recommendation-info .rec-icon {
            background: #17a2b8;
        }

        .recommendation-warning .rec-icon {
            background: #ffc107;
        }

        .recommendation-danger .rec-icon {
            background: #dc3545;
        }

        .rec-content h4 {
            margin: 0 0 0.5rem 0;
            color: var(--dark-color);
        }

        .rec-content p {
            margin: 0;
            color: var(--gray-600);
            line-height: 1.5;
        }

        .overview-stats {
            margin-bottom: 2rem;
        }

        .overview-stats h3 {
            margin: 0 0 1rem 0;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.5rem;
        }

        .stat-content h3 {
            margin: 0 0 0.25rem 0;
            font-size: 1.8rem;
            color: var(--dark-color);
        }

        .stat-content p {
            margin: 0;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .charts-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            height: 400px;
            display: flex;
            flex-direction: column;
        }

        .chart-card h3 {
            margin: 0 0 1rem 0;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
        }

        .chart-card canvas {
            flex: 1;
            max-height: 320px !important;
            max-width: 100% !important;
            width: auto !important;
            height: auto !important;
        }

        /* Specific sizing for pie/doughnut charts */
        #categoryChart {
            max-height: 300px !important;
            max-width: 300px !important;
            margin: 0 auto;
        }

        .data-tables {
            display: grid;
            gap: 2rem;
        }

        .table-section {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .table-section h3 {
            margin: 0;
            padding: 1.5rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .table-container {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .data-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--dark-color);
        }

        .data-table tr:hover {
            background: var(--gray-50);
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .rank-1 {
            background: #ffd700;
            color: #b8860b;
        }

        .rank-2 {
            background: #c0c0c0;
            color: #696969;
        }

        .rank-3 {
            background: #cd7f32;
            color: #8b4513;
        }

        .rank-badge:not(.rank-1):not(.rank-2):not(.rank-3) {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .category-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .category-products {
            background: #e3f2fd;
            color: #1565c0;
        }

        .category-cosmetics {
            background: #fce4ec;
            color: #c2185b;
        }

        .category-dental {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .progress-bar {
            position: relative;
            background: var(--gray-200);
            border-radius: 10px;
            height: 20px;
            min-width: 100px;
        }

        .progress-fill {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        @media (max-width: 1200px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .recommendations-grid {
                grid-template-columns: 1fr;
            }

            .recommendation-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</body>
</html>