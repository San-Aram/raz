<?php
require_once 'database.php';

// Get setting value from database or return default
function getSystemSetting($key, $default = '') {
    static $settingsCache = null;
    
    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $database = new Database();
            $db = $database->connect();
            $result = $db->query("
                SELECT setting_key, setting_value 
                FROM admin_settings 
                WHERE 1=1
            ");
            
            if ($result) {
                foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $settingsCache[$row['setting_key']] = $row['setting_value'];
                }
            }
        } catch (Exception $e) {
            // Table might not exist yet
        }
    }
    
    return isset($settingsCache[$key]) ? $settingsCache[$key] : $default;
}

// Set setting value in database
function setSystemSetting($key, $value, $type = 'string') {
    try {
        $database = new Database();
        $db = $database->connect();
        
        // Create table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS admin_settings (
                setting_key VARCHAR(255) PRIMARY KEY,
                setting_value TEXT NOT NULL,
                setting_type VARCHAR(50) DEFAULT 'string',
                description TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        $stmt = $db->prepare("
            INSERT INTO admin_settings (setting_key, setting_value, setting_type) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                setting_type = VALUES(setting_type),
                updated_at = NOW()
        ");
        
        return $stmt->execute([$key, $value, $type]);
    } catch (Exception $e) {
        error_log("Error setting system setting: " . $e->getMessage());
        return false;
    }
}

// Check if maintenance mode is enabled
function isMaintenanceMode() {
    $mode = getSystemSetting('maintenance_mode', '0');
    return $mode === '1' || $mode === 1 || $mode === true;
}

// Get site name
function getSiteName() {
    return getSystemSetting('site_name', 'Razology Pharmacy');
}

// Get session timeout in seconds (converted from minutes if stored as string)
function getSessionTimeout() {
    $timeout = getSystemSetting('session_timeout', '30');
    $minutes = intval($timeout);
    return max($minutes * 60, 300); // Minimum 5 minutes (300 seconds)
}

// Redirect if maintenance mode is enabled (unless user is admin)
function checkMaintenanceMode() {
    if (isMaintenanceMode()) {
        // Check if user is authenticated as admin
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            // Not an admin, show maintenance page
            if (basename($_SERVER['PHP_SELF']) !== 'admin-login.php') {
                header('Location: maintenance.php');
                exit;
            }
        }
    }
}

// Log an audit event
function logAuditEvent($action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null, $userId = null, $username = null) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        // Create audit_logs table if it doesn't exist
        $db->exec("
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
        ");
        
        // If user_id not provided, try to get from session
        if ($userId === null) {
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
            } elseif (isset($_SESSION['admin_id'])) {
                $userId = $_SESSION['admin_id'];
            }
        }
        
        // If username not provided, try to get from session
        if ($username === null) {
            if (isset($_SESSION['username'])) {
                $username = $_SESSION['username'];
            } elseif (isset($_SESSION['admin_username'])) {
                $username = $_SESSION['admin_username'];
            }
        }
        
        // Insert audit log
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, username, action, table_name, record_id, old_values, new_values, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $userId,
            $username,
            $action,
            $tableName,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null
        ]);
    } catch (Exception $e) {
        error_log("Audit logging error: " . $e->getMessage());
        return false;
    }
}
?>
