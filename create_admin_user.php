<?php
require_once 'includes/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "<h2>🔧 Manual Admin User Creation</h2>";
    
    // Delete existing admin users to start fresh
    $db->exec("DELETE FROM users WHERE username = 'admin' OR role = 'admin'");
    echo "Cleared existing admin users...<br>";
    
    // Create new admin user with known credentials
    $username = 'admin';
    $password = 'admin123';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("
        INSERT INTO users (username, password, role, account_locked, login_attempts, created_at, updated_at) 
        VALUES (?, ?, 'admin', 0, 0, NOW(), NOW())
    ");
    
    $result = $stmt->execute([$username, $hashedPassword]);
    
    if ($result) {
        $adminId = $db->lastInsertId();
        echo "<div style='background: #d4edda; padding: 1.5rem; border-radius: 8px; color: #155724; margin: 1rem 0;'>";
        echo "<h3>✅ Admin User Created Successfully!</h3>";
        echo "<p><strong>User ID:</strong> $adminId</p>";
        echo "<p><strong>Username:</strong> $username</p>";
        echo "<p><strong>Password:</strong> $password</p>";
        echo "<p><strong>Role:</strong> admin</p>";
        echo "<hr>";
        echo "<h4>🚀 Login Instructions:</h4>";
        echo "<ol>";
        echo "<li>Go to: <a href='admin-login.php' style='color: #155724; font-weight: bold;'>Admin Login Page</a></li>";
        echo "<li>Enter username: <code style='background: #c3e6cb; padding: 0.2rem 0.4rem; border-radius: 3px;'>admin</code></li>";
        echo "<li>Enter password: <code style='background: #c3e6cb; padding: 0.2rem 0.4rem; border-radius: 3px;'>admin123</code></li>";
        echo "<li>Click 'Access Admin Panel'</li>";
        echo "</ol>";
        echo "</div>";
        
        // Verify the password works
        echo "<h3>🔍 Password Verification Test</h3>";
        if (password_verify($password, $hashedPassword)) {
            echo "<span style='color: green;'>✅ Password verification successful!</span><br>";
        } else {
            echo "<span style='color: red;'>❌ Password verification failed!</span><br>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
        echo "❌ Failed to create admin user.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    max-width: 800px; 
    margin: 0 auto; 
    padding: 2rem; 
    background: #f8f9fa; 
}
h2, h3 { color: #333; }
code { 
    background: #e9ecef; 
    padding: 0.2rem 0.4rem; 
    border-radius: 3px; 
    font-family: 'Courier New', monospace;
}
</style>