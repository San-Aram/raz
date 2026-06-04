<?php
// Test the current FDA API endpoint
session_start();

// Simulate logged in user for testing
$_SESSION['logged_in'] = true;

echo "<h2>Testing Current FDA API Endpoint</h2>";

// Test with different medications
$test_medications = ['Paracetamol', 'Acetaminophen', 'Amoxicillin', 'Metformin', 'Ibuprofen'];

foreach ($test_medications as $medication) {
    echo "<h3>Testing: $medication</h3>";
    
    // Make API call to our endpoint
    $url = "http://localhost/api/fda-lookup.php?medication=" . urlencode($medication);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'PharmaCare/1.0',
            'header' => [
                'Cookie: ' . session_name() . '=' . session_id()
            ]
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "<p style='color: red;'>❌ API call failed</p>";
    } else {
        echo "<p style='color: green;'>✅ API call successful</p>";
        $data = json_decode($response, true);
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    
    echo "<hr>";
}
?>