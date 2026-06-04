<?php
require_once 'includes/simple-admin-auth.php';
requireAdminLogin();
require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();

echo "<h2>🔧 Fix Audit Logs Table</h2>";

try {
    // Create the audit_logs table
    $createTable = "
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(100) NOT NULL,
            table_name VARCHAR(50) NULL,
            record_id INT NULL,
            old_values TEXT NULL,
            new_values TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ";
    $db->exec($createTable);
    
    echo "<div style='background: #d4edda; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
    echo "✅ Audit logs table created successfully!<br>";
    
    // Add some sample logs
    $sampleLogs = [
        ['action' => 'admin_login', 'table_name' => null, 'record_id' => null],
        ['action' => 'audit_table_created', 'table_name' => 'audit_logs', 'record_id' => null],
        ['action' => 'system_test', 'table_name' => null, 'record_id' => null],
        ['action' => 'admin_dashboard_access', 'table_name' => null, 'record_id' => null],
        ['action' => 'settings_updated', 'table_name' => 'admin_settings', 'record_id' => null]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? HOUR)
    ");
    
    foreach ($sampleLogs as $index => $log) {
        $stmt->execute([
            getAdminId(), 
            $log['action'], 
            $log['table_name'], 
            $log['record_id'],
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Admin Setup Script',
            $index
        ]);
    }
    
    echo "✅ Sample audit logs added!<br>";
    
    // Test the query
    $count = $db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    echo "✅ Total audit logs: {$count}<br>";
    
    echo "</div>";
    
    echo "<div style='background: #d1ecf1; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
    echo "<h3>🎉 Ready to Use!</h3>";
    echo "<a href='simple-admin-logs.php' style='color: #007bff; text-decoration: none; font-weight: bold;'>📝 View Audit Logs</a><br><br>";
    echo "<a href='admin-dashboard.php' style='color: #007bff; text-decoration: none;'>📊 Back to Dashboard</a>";
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
</style>