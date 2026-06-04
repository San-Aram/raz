<?php
require_once 'includes/language-functions.php';
initializeLanguage();
require_once 'includes/admin-auth.php';
$adminUser = initAdminPage('System Settings');
require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        $settings = $_POST['settings'] ?? [];
        $updatedCount = 0;
        
        foreach ($settings as $key => $value) {
            // Determine setting type
            $type = 'string';
            if (in_array($key, ['maintenance_mode', 'backup_enabled'])) {
                $type = 'boolean';
                $value = isset($_POST['settings'][$key]) ? 'true' : 'false';
            } elseif (in_array($key, ['max_login_attempts', 'session_timeout', 'notification_retention_days', 'audit_log_retention_days', 'low_stock_threshold'])) {
                $type = 'integer';
                $value = intval($value);
            }
            
            if (setSystemSetting($key, $value, $type)) {
                $updatedCount++;
            }
        }
        
        if ($updatedCount > 0) {
            $success = "Updated $updatedCount system settings successfully.";
        } else {
            $error = "No settings were updated.";
        }
    } elseif ($action === 'clear_cache') {
        // Clear various cache items
        logAdminAction('system_cache_cleared');
        $success = "System cache cleared successfully.";
    } elseif ($action === 'reset_notifications') {
        try {
            $db->exec("DELETE FROM dismissed_notifications");
            $db->exec("UPDATE user_notification_settings SET dismiss_all_until = NULL");
            logAdminAction('notifications_reset');
            $success = "All notification dismissals reset successfully.";
        } catch (Exception $e) {
            $error = "Failed to reset notifications: " . $e->getMessage();
        }
    }
}

// Get all settings
$settings = $db->query("SELECT setting_key, setting_value, setting_type, description FROM admin_settings ORDER BY setting_key")->fetchAll(PDO::FETCH_ASSOC);
$settingsArray = array_column($settings, null, 'setting_key');

// Get system information
$systemInfo = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_version' => $db->query("SELECT VERSION() as version")->fetchColumn(),
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'total_sales' => $db->query("SELECT COUNT(*) FROM sales")->fetchColumn(),
    'database_size' => $db->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
    ")->fetchColumn(),
    'uptime' => sys_getloadavg()[0] ?? 'N/A'
];
?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo getTextDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .admin-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .settings-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group .description {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .system-info {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #333;
        }
        
        .info-value {
            color: #6c757d;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .maintenance-notice {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #ffeaa7;
        }
        
        .quick-actions {
            display: grid;
            gap: 1rem;
        }
        
        .action-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="container">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <a href="admin-dashboard.php" style="color: white; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> <?php echo t('common.back'); ?>
                    </a>
                    <h1 style="margin: 0;"><?php echo t('admin.settings'); ?></h1>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="nav-language-selector">
                        <select id="languageSelect" class="language-select" onchange="changeLanguage(this.value)">
                            <option value="en">English</option>
                            <option value="ckb">سۆرانی</option>
                            <option value="ar">العربية</option>
                        </select>
                    </div>
                    <i class="fas fa-user-circle"></i>
                    <span><?= htmlspecialchars($adminUser['username']) ?></span>
                </div>
            </div>
        </header>
        
        <main class="admin-content">
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($settingsArray['maintenance_mode']) && $settingsArray['maintenance_mode']['setting_value'] === 'true'): ?>
                <div class="maintenance-notice">
                    <i class="fas fa-tools"></i> <strong>Maintenance Mode Active</strong> - The system is currently in maintenance mode.
                </div>
            <?php endif; ?>
            
            <div class="settings-grid">
                <div>
                    <div class="settings-section">
                        <div class="section-header">
                            <i class="fas fa-cogs"></i>
                            <h3><?php echo t('admin.systemConfiguration'); ?></h3>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <div class="form-group">
                                <label for="system_name">System Name</label>
                                <div class="description">Display name for the pharmacy management system</div>
                                <input type="text" 
                                       id="system_name" 
                                       name="settings[system_name]" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($settingsArray['system_name']['setting_value'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="currency_symbol">Currency Symbol</label>
                                <div class="description">Symbol used for displaying prices</div>
                                <input type="text" 
                                       id="currency_symbol" 
                                       name="settings[currency_symbol]" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($settingsArray['currency_symbol']['setting_value'] ?? '$') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="timezone">System Timezone</label>
                                <div class="description">Default timezone for the system</div>
                                <select id="timezone" name="settings[timezone]" class="form-control">
                                    <option value="America/New_York" <?= ($settingsArray['timezone']['setting_value'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>Eastern Time</option>
                                    <option value="America/Chicago" <?= ($settingsArray['timezone']['setting_value'] ?? '') === 'America/Chicago' ? 'selected' : '' ?>>Central Time</option>
                                    <option value="America/Denver" <?= ($settingsArray['timezone']['setting_value'] ?? '') === 'America/Denver' ? 'selected' : '' ?>>Mountain Time</option>
                                    <option value="America/Los_Angeles" <?= ($settingsArray['timezone']['setting_value'] ?? '') === 'America/Los_Angeles' ? 'selected' : '' ?>>Pacific Time</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="low_stock_threshold">Low Stock Threshold</label>
                                <div class="description">Default threshold for low stock notifications</div>
                                <input type="number" 
                                       id="low_stock_threshold" 
                                       name="settings[low_stock_threshold]" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($settingsArray['low_stock_threshold']['setting_value'] ?? '10') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="max_login_attempts">Maximum Login Attempts</label>
                                <div class="description">Number of failed attempts before account lockout</div>
                                <input type="number" 
                                       id="max_login_attempts" 
                                       name="settings[max_login_attempts]" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($settingsArray['max_login_attempts']['setting_value'] ?? '5') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="session_timeout">Session Timeout (seconds)</label>
                                <div class="description">Time before user sessions expire</div>
                                <input type="number" 
                                       id="session_timeout" 
                                       name="settings[session_timeout]" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($settingsArray['session_timeout']['setting_value'] ?? '3600') ?>">
                            </div>
                            
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           id="maintenance_mode" 
                                           name="settings[maintenance_mode]" 
                                           <?= ($settingsArray['maintenance_mode']['setting_value'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                    <label for="maintenance_mode">Maintenance Mode</label>
                                </div>
                                <div class="description">Enable maintenance mode to restrict system access</div>
                            </div>
                            
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           id="backup_enabled" 
                                           name="settings[backup_enabled]" 
                                           <?= ($settingsArray['backup_enabled']['setting_value'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                    <label for="backup_enabled">Automatic Backups</label>
                                </div>
                                <div class="description">Enable automatic database backups</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </form>
                    </div>
                </div>
                
                <div>
                    <div class="system-info">
                        <div class="section-header">
                            <i class="fas fa-info-circle"></i>
                            <h3>System Information</h3>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">PHP Version</span>
                            <span class="info-value"><?= $systemInfo['php_version'] ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Database Version</span>
                            <span class="info-value"><?= $systemInfo['database_version'] ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Server Software</span>
                            <span class="info-value"><?= $systemInfo['server_software'] ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Total Users</span>
                            <span class="info-value"><?= number_format($systemInfo['total_users']) ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Total Products</span>
                            <span class="info-value"><?= number_format($systemInfo['total_products']) ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Total Sales</span>
                            <span class="info-value"><?= number_format($systemInfo['total_sales']) ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Database Size</span>
                            <span class="info-value"><?= $systemInfo['database_size'] ?> MB</span>
                        </div>
                    </div>
                    
                    <div class="system-info">
                        <div class="section-header">
                            <i class="fas fa-tools"></i>
                            <h3>Quick Actions</h3>
                        </div>
                        
                        <div class="quick-actions">
                            <div class="action-item">
                                <div>
                                    <strong>Reset Notifications</strong>
                                    <div style="font-size: 0.85rem; color: #6c757d;">Clear all dismissed notifications</div>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="reset_notifications">
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('Reset all notifications?')">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                </form>
                            </div>
                            
                            <div class="action-item">
                                <div>
                                    <strong>Clear Cache</strong>
                                    <div style="font-size: 0.85rem; color: #6c757d;">Clear system cache files</div>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="clear_cache">
                                    <button type="submit" class="btn btn-secondary">
                                        <i class="fas fa-broom"></i> Clear
                                    </button>
                                </form>
                            </div>
                            
                            <div class="action-item">
                                <div>
                                    <strong>View Logs</strong>
                                    <div style="font-size: 0.85rem; color: #6c757d;">Access audit and system logs</div>
                                </div>
                                <a href="admin-logs.php" class="btn btn-primary">
                                    <i class="fas fa-file-alt"></i> View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function changeLanguage(lang) {
        const formData = new FormData();
        formData.append('lang', lang);
        fetch('api/set-language.php', { method: 'POST', body: formData })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); })
        .catch(e => console.error('Error:', e));
    }
    document.addEventListener('DOMContentLoaded', function() {
        const ls = document.getElementById('languageSelect');
        if (ls) { ls.value = '<?php echo getCurrentLanguage(); ?>'; }
        if ('<?php echo getTextDirection(); ?>' === 'rtl') document.documentElement.dir = 'rtl';
    });
    </script>
</body>
</html>