<?php
require_once 'includes/database.php';

echo "<h2>🔧 Fix Admin Role - Add 'admin' to Role Enum</h2>";

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
    echo "<strong>Step 1:</strong> Checking current role column structure...<br>";
    
    // Check current enum values
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "<strong>Current role column type:</strong> {$column['Type']}<br>";
        
        // Check if 'admin' is already in the enum
        if (strpos($column['Type'], 'admin') !== false) {
            echo "<span style='color: green;'>✅ 'admin' role already exists in enum!</span><br>";
        } else {
            echo "<span style='color: orange;'>⚠️ 'admin' role missing from enum. Adding it...</span><br>";
            
            // Add 'admin' to the enum
            $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('manager', 'seller', 'admin') DEFAULT 'seller'";
            $db->exec($sql);
            
            echo "<span style='color: green;'>✅ Successfully added 'admin' to role enum!</span><br>";
        }
    } else {
        echo "<span style='color: red;'>❌ Role column not found!</span><br>";
    }
    echo "</div>";
    
    echo "<div style='background: #d1ecf1; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
    echo "<strong>Step 2:</strong> Creating admin user...<br>";
    
    // Clear any existing admin users first
    $stmt = $db->prepare("DELETE FROM users WHERE username = 'admin'");
    $stmt->execute();
    echo "Cleared existing admin users...<br>";
    
    // Create admin user
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $fullName = 'System Administrator';
    $email = 'admin@pharmacy.local';
    
    $stmt = $db->prepare("
        INSERT INTO users (username, password, role, full_name, email, is_active, created_at) 
        VALUES (?, ?, 'admin', ?, ?, 1, NOW())
    ");
    
    $result = $stmt->execute([$username, $password, $fullName, $email]);
    
    if ($result) {
        echo "<span style='color: green;'>✅ Admin user created successfully!</span><br>";
        echo "<strong>Login Credentials:</strong><br>";
        echo "• Username: <code>admin</code><br>";
        echo "• Password: <code>admin123</code><br>";
    } else {
        echo "<span style='color: red;'>❌ Failed to create admin user!</span><br>";
    }
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
    echo "<strong>Step 3:</strong> Verification...<br>";
    
    // Verify the admin user exists
    $stmt = $db->prepare("SELECT id, username, role, full_name, created_at FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($adminUsers)) {
        echo "<span style='color: green;'>✅ Admin user verification successful!</span><br>";
        foreach ($adminUsers as $user) {
            echo "• ID: {$user['id']}, Username: {$user['username']}, Role: {$user['role']}, Name: {$user['full_name']}<br>";
        }
    } else {
        echo "<span style='color: red;'>❌ No admin users found in database!</span><br>";
    }
    echo "</div>";
    
    echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0; border-left: 4px solid #007bff;'>";
    echo "<h3>🎉 Setup Complete!</h3>";
    echo "<p><strong>You can now:</strong></p>";
    echo "<ul>";
    echo "<li><a href='admin-login.php' style='color: #007bff;'>🔐 Login to Admin Panel</a></li>";
    echo "<li><a href='admin-dashboard.php' style='color: #28a745;'>📊 Access Admin Dashboard</a></li>";
    echo "<li><a href='index.php' style='color: #6c757d;'>🏠 Return to Manager Dashboard</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    max-width: 800px; 
    margin: 0 auto; 
    padding: 2rem; 
    background: #f8f9fa; 
}
h2, h3 { color: #333; }
code { 
    background: #f1f3f4; 
    padding: 2px 6px; 
    border-radius: 3px; 
    font-family: 'Courier New', monospace; 
}
a { text-decoration: none; }
a:hover { text-decoration: underline; }
</style>