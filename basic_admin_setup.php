<?php
require_once 'includes/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "<h2>🔧 Basic Admin Setup (No Migration Required)</h2>";
    
    // First, let's see what the current users table looks like
    echo "<h3>1. Current Users Table Structure</h3>";
    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 0.5rem 0;'>";
    echo "Existing columns in users table:<br>";
    foreach ($columns as $column) {
        echo "• <strong>{$column['Field']}</strong> - {$column['Type']}<br>";
    }
    echo "</div>";
    
    // Check if role column exists
    $hasRoleColumn = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'role') {
            $hasRoleColumn = true;
            break;
        }
    }
    
    // Add role column if it doesn't exist
    if (!$hasRoleColumn) {
        echo "<h3>2. Adding Role Column</h3>";
        try {
            $db->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'manager' AFTER password");
            echo "✅ Role column added successfully<br>";
        } catch (Exception $e) {
            echo "❌ Failed to add role column: " . $e->getMessage() . "<br>";
            echo "Manual SQL: ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'manager' AFTER password;<br>";
        }
    } else {
        echo "<h3>2. Role Column Status</h3>";
        echo "✅ Role column already exists<br>";
    }
    
    echo "<h3>3. Creating Admin User</h3>";
    
    // Clear any existing admin users
    $db->exec("DELETE FROM users WHERE username = 'admin' OR role = 'admin'");
    echo "Cleared existing admin users...<br>";
    
    // Create admin user
    $username = 'admin';
    $password = 'admin123';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $result = $stmt->execute([$username, $hashedPassword]);
    
    if ($result) {
        $adminId = $db->lastInsertId();
        
        echo "<div style='background: #d4edda; padding: 1.5rem; border-radius: 8px; color: #155724; margin: 1rem 0; border: 2px solid #c3e6cb;'>";
        echo "<h3>🎉 Admin User Created Successfully!</h3>";
        echo "<p><strong>User ID:</strong> $adminId</p>";
        echo "<p><strong>Username:</strong> <code style='background: #c3e6cb; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 1.1em;'>admin</code></p>";
        echo "<p><strong>Password:</strong> <code style='background: #c3e6cb; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 1.1em;'>admin123</code></p>";
        echo "<p><strong>Role:</strong> admin</p>";
        echo "</div>";
        
        // Test password verification
        echo "<h3>4. Testing Password</h3>";
        if (password_verify($password, $hashedPassword)) {
            echo "<div style='background: #d1ecf1; padding: 1rem; border-radius: 4px; color: #0c5460; border: 1px solid #bee5eb;'>";
            echo "✅ Password verification test: <strong>PASSED</strong><br>";
            echo "The login credentials are working correctly.";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
            echo "❌ Password verification test: FAILED";
            echo "</div>";
        }
        
        // Show final verification
        echo "<h3>5. Database Verification</h3>";
        $verifyStmt = $db->prepare("SELECT id, username, role FROM users WHERE username = 'admin'");
        $verifyStmt->execute();
        $adminUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($adminUser) {
            echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 4px; border: 1px solid #dee2e6;'>";
            echo "Admin user in database:<br>";
            echo "<strong>ID:</strong> {$adminUser['id']}<br>";
            echo "<strong>Username:</strong> {$adminUser['username']}<br>";
            echo "<strong>Role:</strong> {$adminUser['role']}<br>";
            echo "</div>";
        }
        
        echo "<div style='background: #e7f3ff; padding: 1.5rem; border-radius: 8px; color: #004085; margin: 2rem 0; border: 2px solid #b3d7ff;'>";
        echo "<h3>🚀 Ready to Login!</h3>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ol style='margin: 0; padding-left: 1.5rem;'>";
        echo "<li>Click here: <a href='admin-login.php' style='background: #007bff; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; font-weight: bold;'>Admin Login Page</a></li>";
        echo "<li>Enter <strong>admin</strong> as username</li>";
        echo "<li>Enter <strong>admin123</strong> as password</li>";
        echo "<li>Click 'Access Admin Panel'</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
        echo "❌ Failed to create admin user.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
    echo "<strong>Database Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    max-width: 900px; 
    margin: 0 auto; 
    padding: 2rem; 
    background: #f8f9fa; 
    line-height: 1.6;
}
h2, h3 { 
    color: #333; 
    margin-top: 2rem;
    margin-bottom: 1rem;
}
h2 {
    border-bottom: 3px solid #007bff;
    padding-bottom: 0.5rem;
}
code { 
    background: #e9ecef; 
    padding: 0.2rem 0.4rem; 
    border-radius: 3px; 
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
}
</style>