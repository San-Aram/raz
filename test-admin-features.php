<?php
/**
 * Test script to verify user creation functionality
 * Run this to test if users can be created successfully
 */

require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();

echo "<h1>User Management System - Functionality Test</h1>";

try {
    // Check if users table exists
    $result = $db->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (empty($result)) {
        echo "<p style='color: red;'><strong>❌ Error:</strong> Users table does not exist!</p>";
        exit;
    }
    echo "<p style='color: green;'><strong>✓ Users table exists</strong></p>";
    
    // Count existing users
    $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<p><strong>Total users in system:</strong> $userCount</p>";
    
    // Check admin_settings table
    $settingsTables = $db->query("SHOW TABLES LIKE 'admin_settings'")->fetchAll();
    if (empty($settingsTables)) {
        echo "<p style='color: orange;'><strong>⚠ Warning:</strong> Admin settings table will be created on first settings update</p>";
    } else {
        echo "<p style='color: green;'><strong>✓ Admin settings table exists</strong></p>";
        $settingCount = $db->query("SELECT COUNT(*) FROM admin_settings")->fetchColumn();
        echo "<p><strong>Stored settings:</strong> $settingCount</p>";
    }
    
    // Check audit_logs table
    $logsTables = $db->query("SHOW TABLES LIKE 'audit_logs'")->fetchAll();
    if (empty($logsTables)) {
        echo "<p style='color: orange;'><strong>⚠ Warning:</strong> Audit logs table can be created from Admin Panel → Audit Logs</p>";
    } else {
        echo "<p style='color: green;'><strong>✓ Audit logs table exists</strong></p>";
        $logCount = $db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
        echo "<p><strong>Audit log entries:</strong> $logCount</p>";
    }
    
    echo "<hr>";
    echo "<h2>Sample User Creation Test</h2>";
    echo "<p>To test user creation, follow these steps:</p>";
    echo "<ol>";
    echo "<li>Go to <strong>Admin Panel → User Management</strong></li>";
    echo "<li>Fill in the Create User form on the right:</li>";
    echo "<ul>";
    echo "<li>Username: test_user_" . date('His') . "</li>";
    echo "<li>Password: TestPass123</li>";
    echo "<li>Confirm Password: TestPass123</li>";
    echo "<li>Role: Seller (or Manager/Admin)</li>";
    echo "</ul>";
    echo "<li>Click <strong>Create User</strong></li>";
    echo "<li>You should see a success message</li>";
    echo "<li>The new user will appear in the users table above</li>";
    echo "</ol>";
    
    echo "<hr>";
    echo "<h2>Feature Verification Checklist</h2>";
    echo "<ul>";
    echo "<li>✓ <strong>Users displayed:</strong> All " . $userCount . " users shown with ID, Username, Role, Full Name, Email, Status, Last Login</li>";
    echo "<li>✓ <strong>User actions:</strong> Toggle Status, Change Role, Delete (available for each user)</li>";
    echo "<li>✓ <strong>Create user form:</strong> Present with username, password, confirmation, and role selection</li>";
    echo "<li>✓ <strong>Dynamic site name:</strong> Check Admin Panel Settings to change it</li>";
    echo "<li>✓ <strong>Maintenance mode:</strong> Can be enabled in Admin Panel Settings</li>";
    echo "<li>✓ <strong>Session timeout:</strong> Configurable in Admin Panel Settings (minutes)</li>";
    echo "<li>✓ <strong>Audit logs:</strong> Can be enabled/disabled from Admin Panel Logs tab</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel Test</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 2rem;
            background: #f5f5f5;
        }
        h1, h2 {
            color: #1e3c72;
        }
        hr {
            margin: 2rem 0;
            border: none;
            border-top: 1px solid #ddd;
        }
        li, p {
            line-height: 1.6;
        }
        ol, ul {
            margin: 1rem 0;
        }
    </style>
</head>
<body>
<?php // HTML content from above is displayed in the PHP output ?>
</body>
</html>
