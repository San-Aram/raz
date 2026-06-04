<?php
require_once 'includes/language-functions.php';
initializeLanguage();
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/admin-settings-helper.php';

// Check maintenance mode (non-admins will be redirected to maintenance page)
if (isMaintenanceMode() && !isAdmin()) {
    header('Location: maintenance.php');
    exit;
}

$database = new Database();
$db = $database->connect();

// Get statistics for all categories
try {
    // Medications statistics
    $medicationStats = $db->query("
        SELECT 
            class,
            COUNT(*) as count,
            SUM(CASE WHEN pregnancy_safe = 1 THEN 1 ELSE 0 END) as pregnancy_safe_count,
            SUM(CASE WHEN lactation_safe = 1 THEN 1 ELSE 0 END) as lactation_safe_count
        FROM medications 
        GROUP BY class
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Products statistics
    $productStats = $db->query("
        SELECT 
            COUNT(*) as total_products,
            AVG(price) as avg_price,
            MAX(price) as max_price,
            MIN(price) as min_price
        FROM products
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Cosmetics statistics
    $cosmeticsStats = $db->query("
        SELECT 
            class,
            COUNT(*) as count
        FROM cosmetics 
        GROUP BY class
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Dental statistics
    $dentalStats = $db->query("
        SELECT 
            class,
            COUNT(*) as count,
            SUM(CASE WHEN contains_fluoride = 1 THEN 1 ELSE 0 END) as fluoride_count,
            age_group,
            COUNT(*) as age_count
        FROM dental 
        GROUP BY class, age_group
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Overall counts
    $totalCounts = [
        'medications' => $db->query("SELECT COUNT(*) FROM medications")->fetchColumn(),
        'products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'cosmetics' => $db->query("SELECT COUNT(*) FROM cosmetics")->fetchColumn(),
        'dental' => $db->query("SELECT COUNT(*) FROM dental")->fetchColumn()
    ];
    
    // Recent additions (last 30 days)
    $recentStats = [
        'medications' => $db->query("SELECT COUNT(*) FROM medications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
        'products' => $db->query("SELECT COUNT(*) FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
        'cosmetics' => $db->query("SELECT COUNT(*) FROM cosmetics WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
        'dental' => $db->query("SELECT COUNT(*) FROM dental WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn()
    ];
    
} catch (Exception $e) {
    $error = "Error fetching statistics: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics Dashboard - <?php echo getSiteName(); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-pills"></i>
                    <h1>Razology</h1>
                </div>
                <nav class="nav">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="medications.php" class="nav-link">
                        <i class="fas fa-pills"></i> Medications
                    </a>
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-boxes"></i> Products
                    </a>
                    <a href="statistics.php" class="nav-link active">
                        <i class="fas fa-chart-bar"></i> Statistics
                    </a>
                    <a href="logout.php" class="nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="page-header">
                <h2><i class="fas fa-chart-bar"></i> Statistics Dashboard</h2>
                <p>Comprehensive analytics and insights for your pharmacy inventory</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>

            <!-- Overview Cards -->
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-icon medications">
                        <i class="fas fa-pills"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($totalCounts['medications']); ?></h3>
                        <p>Total Medications</p>
                        <span class="stat-change">+<?php echo $recentStats['medications']; ?> this month</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon products">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($totalCounts['products']); ?></h3>
                        <p>Total Products</p>
                        <span class="stat-change">+<?php echo $recentStats['products']; ?> this month</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon cosmetics">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($totalCounts['cosmetics']); ?></h3>
                        <p>Cosmetic Products</p>
                        <span class="stat-change">+<?php echo $recentStats['cosmetics']; ?> this month</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon dental">
                        <i class="fas fa-tooth"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($totalCounts['dental']); ?></h3>
                        <p>Dental Products</p>
                        <span class="stat-change">+<?php echo $recentStats['dental']; ?> this month</span>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <!-- Inventory Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> Inventory Distribution</h3>
                        <p>Overall product distribution across categories</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="inventoryChart"></canvas>
                    </div>
                </div>

                <!-- Medication Classes -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-pills"></i> Medication Classes</h3>
                        <p>Distribution of medications by therapeutic class</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="medicationClassChart"></canvas>
                    </div>
                </div>

                <!-- Safety Statistics -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-baby"></i> Pregnancy & Lactation Safety</h3>
                        <p>Safety profile of medications for pregnant and lactating women</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="safetyChart"></canvas>
                    </div>
                </div>

                <!-- Cosmetics Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-palette"></i> Cosmetics Categories</h3>
                        <p>Distribution of cosmetic products by category</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="cosmeticsChart"></canvas>
                    </div>
                </div>

                <!-- Dental Analysis -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-tooth"></i> Dental Products Analysis</h3>
                        <p>Dental products by category and fluoride content</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="dentalChart"></canvas>
                    </div>
                </div>

                <!-- Price Analysis -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-dollar-sign"></i> Price Analysis</h3>
                        <p>Product pricing statistics and distribution</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="priceChart"></canvas>
                    </div>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology. Professional Pharmacy Management System</p>
        </div>
    </footer>

    <script>
        // Chart.js configuration
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = '#666';

        // Color schemes
        const colors = {
            primary: ['#3498db', '#e74c3c', '#f39c12', '#2ecc71', '#9b59b6', '#1abc9c'],
            medications: '#3498db',
            products: '#e74c3c',
            cosmetics: '#f39c12',
            dental: '#2ecc71',
            safety: ['#27ae60', '#e67e22', '#95a5a6']
        };

        // 1. Inventory Distribution Pie Chart
        const inventoryData = {
            labels: ['Medications', 'Products', 'Cosmetics', 'Dental'],
            datasets: [{
                data: [
                    <?php echo $totalCounts['medications']; ?>,
                    <?php echo $totalCounts['products']; ?>,
                    <?php echo $totalCounts['cosmetics']; ?>,
                    <?php echo $totalCounts['dental']; ?>
                ],
                backgroundColor: colors.primary,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };

        new Chart(document.getElementById('inventoryChart'), {
            type: 'doughnut',
            data: inventoryData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // 2. Medication Classes Bar Chart
        const medicationClassData = {
            labels: [
                <?php 
                foreach ($medicationStats as $stat) {
                    echo '"' . htmlspecialchars($stat['class']) . '",';
                }
                ?>
            ],
            datasets: [{
                label: 'Number of Medications',
                data: [
                    <?php 
                    foreach ($medicationStats as $stat) {
                        echo $stat['count'] . ',';
                    }
                    ?>
                ],
                backgroundColor: colors.medications,
                borderRadius: 8
            }]
        };

        new Chart(document.getElementById('medicationClassChart'), {
            type: 'bar',
            data: medicationClassData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // 3. Safety Statistics
        const totalMedications = <?php echo $totalCounts['medications']; ?>;
        const pregnancySafe = <?php echo array_sum(array_column($medicationStats, 'pregnancy_safe_count')); ?>;
        const lactationSafe = <?php echo array_sum(array_column($medicationStats, 'lactation_safe_count')); ?>;

        const safetyData = {
            labels: ['Pregnancy Safe', 'Lactation Safe', 'Requires Caution'],
            datasets: [{
                label: 'Safety Profile',
                data: [pregnancySafe, lactationSafe, totalMedications - Math.max(pregnancySafe, lactationSafe)],
                backgroundColor: colors.safety,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };

        new Chart(document.getElementById('safetyChart'), {
            type: 'pie',
            data: safetyData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // 4. Cosmetics Distribution
        const cosmeticsData = {
            labels: [
                <?php 
                foreach ($cosmeticsStats as $stat) {
                    echo '"' . htmlspecialchars($stat['class']) . '",';
                }
                ?>
            ],
            datasets: [{
                label: 'Cosmetic Products',
                data: [
                    <?php 
                    foreach ($cosmeticsStats as $stat) {
                        echo $stat['count'] . ',';
                    }
                    ?>
                ],
                backgroundColor: colors.cosmetics,
                borderRadius: 8
            }]
        };

        new Chart(document.getElementById('cosmeticsChart'), {
            type: 'bar',
            data: cosmeticsData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 5. Dental Analysis
        const dentalClasses = [...new Set([
            <?php 
            foreach ($dentalStats as $stat) {
                echo '"' . htmlspecialchars($stat['class']) . '",';
            }
            ?>
        ])];

        const dentalData = {
            labels: dentalClasses,
            datasets: [{
                label: 'Total Products',
                data: [
                    <?php 
                    $classCounts = [];
                    foreach ($dentalStats as $stat) {
                        if (!isset($classCounts[$stat['class']])) {
                            $classCounts[$stat['class']] = 0;
                        }
                        $classCounts[$stat['class']] += $stat['count'];
                    }
                    foreach ($classCounts as $count) {
                        echo $count . ',';
                    }
                    ?>
                ],
                backgroundColor: colors.dental,
                borderRadius: 8
            }]
        };

        new Chart(document.getElementById('dentalChart'), {
            type: 'bar',
            data: dentalData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 6. Price Analysis (using sample data since we need actual price data)
        const priceData = {
            labels: ['$0-10', '$10-25', '$25-50', '$50-100', '$100+'],
            datasets: [{
                label: 'Number of Products',
                data: [45, 89, 67, 23, 12], // Sample data - replace with actual price ranges
                backgroundColor: '#e74c3c',
                borderRadius: 8
            }]
        };

        new Chart(document.getElementById('priceChart'), {
            type: 'bar',
            data: priceData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Add animation and interactivity
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.6s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 150);
            });
        });
    </script>

    <style>
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem 0;
        }

        .page-header h2 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 2.5rem;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.medications { background: #3498db; }
        .stat-icon.products { background: #e74c3c; }
        .stat-icon.cosmetics { background: #f39c12; }
        .stat-icon.dental { background: #2ecc71; }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            color: var(--text-color);
        }

        .stat-content p {
            margin: 0.25rem 0;
            color: var(--text-muted);
            font-weight: 500;
        }

        .stat-change {
            font-size: 0.85rem;
            color: #27ae60;
            font-weight: 600;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #eee;
        }

        .chart-header {
            margin-bottom: 1.5rem;
        }

        .chart-header h3 {
            color: var(--text-color);
            margin: 0 0 0.5rem 0;
            font-size: 1.25rem;
        }

        .chart-header p {
            color: var(--text-muted);
            margin: 0;
            font-size: 0.9rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .page-header h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .stats-overview {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>

    <script src="js/main.js"></script>
</body>
</html>
