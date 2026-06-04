<?php
require_once 'includes/database.php';

echo "<h2>🔧 Create Audit Logs Table (Optional)</h2>";

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
    echo "<strong>Creating audit_logs table...</strong><br>";
    
    // Create audit_logs table
    $sql = "
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
    
    $db->exec($sql);
    echo "<span style='color: green;'>✅ audit_logs table created successfully!</span><br>";
    echo "</div>";
    
    echo "<div style='background: #d1ecf1; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
    echo "<strong>Adding sample audit log entries...</strong><br>";
    
    // Add some sample logs
    $sampleLogs = [
        ['action' => 'admin_login', 'table_name' => null, 'record_id' => null],
        ['action' => 'user_created', 'table_name' => 'users', 'record_id' => 1],
        ['action' => 'medication_added', 'table_name' => 'medications', 'record_id' => null],
        ['action' => 'product_updated', 'table_name' => 'products', 'record_id' => null],
        ['action' => 'system_backup', 'table_name' => null, 'record_id' => null]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, '127.0.0.1', 'Admin Setup Script', NOW() - INTERVAL ? HOUR)
    ");
    
    foreach ($sampleLogs as $index => $log) {
        $stmt->execute([1, $log['action'], $log['table_name'], $log['record_id'], $index]);
    }
    
    echo "<span style='color: green;'>✅ Sample audit logs added!</span><br>";
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
    echo "<strong>Verification...</strong><br>";
    
    // Verify table exists and has data
    $count = $db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    echo "<span style='color: green;'>✅ Audit logs table created with {$count} sample entries!</span><br>";
    echo "</div>";
    
    echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0; border-left: 4px solid #007bff;'>";
    echo "<h3>🎉 Audit Logging Ready!</h3>";
    echo "<p><strong>You can now:</strong></p>";
    echo "<ul>";
    echo "<li><a href='admin-dashboard.php' style='color: #007bff;'>📊 View Admin Dashboard with Activity Log</a></li>";
    echo "<li><a href='admin-logs.php' style='color: #28a745;'>📝 View Full Audit Log</a></li>";
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