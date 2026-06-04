<?php
// Simple test without authentication
header('Content-Type: application/json');

$activeIngredient = $_GET['medication'] ?? '';

if (empty($activeIngredient)) {
    echo json_encode([
        'success' => false,
        'message' => 'Active ingredient is required - got: ' . var_export($_GET, true)
    ]);
    exit;
}

// Test DrugBank local data
$commonDrugs = [
    'paracetamol' => [
        'source' => 'DrugBank',
        'drugbank_id' => 'DB00316',
        'name' => 'Acetaminophen',
        'description' => 'Acetaminophen is an analgesic and antipyretic agent.',
        'cas_number' => '103-90-2',
        'indication' => 'For the relief of mild to moderate pain and fever',
        'mechanism_of_action' => 'Inhibits cyclooxygenase enzymes',
        'half_life' => '1-4 hours',
        'absorption' => 'Well absorbed from gastrointestinal tract'
    ]
];

$searchKey = strtolower(trim($activeIngredient));
$drugBankData = null;

if (isset($commonDrugs[$searchKey])) {
    $drugBankData = $commonDrugs[$searchKey];
}

echo json_encode([
    'success' => true,
    'data' => [
        'fda' => null,
        'drugbank' => $drugBankData
    ],
    'debug' => [
        'received_medication' => $activeIngredient,
        'search_key' => $searchKey,
        'available_drugs' => array_keys($commonDrugs)
    ]
]);
?>
