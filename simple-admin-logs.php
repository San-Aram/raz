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
$hasAuditTable = false;

// Handle delete single record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete_record') {
    $recordId = intval($_POST['record_id'] ?? 0);
    if ($recordId > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM audit_logs WHERE id = ?");
            if ($stmt->execute([$recordId])) {
                $message = "Log record deleted successfully!";
            } else {
                $error = "Failed to delete log record.";
            }
        } catch (Exception $e) {
            $error = "Error deleting record: " . $e->getMessage();
        }
    }
}

// Handle clear all records
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'clear_all') {
    try {
        $db->exec("TRUNCATE TABLE audit_logs");
        $message = "All audit logs cleared successfully!";
    } catch (Exception $e) {
        $error = "Error clearing logs: " . $e->getMessage();
    }
}

try {
    // Check if audit_logs table exists
    $tables = $db->query("SHOW TABLES LIKE 'audit_logs'")->fetchAll();
    
    if (!empty($tables)) {
        $hasAuditTable = true;
        
        // Check if table has old schema (ip_address column) and migrate if needed
        try {
            $columns = $db->query("SHOW COLUMNS FROM audit_logs")->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'Field');
            
            // If ip_address exists but username doesn't, we need to migrate
            if (in_array('ip_address', $columnNames) && !in_array('username', $columnNames)) {
                // Drop old table and recreate with new schema
                $db->exec("DROP TABLE audit_logs");
                
                $createTable = "
                    CREATE TABLE audit_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NULL,
                        username VARCHAR(255) NULL,
                        action VARCHAR(100) NOT NULL,
                        table_name VARCHAR(50) NULL,
                        record_id INT NULL,
                        old_values TEXT NULL,
                        new_values TEXT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                    )
                ";
                $db->exec($createTable);
                $hasAuditTable = true;
                $message = "Audit logs table schema updated successfully!";
            }
        } catch (Exception $e) {
            // If migration fails, just continue
        }
        
        // Get logs with pagination
        $page = intval($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $sql = "
            SELECT * FROM audit_logs
            ORDER BY created_at DESC 
            LIMIT $limit OFFSET $offset
        ";
        $logs = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for pagination
        $totalLogs = $db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
        $totalPages = ceil($totalLogs / $limit);
    }
} catch (Exception $e) {
    $error = "Error loading audit logs: " . $e->getMessage();
}

// Handle create table request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create_table') {
    try {
        $createTable = "
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                username VARCHAR(255) NULL,
                action VARCHAR(100) NOT NULL,
                table_name VARCHAR(50) NULL,
                record_id INT NULL,
                old_values TEXT NULL,
                new_values TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ";
        $db->exec($createTable);
        
        $message = "Audit logs table created successfully!";
        $hasAuditTable = true;
        
        // Refresh the page to show logs
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
        
    } catch (Exception $e) {
        $error = "Error creating audit logs table: " . $e->getMessage();
    }
}
?>
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Admin Panel</title>
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

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-small {
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
        }

        .btn-controls {
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .logs-table th,
        .logs-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.875rem;
        }

        .logs-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .action-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .action-login {
            background: #d4edda;
            color: #155724;
        }

        .action-create {
            background: #cce5ff;
            color: #004085;
        }

        .action-update {
            background: #fff3cd;
            color: #856404;
        }

        .action-delete {
            background: #f8d7da;
            color: #721c24;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
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

        .no-logs {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-logs i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ccc;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            text-decoration: none;
            background: #f8f9fa;
            color: #007bff;
            border-radius: 4px;
        }

        .pagination a.active {
            background: #007bff;
            color: white;
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
                    <li><a href="simple-admin-settings.php"><i class="fas fa-cog"></i> <?= t('common.settings') ?></a></li>
                    <li><a href="simple-admin-logs.php" class="active"><i class="fas fa-history"></i> <?= t('admin.auditLogs') ?></a></li>
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
                    <h1><i class="fas fa-history"></i> <?= t('admin.auditLogs') ?></h1>
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

                <?php if (!$hasAuditTable): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <?= t('messages.auditNotConfiguredMsg') ?>
                    </div>
                    
                    <div class="no-logs">
                        <i class="fas fa-history"></i>
                        <h3><?= t('messages.auditNotConfigured') ?></h3>
                        <p><?= t('messages.setupAuditLogging') ?></p>
                        <br>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="create_table">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?= t('messages.enableAuditLogging') ?>
                            </button>
                        </form>
                    </div>
                    
                <?php elseif (empty($logs)): ?>
                    <div class="no-logs">
                        <i class="fas fa-clipboard-list"></i>
                        <h3><?= t('messages.noAuditLogsYet') ?></h3>
                        <p><?= t('messages.noAuditLogsMsg') ?></p>
                    </div>
                    
                <?php else: ?>
                    <div class="btn-controls">
                        <form method="post" style="display: inline;" onsubmit="return confirm('<?= t('messages.clearAllLogsConfirm') ?>');">
                            <input type="hidden" name="action" value="clear_all">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> <?= t('admin.clearAll') ?>
                            </button>
                        </form>
                    </div>

                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th><?= t('admin.auditTime') ?></th>
                                <th><?= t('admin.auditUser') ?></th>
                                <th><?= t('admin.auditAction') ?></th>
                                <th><?= t('admin.auditTable') ?></th>
                                <th><?= t('admin.auditRecordId') ?></th>
                                <th><?= t('common.actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= date('M j, Y H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                                    <td>
                                        <span class="action-badge action-<?= strtolower(explode('_', $log['action'])[0]) ?>">
                                            <?= htmlspecialchars(str_replace('_', ' ', $log['action'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['table_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($log['record_id'] ?? '-') ?></td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_record">
                                            <input type="hidden" name="record_id" value="<?= $log['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('<?= t('messages.deleteLogEntryConfirm') ?>');">
                                                <i class="fas fa-trash"></i> <?= t('common.delete') ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (isset($totalPages) && $totalPages > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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