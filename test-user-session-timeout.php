<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

// Get current session timeout setting from admin panel
$sessionTimeoutMinutes = (int)getAdminSetting('session_timeout', 30);
$sessionTimeoutSeconds = $sessionTimeoutMinutes * 60;

$currentTime = time();
$loginTime = $_SESSION['user_login_time'] ?? 0;
$sessionAge = $currentTime - $loginTime;
$timeRemaining = $sessionTimeoutSeconds - $sessionAge;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Session Timeout Test - <?php echo $sessionTimeoutMinutes; ?> Minutes</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .test-container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .session-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .countdown {
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
            margin: 1rem 0;
        }
        .auto-refresh {
            background: #e9ecef;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            text-align: center;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .settings-info {
            background: #e3f2fd;
            color: #0d47a1;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🕒 User Session Timeout Test</h1>
        
        <div class="settings-info">
            <h3>⚙️ Admin Configuration:</h3>
            <p><strong>Session Timeout Setting:</strong> <?php echo $sessionTimeoutMinutes; ?> minutes (<?php echo $sessionTimeoutSeconds; ?> seconds)</p>
            <p><em>This setting is configured in the Admin Panel → System Settings</em></p>
        </div>
        
        <div class="session-info">
            <h3>📊 Current Session Information:</h3>
            <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s', $currentTime); ?></p>
            <p><strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s', $loginTime); ?></p>
            <p><strong>Session Age:</strong> <?php echo $sessionAge; ?> seconds (<?php echo round($sessionAge/60, 1); ?> minutes)</p>
            <p><strong>User:</strong> <?php echo $_SESSION['username'] ?? 'Unknown'; ?></p>
            <p><strong>Role:</strong> <?php echo getUserRole(); ?></p>
        </div>
        
        <div class="countdown">
            Time Remaining: <span id="timeRemaining"><?php echo max(0, $timeRemaining); ?></span> seconds
        </div>
        
        <?php if ($timeRemaining <= 60 && $timeRemaining > 0): ?>
        <div class="warning">
            ⚠️ <strong>Warning:</strong> Your session will expire in less than 1 minute!
        </div>
        <?php elseif ($timeRemaining > 60): ?>
        <div class="success">
            ✅ <strong>Session Active:</strong> You have <?php echo round($timeRemaining/60, 1); ?> minutes remaining.
        </div>
        <?php endif; ?>
        
        <div class="auto-refresh">
            This page automatically refreshes every 5 seconds to show real-time countdown.
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="index.php" class="btn btn-primary">Back to Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Logout Now</a>
            <a href="admin-dashboard.php" class="btn btn-secondary">Admin Panel</a>
        </div>
        
        <div style="margin-top: 2rem;">
            <h3>🧪 How to Test Session Timeout:</h3>
            <ol>
                <li><strong>Set Timeout in Admin:</strong> Go to Admin Panel → System Settings and change "Session Timeout" to 5 minutes</li>
                <li><strong>Watch Countdown:</strong> Keep this page open and watch the countdown timer</li>
                <li><strong>Wait for Expiry:</strong> When it reaches 0, the session should expire</li>
                <li><strong>Test Redirect:</strong> Try to navigate to any manager page - you should see "session expired" message</li>
                <li><strong>Alternative Test:</strong> Close this page and wait <?php echo $sessionTimeoutMinutes; ?> minutes, then try to access any page</li>
            </ol>
            
            <h3>📝 Current Admin Settings:</h3>
            <ul>
                <li><strong>Session Timeout:</strong> <?php echo $sessionTimeoutMinutes; ?> minutes</li>
                <li><strong>Site Name:</strong> <?php echo getAdminSetting('site_name', 'Razology Pharmacy'); ?></li>
                <li><strong>Max Login Attempts:</strong> <?php echo getAdminSetting('max_login_attempts', '5'); ?></li>
            </ul>
        </div>
    </div>

    <script>
        // Auto-refresh every 5 seconds
        setTimeout(function() {
            location.reload();
        }, 5000);
        
        // Client-side countdown for better UX
        let timeRemaining = <?php echo max(0, $timeRemaining); ?>;
        const countdownElement = document.getElementById('timeRemaining');
        
        const updateCountdown = () => {
            if (timeRemaining > 0) {
                timeRemaining--;
                countdownElement.textContent = timeRemaining;
                
                if (timeRemaining <= 0) {
                    countdownElement.textContent = "SESSION EXPIRED!";
                    countdownElement.style.color = "#dc3545";
                    
                    // Show expiration message
                    setTimeout(() => {
                        alert('Session has expired! You will be redirected to login.');
                        window.location.href = 'login.php?error=session_expired';
                    }, 1000);
                }
            }
        };
        
        // Update countdown every second
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>