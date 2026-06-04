<?php
// Debug dental boolean handling
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pharmacy_db', 'root', '12345');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Testing dental contains_fluoride handling:\n\n";
    
    // Test different boolean scenarios
    $testScenarios = [
        ['name' => 'Checkbox checked', 'value' => true],
        ['name' => 'Checkbox unchecked', 'value' => false],
        ['name' => 'isset() result true', 'value' => isset($_POST['some_value'])], // Will be false
        ['name' => 'Empty string', 'value' => ''],
        ['name' => 'String "1"', 'value' => '1'],
        ['name' => 'String "0"', 'value' => '0'],
        ['name' => 'Integer 1', 'value' => 1],
        ['name' => 'Integer 0', 'value' => 0],
    ];
    
    foreach ($testScenarios as $scenario) {
        $originalValue = $scenario['value'];
        $convertedValue = (bool)($originalValue ?? false) ? 1 : 0;
        
        echo "Scenario: {$scenario['name']}\n";
        echo "  Original: " . var_export($originalValue, true) . "\n";
        echo "  Converted: " . var_export($convertedValue, true) . "\n";
        echo "  Type: " . gettype($convertedValue) . "\n\n";
    }
    
    // Test actual database update with safe values
    echo "Testing database update with integer values:\n";
    
    $testQuery = "UPDATE dental SET contains_fluoride = :contains_fluoride WHERE id = 9999"; // Non-existent ID
    $stmt = $pdo->prepare($testQuery);
    
    $testValues = [0, 1];
    foreach ($testValues as $value) {
        try {
            $stmt->execute([':contains_fluoride' => $value]);
            echo "✓ Successfully tested value: $value\n";
        } catch (Exception $e) {
            echo "✗ Failed with value $value: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
