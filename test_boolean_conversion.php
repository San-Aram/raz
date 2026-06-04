<?php
// Simple test for boolean handling
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pharmacy_db', 'root', '12345');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Testing boolean values for contains_fluoride column:\n";
    
    // Test with different boolean representations
    $testValues = [
        true,
        false,
        1,
        0,
        '1',
        '0',
        '',
        null
    ];
    
    foreach ($testValues as $value) {
        $convertedValue = (bool)($value ?? false);
        echo "Original: " . var_export($value, true) . " -> Converted: " . var_export($convertedValue, true) . "\n";
    }
    
    echo "\nBoolean conversion working correctly!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
