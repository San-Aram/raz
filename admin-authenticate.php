<?php
session_start();
require_once 'includes/database.php';

// Function to log audit events
function logAuditEvent($db, $userId, $userRole, $action, $details = null) {
    try {
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, user_role, action, old_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId ?: 0,
            $userRole ?: 'unknown',
            $action,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Audit logging failed: " . $e->getMessage());
    }
}

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
        logAuditEvent($db, null, null, 'admin_login_attempt_empty_credentials', ['username' => $username]);
        header('Location: admin-login.php?error=invalid_credentials');
        exit;
    }
    
    // Check for user and verify admin role
    $stmt = $db->prepare("
        SELECT id, username, password, role, login_attempts, account_locked, last_login 
        FROM users 
        WHERE username = ? AND role = 'admin'
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        logAuditEvent($db, null, null, 'admin_login_attempt_invalid_user', ['username' => $username]);
        header('Location: admin-login.php?error=invalid_credentials');
        exit;
    }
    
    // Check if account is locked
    if ($user['account_locked']) {
        logAuditEvent($db, $user['id'], 'admin', 'admin_login_attempt_locked_account', ['username' => $username]);
        header('Location: admin-login.php?error=account_locked');
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Increment login attempts
        $newAttempts = $user['login_attempts'] + 1;
        $lockAccount = $newAttempts >= 5; // Lock after 5 failed attempts
        
        $stmt = $db->prepare("
            UPDATE users 
            SET login_attempts = ?, account_locked = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$newAttempts, $lockAccount, $user['id']]);
        
        logAuditEvent($db, $user['id'], 'admin', 'admin_login_failed', [
            'username' => $username,
            'attempts' => $newAttempts,
            'locked' => $lockAccount
        ]);
        
        if ($lockAccount) {
            header('Location: admin-login.php?error=account_locked');
        } else {
            header('Location: admin-login.php?error=invalid_credentials');
        }
        exit;
    }
    
    // Successful login - reset attempts and update last login
    $stmt = $db->prepare("
        UPDATE users 
        SET login_attempts = 0, account_locked = FALSE, last_login = NOW(), updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    
    // Set admin session variables
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['user_role'] = 'admin';
    $_SESSION['admin_login_time'] = time();
    
    // Log successful login
    logAuditEvent($db, $user['id'], 'admin', 'admin_login_success', [
        'username' => $username,
        'last_login' => $user['last_login']
    ]);
    
    // Redirect to admin dashboard
    header('Location: admin-dashboard.php');
    exit;
    
} catch (Exception $e) {
    error_log("Admin authentication error: " . $e->getMessage());
    logAuditEvent($db ?? null, null, null, 'admin_login_system_error', ['error' => $e->getMessage()]);
    header('Location: admin-login.php?error=system_error');
    exit;
}
?>