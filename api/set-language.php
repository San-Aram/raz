<?php
session_start();
require_once '../includes/language-functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lang = $_POST['lang'] ?? '';
    
    if (setLanguage($lang)) {
        echo json_encode([
            'success' => true,
            'language' => getCurrentLanguage(),
            'message' => 'Language changed successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid language'
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return current language and available languages
    echo json_encode([
        'current' => getCurrentLanguage(),
        'available' => getAvailableLanguages(),
        'isRTL' => isRTL()
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
