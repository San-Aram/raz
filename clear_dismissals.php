<?php
session_start();
require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();

echo "<h2>Clear All Dismissals</h2>";

try {
    // Clear all dismissed notifications
    $stmt1 = $db->query("DELETE FROM dismissed_notifications");
    echo "✅ Cleared " . $stmt1->rowCount() . " dismissed notification records<br>";
    
    // Clear dismiss all settings
    $stmt2 = $db->query("UPDATE user_notification_settings SET dismiss_all_until = NULL WHERE user_id = 1");
    echo "✅ Cleared dismiss all settings<br>";
    
    echo "<br><div style='background: #d4edda; padding: 1rem; border-radius: 4px; color: #155724;'>";
    echo "<strong>All dismissals cleared! Notifications should appear again.</strong><br>";
    echo "<a href='index.php'>← Back to Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 2rem; }
</style>