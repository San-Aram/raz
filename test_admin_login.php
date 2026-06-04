<?php
// Simple admin login test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    require_once 'includes/database.php';
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "<h2>🔍 Admin Login Debug Test</h2>";
    echo "<p><strong>Testing credentials:</strong></p>";
    echo "<p>Username: '" . htmlspecialchars($username) . "'</p>";
    echo "<p>Password: '" . htmlspecialchars($password) . "'</p>";
    
    try {
        $database = new Database();
        $db = $database->connect();
        
        // Check for user
        $stmt = $db->prepare("SELECT id, username, password, role, account_locked FROM users WHERE username = ? AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Database Query Result:</h3>";
        if ($user) {
            echo "<div style='background: #d4edda; padding: 1rem; border-radius: 4px; color: #155724;'>";
            echo "✅ User found in database!<br>";
            echo "ID: {$user['id']}<br>";
            echo "Username: {$user['username']}<br>";
            echo "Role: {$user['role']}<br>";
            echo "Account Locked: " . ($user['account_locked'] ? 'Yes' : 'No') . "<br>";
            echo "</div>";
            
            // Test password
            echo "<h3>Password Verification:</h3>";
            if (password_verify($password, $user['password'])) {
                echo "<div style='background: #d4edda; padding: 1rem; border-radius: 4px; color: #155724;'>";
                echo "✅ Password is correct!<br>";
                echo "<strong>Login should work. Try the actual admin login page now.</strong>";
                echo "</div>";
                
                // Set session for testing
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['user_role'] = 'admin';
                $_SESSION['admin_login_time'] = time();
                
                echo "<p><a href='admin-dashboard.php' style='background: #007bff; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Go to Admin Dashboard</a></p>";
                
            } else {
                echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
                echo "❌ Password verification failed!<br>";
                echo "This means there's an issue with the password hash.";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
            echo "❌ No admin user found with username '$username'<br>";
            echo "Make sure you run the create_admin_user.php script first.";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; color: #721c24;'>";
        echo "Error: " . $e->getMessage();
        echo "</div>";
    }
    
} else {
    // Show form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login Test</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 2rem; background: #f8f9fa; }
            .form-group { margin-bottom: 1rem; }
            label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
            input[type="text"], input[type="password"] { width: 100%; padding: 0.75rem; border: 2px solid #ddd; border-radius: 4px; }
            button { background: #007bff; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <h2>🧪 Admin Login Test</h2>
        <p>Use this to test admin login credentials before using the actual login page.</p>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="admin" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" value="admin123" required>
            </div>
            
            <button type="submit">Test Login</button>
        </form>
        
        <p><small>Default credentials: admin / admin123</small></p>
    </body>
    </html>
    <?php
}
?>