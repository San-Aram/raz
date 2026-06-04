<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System Test - Razology</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .test-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .debug-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .notification-test {
            border: 2px dashed #dee2e6;
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
        }
        #debugOutput {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        .test-button {
            background: #3182ce;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            margin: 0.5rem;
            font-size: 1rem;
        }
        .test-button:hover {
            background: #2c5282;
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
                    <!-- Notification system will inject its dropdown here -->
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="test-container">
                <h1>🔔 Notification System Test</h1>
                
                <div class="debug-section">
                    <h3>Step 1: Check Session Status</h3>
                    <p id="sessionStatus">Checking session...</p>
                </div>
                
                <div class="debug-section">
                    <h3>Step 2: Test Notification API</h3>
                    <button class="test-button" onclick="testNotificationAPI()">Test API Call</button>
                    <button class="test-button" onclick="clearAllNotifications()">Clear All Dismissed</button>
                    <button class="test-button" onclick="refreshNotifications()">Refresh Notifications</button>
                </div>
                
                <div class="notification-test">
                    <h3>👀 Look for notification bell icon in the header above</h3>
                    <p>If the notification system is working, you should see a bell icon in the navigation bar.</p>
                    <p>The notification count badge should show the number of alerts.</p>
                </div>
                
                <div class="debug-section">
                    <h3>Debug Output:</h3>
                    <div id="debugOutput">Initializing notification system...\n</div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Debug logging function
        function debugLog(message) {
            const output = document.getElementById('debugOutput');
            const timestamp = new Date().toLocaleTimeString();
            output.textContent += `[${timestamp}] ${message}\n`;
            output.scrollTop = output.scrollHeight;
        }

        // Check session status
        function checkSession() {
            debugLog('Checking session status...');
            
            // Simulate session check by trying to access the API
            fetch('api/notifications.php')
                .then(response => {
                    debugLog(`API Response Status: ${response.status}`);
                    if (response.status === 401) {
                        document.getElementById('sessionStatus').innerHTML = 
                            '<span style="color: red;">❌ Not logged in - <a href="login.php">Login required</a></span>';
                        debugLog('ERROR: User not logged in');
                    } else if (response.status === 200) {
                        document.getElementById('sessionStatus').innerHTML = 
                            '<span style="color: green;">✅ Logged in successfully</span>';
                        debugLog('SUCCESS: User is logged in');
                        return response.json();
                    } else {
                        throw new Error(`HTTP ${response.status}`);
                    }
                })
                .then(data => {
                    if (data) {
                        debugLog(`Notifications received: ${data.totalCount || 0} items`);
                        if (data.notifications && data.notifications.length > 0) {
                            debugLog('Sample notification: ' + JSON.stringify(data.notifications[0], null, 2));
                        }
                    }
                })
                .catch(error => {
                    debugLog(`ERROR: ${error.message}`);
                    document.getElementById('sessionStatus').innerHTML = 
                        '<span style="color: red;">❌ Error checking session</span>';
                });
        }

        // Test the notification API directly
        function testNotificationAPI() {
            debugLog('Testing notification API...');
            
            fetch('api/notifications.php')
                .then(response => response.json())
                .then(data => {
                    debugLog(`API Response: ${JSON.stringify(data, null, 2)}`);
                    if (data.success && data.notifications) {
                        debugLog(`Found ${data.notifications.length} notifications`);
                        data.notifications.forEach((notif, index) => {
                            debugLog(`${index + 1}. ${notif.type}: ${notif.item_name} - ${notif.message}`);
                        });
                    }
                })
                .catch(error => {
                    debugLog(`API Error: ${error.message}`);
                });
        }

        // Clear all dismissed notifications
        function clearAllNotifications() {
            debugLog('Clearing all dismissed notifications...');
            
            fetch('clear_dismissals.php')
                .then(response => response.text())
                .then(html => {
                    debugLog('Clear dismissals response received');
                    // Try to refresh notifications after clearing
                    setTimeout(() => {
                        if (window.notificationSystem) {
                            window.notificationSystem.loadNotifications();
                            debugLog('Notification system refreshed');
                        }
                    }, 1000);
                })
                .catch(error => {
                    debugLog(`Clear Error: ${error.message}`);
                });
        }

        // Refresh notifications manually
        function refreshNotifications() {
            debugLog('Manually refreshing notifications...');
            if (window.notificationSystem) {
                window.notificationSystem.loadNotifications();
                debugLog('Notification system refresh called');
            } else {
                debugLog('ERROR: Notification system not initialized');
            }
        }

        // Override console.log to capture notification system logs
        const originalLog = console.log;
        const originalError = console.error;
        
        console.log = function(...args) {
            debugLog('CONSOLE: ' + args.join(' '));
            originalLog.apply(console, args);
        };
        
        console.error = function(...args) {
            debugLog('ERROR: ' + args.join(' '));
            originalError.apply(console, args);
        };

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('DOM loaded, initializing tests...');
            checkSession();
            
            // Wait a bit for notification system to initialize
            setTimeout(() => {
                if (window.notificationSystem) {
                    debugLog('✅ Notification system found and initialized');
                } else {
                    debugLog('❌ Notification system NOT found - checking if script loaded');
                }
            }, 2000);
        });
    </script>
    
    <!-- Include the notification system -->
    <script src="js/notifications.js"></script>
</body>
</html>