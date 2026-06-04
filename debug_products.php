<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pharmacy_db', 'root', '12345');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Products table structure:\n";
    $result = $pdo->query('DESCRIBE products');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Key'] . "\n";
    }
    
    echo "\nChecking for duplicate barcodes:\n";
    $result = $pdo->query('SELECT barcode, COUNT(*) as count FROM products GROUP BY barcode HAVING count > 1');
    $duplicates = $result->fetchAll(PDO::FETCH_ASSOC);
    if (empty($duplicates)) {
        echo "No duplicate barcodes found.\n";
    } else {
        foreach ($duplicates as $dup) {
            echo "Barcode: " . $dup['barcode'] . " appears " . $dup['count'] . " times\n";
        }
    }
    
    echo "\nTotal products count: ";
    $result = $pdo->query('SELECT COUNT(*) as count FROM products');
    $count = $result->fetch(PDO::FETCH_ASSOC);
    echo $count['count'] . "\n";
    
    echo "\nRecent products (last 10):\n";
    $result = $pdo->query('SELECT id, barcode, product_name, created_at FROM products ORDER BY id DESC LIMIT 10');
    $recent = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recent as $prod) {
        echo "ID: " . $prod['id'] . " | Barcode: " . $prod['barcode'] . " | Name: " . $prod['product_name'] . " | Created: " . ($prod['created_at'] ?? 'N/A') . "\n";
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
