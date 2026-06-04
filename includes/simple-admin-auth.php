<?php
// Simple admin authentication check - works with any database setup
session_start();

require_once dirname(__FILE__) . '/admin-settings-helper.php';

// Get session timeout from database or use default (30 minutes = 1800 seconds)
$sessionTimeoutMinutes = intval(getSystemSetting('session_timeout', '30'));
define('ADMIN_SESSION_TIMEOUT', max($sessionTimeoutMinutes * 60, 300)); // Minimum 5 minutes

function isAdminLoggedIn() {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['admin_login_time'])) {
        $currentTime = time();
        $sessionAge = $currentTime - $_SESSION['admin_login_time'];
        
        // If session has expired
        if ($sessionAge > ADMIN_SESSION_TIMEOUT) {
            // Clear session
            session_unset();
            session_destroy();
            return false;
        }
        
        // Update last activity time (keep session alive during active use)
        $_SESSION['admin_login_time'] = $currentTime;
    }
    
    return true;
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin-login.php?error=session_expired');
        exit;
    }
}

function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

function getAdminUsername() {
    return $_SESSION['admin_username'] ?? 'Admin';
}

// Simple logout function
if (isset($_GET['logout'])) {
    // Log admin logout
    if (isset($_SESSION['admin_id'])) {
        require_once dirname(__FILE__) . '/database.php';
        require_once dirname(__FILE__) . '/admin-settings-helper.php';
        logAuditEvent('admin_logout', null, null, null, null, $_SESSION['admin_id'], $_SESSION['admin_username'] ?? 'unknown');
    }
    session_destroy();
    header('Location: ../admin-login.php?message=logged_out');
    exit;
}
?>