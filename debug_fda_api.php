<?php
// Simple debug script to test FDA API directly
require_once 'includes/auth.php';

echo "<h2>FDA API Debug Test</h2>";

// Test direct API call to FDA
$medication = "Paracetamol";
echo "<h3>Testing medication: $medication</h3>";

// Test the FDA API directly
$url = "https://api.fda.gov/drug/label.json?search=active_ingredient:" . urlencode($medication) . "&limit=1";
echo "<p><strong>FDA URL:</strong> $url</p>";

$context = stream_context_create([
    'http' => [
        'timeout' => 15,
        'user_agent' => 'PharmaCare/1.0'
    ]
]);

echo "<h4>Making FDA API call...</h4>";
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "<p style='color: red;'>❌ FDA API call failed</p>";
    $error = error_get_last();
    echo "<p>Error: " . ($error['message'] ?? 'Unknown error') . "</p>";
} else {
    echo "<p style='color: green;'>✅ FDA API call successful</p>";
    $data = json_decode($response, true);
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

echo "<hr>";

// Test alternative with different search terms
$alternatives = ['acetaminophen', 'tylenol', 'APAP'];
foreach ($alternatives as $alt) {
    echo "<h4>Testing alternative: $alt</h4>";
    $url = "https://api.fda.gov/drug/label.json?search=active_ingredient:" . urlencode($alt) . "&limit=1";
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['results']) && !empty($data['results'])) {
            echo "<p style='color: green;'>✅ Found results for $alt</p>";
            echo "<p>Found " . count($data['results']) . " results</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No results for $alt</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ API call failed for $alt</p>";
    }
}

echo "<hr>";

// Test RxNorm API
echo "<h3>Testing RxNorm API for ingredient normalization</h3>";
$rxnorm_url = "https://rxnav.nlm.nih.gov/REST/approximateTerm.json?term=" . urlencode($medication) . "&maxEntries=5";
echo "<p><strong>RxNorm URL:</strong> $rxnorm_url</p>";

$rxnorm_response = @file_get_contents($rxnorm_url, false, $context);
if ($rxnorm_response === false) {
    echo "<p style='color: red;'>❌ RxNorm API call failed</p>";
} else {
    echo "<p style='color: green;'>✅ RxNorm API call successful</p>";
    $rxnorm_data = json_decode($rxnorm_response, true);
    echo "<pre>";
    print_r($rxnorm_data);
    echo "</pre>";
}

?>