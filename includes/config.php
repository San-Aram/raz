<?php
// Configuration file for different environments

// Detect environment
function getEnvironment() {
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            return 'local';
        } elseif (strpos($host, 'atwebpages.com') !== false) {
            return 'production';
        }
    }
    return 'local'; // Default to local
}

// Database configuration based on environment
function getDatabaseConfig() {
    $env = getEnvironment();
    
    switch ($env) {
        case 'production':
            return [
                'host' => 'localhost', // Most shared hosts use localhost
                'dbname' => 'your_database_name', // UPDATE: Get this from your hosting control panel
                'username' => 'your_db_username', // UPDATE: Get this from your hosting control panel
                'password' => 'your_db_password', // UPDATE: Get this from your hosting control panel
                'charset' => 'utf8mb4'
            ];
        
        case 'local':
        default:
            return [
                'host' => 'localhost',
                'dbname' => 'fyp',
                'username' => 'root',
                'password' => '12345',
                'charset' => 'utf8mb4'
            ];
    }
}

// Get current environment name
function getCurrentEnvironment() {
    return getEnvironment();
}
?>
