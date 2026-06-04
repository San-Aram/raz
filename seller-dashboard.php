<?php
require_once 'includes/language-functions.php';
initializeLanguage();
require_once 'includes/seller-auth.php';
require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();
$saleModel = new Sale($db);

// Get today's sales statistics
$todaysSales = $saleModel->getTodaysSales($_SESSION['user_id']);
$allTodaysSales = $saleModel->getTodaysSales(); // All sellers
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Razology POS</title>
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
                    <a href="seller-dashboard.php" class="nav-link active">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="checkout.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i> Checkout
                    </a>
                    <a href="sales-history.php" class="nav-link">
                        <i class="fas fa-receipt"></i> Sales
                    </a>
                    <a href="product-lookup.php" class="nav-link">
                        <i class="fas fa-search"></i> Products
                    </a>
                    <div class="user-info">
                        <span class="user-welcome">
                            <i class="fas fa-user-circle"></i>
                            Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </span>
                        <a href="seller-logout.php" class="nav-link logout-link">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <!-- Quick Actions Section -->
            <div class="quick-actions-section">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                <div class="quick-actions-grid">
                    <a href="checkout.php" class="quick-action-card primary">
                        <div class="quick-action-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3>New Sale</h3>
                        <p>Start a new transaction</p>
                        <div class="action-shortcut">F1</div>
                    </a>

                    <a href="checkout.php?scan=1" class="quick-action-card secondary">
                        <div class="quick-action-icon">
                            <i class="fas fa-barcode"></i>
                        </div>
                        <h3>Scan Barcode</h3>
                        <p>Quick barcode scanning</p>
                        <div class="action-shortcut">F2</div>
                    </a>

                    <a href="product-lookup.php" class="quick-action-card info">
                        <div class="quick-action-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Find Product</h3>
                        <p>Search inventory</p>
                        <div class="action-shortcut">F3</div>
                    </a>

                    <a href="sales-history.php" class="quick-action-card success">
                        <div class="quick-action-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <h3>Sales History</h3>
                        <p>View transactions</p>
                        <div class="action-shortcut">F4</div>
                    </a>
                </div>
            </div>

            <!-- Sales Statistics Section -->
            <div class="stats-section">
                <h2><i class="fas fa-chart-line"></i> Today's Performance</h2>
                <div class="stats-grid">
                    <div class="stat-card my-sales">
                        <div class="stat-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number">₱<?php echo number_format($todaysSales['total'], 2); ?></div>
                            <div class="stat-label">My Sales Today</div>
                            <div class="stat-detail"><?php echo $todaysSales['count']; ?> transactions</div>
                        </div>
                    </div>

                    <div class="stat-card total-sales">
                        <div class="stat-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number">₱<?php echo number_format($allTodaysSales['total'], 2); ?></div>
                            <div class="stat-label">Store Total Today</div>
                            <div class="stat-detail"><?php echo $allTodaysSales['count']; ?> transactions</div>
                        </div>
                    </div>

                    <div class="stat-card average-sale">
                        <div class="stat-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number">
                                ₱<?php echo $todaysSales['count'] > 0 ? number_format($todaysSales['total'] / $todaysSales['count'], 2) : '0.00'; ?>
                            </div>
                            <div class="stat-label">Average Sale</div>
                            <div class="stat-detail">Per transaction</div>
                        </div>
                    </div>

                    <div class="stat-card time-info">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number" id="currentTime"><?php echo date('H:i'); ?></div>
                            <div class="stat-label">Current Time</div>
                            <div class="stat-detail"><?php echo date('M j, Y'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sales Section -->
            <div class="recent-sales-section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Recent Sales</h2>
                    <a href="sales-history.php" class="btn btn-outline">View All</a>
                </div>
                
                <div class="recent-sales-list" id="recentSalesList">
                    <div class="loading-indicator">
                        <i class="fas fa-spinner fa-spin"></i> Loading recent sales...
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="tips-section">
                <h2><i class="fas fa-lightbulb"></i> Tips & Shortcuts</h2>
                <div class="tips-grid">
                    <div class="tip-card">
                        <i class="fas fa-keyboard"></i>
                        <h4>Keyboard Shortcuts</h4>
                        <ul>
                            <li><kbd>F1</kbd> - New Sale</li>
                            <li><kbd>F2</kbd> - Scan Barcode</li>
                            <li><kbd>F3</kbd> - Find Product</li>
                            <li><kbd>F4</kbd> - Sales History</li>
                            <li><kbd>Esc</kbd> - Cancel/Go Back</li>
                        </ul>
                    </div>

                    <div class="tip-card">
                        <i class="fas fa-barcode"></i>
                        <h4>Barcode Scanning</h4>
                        <ul>
                            <li>Use handheld scanner or camera</li>
                            <li>Ensure barcode is clean and visible</li>
                            <li>Manual entry available if needed</li>
                            <li>Auto-adds to current transaction</li>
                        </ul>
                    </div>

                    <div class="tip-card">
                        <i class="fas fa-coins"></i>
                        <h4>Payment Tips</h4>
                        <ul>
                            <li>Accept cash, card, and mobile payments</li>
                            <li>Calculate change automatically</li>
                            <li>Print receipts for all transactions</li>
                            <li>Keep cash drawer organized</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer seller-footer">
        <div class="container">
            <p>&copy; 2025 Razology POS - Seller Terminal | Session: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
    </footer>

    <!-- Keyboard Shortcuts -->
    <script>
        document.addEventListener('keydown', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            switch(e.key) {
                case 'F1':
                    e.preventDefault();
                    window.location.href = 'checkout.php';
                    break;
                case 'F2':
                    e.preventDefault();
                    window.location.href = 'checkout.php?scan=1';
                    break;
                case 'F3':
                    e.preventDefault();
                    window.location.href = 'product-lookup.php';
                    break;
                case 'F4':
                    e.preventDefault();
                    window.location.href = 'sales-history.php';
                    break;
            }
        });

        // Update current time every minute
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: false 
            });
            document.getElementById('currentTime').textContent = timeString;
        }
        
        setInterval(updateTime, 60000);

        // Load recent sales
        async function loadRecentSales() {
            try {
                const response = await fetch('api/recent-sales.php');
                const sales = await response.json();
                
                const container = document.getElementById('recentSalesList');
                
                if (sales.length === 0) {
                    container.innerHTML = `
                        <div class="no-sales">
                            <i class="fas fa-receipt"></i>
                            <p>No sales recorded today</p>
                            <a href="checkout.php" class="btn btn-primary">Make First Sale</a>
                        </div>
                    `;
                    return;
                }
                
                let html = '';
                sales.forEach(sale => {
                    html += `
                        <div class="recent-sale-item">
                            <div class="sale-info">
                                <div class="sale-number">#${sale.sale_number}</div>
                                <div class="sale-time">${formatTime(sale.sale_date)}</div>
                            </div>
                            <div class="sale-details">
                                <div class="sale-amount">₱${parseFloat(sale.total_amount).toFixed(2)}</div>
                                <div class="sale-method">${sale.payment_method.toUpperCase()}</div>
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = html;
                
            } catch (error) {
                document.getElementById('recentSalesList').innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Unable to load recent sales</p>
                    </div>
                `;
            }
        }

        function formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
        }

        // Load recent sales on page load
        loadRecentSales();
        
        // Refresh recent sales every 5 minutes
        setInterval(loadRecentSales, 300000);
    </script>

    <style>
        .seller-header {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }

        .quick-actions-section {
            margin-bottom: 2rem;
        }

        .quick-actions-section h2 {
            color: var(--dark-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .quick-action-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-decoration: none;
            color: inherit;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            position: relative;
            border: 2px solid transparent;
        }

        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .quick-action-card.primary {
            border-color: var(--primary-color);
        }

        .quick-action-card.primary .quick-action-icon {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
        }

        .quick-action-card.secondary {
            border-color: var(--secondary-color);
        }

        .quick-action-card.secondary .quick-action-icon {
            background: linear-gradient(135deg, var(--secondary-color), #5a6268);
        }

        .quick-action-card.info {
            border-color: var(--info-color);
        }

        .quick-action-card.info .quick-action-icon {
            background: linear-gradient(135deg, var(--info-color), #117a8b);
        }

        .quick-action-card.success {
            border-color: var(--success-color);
        }

        .quick-action-card.success .quick-action-icon {
            background: linear-gradient(135deg, var(--success-color), #1e7e34);
        }

        .quick-action-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: var(--white);
            font-size: 1.5rem;
        }

        .quick-action-card h3 {
            margin: 0 0 0.5rem 0;
            text-align: center;
            font-size: 1.1rem;
        }

        .quick-action-card p {
            margin: 0;
            text-align: center;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .action-shortcut {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: var(--gray-100);
            color: var(--gray-600);
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
        }

        .stats-section {
            margin-bottom: 2rem;
        }

        .stats-section h2 {
            color: var(--dark-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-card.my-sales {
            border-left: 4px solid var(--primary-color);
        }

        .stat-card.total-sales {
            border-left: 4px solid var(--success-color);
        }

        .stat-card.average-sale {
            border-left: 4px solid var(--info-color);
        }

        .stat-card.time-info {
            border-left: 4px solid var(--warning-color);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--gray-600);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        .stat-detail {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .recent-sales-section, .tips-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .section-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .recent-sale-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .recent-sale-item:last-child {
            border-bottom: none;
        }

        .sale-number {
            font-weight: 600;
            color: var(--dark-color);
        }

        .sale-time {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .sale-amount {
            font-weight: 600;
            color: var(--success-color);
            font-size: 1.1rem;
        }

        .sale-method {
            font-size: 0.8rem;
            color: var(--gray-500);
            text-align: right;
        }

        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .tip-card {
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 1rem;
        }

        .tip-card i {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .tip-card h4 {
            margin: 0 0 0.75rem 0;
            color: var(--dark-color);
        }

        .tip-card ul {
            margin: 0;
            padding-left: 1rem;
        }

        .tip-card li {
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        kbd {
            background: var(--gray-100);
            border: 1px solid var(--gray-300);
            border-radius: 3px;
            padding: 0.1rem 0.3rem;
            font-family: monospace;
            font-size: 0.8rem;
        }

        .no-sales, .error-message {
            text-align: center;
            padding: 2rem;
            color: var(--gray-500);
        }

        .no-sales i, .error-message i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-400);
        }

        .loading-indicator {
            text-align: center;
            padding: 2rem;
            color: var(--gray-500);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-welcome {
            color: var(--white);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .seller-footer {
            background: var(--gray-800);
            color: var(--gray-300);
            text-align: center;
            padding: 1rem 0;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .quick-actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .tips-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .user-info {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</body>
</html>