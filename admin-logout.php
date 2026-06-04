<?php
session_start();

// Log the logout action before clearing session
if (isset($_SESSION['admin_id'])) {
    try {
        require_once 'includes/database.php';
        $database = new Database();
        $db = $database->connect();
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, user_role, action, old_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['admin_id'],
            'admin',
            'admin_logout',
            json_encode(['logout_time' => date('Y-m-d H:i:s')]),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Admin logout audit logging failed: " . $e->getMessage());
    }
}

// Clear admin session variables
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_login_time']);
unset($_SESSION['admin_last_activity']);

// If user_role was set to admin, clear it
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    unset($_SESSION['user_role']);
}

// Redirect to admin login with success message
header('Location: admin-login.php?success=logout');
exit;
?>