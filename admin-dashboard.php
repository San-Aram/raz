<?php
require_once 'includes/simple-admin-auth.php';
requireAdminLogin();
require_once 'includes/language-functions.php';
require_once 'includes/admin-settings-helper.php';
initializeLanguage();
require_once 'includes/database.php';

// Define admin user from session
$adminUser = [
    'id' => getAdminId(),
    'username' => getAdminUsername()
];

$database = new Database();
$db = $database->connect();

// Get system statistics with error handling
function getSystemStats($db) {
    $stats = [
        'users' => [],
        'products' => 0,
        'cosmetics' => 0,
        'dental' => 0,
        'medications' => 0,
        'total_users' => 0
    ];
    
    try {
        // User counts
        $userStats = $db->query("
            SELECT 
                role,
                COUNT(*) as count
            FROM users 
            GROUP BY role
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['users'] = array_column($userStats, 'count', 'role');
        $stats['total_users'] = array_sum($stats['users']);
    } catch (Exception $e) {
        error_log("Error getting user stats: " . $e->getMessage());
    }
    
    // Product counts with error handling
    try {
        $stats['products'] = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    } catch (Exception $e) {
        $stats['products'] = 0;
    }
    
    try {
        $stats['cosmetics'] = (int)$db->query("SELECT COUNT(*) FROM cosmetics")->fetchColumn();
    } catch (Exception $e) {
        $stats['cosmetics'] = 0;
    }
    
    try {
        $stats['dental'] = (int)$db->query("SELECT COUNT(*) FROM dental")->fetchColumn();
    } catch (Exception $e) {
        $stats['dental'] = 0;
    }
    
    try {
        $stats['medications'] = (int)$db->query("SELECT COUNT(*) FROM medications")->fetchColumn();
    } catch (Exception $e) {
        $stats['medications'] = 0;
    }
    
    return $stats;
}

$systemStats = getSystemStats($db);

// Get recent activity
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
    error_log("Error getting recent activity: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo getTextDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo getSiteName(); ?></title>
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
            display: flex;
            flex-direction: column;
        }

        .admin-header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
            padding: 0 2rem;
        }

        .admin-nav {
            list-style: none;
            flex: 1;
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

        .admin-user {
            color: rgba(255, 255, 255, 0.9);
            padding: 1rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
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

        .dashboard-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .dashboard-header p {
            color: #666;
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
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.users { border-left-color: #007bff; }
        .stat-card.products { border-left-color: #28a745; }
        .stat-card.cosmetics { border-left-color: #ffc107; }
        .stat-card.dental { border-left-color: #17a2b8; }
        .stat-card.medications { border-left-color: #dc3545; }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
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
            font-weight: 500;
        }

        .action-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }

        .action-btn i {
            margin-right: 0.5rem;
        }

        .dashboard-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-container,
        .recent-activity {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .chart-container h3,
        .recent-activity h3 {
            margin-bottom: 1rem;
            color: #333;
            display: flex;
            align-items: center;
        }

        .chart-container h3 i,
        .recent-activity h3 i {
            margin-right: 0.5rem;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-user {
            display: flex;
            align-items: center;
            color: #333;
        }

        .activity-user i {
            margin-right: 0.5rem;
            color: #007bff;
        }

        .activity-time {
            color: #666;
            font-size: 0.9rem;
        }

        .no-activity {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
                padding: 1rem 0;
            }
            
            .dashboard-section {
                grid-template-columns: 1fr;
            }
        }

        .admin-language {
            padding: 1rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: auto;
        }

        .admin-language label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .language-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .language-select option {
            background: #1e3c72;
            color: white;
        }

        .language-select:hover {
            background: rgba(255, 255, 255, 0.15);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-header">
                <h2><i class="fas fa-user-shield"></i> <?php echo t('header.admin'); ?> <?php echo t('header.dashboard'); ?></h2>
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
            
            <div class="admin-language">
                <label>Language / زمان</label>
                <select class="language-select" id="languageSelect" onchange="changeLanguage(this.value)">
                    <option value="en">English</option>
                    <option value="ckb">سۆرانی (Kurdish)</option>
                    <option value="ar">العربية (Arabic)</option>
                </select>
            </div>
            
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
                    <h1><i class="fas fa-tachometer-alt"></i> <?php echo t('header.admin'); ?> <?php echo t('header.dashboard'); ?></h1>
                    <p><?php echo sprintf(t('admin.welcomeBack'), htmlspecialchars($adminUser['username'])); ?> - <strong><?php echo getSiteName(); ?></strong></p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card users">
                        <div class="stat-number"><?= $systemStats['total_users'] ?></div>
                        <div class="stat-label"><?php echo t('admin.totalUsers'); ?></div>
                    </div>
                    <div class="stat-card products">
                        <div class="stat-number"><?= $systemStats['products'] ?></div>
                        <div class="stat-label"><?php echo t('admin.products'); ?></div>
                    </div>
                    <div class="stat-card cosmetics">
                        <div class="stat-number"><?= $systemStats['cosmetics'] ?></div>
                        <div class="stat-label"><?php echo t('admin.cosmetics'); ?></div>
                    </div>
                    <div class="stat-card dental">
                        <div class="stat-number"><?= $systemStats['dental'] ?></div>
                        <div class="stat-label"><?php echo t('admin.dentalItems'); ?></div>
                    </div>
                    <div class="stat-card medications">
                        <div class="stat-number"><?= $systemStats['medications'] ?></div>
                        <div class="stat-label"><?php echo t('admin.medications'); ?></div>
                    </div>
                </div>

                <div class="quick-actions">
                    <a href="simple-admin-users.php" class="action-btn">
                        <i class="fas fa-users"></i> <?php echo t('admin.manageUsers'); ?>
                    </a>
                    <a href="simple-admin-settings.php" class="action-btn">
                        <i class="fas fa-cog"></i> <?php echo t('admin.systemSettings'); ?>
                    </a>
                    <a href="backup.php" class="action-btn">
                        <i class="fas fa-database"></i> <?php echo t('admin.createBackup'); ?>
                    </a>
                    <a href="simple-admin-logs.php" class="action-btn">
                        <i class="fas fa-history"></i> <?php echo t('admin.viewLogs'); ?>
                    </a>
                </div>

                <div class="dashboard-section">
                    <div class="chart-container">
                        <h3><i class="fas fa-chart-pie"></i> <?php echo t('admin.systemOverview'); ?></h3>
                        <div class="chart-wrapper">
                            <canvas id="systemChart"></canvas>
                        </div>
                    </div>

                    <div class="recent-activity">
                        <h3><i class="fas fa-clock"></i> <?php echo t('admin.recentActivity'); ?></h3>
                        <?php if (!empty($recentActivity)): ?>
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-user">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($activity['username']) ?> <?php echo t('admin.loggedIn'); ?></span>
                                    </div>
                                    <div class="activity-time">
                                        <?= $activity['last_login'] ? date('M j, H:i', strtotime($activity['last_login'])) : 'Unknown' ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-activity">
                                <i class="fas fa-info-circle"></i>
                                <p><?php echo t('admin.noRecentActivity'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function changeLanguage(lang) {
        const formData = new FormData();
        formData.append('lang', lang);
        
        fetch('api/set-language.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    document.addEventListener('DOMContentLoaded', function() {
        const langSelect = document.getElementById('languageSelect');
        if (langSelect) {
            langSelect.value = '<?php echo getCurrentLanguage(); ?>';
        }
        if ('<?php echo getTextDirection(); ?>' === 'rtl') document.documentElement.dir = 'rtl';
    });
        
        // System Overview Chart
        const ctx = document.getElementById('systemChart').getContext('2d');
        
        const chartData = {
            labels: ['<?php echo t("admin.products"); ?>', '<?php echo t("admin.cosmetics"); ?>', '<?php echo t("admin.dentalItems"); ?>', '<?php echo t("admin.medications"); ?>'],
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
        };

        new Chart(ctx, {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>