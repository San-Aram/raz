<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System Debug</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
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
                    <a href="index.php" class="nav-link active">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <a href="medications.php" class="nav-link">
                        <i class="fas fa-capsules"></i> Medications
                    </a>
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-box"></i> Products
                    </a>
                    <a href="logout.php" class="nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <h2>Notification System Debug</h2>
            <div id="debug-info">
                <p>Loading notification system...</p>
            </div>
            
            <div id="console-log">
                <h3>Console Log:</h3>
                <pre id="log-output"></pre>
            </div>
        </div>
    </main>

    <script>
        // Capture console logs
        const originalLog = console.log;
        const originalError = console.error;
        const logOutput = document.getElementById('log-output');
        
        function addLog(type, message) {
            logOutput.textContent += `[${type}] ${message}\n`;
        }
        
        console.log = function(...args) {
            addLog('LOG', args.join(' '));
            originalLog.apply(console, args);
        };
        
        console.error = function(...args) {
            addLog('ERROR', args.join(' '));
            originalError.apply(console, args);
        };
        
        // Check if notification system loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, checking notification system...');
            
            setTimeout(async () => {
                const debugInfo = document.getElementById('debug-info');
                const notificationBtn = document.querySelector('.notification-btn');
                const nav = document.querySelector('.nav');
                
                // Test the API directly
                let apiResponse = 'Not tested';
                let apiData = null;
                try {
                    console.log('Testing API directly...');
                    const response = await fetch('api/notifications.php');
                    const text = await response.text();
                    console.log('Raw API response:', text);
                    
                    try {
                        apiData = JSON.parse(text);
                        apiResponse = JSON.stringify(apiData, null, 2);
                        console.log('Parsed API data:', apiData);
                    } catch (e) {
                        apiResponse = 'Failed to parse JSON: ' + text;
                        console.error('JSON parse error:', e);
                    }
                } catch (error) {
                    apiResponse = 'API call failed: ' + error.message;
                    console.error('API call error:', error);
                }
                
                // Check authentication status
                let authStatus = 'Not checked';
                try {
                    const authResponse = await fetch('session-debug.php');
                    const authText = await authResponse.text();
                    
                    // Extract session info from the debug page
                    const match = authText.match(/All Session Variables:(.*?)Authentication Detection:/s);
                    if (match) {
                        authStatus = match[1].trim();
                    } else {
                        authStatus = 'Could not parse session info';
                    }
                } catch (error) {
                    authStatus = 'Error checking auth: ' + error.message;
                }
                
                debugInfo.innerHTML = `
                    <p><strong>Navigation found:</strong> ${nav ? 'YES' : 'NO'}</p>
                    <p><strong>Notification button created:</strong> ${notificationBtn ? 'YES' : 'NO'}</p>
                    <p><strong>NotificationSystem available:</strong> ${window.notificationSystem ? 'YES' : 'NO'}</p>
                    <p><strong>Notification count in system:</strong> ${window.notificationSystem ? window.notificationSystem.notificationCount : 'N/A'}</p>
                    
                    <p><strong>Authentication Status:</strong></p>
                    <pre style="background: #fff3cd; padding: 10px; max-height: 200px; overflow-y: auto;">${authStatus}</pre>
                    
                    <p><strong>API Response:</strong></p>
                    <pre style="background: #f5f5f5; padding: 10px; max-height: 400px; overflow-y: auto;">${apiResponse}</pre>
                    <p><strong>API Data Analysis:</strong></p>
                    <ul>
                        <li>Success: ${apiData ? apiData.success : 'N/A'}</li>
                        <li>Total Count (totalCount): ${apiData ? apiData.totalCount : 'N/A'}</li>
                        <li>Total Count (total_count): ${apiData ? apiData.total_count : 'N/A'}</li>
                        <li>Notifications Array Length: ${apiData && apiData.notifications ? apiData.notifications.length : 'N/A'}</li>
                    </ul>
                    
                    <p><strong>Quick Fix:</strong></p>
                    <p><a href="login.php" style="background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">Login as Manager</a></p>
                    <p><a href="debug-notifications.php" style="background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">Check Database Debug</a></p>
                `;
                
                if (window.notificationSystem) {
                    console.log('Notification system initialized successfully');
                    console.log('Notification count:', window.notificationSystem.notificationCount);
                    console.log('Notifications array:', window.notificationSystem.notifications);
                } else {
                    console.error('Notification system failed to initialize');
                }
            }, 2000);
        });
    </script>
    
    <script src="js/notifications.js"></script>
</body>
</html>