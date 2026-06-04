<?php
session_start();

echo "<h2>Checkout Authentication Debug</h2>";
echo "<p><strong>Session Data:</strong></p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p><strong>Authentication Checks:</strong></p>";

// Check seller authentication (what the API checks)
$is_seller = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller' && isset($_SESSION['seller_id']);
echo "<p>Is Seller: " . ($is_seller ? 'YES' : 'NO') . "</p>";

if (isset($_SESSION['user_role'])) {
    echo "<p>User Role: " . $_SESSION['user_role'] . "</p>";
}

if (isset($_SESSION['seller_id'])) {
    echo "<p>Seller ID: " . $_SESSION['seller_id'] . "</p>";
}

// Check manager authentication
$is_manager = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['username']);
echo "<p>Is Manager: " . ($is_manager ? 'YES' : 'NO') . "</p>";

if (isset($_SESSION['logged_in'])) {
    echo "<p>Logged In: " . ($_SESSION['logged_in'] ? 'YES' : 'NO') . "</p>";
}

if (isset($_SESSION['username'])) {
    echo "<p>Username: " . $_SESSION['username'] . "</p>";
}

echo "<p><strong>Expected API Response Format:</strong></p>";
if ($is_seller) {
    echo "<p>Seller format: {success: true, product: {...}}</p>";
} else {
    echo "<p>Manager format: {success: true, exists: true, product: {...}}</p>";
}

// Test barcode API call
echo "<hr>";
echo "<h3>Test Barcode API Call</h3>";
echo "<p>Testing with a known barcode...</p>";

// Simulate what checkout.js sends
$test_data = json_encode(['barcode' => '123456789']);
echo "<p>Request data: " . $test_data . "</p>";

// Make internal API call
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $test_data
    ]
]);

$response = file_get_contents('http://localhost/api/check-barcode.php', false, $context);
echo "<p>API Response: " . htmlspecialchars($response) . "</p>";
?>