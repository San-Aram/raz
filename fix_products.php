<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pharmacy_db', 'root', '12345');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRODUCT DATABASE CLEANUP ===\n\n";
    
    // 1. Check for duplicate barcodes
    echo "1. Checking for duplicate barcodes...\n";
    $result = $pdo->query('
        SELECT barcode, COUNT(*) as count, GROUP_CONCAT(id) as ids 
        FROM products 
        GROUP BY barcode 
        HAVING count > 1
    ');
    $duplicates = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        echo "   ✓ No duplicate barcodes found.\n";
    } else {
        echo "   ⚠ Found " . count($duplicates) . " duplicate barcode(s):\n";
        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup['ids']);
            echo "   - Barcode: {$dup['barcode']} (IDs: {$dup['ids']})\n";
            
            // Keep the first one, delete the rest
            $keepId = array_shift($ids);
            foreach ($ids as $deleteId) {
                echo "     Deleting duplicate ID: $deleteId\n";
                $pdo->exec("DELETE FROM products WHERE id = $deleteId");
            }
        }
        echo "   ✓ Duplicates cleaned up.\n";
    }
    
    // 2. Check for orphaned medication links
    echo "\n2. Checking for orphaned medication links...\n";
    $result = $pdo->query('
        SELECT p.id, p.product_name, p.active_ingredient, p.medication_id 
        FROM products p 
        LEFT JOIN medications m ON p.medication_id = m.id 
        WHERE p.medication_id IS NOT NULL AND m.id IS NULL
    ');
    $orphaned = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orphaned)) {
        echo "   ✓ No orphaned medication links found.\n";
    } else {
        echo "   ⚠ Found " . count($orphaned) . " orphaned medication link(s):\n";
        foreach ($orphaned as $orph) {
            echo "   - Product ID {$orph['id']}: {$orph['product_name']} (medication_id: {$orph['medication_id']})\n";
            $pdo->exec("UPDATE products SET medication_id = NULL WHERE id = {$orph['id']}");
        }
        echo "   ✓ Orphaned links cleaned up.\n";
    }
    
    // 3. Fix medication linking for existing products
    echo "\n3. Fixing medication linking...\n";
    $result = $pdo->query('
        SELECT p.id, p.active_ingredient 
        FROM products p 
        WHERE p.medication_id IS NULL AND p.active_ingredient != ""
    ');
    $unlinkeds = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $linkedCount = 0;
    foreach ($unlinkeds as $unlinked) {
        $medicationQuery = $pdo->prepare('
            SELECT id FROM medications 
            WHERE TRIM(LOWER(active_ingredient)) = TRIM(LOWER(?))
        ');
        $medicationQuery->execute([$unlinked['active_ingredient']]);
        $medication = $medicationQuery->fetch(PDO::FETCH_ASSOC);
        
        if ($medication) {
            $pdo->exec("UPDATE products SET medication_id = {$medication['id']} WHERE id = {$unlinked['id']}");
            $linkedCount++;
        }
    }
    echo "   ✓ Linked $linkedCount products to medications.\n";
    
    // 4. Final status
    echo "\n4. Final database status:\n";
    $result = $pdo->query('SELECT COUNT(*) as count FROM products');
    $totalProducts = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Total products: $totalProducts\n";
    
    $result = $pdo->query('SELECT COUNT(*) as count FROM products WHERE medication_id IS NOT NULL');
    $linkedProducts = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Products linked to medications: $linkedProducts\n";
    
    echo "\n=== CLEANUP COMPLETED ===\n";
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
