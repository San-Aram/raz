<?php
require_once 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Notifications - Razology</title>
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
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="form-container" style="max-width: 800px;">
                <h2><i class="fas fa-bell"></i> Notifications Test</h2>
                
                <div class="test-section">
                    <h3>Testing Notifications API</h3>
                    <button class="btn btn-primary" onclick="testNotifications()">
                        <i class="fas fa-test-tube"></i> Test Notifications API
                    </button>
                    
                    <div id="test-results" style="margin-top: 1rem;">
                        <!-- Results will appear here -->
                    </div>
                </div>
                
                <div class="test-section" style="margin-top: 2rem;">
                    <h3>Current Session Info</h3>
                    <p><strong>Logged In:</strong> <?php echo isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'Yes' : 'No') : 'Not set'; ?></p>
                    <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology</p>
        </div>
    </footer>

    <style>
        .test-section {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1rem;
        }
        
        .test-section h3 {
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        #test-results {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            border-left: 4px solid var(--primary-color);
            display: none;
        }
        
        .result-success {
            color: #28a745;
        }
        
        .result-error {
            color: #dc3545;
        }
        
        .json-result {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            margin-top: 0.5rem;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>

    <script>
        async function testNotifications() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.style.display = 'block';
            resultsDiv.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Testing notifications API...</p>';
            
            try {
                // Test the notifications API
                const response = await fetch('api/notifications.php');
                const responseText = await response.text();
                
                resultsDiv.innerHTML = `
                    <h4>Response Status: <span class="${response.ok ? 'result-success' : 'result-error'}">${response.status} ${response.statusText}</span></h4>
                    <p><strong>Content-Type:</strong> ${response.headers.get('content-type') || 'Not set'}</p>
                `;
                
                if (response.ok) {
                    try {
                        const data = JSON.parse(responseText);
                        resultsDiv.innerHTML += `
                            <p class="result-success"><i class="fas fa-check"></i> API call successful!</p>
                            <h5>Response Data:</h5>
                            <div class="json-result">${JSON.stringify(data, null, 2)}</div>
                        `;
                    } catch (jsonError) {
                        resultsDiv.innerHTML += `
                            <p class="result-error"><i class="fas fa-exclamation-triangle"></i> Invalid JSON response</p>
                            <h5>Raw Response:</h5>
                            <div class="json-result">${responseText}</div>
                            <p><strong>JSON Error:</strong> ${jsonError.message}</p>
                        `;
                    }
                } else {
                    resultsDiv.innerHTML += `
                        <p class="result-error"><i class="fas fa-times"></i> API call failed</p>
                        <h5>Response:</h5>
                        <div class="json-result">${responseText}</div>
                    `;
                }
                
            } catch (error) {
                resultsDiv.innerHTML = `
                    <p class="result-error"><i class="fas fa-times"></i> Network error: ${error.message}</p>
                `;
            }
        }
    </script>
</body>
</html>