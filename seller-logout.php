<?php
session_start();

if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'seller') {
    require_once 'includes/admin-settings-helper.php';
    logAuditEvent('seller_logout', 'users', $_SESSION['user_id'], null, [
        'logout_time' => date('Y-m-d H:i:s')
    ], $_SESSION['user_id'], $_SESSION['username'] ?? 'unknown');
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to seller login
header('Location: seller-login.php');
exit;
?>
