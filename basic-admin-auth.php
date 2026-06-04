<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/admin-settings-helper.php';

// Simple debug mode - remove this after testing
$debug = isset($_GET['debug']);

if ($debug) {
    echo "<h2>🔍 Admin Login Debug Mode</h2>";
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Allow debug mode with GET parameters
    if ($debug && isset($_GET['username']) && isset($_GET['password'])) {
        $_POST['username'] = $_GET['username'];
        $_POST['password'] = $_GET['password'];
    } else {
        if ($debug) {
            echo "<p>❌ Not a POST request and no GET parameters provided</p>";
        }
        header('Location: admin-login.php?error=invalid_request');
        exit;
    }
}

try {
    $database = new Database();
    $db = $database->connect();
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($debug) {
        echo "<p><strong>Username received:</strong> '$username'</p>";
        echo "<p><strong>Password received:</strong> '$password'</p>";
    }
    
    if (empty($username) || empty($password)) {
        if ($debug) {
            echo "<p>❌ Empty username or password</p>";
        }
        logAuditEvent('admin_login_attempt_empty_credentials', null, null, ['username' => $username], null, null, $username);
        header('Location: admin-login.php?error=invalid_credentials');
        exit;
    }
    
    // Check for admin user (try with and without role column)
    try {
        // First try with role column
        $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($debug) {
            echo "<p><strong>Query with role column:</strong> " . ($user ? "Found user" : "No user found") . "</p>";
        }
    } catch (Exception $e) {
        if ($debug) {
            echo "<p><strong>Role column query failed:</strong> " . $e->getMessage() . "</p>";
        }
        
        // Fallback: try without role column (for basic setup)
        $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($debug) {
            echo "<p><strong>Query without role column:</strong> " . ($user ? "Found user" : "No user found") . "</p>";
        }
    }
    
    if (!$user) {
        if ($debug) {
            echo "<p>❌ No admin user found with username '$username'</p>";
            echo "<p><a href='basic_admin_setup.php'>Create Admin User</a></p>";
        }
        logAuditEvent('admin_login_attempt_invalid_user', null, null, ['username' => $username], null, null, $username);
        header('Location: admin-login.php?error=invalid_credentials');
        exit;
    }
    
    // Verify password
    $passwordValid = password_verify($password, $user['password']);
    
    if ($debug) {
        echo "<p><strong>Password verification:</strong> " . ($passwordValid ? "✅ PASSED" : "❌ FAILED") . "</p>";
        if ($passwordValid) {
            echo "<p><strong>Login would be successful!</strong></p>";
        }
    }
    
    if (!$passwordValid) {
        if ($debug) {
            echo "<p>❌ Password verification failed</p>";
        }
        logAuditEvent('admin_login_attempt_failed', null, null, ['username' => $username], null, null, $username);
        header('Location: admin-login.php?error=invalid_credentials');
        exit;
    }
    
    // Successful login
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['user_role'] = 'admin';
    $_SESSION['admin_login_time'] = time();
    
    // Log successful admin login
    logAuditEvent('admin_login_success', 'users', $user['id'], null, ['username' => $user['username']], $user['id'], $user['username']);
    
    if ($debug) {
        echo "<div style='background: #d4edda; padding: 1rem; border-radius: 4px; color: #155724; margin: 1rem 0;'>";
        echo "<h3>✅ Login Successful!</h3>";
        echo "<p>Session variables set:</p>";
        echo "<ul>";
        echo "<li>admin_logged_in: true</li>";
        echo "<li>admin_id: {$user['id']}</li>";
        echo "<li>admin_username: {$user['username']}</li>";
        echo "<li>user_role: admin</li>";
        echo "</ul>";
        echo "<p><a href='admin-dashboard.php'>Go to Admin Dashboard</a></p>";
        echo "</div>";
        exit; // Don't redirect in debug mode
    }
    
    // Redirect to admin dashboard
    header('Location: admin-dashboard.php');
    exit;
    
} catch (Exception $e) {
    if ($debug) {
        echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
        echo "<strong>Error:</strong> " . $e->getMessage();
        echo "</div>";
        exit;
    }
    
    logAuditEvent('admin_login_system_error', null, null, ['error' => $e->getMessage()], null, null, $username ?? 'unknown');
    error_log("Admin authentication error: " . $e->getMessage());
    header('Location: admin-login.php?error=system_error');
    exit;
}
?>
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    max-width: 800px; 
    margin: 0 auto; 
    padding: 2rem; 
    background: #f8f9fa; 
}
h2 { color: #333; }
</style>