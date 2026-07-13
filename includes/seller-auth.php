<?php
session_start();
require_once __DIR__ . '/database.php';

// Function to get admin settings
function getAdminSetting($key, $default = null) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        $stmt = $db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Function to check session timeout for sellers
function checkSellerSessionTimeout() {
    $sessionTimeoutMinutes = (int)getAdminSetting('session_timeout', 30);
    $sessionTimeoutSeconds = $sessionTimeoutMinutes * 60;
    
    if (isset($_SESSION['user_login_time'])) {
        $currentTime = time();
        $sessionAge = $currentTime - $_SESSION['user_login_time'];
        
        // If session has expired
        if ($sessionAge > $sessionTimeoutSeconds) {
            // Set a cookie to indicate session expired (since we'll destroy the session)
            setcookie('session_expired', '1', time() + 3600, '/');
            // Clear session
            session_unset();
            session_destroy();
            return false;
        }
        
        // Update last activity time (keep session alive during active use)
        $_SESSION['user_login_time'] = $currentTime;
    }
    
    return true;
}

// Check if user is logged in and has seller role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'seller') {
    // Get current page for redirect after login
    $currentPage = $_SERVER['REQUEST_URI'];
    
    // Check if session expired due to timeout (using cookie since session is destroyed)
    $sessionExpired = isset($_COOKIE['session_expired']) ? $_COOKIE['session_expired'] : false;
    $errorParam = $sessionExpired ? '?error=session_expired' : '';
    
    header('Location: seller-login.php?redirect=' . urlencode($currentPage) . $errorParam);
    exit;
}

// Check session timeout for sellers
if (!checkSellerSessionTimeout()) {
    header('Location: seller-login.php?error=session_expired&redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
?>