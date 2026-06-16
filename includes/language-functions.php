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

// Load one language file plus optional project overrides.
function loadLanguageData($lang) {
    $filePath = __DIR__ . '/lang-' . $lang . '.php';

    if (!file_exists($filePath)) {
        $filePath = __DIR__ . '/lang-' . DEFAULT_LANGUAGE . '.php';
    }

    $translations = include $filePath;
    $overridePath = __DIR__ . '/lang-overrides.php';

    if (file_exists($overridePath)) {
        $overrides = include $overridePath;
        if (isset($overrides[$lang]) && is_array($overrides[$lang])) {
            $translations = array_replace_recursive($translations, $overrides[$lang]);
        }
    }

    return $translations;
}

// Load language file
function loadLanguageFile($lang = null) {
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }

    $translations = loadLanguageData($lang);

    // Missing keys in translated files should fall back to readable English,
    // not leak raw keys like admin.viewLogs into the UI.
    if ($lang !== DEFAULT_LANGUAGE) {
        $translations = array_replace_recursive(loadLanguageData(DEFAULT_LANGUAGE), $translations);
    }

    return $translations;
}

// Get translation
function t($key, $default = '') {
    static $translationsByLanguage = [];

    $language = getCurrentLanguage();

    if (!isset($translationsByLanguage[$language])) {
        $translationsByLanguage[$language] = loadLanguageFile($language);
    }

    $translations = $translationsByLanguage[$language];
    
    // Support nested keys with dot notation (e.g., 'header.home')
    $keys = explode('.', $key);
    $value = $translations;
    
    foreach ($keys as $k) {
        if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $default ?: humanizeTranslationKey($key);
        }
    }
    
    return $value ?: ($default ?: humanizeTranslationKey($key));
}

// Final safety net for missing translations.
function humanizeTranslationKey($key) {
    $parts = explode('.', $key);
    $label = end($parts);
    $label = str_replace(['_', '-'], ' ', $label);
    $label = preg_replace('/(?<!^)[A-Z]/', ' $0', $label);
    return ucwords(trim($label));
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
