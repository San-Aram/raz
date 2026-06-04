<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pharmacy_db', 'root', '12345');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Current medications table structure:\n";
    $result = $pdo->query('DESCRIBE medications');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\n--- Adding new dosage columns ---\n";
    
    // Add new columns for adult and children dosages
    try {
        $pdo->exec('ALTER TABLE medications ADD COLUMN adult_dosage_1 TEXT');
        echo "Added 'adult_dosage_1' column\n";
    } catch(Exception $e) {
        echo "Column 'adult_dosage_1' already exists or error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec('ALTER TABLE medications ADD COLUMN adult_frequency_1 TEXT');
        echo "Added 'adult_frequency_1' column\n";
    } catch(Exception $e) {
        echo "Column 'adult_frequency_1' already exists or error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec('ALTER TABLE medications ADD COLUMN adult_dosage_2 TEXT');
        echo "Added 'adult_dosage_2' column\n";
    } catch(Exception $e) {
        echo "Column 'adult_dosage_2' already exists or error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec('ALTER TABLE medications ADD COLUMN adult_frequency_2 TEXT');
        echo "Added 'adult_frequency_2' column\n";
    } catch(Exception $e) {
        echo "Column 'adult_frequency_2' already exists or error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec('ALTER TABLE medications ADD COLUMN children_dosage_1 TEXT');
        echo "Added 'children_dosage_1' column\n";
    } catch(Exception $e) {
        echo "Column 'children_dosage_1' already exists or error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec('ALTER TABLE medications ADD COLUMN children_frequency_1 TEXT');
        echo "Added 'children_frequency_1' column\n";
    } catch(Exception $e) {
        echo "Column 'children_frequency_1' already exists or error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec('ALTER TABLE medications ADD COLUMN children_dosage_2 TEXT');
        echo "Added 'children_dosage_2' column\n";
    } catch(Exception $e) {
        echo "Column 'children_dosage_2' already exists or error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec('ALTER TABLE medications ADD COLUMN children_frequency_2 TEXT');
        echo "Added 'children_frequency_2' column\n";
    } catch(Exception $e) {
        echo "Column 'children_frequency_2' already exists or error: " . $e->getMessage() . "\n";
    }
    
    echo "\n--- Migrating existing data ---\n";
    
    // Migrate existing data from old dosage/dose_frequency to new structure
    $stmt = $pdo->query("SELECT id, dosage, dose_frequency FROM medications WHERE dosage IS NOT NULL AND dosage != ''");
    $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($medications as $med) {
        $dosages = explode('|', $med['dosage']);
        $frequencies = explode('|', $med['dose_frequency']);
        
        // Split existing dosages: first 2 go to adult, remaining go to children
        $adult_dosage_1 = isset($dosages[0]) ? trim($dosages[0]) : '';
        $adult_frequency_1 = isset($frequencies[0]) ? trim($frequencies[0]) : '';
        $adult_dosage_2 = isset($dosages[1]) ? trim($dosages[1]) : '';
        $adult_frequency_2 = isset($frequencies[1]) ? trim($frequencies[1]) : '';
        $children_dosage_1 = isset($dosages[2]) ? trim($dosages[2]) : '';
        $children_frequency_1 = isset($frequencies[2]) ? trim($frequencies[2]) : '';
        $children_dosage_2 = isset($dosages[3]) ? trim($dosages[3]) : '';
        $children_frequency_2 = isset($frequencies[3]) ? trim($frequencies[3]) : '';
        
        $updateStmt = $pdo->prepare("
            UPDATE medications SET 
                adult_dosage_1 = :adult_dosage_1,
                adult_frequency_1 = :adult_frequency_1,
                adult_dosage_2 = :adult_dosage_2,
                adult_frequency_2 = :adult_frequency_2,
                children_dosage_1 = :children_dosage_1,
                children_frequency_1 = :children_frequency_1,
                children_dosage_2 = :children_dosage_2,
                children_frequency_2 = :children_frequency_2
            WHERE id = :id
        ");
        
        $updateStmt->execute([
            ':adult_dosage_1' => $adult_dosage_1,
            ':adult_frequency_1' => $adult_frequency_1,
            ':adult_dosage_2' => $adult_dosage_2,
            ':adult_frequency_2' => $adult_frequency_2,
            ':children_dosage_1' => $children_dosage_1,
            ':children_frequency_1' => $children_frequency_1,
            ':children_dosage_2' => $children_dosage_2,
            ':children_frequency_2' => $children_frequency_2,
            ':id' => $med['id']
        ]);
        
        echo "Migrated medication ID " . $med['id'] . "\n";
    }
    
    echo "\n--- Updated structure ---\n";
    $result = $pdo->query('DESCRIBE medications');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\n--- Migration complete! ---\n";
    echo "You can now remove the old 'dosage' and 'dose_frequency' columns if needed.\n";
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
