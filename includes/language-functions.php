<?php
/**
 * Multi-language support functions
 * Supports: English (en), Sorani Kurdish (ckb), Arabic (ar)
 */

// Default language
define('DEFAULT_LANGUAGE', 'en');
define('SUPPORTED_LANGUAGES', ['en', 'ckb', 'ar']);

// Initialize language from session or GET parameter
function initializeLanguage() {
    // Check if language is being set via GET parameter
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
        $_SESSION['language'] = $_GET['lang'];
    }
    
    // Set language from session or use default
    if (!isset($_SESSION['language'])) {
        $_SESSION['language'] = DEFAULT_LANGUAGE;
    }
    
    return $_SESSION['language'];
}

// Get current language
function getCurrentLanguage() {
    return $_SESSION['language'] ?? DEFAULT_LANGUAGE;
}

// Set language
function setLanguage($lang) {
    if (in_array($lang, SUPPORTED_LANGUAGES)) {
        $_SESSION['language'] = $lang;
        return true;
    }
    return false;
}

// Load language file
function loadLanguageFile($lang = null) {
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    
    $filePath = __DIR__ . '/lang-' . $lang . '.php';
    
    if (!file_exists($filePath)) {
        $filePath = __DIR__ . '/lang-en.php';
    }
    
    return include $filePath;
}

// Get translation
function t($key, $default = '') {
    static $translations = null;
    
    if ($translations === null) {
        $translations = loadLanguageFile();
    }
    
    // Support nested keys with dot notation (e.g., 'header.home')
    $keys = explode('.', $key);
    $value = $translations;
    
    foreach ($keys as $k) {
        if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $default ?: $key;
        }
    }
    
    return $value ?: ($default ?: $key);
}

// Get language name
function getLanguageName($lang) {
    $names = [
        'en' => 'English',
        'ckb' => 'سۆرانی',
        'ar' => 'العربية'
    ];
    return $names[$lang] ?? $lang;
}

// Get language display name
function getLanguageDisplayName($lang) {
    $names = [
        'en' => 'English',
        'ckb' => 'Sorani',
        'ar' => 'Arabic'
    ];
    return $names[$lang] ?? $lang;
}

// Check if current language is RTL
function isRTL() {
    $rtlLanguages = ['ar', 'ckb'];
    return in_array(getCurrentLanguage(), $rtlLanguages);
}

// Get text direction
function getTextDirection() {
    return isRTL() ? 'rtl' : 'ltr';
}

// Determine HTML lang attribute
function getHtmlLang() {
    $lang = getCurrentLanguage();
    $langMap = [
        'en' => 'en',
        'ckb' => 'ckb',
        'ar' => 'ar'
    ];
    return $langMap[$lang] ?? 'en';
}

// Get all available languages
function getAvailableLanguages() {
    return array_combine(SUPPORTED_LANGUAGES, array_map('getLanguageName', SUPPORTED_LANGUAGES));
}

// Reload translations (useful after language change)
function reloadTranslations() {
    // Force reload by clearing cache
    return loadLanguageFile();
}
?>
