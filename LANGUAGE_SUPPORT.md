# Multi-Language Support Documentation

## Overview
The Razology Pharmacy Management System now includes full multi-language support for **English**, **Sorani Kurdish (ckb)**, and **Arabic (ar)**.

## Supported Languages
- **English (en)** - Default language
- **Sorani Kurdish (ckb)** - RTL support
- **Arabic (ar)** - RTL support

## How It Works

### Language Files
Language translations are stored in PHP files in the `includes/` directory:
- `includes/lang-en.php` - English translations
- `includes/lang-ckb.php` - Sorani Kurdish translations
- `includes/lang-ar.php` - Arabic translations

### Translation Function
The `t()` function retrieves translated strings using a key-based system:
```php
<?php echo t('header.home'); ?> // Returns "Home" in English, "ماڵ" in Kurdish, etc.
?>
```

### Language Selection
Users can switch languages using the language dropdown selector in the navigation bar on any page (except login pages). The language preference is stored in the PHP session and persists throughout the user's session.

## Implementation Details

### Core Files
- **includes/language-functions.php** - Contains all language management functions
- **api/set-language.php** - API endpoint for changing language preference
- **includes/lang-*.php** - Translation files for each language

### Functions
The following functions are available in `language-functions.php`:

```php
initializeLanguage()           // Initialize language from session
getCurrentLanguage()           // Get the current language code
setLanguage($lang)            // Set the language (en, ckb, ar)
t($key, $default)             // Get translated string by key
getLanguageName($lang)        // Get native language name
isRTL()                        // Check if current language is RTL
getTextDirection()             // Get direction (ltr or rtl)
getHtmlLang()                  // Get HTML lang attribute value
```

### Usage in PHP Files

1. **Include the language functions:**
```php
<?php
require_once 'includes/language-functions.php';
initializeLanguage();
?>
```

2. **Use the correct HTML tag:**
```php
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo getTextDirection(); ?>">
```

3. **Translate UI strings:**
```php
<a href="index.php"><?php echo t('header.home'); ?></a>
<button><?php echo t('common.save'); ?></button>
```

4. **Add language selector in navigation:**
```php
<div class="nav-language-selector">
    <select id="languageSelect" class="language-select" onchange="changeLanguage(this.value)">
        <option value="en">English</option>
        <option value="ckb">سۆرانی</option>
        <option value="ar">العربية</option>
    </select>
</div>
```

5. **Add language switching JavaScript before closing body tag:**
```javascript
<script>
function changeLanguage(lang) {
    const formData = new FormData();
    formData.append('lang', lang);
    
    fetch('api/set-language.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    const langSelect = document.getElementById('languageSelect');
    if (langSelect) {
        langSelect.value = '<?php echo getCurrentLanguage(); ?>';
        
        if ('<?php echo getTextDirection(); ?>' === 'rtl') {
            document.documentElement.dir = 'rtl';
        }
    }
});
</script>
```

## Pages Updated with Language Support

### Main Pages
- ✅ index.php - Home/Dashboard
- ✅ checkout.php - POS System
- ✅ products.php - Product Listing
- ✅ medications.php - Medication Search
- ✅ sales-history.php - Sales History

### Admin Pages
- ✅ admin-dashboard.php - Admin Dashboard
- ✅ admin-settings.php - System Settings
- ✅ admin-users.php - User Management

### Seller Pages
- ✅ seller-dashboard.php - Seller Dashboard

### Detail Pages
- ✅ product-detail.php - Product Details
- ✅ medication-detail.php - Medication Details
- ✅ dental-detail.php - Dental Product Details
- ✅ cosmetics-detail.php - Cosmetic Product Details

### Pages NOT Translated (As Requested)
- ❌ login.php - User login (English only)
- ❌ admin-login.php - Admin login (English only)
- ❌ seller-login.php - Seller login (English only)

**Note:** Login pages are kept in English only for security and consistency reasons.

## RTL (Right-to-Left) Support

### Automatic RTL Application
The system automatically applies RTL styling for Arabic and Sorani Kurdish:
- `dir="rtl"` attribute set on HTML tag
- CSS flexbox items reversed with `flex-direction: row-reverse`
- Text alignment adjusted for RTL languages
- Form inputs and text areas set to right-aligned

### RTL CSS Rules
Comprehensive RTL CSS is defined in `css/style.css` and includes:
- Navigation reversal
- Form layout adjustment
- Table direction reversal
- Modal and dropdown positioning
- Button and link alignment

## Translation Keys Structure

Translations are organized by section:
```
header.*         - Navigation and header items
login.*          - Login page strings
checkout.*       - POS/Checkout strings
products.*       - Product listing strings
admin.*          - Admin panel strings
inventory.*      - Inventory management strings
stats.*          - Statistics page strings
common.*         - Common UI elements (buttons, etc.)
messages.*       - Alert and message strings
```

## Adding New Translations

### To add a new translation:

1. **Add the key to all language files:**

In `includes/lang-en.php`:
```php
"mySection" => [
    "myKey" => "English text"
]
```

In `includes/lang-ckb.php`:
```php
"mySection" => [
    "myKey" => "کوردی تێکست"
]
```

In `includes/lang-ar.php`:
```php
"mySection" => [
    "myKey" => "النص العربي"
]
```

2. **Use in PHP code:**
```php
<?php echo t('mySection.myKey'); ?>
```

## Translation Completeness
All main UI text has been translated:
- Navigation menus
- Button labels
- Form labels
- Page titles
- Status messages
- Table headers
- Modal titles

**Product/Medication Names**: These remain unchanged and are NOT translated, as per requirements.

## Browser Compatibility
- Chrome/Edge: ✅ Full support
- Firefox: ✅ Full support
- Safari: ✅ Full support
- Mobile Browsers: ✅ Full support with RTL

## Session Management
Language preference is stored in the PHP session (`$_SESSION['language']`):
- Persists across pages during user session
- Can be changed via language selector
- Resets when user logs out
- Defaults to English on first visit

## API Endpoint

### set-language.php
**Method:** POST
**Parameters:** `lang` (en, ckb, or ar)
**Response:** JSON with success status and updated language info

Example:
```javascript
fetch('api/set-language.php', {
    method: 'POST',
    body: new FormData(Object.assign(document.createElement('form'), {
        elements: [{name: 'lang', value: 'ar'}]
    }))
})
.then(r => r.json())
.then(d => console.log(d));
```

## Troubleshooting

### Translations not showing?
1. Check if `require_once 'includes/language-functions.php';` is added
2. Check if `initializeLanguage();` is called
3. Verify language file exists for the selected language
4. Check that translation keys match exactly

### RTL not working?
1. Verify `dir="<?php echo getTextDirection(); ?>"` is on HTML tag
2. Check CSS file loads properly
3. Test with Arabic or Kurdish language selected
4. Check browser developer tools for dir attribute

### Language not persisting?
1. Ensure session_start() is called on the page
2. Check that api/set-language.php is accessible
3. Verify POST request goes to correct API endpoint
4. Check browser cookie settings

## Future Enhancements
- Add more languages
- Implement translation management UI
- Add pluralization support
- Add date/time localization
- Add currency localization
