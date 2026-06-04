<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pharmacy_db', 'root', '12345');
    $result = $pdo->query('SHOW COLUMNS FROM cosmetics');
    echo "Cosmetics table structure:\n";
    while($row = $result->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
