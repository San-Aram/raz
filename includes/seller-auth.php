<?php
session_start();

// Check if user is logged in and has seller role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'seller') {
    // Get current page for redirect after login
    $currentPage = $_SERVER['REQUEST_URI'];
    header('Location: seller-login.php?redirect=' . urlencode($currentPage));
    exit;
}
?>