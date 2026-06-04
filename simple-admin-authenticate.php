<?php
session_start();
require_once 'includes/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-login.php?error=invalid_request');
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        header('Location: admin-login.php?error=invalid_credentials');
        exit;
    }
    
    // Check for user and verify admin role
    $stmt = $db->prepare("
        SELECT id, username, password, role 
        FROM users 
        WHERE username = ? AND role = 'admin'
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: admin-login.php?error=invalid_credentials');
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        header('Location: admin-login.php?error=invalid_credentials');
        exit;
    }
    
    // Successful login - set admin session variables
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['user_role'] = 'admin';
    $_SESSION['admin_login_time'] = time();
    
    // Try to log the login (optional - won't fail if audit table doesn't exist)
    try {
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, user_role, action, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            'admin',
            'admin_login_success',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Audit logging failed but login should still work
        error_log("Admin audit logging failed: " . $e->getMessage());
    }
    
    // Redirect to admin dashboard
    header('Location: admin-dashboard.php');
    exit;
    
} catch (Exception $e) {
    error_log("Admin authentication error: " . $e->getMessage());
    header('Location: admin-login.php?error=system_error');
    exit;
}
?>