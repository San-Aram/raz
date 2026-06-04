<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Full Test</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1>Products Full Rendering Test</h1>
        
        <?php
        session_start();
        $_SESSION['user_id'] = 1;
        require_once 'includes/database.php';

        $database = new Database();
        $db = $database->connect();
        $product = new Product($db);
        $products = $product->getAll('');
        
        echo "<p><strong>DEBUG: Found " . count($products) . " products</strong></p>";
        ?>
        
        <div class="products-grid">
            <?php foreach ($products as $prod): ?>
                <div class="product-card" data-product-id="<?php echo $prod['id']; ?>" data-product-barcode="<?php echo htmlspecialchars($prod['barcode']); ?>">
                    <!-- Debug header for each card -->
                    <div style="background: #ff0; padding: 5px; font-size: 12px; font-weight: bold;">
                        ID: <?php echo $prod['id']; ?> | Barcode: <?php echo htmlspecialchars($prod['barcode']); ?>
                    </div>
                    
                    <img src="<?php echo $prod['image_url'] ?: 'images/default-product.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($prod['product_name']); ?>" 
                         class="product-image" 
                         onerror="this.src='images/default-product.jpg'">
                    
                    <div class="product-content">
                        <h3 class="product-title"><?php echo htmlspecialchars($prod['product_name']); ?></h3>
                        <p class="product-company">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($prod['company']); ?>
                        </p>
                        
                        <div class="product-details">
                            <p><strong>Active Ingredient:</strong> <?php echo htmlspecialchars($prod['active_ingredient']); ?></p>
                            <p><strong>Dose:</strong> <?php echo htmlspecialchars($prod['dose']); ?></p>
                            <p><strong>Form:</strong> <?php echo htmlspecialchars($prod['form']); ?></p>
                            <p><strong>Barcode:</strong> <?php echo htmlspecialchars($prod['barcode']); ?></p>
                        </div>

                        <div class="product-price">
                            <?php echo number_format($prod['price'], 0); ?> IQD
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
