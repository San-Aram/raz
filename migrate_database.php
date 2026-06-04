<?php
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '12345');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec('CREATE DATABASE IF NOT EXISTS pharmacy_db');
    $pdo->exec('USE pharmacy_db');
    
    // Add new columns to medications table if they don't exist
    $columns = [
        'adult_dosage_1' => 'VARCHAR(255)',
        'adult_frequency_1' => 'VARCHAR(255)',
        'adult_dosage_2' => 'VARCHAR(255)',
        'adult_frequency_2' => 'VARCHAR(255)',
        'children_dosage_1' => 'VARCHAR(255)',
        'children_frequency_1' => 'VARCHAR(255)',
        'children_dosage_2' => 'VARCHAR(255)',
        'children_frequency_2' => 'VARCHAR(255)'
    ];
    
    foreach ($columns as $column => $type) {
        try {
            $pdo->exec("ALTER TABLE medications ADD COLUMN $column $type");
            echo "Added column $column to medications table\n";
        } catch (Exception $e) {
            echo "Column $column already exists or error: " . $e->getMessage() . "\n";
        }
    }
    
    // Create cosmetics table
    $sql = 'CREATE TABLE IF NOT EXISTS cosmetics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        barcode VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        company VARCHAR(255) NOT NULL,
        indication TEXT,
        notes TEXT,
        class VARCHAR(255),
        price DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_name (name),
        INDEX idx_barcode (barcode)
    )';
    $pdo->exec($sql);
    echo "Cosmetics table created successfully!\n";
    
    // Create dental table
    $sql = 'CREATE TABLE IF NOT EXISTS dental (
        id INT AUTO_INCREMENT PRIMARY KEY,
        barcode VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        company VARCHAR(255) NOT NULL,
        indication TEXT,
        notes TEXT,
        class VARCHAR(255),
        price DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_name (name),
        INDEX idx_barcode (barcode)
    )';
    $pdo->exec($sql);
    echo "Dental table created successfully!\n";
    
    echo "Database migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
