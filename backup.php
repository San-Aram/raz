<?php
require_once 'includes/config.php';

// Start session and check admin authentication
session_start();

// Check if user is logged in as admin OR manager
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isManager = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

if (!$isAdmin && !$isManager) {
    header('Location: login.php');
    exit();
}

// Define user info
if ($isAdmin) {
    $currentUser = [
        'username' => $_SESSION['admin_username'] ?? 'Admin',
        'role' => 'admin'
    ];
} else {
    $currentUser = [
        'username' => $_SESSION['username'] ?? 'Manager',
        'role' => 'manager'
    ];
}

// Create backups directory if it doesn't exist
$backupsDir = "backups/";
if (!is_dir($backupsDir)) {
    mkdir($backupsDir, 0755, true);
}

// Find the next backup version number
$counter = 1;
$filename = $backupsDir . "backup" . $counter . ".sql";

while (file_exists($filename)) {
    $counter++;
    $filename = $backupsDir . "backup" . $counter . ".sql";
}

// Get database credentials from config
$dbConfig = getDatabaseConfig();
$host = $dbConfig['host'];
$database = $dbConfig['dbname'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];

// Create mysqldump command
$command = "mysqldump -h $host -u $username -p$password $database > \"$filename\"";

// Execute the backup
$output = [];
$return_var = 0;
exec($command, $output, $return_var);

if ($return_var === 0) {
    // Send file to browser for download
    if (file_exists($filename)) {
        $downloadName = "backup" . $counter . ".sql";
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($filename));
        
        // Output file contents
        readfile($filename);
        
        // Keep the backup file in the backups directory for future reference
        exit();
    } else {
        echo "Error: Backup file was not created.";
    }
} else {
    echo "Error creating backup. Please check your database configuration.";
    echo "<br>Command: " . htmlspecialchars($command);
    echo "<br>Output: " . implode("<br>", $output);
}
?>
