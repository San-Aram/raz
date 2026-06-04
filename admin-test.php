<?php
require_once 'includes/simple-admin-auth.php';
requireAdminLogin();
require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();

echo "<h2>🔧 Admin System Status Check</h2>";

// Test 1: Database Connection
echo "<div style='background: #d1ecf1; padding: 1rem; margin: 1rem 0; border-radius: 4px;'>";
echo "<strong>✅ Database Connection:</strong> Working<br>";

// Test 2: User Management
try {
    $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<strong>✅ User Management:</strong> {$userCount} users found<br>";
} catch (Exception $e) {
    echo "<strong>❌ User Management:</strong> " . $e->getMessage() . "<br>";
}

// Test 3: Admin Settings Table
try {
    $db->exec("CREATE TABLE IF NOT EXISTS admin_settings (setting_key VARCHAR(255) PRIMARY KEY, setting_value TEXT NOT NULL)");
    echo "<strong>✅ Admin Settings:</strong> Table ready<br>";
} catch (Exception $e) {
    echo "<strong>❌ Admin Settings:</strong> " . $e->getMessage() . "<br>";
}

// Test 4: Audit Logs Table
try {
    $tables = $db->query("SHOW TABLES LIKE 'audit_logs'")->fetchAll();
    if (!empty($tables)) {
        echo "<strong>✅ Audit Logs:</strong> Table exists<br>";
    } else {
        echo "<strong>⚠️ Audit Logs:</strong> Table not created yet (will be created when needed)<br>";
    }
} catch (Exception $e) {
    echo "<strong>❌ Audit Logs:</strong> " . $e->getMessage() . "<br>";
}

// Test 5: Authentication
echo "<strong>✅ Authentication:</strong> Admin session active<br>";
echo "<strong>Admin User:</strong> " . ($_SESSION['admin_username'] ?? 'Unknown') . "<br>";

echo "</div>";

echo "<div style='background: #d4edda; padding: 1rem; margin: 1rem 0; border-radius: 4px;'>";
echo "<h3>🎉 Quick Links</h3>";
echo "<a href='admin-dashboard.php' style='margin-right: 1rem;'>📊 Dashboard</a>";
echo "<a href='simple-admin-users.php' style='margin-right: 1rem;'>👥 Users</a>";
echo "<a href='simple-admin-settings.php' style='margin-right: 1rem;'>⚙️ Settings</a>";
echo "<a href='simple-admin-logs.php' style='margin-right: 1rem;'>📝 Logs</a>";
echo "<a href='backup.php'>💾 Backup</a>";
echo "</div>";

?>

<style>
body { 
    font-family: Arial, sans-serif; 
    max-width: 800px; 
    margin: 0 auto; 
    padding: 2rem; 
    background: #f8f9fa; 
}
a { 
    color: #007bff; 
    text-decoration: none; 
    padding: 0.5rem 1rem; 
    background: white; 
    border-radius: 4px; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
}
a:hover { background: #e9ecef; }
</style>