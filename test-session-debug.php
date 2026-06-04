<?php
session_start();

echo "<h2>Session Debug for Notifications</h2>";

echo "<h3>Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Authentication Status:</h3>";

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
echo "<p><strong>Logged In:</strong> " . ($isLoggedIn ? "YES ✅" : "NO ❌") . "</p>";

if (isset($_SESSION['logged_in'])) {
    echo "<p><strong>logged_in value:</strong> " . var_export($_SESSION['logged_in'], true) . "</p>";
}

if (isset($_SESSION['username'])) {
    echo "<p><strong>Username:</strong> " . $_SESSION['username'] . "</p>";
}

echo "<h3>Test Notification API:</h3>";

if ($isLoggedIn) {
    echo "<p style='color: green;'>✅ Session is valid - API should work</p>";
    echo "<p><a href='api/notifications.php' target='_blank'>Test API directly</a></p>";
} else {
    echo "<p style='color: red;'>❌ Session invalid - need to login first</p>";
    echo "<p><a href='login.php'>Login here</a></p>";
}

echo "<h3>Quick Login Test:</h3>";
echo "<form method='post' style='background: #f8f9fa; padding: 1rem; border-radius: 8px;'>";
echo "<p>Username: <input type='text' name='username' value='raz' style='padding: 0.5rem;'></p>";
echo "<p>Password: <input type='password' name='password' value='raz' style='padding: 0.5rem;'></p>";
echo "<p><button type='submit' style='padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 4px;'>Quick Login</button></p>";
echo "</form>";

// Handle quick login
if ($_POST && isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($username === 'raz' && $password === 'raz') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = 'raz';
        $_SESSION['user_login_time'] = time();
        
        echo "<div style='background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
        echo "<strong>✅ Login Successful!</strong><br>";
        echo "Session updated. <a href='?'>Refresh this page</a> to see updated session data.";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
        echo "<strong>❌ Login Failed!</strong><br>";
        echo "Invalid credentials.";
        echo "</div>";
    }
}

echo "<h3>Test Pages with Notifications:</h3>";
echo "<ul>";
echo "<li><a href='index.php'>Main Dashboard</a></li>";
echo "<li><a href='products.php'>Products Page</a></li>";
echo "<li><a href='test-notifications-ui.php'>Notification Test Page</a></li>";
echo "</ul>";
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
pre { 
    background: #2d3748; 
    color: #e2e8f0; 
    padding: 1rem; 
    border-radius: 8px; 
    overflow-x: auto; 
}
</style>