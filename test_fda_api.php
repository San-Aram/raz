<?php
session_start();

// Set up session for testing
$_SESSION['logged_in'] = true;

echo "<h2>FDA/DrugBank API Test</h2>";

// Test the API with a simple medication
$testMedication = 'paracetamol';
echo "<h3>Testing with: $testMedication</h3>";

// Simulate the API call
$url = "http://localhost:8000/api/fda-lookup.php?medication=" . urlencode($testMedication);

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Cookie: ' . session_name() . '=' . session_id()
        ]
    ]
]);

echo "<p>Calling URL: $url</p>";

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "<p style='color: red;'>Failed to get response from API</p>";
    print_r(error_get_last());
} else {
    echo "<h4>API Response:</h4>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $data = json_decode($response, true);
    if ($data) {
        echo "<h4>Parsed JSON:</h4>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>Failed to parse JSON response</p>";
    }
}
?>
