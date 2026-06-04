<?php
require_once 'includes/simple-admin-auth.php';
requireAdminLogin();
require_once 'includes/database.php';

// Define admin user from session
$adminUser = [
    'id' => getAdminId(),
    'username' => getAdminUsername()
];

$database = new Database();
$db = $database->connect();

// Get system statistics
function getSystemStats($db) {
    $stats = [];
    
    // Initialize default values
    $stats['users'] = [];
    $stats['products'] = 0;
    $stats['cosmetics'] = 0;
    $stats['dental'] = 0;
    $stats['medications'] = 0;
    
    try {
        // User counts
        $userStats = $db->query("
            SELECT 
                role,
                COUNT(*) as count
            FROM users 
            GROUP BY role
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['users'] = array_column($userStats, null, 'role');
    } catch (Exception $e) {
        // Handle user stats error
    }
    
    // Product counts with error handling
    try {
        $stats['products'] = $db->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $stats['products'] = 0;
    }
    
    try {
        $stats['cosmetics'] = $db->query("SELECT COUNT(*) FROM cosmetics")->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $stats['cosmetics'] = 0;
    }
    
    try {
        $stats['dental'] = $db->query("SELECT COUNT(*) FROM dental")->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $stats['dental'] = 0;
    }
    
    try {
        $stats['medications'] = $db->query("SELECT COUNT(*) FROM medications")->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $stats['medications'] = 0;
    }
    
    return $stats;
}

$systemStats = getSystemStats($db);

// Get recent activity (simplified)
$recentActivity = [];
try {
    $recentActivity = $db->query("
        SELECT username, last_login 
        FROM users 
        WHERE last_login IS NOT NULL 
        ORDER BY last_login DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Handle error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Razology Pharmacy</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem 0;
        }

        .admin-header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
            padding: 0 2rem;
        }

        .admin-nav {
            list-style: none;
        }

        .admin-nav li {
            margin-bottom: 0.5rem;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .admin-nav i {
            margin-right: 1rem;
            width: 20px;
        }

        .admin-main {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .admin-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .dashboard-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
        }

        .stat-card.users { border-left-color: #007bff; }
        .stat-card.products { border-left-color: #28a745; }
        .stat-card.cosmetics { border-left-color: #ffc107; }
        .stat-card.dental { border-left-color: #17a2b8; }
        .stat-card.medications { border-left-color: #dc3545; }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .action-btn i {
            margin-right: 0.5rem;
        }

        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .chart-container h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        .admin-user {
            color: rgba(255, 255, 255, 0.9);
            padding: 1rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: auto;
        }

        .recent-activity {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-header">
                <h2><i class="fas fa-user-shield"></i> Admin Panel</h2>
            </div>
            
            <nav>
                <ul class="admin-nav">
                    <li><a href="admin-dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="simple-admin-users.php"><i class="fas fa-users"></i> User Management</a></li>
                    <li><a href="simple-admin-settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="simple-admin-logs.php"><i class="fas fa-history"></i> Audit Logs</a></li>
                    <li><a href="backup.php"><i class="fas fa-database"></i> Backup</a></li>
                </ul>
            </nav>
            
            <div class="admin-user">
                <i class="fas fa-user-circle"></i>
                <span><?= htmlspecialchars($adminUser['username']) ?></span>
                <a href="includes/simple-admin-auth.php?logout=1" style="color: #ffd700; margin-left: 1rem;">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </aside>

        <main class="admin-main">
            <div class="admin-content">
                <div class="dashboard-header">
                    <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                    <p>Welcome back, <?= htmlspecialchars($adminUser['username']) ?>! Here's your system overview.</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card users">
                        <div class="stat-number"><?= array_sum(array_column($systemStats['users'], 'count')) ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card products">
                        <div class="stat-number"><?= $systemStats['products'] ?></div>
                        <div class="stat-label">Products</div>
                    </div>
                    <div class="stat-card cosmetics">
                        <div class="stat-number"><?= $systemStats['cosmetics'] ?></div>
                        <div class="stat-label">Cosmetics</div>
                    </div>
                    <div class="stat-card dental">
                        <div class="stat-number"><?= $systemStats['dental'] ?></div>
                        <div class="stat-label">Dental Items</div>
                    </div>
                    <div class="stat-card medications">
                        <div class="stat-number"><?= $systemStats['medications'] ?></div>
                        <div class="stat-label">Medications</div>
                    </div>
                </div>

                <div class="quick-actions">
                    <a href="simple-admin-users.php" class="action-btn">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a href="simple-admin-settings.php" class="action-btn">
                        <i class="fas fa-cog"></i> System Settings
                    </a>
                    <a href="backup.php" class="action-btn">
                        <i class="fas fa-database"></i> Create Backup
                    </a>
                    <a href="simple-admin-logs.php" class="action-btn">
                        <i class="fas fa-history"></i> View Logs
                    </a>
                </div>

                <div class="chart-container">
                    <h3><i class="fas fa-chart-pie"></i> System Overview</h3>
                    <div class="chart-wrapper">
                        <canvas id="systemChart"></canvas>
                    </div>
                </div>

                <div class="recent-activity">
                    <h3><i class="fas fa-clock"></i> Recent Activity</h3>
                    <?php if (!empty($recentActivity)): ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($activity['username']) ?> logged in</span>
                                <span><?= $activity['last_login'] ? date('M j, H:i', strtotime($activity['last_login'])) : 'Unknown' ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="activity-item">
                            <span style="color: #666; font-style: italic;">
                                <i class="fas fa-info-circle"></i> No recent activity to display
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // System Overview Chart
        const ctx = document.getElementById('systemChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Products', 'Cosmetics', 'Dental', 'Medications'],
                datasets: [{
                    data: [
                        <?= $systemStats['products'] ?>,
                        <?= $systemStats['cosmetics'] ?>,
                        <?= $systemStats['dental'] ?>,
                        <?= $systemStats['medications'] ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#17a2b8',
                        '#dc3545'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    </script>
</body>
</html>