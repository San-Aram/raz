<?php
session_start();

// Check if admin is logged in
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Check if regular user is logged in
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

if (!$isLoggedIn && !$isAdmin) {
    header('Location: login.php');
    exit;
}

// Log the logout action if admin
if ($isAdmin) {
    try {
        require_once 'includes/database.php';
        $database = new Database();
        $db = $database->connect();
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, user_role, action, old_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['admin_id'] ?? 0,
            'admin',
            'admin_logout_from_manager_interface',
            json_encode(['logout_time' => date('Y-m-d H:i:s')]),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Admin logout audit logging failed: " . $e->getMessage());
    }
}

// Destroy the entire session to clear both admin and manager sessions
session_destroy();

// Redirect to appropriate login page
if ($isAdmin) {
    header('Location: admin-login.php?success=logout');
} else {
    header('Location: login.php');
}
exit;
?>
