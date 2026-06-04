<?php
// Simple test of the product search API
echo "Testing product search API...\n";

$searchQuery = 'test';
$url = "http://localhost/FYP%20Pharma/raz/api/product-search.php?q=" . urlencode($searchQuery);

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Cookie: PHPSESSID=' . session_id()
        ]
    ]
]);

// Start session to simulate being logged in as seller
session_start();
$_SESSION['user_id'] = 2; // Assuming seller user ID is 2
$_SESSION['role'] = 'seller';
$_SESSION['username'] = 'seller';

// Simulate the API call by including the file directly
$_GET['q'] = $searchQuery;
ob_start();
include 'api/product-search.php';
$response = ob_get_clean();

echo "API Response:\n";
echo $response . "\n";

$data = json_decode($response, true);
if ($data && isset($data['products'])) {
    echo "\nFound " . count($data['products']) . " products:\n";
    foreach ($data['products'] as $product) {
        echo "- {$product['name']} by {$product['brand']} - ₱{$product['price']}\n";
    }
} else {
    echo "No products found or API error\n";
}
?>