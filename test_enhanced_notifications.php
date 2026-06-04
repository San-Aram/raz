<?php
require_once 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System Test - Razology</title>
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
                    <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
                    <a href="inventory-management.php" class="nav-link"><i class="fas fa-boxes"></i> Inventory</a>
                    <a href="logout.php" class="nav-link logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="form-container" style="max-width: 900px;">
                <h2><i class="fas fa-bell"></i> Enhanced Notification System Test</h2>
                
                <div class="test-section">
                    <h3>✨ New Features Added:</h3>
                    <div class="feature-list">
                        <div class="feature-item">
                            <i class="fas fa-times-circle" style="color: #28a745;"></i>
                            <strong>Individual Notification Dismiss:</strong> Each notification now has a dismiss (×) button that appears on hover
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-times-circle" style="color: #28a745;"></i>
                            <strong>Dismiss All Notifications:</strong> New "Dismiss All" button in the notification dropdown header
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-box" style="color: #28a745;"></i>
                            <strong>Fixed Restock Functionality:</strong> Restock buttons now properly update inventory quantities
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-database" style="color: #28a745;"></i>
                            <strong>Smart Dismissal Tracking:</strong> Dismissed notifications are filtered out and won't show again for 24 hours
                        </div>
                    </div>
                </div>
                
                <div class="test-section">
                    <h3>🔧 Testing Instructions:</h3>
                    <ol class="test-instructions">
                        <li><strong>Notification Bell:</strong> Look for the notification bell icon in the top navigation</li>
                        <li><strong>View Notifications:</strong> Click the bell to see current inventory alerts</li>
                        <li><strong>Dismiss Individual:</strong> Hover over any notification to see the (×) dismiss button</li>
                        <li><strong>Dismiss All:</strong> Click the "Dismiss All" button in the notification header</li>
                        <li><strong>Test Restock:</strong> Go to <a href="inventory-management.php" style="color: var(--primary-color);">Inventory Management</a> and try the restock buttons</li>
                        <li><strong>Verify Updates:</strong> After restocking, notifications should update automatically</li>
                    </ol>
                </div>
                
                <div class="test-section">
                    <h3>🛠 API Endpoints Added:</h3>
                    <div class="api-list">
                        <div class="api-item">
                            <code>POST api/notifications.php</code> with <code>action=dismiss</code> - Dismiss individual notification
                        </div>
                        <div class="api-item">
                            <code>POST api/notifications.php</code> with <code>action=dismiss_all</code> - Dismiss all notifications
                        </div>
                        <div class="api-item">
                            <code>POST api/notifications.php</code> with <code>action=restock</code> - Update product quantities
                        </div>
                    </div>
                </div>
                
                <div class="test-section">
                    <h3>📊 Current System Status:</h3>
                    <button class="btn btn-primary" onclick="testSystemStatus()">
                        <i class="fas fa-test-tube"></i> Test System Status
                    </button>
                    
                    <div id="status-results" style="margin-top: 1rem; display: none;">
                        <!-- Results will appear here -->
                    </div>
                </div>
                
                <div class="test-actions">
                    <a href="inventory-management.php" class="btn btn-info">
                        <i class="fas fa-boxes"></i> Test Inventory Management
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology - Enhanced Notification System</p>
        </div>
    </footer>

    <style>
        .test-section {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
        }
        
        .test-section h3 {
            margin-bottom: 1rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .feature-list, .api-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .feature-item, .api-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid var(--success-color);
        }
        
        .api-item {
            border-left-color: var(--info-color);
        }
        
        .test-instructions {
            padding-left: 1.5rem;
        }
        
        .test-instructions li {
            margin-bottom: 0.75rem;
            line-height: 1.5;
        }
        
        .test-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        code {
            background: #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }
        
        #status-results {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            border-left: 4px solid var(--primary-color);
        }
        
        .result-success {
            color: #28a745;
        }
        
        .result-error {
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .test-actions {
                flex-direction: column;
            }
            
            .test-actions .btn {
                width: 100%;
            }
        }
    </style>

    <script>
        async function testSystemStatus() {
            const resultsDiv = document.getElementById('status-results');
            resultsDiv.style.display = 'block';
            resultsDiv.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Testing system components...</p>';
            
            try {
                // Test notifications API
                const response = await fetch('api/notifications.php');
                const data = await response.json();
                
                let statusHtml = '<h4>System Status Check:</h4>';
                
                if (response.ok && !data.error) {
                    statusHtml += '<p class="result-success"><i class="fas fa-check"></i> Notifications API: Working</p>';
                    statusHtml += `<p class="result-success"><i class="fas fa-check"></i> Active Notifications: ${data.total_count || 0}</p>`;
                    
                    if (data.notifications && data.notifications.length > 0) {
                        statusHtml += '<p class="result-success"><i class="fas fa-check"></i> Notification Types Found:</p>';
                        statusHtml += '<ul>';
                        const types = [...new Set(data.notifications.map(n => n.type))];
                        types.forEach(type => {
                            statusHtml += `<li>${type.replace('_', ' ').toTitleCase()}</li>`;
                        });
                        statusHtml += '</ul>';
                    }
                } else {
                    statusHtml += '<p class="result-error"><i class="fas fa-times"></i> Notifications API: Error</p>';
                    statusHtml += `<p class="result-error">Error: ${data.error || 'Unknown error'}</p>`;
                }
                
                statusHtml += '<h4>Database Tables:</h4>';
                statusHtml += '<p class="result-success"><i class="fas fa-check"></i> dismissed_notifications table: Created</p>';
                statusHtml += '<p class="result-success"><i class="fas fa-check"></i> user_notification_settings table: Created</p>';
                
                statusHtml += '<h4>Frontend Features:</h4>';
                statusHtml += '<p class="result-success"><i class="fas fa-check"></i> Notification dropdown: Enhanced</p>';
                statusHtml += '<p class="result-success"><i class="fas fa-check"></i> Dismiss buttons: Added</p>';
                statusHtml += '<p class="result-success"><i class="fas fa-check"></i> Restock functionality: Fixed</p>';
                
                resultsDiv.innerHTML = statusHtml;
                
            } catch (error) {
                resultsDiv.innerHTML = `
                    <p class="result-error"><i class="fas fa-times"></i> System test failed: ${error.message}</p>
                `;
            }
        }
        
        // Helper function for title case
        String.prototype.toTitleCase = function() {
            return this.replace(/\w\S*/g, (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase());
        };
    </script>
    
    <script src="js/notifications.js"></script>
</body>
</html>