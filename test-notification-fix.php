<?php
// Simple auth setup for testing
session_start();

// Quick login if not logged in
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = 'raz';
    $_SESSION['user_login_time'] = time();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System Fix Test</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .test-info {
            background: #e3f2fd;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
        }
        .debug-box {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-pills"></i>
                    <h1>Razology</h1>
                </div>
                <nav class="nav">
                    <a href="index.php" class="nav-link">Dashboard</a>
                    <a href="products.php" class="nav-link">Products</a>
                    <!-- Notification bell should appear here -->
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <h1>🔔 Notification System Fix Test</h1>
            
            <div class="test-info">
                <h3>What to look for:</h3>
                <ul>
                    <li>Bell icon (🔔) should appear in the navigation bar above</li>
                    <li>Badge with number should show if there are notifications</li>
                    <li>Clicking the bell should show a dropdown with notifications</li>
                </ul>
            </div>

            <div class="test-info">
                <h3>Current Status:</h3>
                <p><strong>Session:</strong> <?php echo $_SESSION['logged_in'] ? 'Logged in ✅' : 'Not logged in ❌'; ?></p>
                <p><strong>Username:</strong> <?php echo $_SESSION['username'] ?? 'None'; ?></p>
            </div>

            <div>
                <h3>Manual Tests:</h3>
                <button onclick="testAPI()" style="padding: 0.5rem 1rem; margin: 0.5rem; background: #007bff; color: white; border: none; border-radius: 4px;">Test API</button>
                <button onclick="testAPIIgnoreDismissed()" style="padding: 0.5rem 1rem; margin: 0.5rem; background: #6f42c1; color: white; border: none; border-radius: 4px;">Test API (Ignore Dismissed)</button>
                <button onclick="checkNotificationSystem()" style="padding: 0.5rem 1rem; margin: 0.5rem; background: #28a745; color: white; border: none; border-radius: 4px;">Check Notification System</button>
                <button onclick="forceLoadNotifications()" style="padding: 0.5rem 1rem; margin: 0.5rem; background: #ffc107; color: black; border: none; border-radius: 4px;">Force Load Notifications</button>
                <button onclick="clearDismissals()" style="padding: 0.5rem 1rem; margin: 0.5rem; background: #dc3545; color: white; border: none; border-radius: 4px;">Clear Dismissals</button>
            </div>

            <div class="debug-box" id="debugOutput">Initializing...</div>
        </div>
    </main>

    <script>
        let debugOutput = document.getElementById('debugOutput');
        
        function log(message) {
            const timestamp = new Date().toLocaleTimeString();
            debugOutput.textContent += `[${timestamp}] ${message}\n`;
            debugOutput.scrollTop = debugOutput.scrollHeight;
        }

        function testAPI() {
            log('Testing notification API...');
            fetch('api/notifications.php')
                .then(response => {
                    log(`Response status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    log(`API Success: ${JSON.stringify(data, null, 2)}`);
                })
                .catch(error => {
                    log(`API Error: ${error.message}`);
                });
        }

        function testAPIIgnoreDismissed() {
            log('Testing notification API (ignoring dismissed)...');
            fetch('api/notifications.php?ignore_dismissed=1')
                .then(response => {
                    log(`Response status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    log(`API Success (ignore dismissed): ${JSON.stringify(data, null, 2)}`);
                })
                .catch(error => {
                    log(`API Error: ${error.message}`);
                });
        }

        function checkNotificationSystem() {
            log('Checking if notification system is initialized...');
            if (window.notificationSystem) {
                log('✅ Notification system found!');
                log(`Notification count: ${window.notificationSystem.notificationCount}`);
                log(`Notifications: ${JSON.stringify(window.notificationSystem.notifications, null, 2)}`);
            } else {
                log('❌ Notification system not found');
            }
            
            // Check if notification button exists in DOM
            const notificationBtn = document.querySelector('.notification-btn');
            if (notificationBtn) {
                log('✅ Notification button found in DOM');
            } else {
                log('❌ Notification button NOT found in DOM');
            }
        }

        function clearDismissals() {
            log('Clearing all dismissed notifications...');
            fetch('clear_dismissals.php')
                .then(response => response.text())
                .then(result => {
                    log('Dismissals cleared');
                    if (window.notificationSystem) {
                        window.notificationSystem.loadNotifications();
                        log('Refreshed notification system');
                    }
                })
                .catch(error => {
                    log(`Clear error: ${error.message}`);
                });
        }

        function forceLoadNotifications() {
            log('Forcing notification system to load notifications...');
            if (window.notificationSystem) {
                window.notificationSystem.loadNotifications();
                log('Load notifications called');
            } else {
                log('ERROR: Notification system not available');
            }
        }

        // Intercept console for debugging
        const originalLog = console.log;
        console.log = function(...args) {
            log('CONSOLE: ' + args.join(' '));
            originalLog.apply(console, args);
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            log('DOM loaded');
            
            setTimeout(() => {
                checkNotificationSystem();
            }, 2000);
        });
    </script>

    <script src="js/notifications.js"></script>
</body>
</html>