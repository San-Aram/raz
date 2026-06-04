<?php
require_once 'includes/database.php';

echo "<h2>🔍 Admin User Debug</h2>";

try {
    $database = new Database();
    $db = $database->connect();
    
    // Check if admin user exists
    echo "<h3>1. Checking Admin User</h3>";
    $stmt = $db->prepare("SELECT id, username, password, role, account_locked, login_attempts FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($adminUsers)) {
        echo "❌ No admin users found!<br>";
        echo "<strong>Creating admin user now...</strong><br>";
        
        // Create admin user
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $createStmt = $db->prepare("INSERT INTO users (username, password, role, created_at) VALUES ('admin', ?, 'admin', NOW())");
        $result = $createStmt->execute([$adminPassword]);
        
        if ($result) {
            echo "✅ Admin user created successfully!<br>";
            echo "<strong>Username:</strong> admin<br>";
            echo "<strong>Password:</strong> admin123<br>";
        } else {
            echo "❌ Failed to create admin user<br>";
        }
    } else {
        echo "✅ Found " . count($adminUsers) . " admin user(s):<br>";
        foreach ($adminUsers as $admin) {
            echo "<div style='background: #f8f9fa; padding: 1rem; margin: 0.5rem 0; border-radius: 6px;'>";
            echo "<strong>ID:</strong> {$admin['id']}<br>";
            echo "<strong>Username:</strong> {$admin['username']}<br>";
            echo "<strong>Role:</strong> {$admin['role']}<br>";
            echo "<strong>Account Locked:</strong> " . ($admin['account_locked'] ? 'Yes' : 'No') . "<br>";
            echo "<strong>Login Attempts:</strong> {$admin['login_attempts']}<br>";
            echo "<strong>Password Hash:</strong> " . substr($admin['password'], 0, 30) . "...<br>";
            echo "</div>";
        }
    }
    
    // Test password verification
    echo "<h3>2. Testing Password Verification</h3>";
    if (!empty($adminUsers)) {
        $admin = $adminUsers[0];
        $testPassword = 'admin123';
        
        if (password_verify($testPassword, $admin['password'])) {
            echo "✅ Password 'admin123' is correct for user '{$admin['username']}'<br>";
        } else {
            echo "❌ Password 'admin123' does NOT match for user '{$admin['username']}'<br>";
            echo "Let's reset the password...<br>";
            
            // Reset password
            $newPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $resetStmt = $db->prepare("UPDATE users SET password = ?, login_attempts = 0, account_locked = 0 WHERE id = ?");
            $resetResult = $resetStmt->execute([$newPassword, $admin['id']]);
            
            if ($resetResult) {
                echo "✅ Password reset successfully! You can now login with 'admin123'<br>";
            } else {
                echo "❌ Failed to reset password<br>";
            }
        }
    }
    
    // Check tables exist
    echo "<h3>3. Checking Required Tables</h3>";
    $requiredTables = ['users', 'admin_settings', 'audit_logs'];
    foreach ($requiredTables as $table) {
        $tableCheck = $db->query("SHOW TABLES LIKE '$table'");
        if ($tableCheck->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    }
    
    // Check admin settings
    echo "<h3>4. Admin Settings Check</h3>";
    $settingsCount = $db->query("SELECT COUNT(*) FROM admin_settings")->fetchColumn();
    echo "Admin settings records: $settingsCount<br>";
    
    echo "<br><div style='background: #d4edda; padding: 1rem; border-radius: 6px; color: #155724;'>";
    echo "<h4>✅ Admin Login Instructions:</h4>";
    echo "<ol>";
    echo "<li>Go to: <a href='admin-login.php' target='_blank'>admin-login.php</a></li>";
    echo "<li>Username: <strong>admin</strong></li>";
    echo "<li>Password: <strong>admin123</strong></li>";
    echo "<li>Click 'Access Admin Panel'</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
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
</style>