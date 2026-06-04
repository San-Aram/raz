<?php
require_once 'includes/language-functions.php';
initializeLanguage();
require_once 'includes/seller-auth.php';
require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();

// Get filter parameters
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$paymentMethod = $_GET['payment_method'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$whereConditions = ["DATE(s.sale_date) BETWEEN :date_from AND :date_to"];
$params = [
    'date_from' => $dateFrom,
    'date_to' => $dateTo
];

if ($paymentMethod) {
    $whereConditions[] = "s.payment_method = :payment_method";
    $params['payment_method'] = $paymentMethod;
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM sales s WHERE $whereClause";
$stmt = $db->prepare($countQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
$stmt->execute();
$totalSales = $stmt->fetch()['total'];
$totalPages = ceil($totalSales / $limit);

// Get sales data
$salesQuery = "
    SELECT s.*, u.username as seller_name,
           COUNT(si.id) as item_count,
           GROUP_CONCAT(si.product_name SEPARATOR ', ') as items_preview
    FROM sales s
    LEFT JOIN users u ON s.seller_id = u.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    WHERE $whereClause
    GROUP BY s.id
    ORDER BY s.sale_date DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $db->prepare($salesQuery);
foreach ($params as $key => $value) {
    if ($key !== 'limit' && $key !== 'offset') {
        $stmt->bindValue(":$key", $value);
    }
}
$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$summaryQuery = "
    SELECT 
        COUNT(*) as total_transactions,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_transaction,
        SUM(CASE WHEN payment_method = 'cash' THEN 1 ELSE 0 END) as cash_transactions,
        SUM(CASE WHEN payment_method = 'card' THEN 1 ELSE 0 END) as card_transactions
    FROM sales s 
    WHERE $whereClause
";

$stmt = $db->prepare($summaryQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
$stmt->execute();
$summary = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History - Razology POS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
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
                    <a href="sales-history.php" class="nav-link active">
                        <i class="fas fa-receipt"></i> Sales
                    </a>
                    <a href="sales-stats.php" class="nav-link">
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

    <main class="main sales-history-main">
        <div class="container">
            <div class="page-header">
                <h2><i class="fas fa-receipt"></i> Sales History</h2>
                <div class="header-actions">
                    <a href="sales-stats.php" class="btn btn-outline">
                        <i class="fas fa-chart-line"></i> View Statistics
                    </a>
                    <a href="checkout.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Sale
                    </a>
                </div>
            </div>

            <!-- Filters -->
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
                            <label for="payment_method">Payment Method</label>
                            <select id="payment_method" name="payment_method" class="form-control">
                                <option value="">All Methods</option>
                                <option value="cash" <?php echo $paymentMethod === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                <option value="card" <?php echo $paymentMethod === 'card' ? 'selected' : ''; ?>>Card</option>
                                <option value="mobile" <?php echo $paymentMethod === 'mobile' ? 'selected' : ''; ?>>Mobile</option>
                                <option value="insurance" <?php echo $paymentMethod === 'insurance' ? 'selected' : ''; ?>>Insurance</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="sales-history.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="card-content">
                        <div class="card-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="card-info">
                            <h3><?php echo number_format($summary['total_transactions']); ?></h3>
                            <p>Total Transactions</p>
                        </div>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-content">
                        <div class="card-icon">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                        <div class="card-info">
                            <h3>₱<?php echo number_format($summary['total_revenue'], 2); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-content">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="card-info">
                            <h3>₱<?php echo number_format($summary['avg_transaction'], 2); ?></h3>
                            <p>Average Transaction</p>
                        </div>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-content">
                        <div class="card-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="card-info">
                            <h3><?php echo $summary['cash_transactions']; ?> / <?php echo $summary['card_transactions']; ?></h3>
                            <p>Cash / Card</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Table -->
            <div class="sales-table-section">
                <div class="table-header">
                    <h3>Sales Transactions</h3>
                    <div class="table-actions">
                        <span class="results-info">
                            Showing <?php echo min($offset + 1, $totalSales); ?>-<?php echo min($offset + $limit, $totalSales); ?> 
                            of <?php echo number_format($totalSales); ?> transactions
                        </span>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>Sale #</th>
                                <th>Date & Time</th>
                                <th>Items</th>
                                <th>Customer</th>
                                <th>Payment</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sales)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-inbox"></i>
                                        <p>No sales found for the selected period</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($sale['sale_number']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="date-time">
                                                <div class="date"><?php echo date('M j, Y', strtotime($sale['sale_date'])); ?></div>
                                                <div class="time"><?php echo date('g:i A', strtotime($sale['sale_date'])); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="items-info">
                                                <strong><?php echo $sale['item_count']; ?> item<?php echo $sale['item_count'] != 1 ? 's' : ''; ?></strong>
                                                <div class="items-preview" title="<?php echo htmlspecialchars($sale['items_preview']); ?>">
                                                    <?php echo strlen($sale['items_preview']) > 30 ? 
                                                        htmlspecialchars(substr($sale['items_preview'], 0, 30)) . '...' : 
                                                        htmlspecialchars($sale['items_preview']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($sale['customer_name']): ?>
                                                <div class="customer-info">
                                                    <strong><?php echo htmlspecialchars($sale['customer_name']); ?></strong>
                                                    <?php if ($sale['customer_phone']): ?>
                                                        <div class="phone"><?php echo htmlspecialchars($sale['customer_phone']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Walk-in</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="payment-method payment-<?php echo $sale['payment_method']; ?>">
                                                <i class="fas fa-<?php echo $sale['payment_method'] === 'cash' ? 'money-bill-wave' : 
                                                    ($sale['payment_method'] === 'card' ? 'credit-card' : 
                                                    ($sale['payment_method'] === 'mobile' ? 'mobile-alt' : 'shield-alt')); ?>"></i>
                                                <?php echo ucfirst($sale['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="total-amount">₱<?php echo number_format($sale['total_amount'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon" onclick="viewSaleDetails(<?php echo $sale['id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-icon" onclick="printReceipt(<?php echo $sale['id']; ?>)" title="Print Receipt">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-btn">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Sale Details Modal -->
    <div id="saleDetailsModal" class="modal">
        <div class="modal-content sale-details-modal">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Sale Details</h3>
                <span class="close" onclick="closeSaleDetails()">&times;</span>
            </div>
            <div class="modal-body" id="saleDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <footer class="footer seller-footer">
        <div class="container">
            <p>&copy; 2025 Razology POS - Sales Management</p>
        </div>
    </footer>

    <script src="js/sales-history.js"></script>
    
    <style>
        .sales-history-main {
            padding: 1rem 0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .filters-section {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
        }

        .card-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .card-icon {
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

        .card-info h3 {
            margin: 0 0 0.25rem 0;
            font-size: 1.8rem;
            color: var(--dark-color);
        }

        .card-info p {
            margin: 0;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .sales-table-section {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .table-header h3 {
            margin: 0;
            color: var(--dark-color);
        }

        .results-info {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .table-container {
            overflow-x: auto;
        }

        .sales-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sales-table th {
            background: var(--gray-50);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark-color);
            border-bottom: 2px solid var(--gray-200);
        }

        .sales-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: top;
        }

        .sales-table tr:hover {
            background: var(--gray-50);
        }

        .date-time .date {
            font-weight: 600;
            color: var(--dark-color);
        }

        .date-time .time {
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .items-info strong {
            color: var(--dark-color);
        }

        .items-preview {
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-top: 0.25rem;
        }

        .customer-info strong {
            color: var(--dark-color);
        }

        .customer-info .phone {
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .payment-method {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .payment-cash {
            background: #d4edda;
            color: #155724;
        }

        .payment-card {
            background: #cce5f5;
            color: #0c5460;
        }

        .payment-mobile {
            background: #e2e3ff;
            color: #383d41;
        }

        .payment-insurance {
            background: #fff3cd;
            color: #856404;
        }

        .total-amount {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            background: none;
            border: 1px solid var(--gray-300);
            padding: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            color: var(--gray-600);
            transition: all 0.3s ease;
        }

        .btn-icon:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--white);
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: var(--gray-500);
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-400);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }

        .page-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 4px;
            text-decoration: none;
            color: var(--gray-700);
            transition: all 0.3s ease;
        }

        .page-btn:hover, .page-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--white);
        }

        .sale-details-modal {
            max-width: 800px;
        }

        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .header-actions {
                justify-content: center;
            }

            .summary-cards {
                grid-template-columns: 1fr;
            }

            .sales-table {
                font-size: 0.9rem;
            }

            .sales-table th,
            .sales-table td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</body>
</html>