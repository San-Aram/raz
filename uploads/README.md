# Upload Directory

This directory stores uploaded product images.

## Security Features
- Only image files are allowed (JPEG, PNG, GIF, WebP)
- Maximum file size: 5MB
- PHP files are blocked via .htaccess
- Files are renamed with unique IDs to prevent conflicts

## File Structure
- Product images are stored with prefix `product_` followed by unique ID
- Original filename extensions are preserved
- Files are accessible via web URL for display in product pages

## Maintenance
- Regularly clean up orphaned images (images not linked to products)
- Monitor disk space usage
- Consider implementing image compression for large files
