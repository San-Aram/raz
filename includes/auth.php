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

// Function to check session timeout for regular users
function checkUserSessionTimeout() {
    $sessionTimeoutMinutes = (int)getAdminSetting('session_timeout', 30);
    $sessionTimeoutSeconds = $sessionTimeoutMinutes * 60;
    
    if (isset($_SESSION['user_login_time'])) {
        $currentTime = time();
        $sessionAge = $currentTime - $_SESSION['user_login_time'];
        
        // If session has expired
        if ($sessionAge > $sessionTimeoutSeconds) {
            // Set flag to indicate session expired due to timeout
            $_SESSION['session_expired'] = true;
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

// Check if user is logged in (regular manager system)
$isManagerLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Check session timeout for regular users
if ($isManagerLoggedIn && !checkUserSessionTimeout()) {
    $isManagerLoggedIn = false;
}

// Check if admin is logged in
$isAdminLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// If admin is logged in, they can access manager features too
if ($isAdminLoggedIn) {
    // Set compatibility session variables for admin accessing manager interface
    if (!$isManagerLoggedIn) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $_SESSION['admin_username'] ?? 'admin';
        $_SESSION['user_role'] = 'admin'; // Set role to admin for compatibility
    }
} elseif (!$isManagerLoggedIn) {
    // Neither admin nor manager is logged in
    $currentPage = $_SERVER['REQUEST_URI'];
    
    // Check if session expired due to timeout
    $sessionExpired = isset($_SESSION['session_expired']) ? $_SESSION['session_expired'] : false;
    
    // Redirect to appropriate login based on the requested page
    if (strpos($_SERVER['REQUEST_URI'], 'admin-') === 0) {
        header('Location: admin-login.php?redirect=' . urlencode($currentPage));
    } else {
        $errorParam = $sessionExpired ? '&error=session_expired' : '';
        header('Location: login.php?redirect=' . urlencode($currentPage) . $errorParam);
    }
    exit;
}

// Helper function to check if current user is admin
function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Helper function to check if current user is manager
function isManager() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && !isAdmin();
}

// Helper function to get user role
function getUserRole() {
    if (isAdmin()) return 'admin';
    if (isManager()) return 'manager';
    return 'guest';
}
?>
