<?php
// Admin Authentication Middleware
// Include this file at the top of any admin-only pages

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAdminAuth() {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_logged_in']) || 
        $_SESSION['admin_logged_in'] !== true || 
        !isset($_SESSION['admin_id']) || 
        $_SESSION['user_role'] !== 'admin') {
        
        // Log unauthorized access attempt
        if (file_exists(__DIR__ . '/database.php')) {
            try {
                require_once __DIR__ . '/database.php';
                $database = new Database();
                $db = $database->connect();
                
                $stmt = $db->prepare("
                    INSERT INTO audit_logs (user_id, user_role, action, old_values, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['admin_id'] ?? 0,
                    $_SESSION['user_role'] ?? 'unknown',
                    'unauthorized_admin_access_attempt',
                    json_encode(['requested_page' => $_SERVER['REQUEST_URI'] ?? 'unknown']),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
            } catch (Exception $e) {
                error_log("Admin auth audit logging failed: " . $e->getMessage());
            }
        }
        
        // Redirect to admin login
        header('Location: admin-login.php?error=access_denied');
        exit;
    }
    
    // Check session timeout (4 hours for admin)
    $sessionTimeout = 4 * 60 * 60; // 4 hours in seconds
    if (isset($_SESSION['admin_login_time']) && 
        (time() - $_SESSION['admin_login_time']) > $sessionTimeout) {
        
        // Log session timeout
        if (file_exists(__DIR__ . '/database.php')) {
            try {
                require_once __DIR__ . '/database.php';
                $database = new Database();
                $db = $database->connect();
                
                $stmt = $db->prepare("
                    INSERT INTO audit_logs (user_id, user_role, action, old_values, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['admin_id'],
                    'admin',
                    'admin_session_timeout',
                    json_encode(['session_duration' => time() - $_SESSION['admin_login_time']]),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
            } catch (Exception $e) {
                error_log("Admin session timeout audit logging failed: " . $e->getMessage());
            }
        }
        
        // Clear admin session
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_login_time']);
        
        header('Location: admin-login.php?error=session_timeout');
        exit;
    }
    
    // Update last activity time
    $_SESSION['admin_last_activity'] = time();
    
    return true;
}

function getAdminUser() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    try {
        require_once __DIR__ . '/database.php';
        $database = new Database();
        $db = $database->connect();
        
        $stmt = $db->prepare("
            SELECT id, username, role, last_login, created_at 
            FROM users 
            WHERE id = ? AND role = 'admin'
        ");
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get admin user failed: " . $e->getMessage());
        return null;
    }
}

function logAdminAction($action, $details = null, $tableAffected = null, $recordId = null) {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    try {
        require_once __DIR__ . '/database.php';
        $database = new Database();
        $db = $database->connect();
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs 
            (user_id, user_role, action, table_affected, record_id, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['admin_id'],
            'admin',
            $action,
            $tableAffected,
            $recordId,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Admin action logging failed: " . $e->getMessage());
        return false;
    }
}

function hasAdminPermission($permission = null) {
    // For now, all admin users have all permissions
    // This can be extended later for role-based permissions within admin
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function getSystemSetting($key, $default = null) {
    try {
        require_once __DIR__ . '/database.php';
        $database = new Database();
        $db = $database->connect();
        
        $stmt = $db->prepare("SELECT setting_value, setting_type FROM admin_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $setting = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$setting) {
            return $default;
        }
        
        // Convert based on type
        switch ($setting['setting_type']) {
            case 'boolean':
                return filter_var($setting['setting_value'], FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return intval($setting['setting_value']);
            case 'json':
                return json_decode($setting['setting_value'], true);
            default:
                return $setting['setting_value'];
        }
    } catch (Exception $e) {
        error_log("Get system setting failed: " . $e->getMessage());
        return $default;
    }
}

function setSystemSetting($key, $value, $type = 'string') {
    if (!hasAdminPermission()) {
        return false;
    }
    
    try {
        require_once __DIR__ . '/database.php';
        $database = new Database();
        $db = $database->connect();
        
        // Convert value based on type
        switch ($type) {
            case 'boolean':
                $value = $value ? 'true' : 'false';
                break;
            case 'integer':
                $value = strval(intval($value));
                break;
            case 'json':
                $value = json_encode($value);
                break;
            default:
                $value = strval($value);
        }
        
        $stmt = $db->prepare("
            UPDATE admin_settings 
            SET setting_value = ?, setting_type = ?, updated_at = NOW() 
            WHERE setting_key = ?
        ");
        $result = $stmt->execute([$value, $type, $key]);
        
        if ($result) {
            logAdminAction('system_setting_updated', [
                'setting_key' => $key,
                'new_value' => $value,
                'type' => $type
            ], 'admin_settings');
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Set system setting failed: " . $e->getMessage());
        return false;
    }
}

// Call this function at the top of admin pages
function initAdminPage($pageTitle = 'Admin Panel') {
    requireAdminAuth();
    
    // Update page access log
    logAdminAction('admin_page_access', [
        'page' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'page_title' => $pageTitle
    ]);
    
    return getAdminUser();
}
?>