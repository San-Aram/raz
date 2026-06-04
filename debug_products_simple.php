<!DOCTYPE html>
<html>
<head>
    <title>Products Debug Test</title>
    <style>
        .debug-product {
            border: 1px solid #333;
            margin: 10px;
            padding: 10px;
            background: #f0f0f0;
        }
    </style>
</head>
<body>
    <h1>Products Debug Test</h1>
    
    <?php
    // Set up minimal environment
    session_start();
    $_SESSION['user_id'] = 1;
    require_once 'includes/database.php';

    $database = new Database();
    $db = $database->connect();
    $product = new Product($db);

    // Get all products
    $products = $product->getAll('');
    
    echo "<h2>Total Products Found: " . count($products) . "</h2>";
    
    foreach ($products as $index => $prod) {
        echo "<div class='debug-product'>";
        echo "<h3>Product Card #" . ($index + 1) . "</h3>";
        echo "<p><strong>Database ID:</strong> " . $prod['id'] . "</p>";
        echo "<p><strong>Barcode:</strong> " . htmlspecialchars($prod['barcode']) . "</p>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($prod['product_name']) . "</p>";
        echo "<p><strong>Company:</strong> " . htmlspecialchars($prod['company']) . "</p>";
        echo "<p><strong>Active Ingredient:</strong> " . htmlspecialchars($prod['active_ingredient']) . "</p>";
        echo "<p><strong>Price:</strong> " . number_format($prod['price'], 0) . " IQD</p>";
        echo "</div>";
    }
    ?>
    
</body>
</html>
