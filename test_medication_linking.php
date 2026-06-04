<?php
// Test medication linking with multiple active ingredients
require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();
$medication = new Medication($db);

echo "=== MEDICATION LINKING TEST ===\n\n";

// Test case 1: Single active ingredient
echo "1. Testing single active ingredient:\n";
$testIngredient = "Paracetamol";
$result = $medication->getByActiveIngredient($testIngredient);
if ($result) {
    echo "   ✓ Found medication for '$testIngredient'\n";
    echo "   - ID: " . $result['id'] . "\n";
    echo "   - Active Ingredient: " . $result['active_ingredient'] . "\n";
} else {
    echo "   ✗ No medication found for '$testIngredient'\n";
}

// Test case 2: Multiple active ingredients (simulating product data)
echo "\n2. Testing multiple active ingredients:\n";
$multipleIngredients = "Paracetamol | Ibuprofen | Caffeine";
$ingredients = explode(' | ', $multipleIngredients);

echo "   Testing ingredients: $multipleIngredients\n";
$foundMedications = [];

foreach ($ingredients as $ingredient) {
    $ingredient = trim($ingredient);
    echo "   - Searching for: '$ingredient'\n";
    
    $result = $medication->getByActiveIngredient($ingredient);
    if ($result) {
        echo "     ✓ Found: " . $result['active_ingredient'] . " (ID: " . $result['id'] . ")\n";
        $foundMedications[] = $result;
    } else {
        echo "     ✗ Not found\n";
    }
}

echo "\n3. Summary:\n";
echo "   Total ingredients tested: " . count($ingredients) . "\n";
echo "   Medications found: " . count($foundMedications) . "\n";

if (!empty($foundMedications)) {
    echo "   Found medications:\n";
    foreach ($foundMedications as $med) {
        echo "   - " . $med['active_ingredient'] . " (Class: " . $med['class'] . ")\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
?>
