<?php
// Set up minimal environment to avoid auth issues
$_SERVER['REQUEST_URI'] = '/test';
session_start();
$_SESSION['user_id'] = 1; // Fake login

require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();
$product = new Product($db);

// Get all products without any search filter
$products = $product->getAll('');

echo "=== PHP PRODUCTS DEBUG ===\n";
echo "Total products retrieved: " . count($products) . "\n\n";

foreach ($products as $index => $prod) {
    echo "Product $index:\n";
    echo "  ID: " . $prod['id'] . "\n";
    echo "  Barcode: " . $prod['barcode'] . "\n";
    echo "  Name: " . $prod['product_name'] . "\n";
    echo "  Company: " . $prod['company'] . "\n";
    echo "  Active Ingredient: " . $prod['active_ingredient'] . "\n";
    echo "  Dose: " . $prod['dose'] . "\n";
    echo "  Form: " . $prod['form'] . "\n";
    echo "  Price: " . $prod['price'] . "\n";
    echo "  Image URL: " . ($prod['image_url'] ?: 'NULL') . "\n";
    echo "  Medication ID: " . ($prod['medication_id'] ?: 'NULL') . "\n";
    echo "  Medication Name: " . ($prod['medication_name'] ?: 'NULL') . "\n";
    echo "\n";
}
?>
