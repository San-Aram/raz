<?php
require_once 'includes/simple-admin-auth.php';
requireAdminLogin();

$currentTime = time();
$loginTime = $_SESSION['admin_login_time'] ?? 0;
$sessionAge = $currentTime - $loginTime;
$timeRemaining = 300 - $sessionAge; // 300 seconds = 5 minutes

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Timeout Test - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .test-container {
            max-width: 600px;
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
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🕒 Session Timeout Test</h1>
        
        <div class="session-info">
            <h3>Session Information:</h3>
            <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s', $currentTime); ?></p>
            <p><strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s', $loginTime); ?></p>
            <p><strong>Session Age:</strong> <?php echo $sessionAge; ?> seconds</p>
            <p><strong>Admin Username:</strong> <?php echo getAdminUsername(); ?></p>
        </div>
        
        <div class="countdown">
            Time Remaining: <span id="timeRemaining"><?php echo max(0, $timeRemaining); ?></span> seconds
        </div>
        
        <?php if ($timeRemaining <= 60 && $timeRemaining > 0): ?>
        <div class="warning">
            ⚠️ <strong>Warning:</strong> Your session will expire in less than 1 minute!
        </div>
        <?php endif; ?>
        
        <div class="auto-refresh">
            This page automatically refreshes every 5 seconds to show real-time countdown.
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="admin-dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            <a href="includes/simple-admin-auth.php?logout=1" class="btn btn-danger">Logout Now</a>
        </div>
        
        <h3>How to Test:</h3>
        <ol>
            <li>Keep this page open and watch the countdown</li>
            <li>When it reaches 0, the session should expire</li>
            <li>Try to navigate to any admin page - you should be redirected to login</li>
            <li>Alternatively, wait 5 minutes without any activity and try to access admin pages</li>
        </ol>
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
                        window.location.href = 'admin-login.php?error=session_expired';
                    }, 1000);
                }
            }
        };
        
        // Update countdown every second
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>