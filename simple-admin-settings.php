<?php
require_once 'includes/language-functions.php';
initializeLanguage();
require_once 'includes/simple-admin-auth.php';
requireAdminLogin();
require_once 'includes/database.php';
require_once 'includes/admin-settings-helper.php';

// Initialize variables
$message = '';
$error = '';
$adminUser = [
    'username' => getAdminUsername(),
    'id' => getAdminId()
];

$defaultSettings = [
    'site_name' => 'Razology Pharmacy',
    'maintenance_mode' => '0',
    'backup_frequency' => 'daily',
    'session_timeout' => '30',
    'max_login_attempts' => '5',
    'enable_audit_log' => '1',
    'backup_retention_days' => '30'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create admin_settings table if it doesn't exist
        $createTable = "
            CREATE TABLE IF NOT EXISTS admin_settings (
                setting_key VARCHAR(255) PRIMARY KEY,
                setting_value TEXT NOT NULL,
                setting_type VARCHAR(50) DEFAULT 'string',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        $db->exec($createTable);
        
        // Handle checkbox for maintenance_mode (unchecked boxes don't send data)
        $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';
        
        // Update or insert settings
        $settingsToUpdate = [];
        foreach ($_POST as $key => $value) {
            if (!in_array($key, ['action', 'maintenance_mode']) && !empty($key)) {
                $settingsToUpdate[$key] = $value;
            }
        }
        $settingsToUpdate['maintenance_mode'] = $maintenance_mode;
        
        foreach ($settingsToUpdate as $key => $value) {
            $stmt = $db->prepare("
                INSERT INTO admin_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
            ");
            $stmt->execute([$key, $value]);
        }
        
        $message = "Settings updated successfully!";
        
        // Reload settings to show updated values
        $result = $db->query("SELECT setting_key, setting_value FROM admin_settings");
        $settings = $defaultSettings;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
    } catch (Exception $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get current settings
$settings = $defaultSettings; // Start with defaults

try {
    // Check if admin_settings table exists
    $tables = $db->query("SHOW TABLES LIKE 'admin_settings'")->fetchAll();
    
    if (!empty($tables)) {
        $result = $db->query("SELECT setting_key, setting_value FROM admin_settings");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (Exception $e) {
    // Use default settings if table doesn't exist
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
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

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .settings-form {
            max-width: 600px;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .form-group small {
            color: #666;
            font-size: 0.875rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .admin-user {
            color: rgba(255, 255, 255, 0.9);
            padding: 1rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: auto;
        }

        .settings-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .settings-section h3 {
            margin-bottom: 1rem;
            color: #333;
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
                <h2><i class="fas fa-user-shield"></i> <?= t('admin.admin') ?></h2>
            </div>
            
            <nav>
                <ul class="admin-nav">
                    <li><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> <?= t('nav.home') ?></a></li>
                    <li><a href="simple-admin-users.php"><i class="fas fa-users"></i> <?= t('admin.userManagement') ?></a></li>
                    <li><a href="admin-settings.php" class="active"><i class="fas fa-cog"></i> <?= t('common.settings') ?></a></li>
                    <li><a href="admin-logs.php"><i class="fas fa-history"></i> <?= t('admin.auditLogs') ?></a></li>
                    <li><a href="backup.php"><i class="fas fa-database"></i> <?= t('common.backup') ?></a></li>
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
                <div class="page-header">
                    <h1><i class="fas fa-cog"></i> <?= t('admin.systemSettings') ?></h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="settings-form">
                    <div class="settings-section">
                        <h3><i class="fas fa-globe"></i> <?= t('common.generalSettings') ?></h3>
                        
                        <div class="form-group">
                            <label for="site_name"><?= t('admin.siteName') ?></label>
                            <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                            <small><?= t('messages.siteNameDesc') ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="maintenance_mode" style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" <?= $settings['maintenance_mode'] == '1' ? 'checked' : '' ?> style="width: auto;">
                                <?= t('admin.maintenanceMode') ?>
                            </label>
                            <small><?= t('messages.maintenanceModeDesc') ?></small>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-shield-alt"></i> <?= t('common.securitySettings') ?></h3>
                        
                        <div class="form-group">
                            <label for="session_timeout"><?= t('admin.sessionTimeout') ?></label>
                            <input type="number" id="session_timeout" name="session_timeout" value="<?= htmlspecialchars($settings['session_timeout']) ?>" min="5" max="480">
                            <small><?= t('messages.sessionTimeoutDesc') ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_login_attempts"><?= t('common.maxLoginAttempts') ?></label>
                            <input type="number" id="max_login_attempts" name="max_login_attempts" value="<?= htmlspecialchars($settings['max_login_attempts']) ?>" min="3" max="10">
                            <small><?= t('messages.maxAttemptsDesc') ?></small>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-database"></i> <?= t('common.backupSettings') ?></h3>
                        
                        <div class="form-group">
                            <label for="backup_frequency"><?= t('admin.backupFrequency') ?></label>
                            <select id="backup_frequency" name="backup_frequency">
                                <option value="daily" <?= $settings['backup_frequency'] == 'daily' ? 'selected' : '' ?>><?= t('common.daily') ?></option>
                                <option value="weekly" <?= $settings['backup_frequency'] == 'weekly' ? 'selected' : '' ?>><?= t('common.weekly') ?></option>
                                <option value="monthly" <?= $settings['backup_frequency'] == 'monthly' ? 'selected' : '' ?>><?= t('common.monthly') ?></option>
                            </select>
                            <small><?= t('messages.backupFreqDesc') ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="backup_retention_days"><?= t('admin.backupRetention') ?></label>
                            <input type="number" id="backup_retention_days" name="backup_retention_days" value="<?= htmlspecialchars($settings['backup_retention_days']) ?>" min="7" max="365">
                            <small><?= t('messages.backupRetentionDesc') ?></small>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-history"></i> <?= t('common.loggingSettings') ?></h3>
                        
                        <div class="form-group">
                            <label for="enable_audit_log"><?= t('admin.enableAuditLog') ?></label>
                            <select id="enable_audit_log" name="enable_audit_log">
                                <option value="1" <?= $settings['enable_audit_log'] == '1' ? 'selected' : '' ?>><?= t('common.enabled') ?></option>
                                <option value="0" <?= $settings['enable_audit_log'] == '0' ? 'selected' : '' ?>><?= t('common.disabled') ?></option>
                            </select>
                            <small><?= t('messages.auditLogDesc') ?></small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= t('common.saveSettings') ?>
                    </button>
                </form>
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
    });
    </script>
</body>
</html>