<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
</head>
<body>
    <h1>Session Debug Information</h1>
    
    <h2>All Session Variables:</h2>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <h2>Authentication Detection:</h2>
    <?php
    $is_seller = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller';
    $is_manager = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['username']);
    
    echo "<p><strong>Is Seller:</strong> " . ($is_seller ? 'YES' : 'NO') . "</p>";
    echo "<p><strong>Is Manager:</strong> " . ($is_manager ? 'YES' : 'NO') . "</p>";
    
    if ($is_seller) {
        echo "<p><strong>Seller ID present:</strong> " . (isset($_SESSION['seller_id']) ? 'YES (' . $_SESSION['seller_id'] . ')' : 'NO') . "</p>";
    }
    ?>
    
    <h2>Quick API Test:</h2>
    <button onclick="testAPI()">Test Barcode API</button>
    <div id="apiResult"></div>
    
    <script>
    function testAPI() {
        const resultDiv = document.getElementById('apiResult');
        resultDiv.innerHTML = 'Testing...';
        
        fetch('api/check-barcode.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ barcode: '123456', category: 'pharmaceutics' })
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Response text:', text);
            resultDiv.innerHTML = '<h3>Raw Response:</h3><pre>' + text + '</pre>';
            
            try {
                const data = JSON.parse(text);
                resultDiv.innerHTML += '<h3>Parsed JSON:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (e) {
                resultDiv.innerHTML += '<p style="color: red;">Failed to parse as JSON: ' + e.message + '</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
        });
    }
    </script>
</body>
</html>