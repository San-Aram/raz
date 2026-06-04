<?php
require_once 'includes/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "<h2>🔧 Simple Admin User Creation</h2>";
    
    // First, let's check what columns exist in the users table
    echo "<h3>1. Checking Users Table Structure</h3>";
    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "Current users table columns:<br>";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>";
    }
    
    echo "<h3>2. Creating Admin User</h3>";
    
    // Delete existing admin users to start fresh
    $db->exec("DELETE FROM users WHERE username = 'admin'");
    echo "Cleared existing admin users...<br>";
    
    // Create new admin user with basic structure
    $username = 'admin';
    $password = 'admin123';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if role column exists, if not add it
    $roleColumnExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'role') {
            $roleColumnExists = true;
            break;
        }
    }
    
    if (!$roleColumnExists) {
        echo "Adding role column to users table...<br>";
        $db->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'manager', 'seller') DEFAULT 'manager' AFTER password");
    }
    
    // Insert admin user with available columns
    if ($roleColumnExists) {
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    } else {
        $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    }
    
    $result = $stmt->execute([$username, $hashedPassword]);
    
    if ($result) {
        $adminId = $db->lastInsertId();
        
        // If role column didn't exist before, update the user to admin
        if (!$roleColumnExists) {
            $db->exec("UPDATE users SET role = 'admin' WHERE id = $adminId");
        }
        
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
        
        // Show final user data
        echo "<h3>3. Final User Verification</h3>";
        $verifyStmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
        $verifyStmt->execute();
        $adminUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($adminUser) {
            echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 0.5rem 0;'>";
            foreach ($adminUser as $key => $value) {
                if ($key !== 'password') {
                    echo "<strong>$key:</strong> $value<br>";
                } else {
                    echo "<strong>$key:</strong> [hidden]<br>";
                }
            }
            echo "</div>";
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