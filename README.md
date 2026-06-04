# Razology - Professional Pharmacy Management System

A comprehensive dual-role pharmacy management system with manager and seller/POS interfaces, featuring medication database, barcode scanning, inventory management, sales processing, and real-time notifications.

## Features

### � **Dual-Role System**
- **Manager Interface**: Complete pharmacy management with full inventory control
- **Seller/POS Interface**: Streamlined point-of-sale system for retail operations
- Role-based authentication and access control
- Secure session management

### �🔍 **Smart Search & Filtering**
- Search medications by name with real-time results
- Filter by pregnancy safety (Yes/No)
- Filter by lactation safety (Yes/No)
- Advanced medication lookup with category filtering
- Product search with barcode integration

### 📱 **Advanced Barcode Scanner**
- Real-time barcode scanning using device camera
- Manual barcode entry option
- Automatic product lookup across all categories (products, cosmetics, dental)
- Add new products for unknown barcodes
- Cross-platform compatibility (manager and seller systems)
- QuaggaJS integration with enhanced error handling

### 💊 **Comprehensive Medication Database**
- Complete medication profiles with:
  - Class of medication and therapeutic category
  - Mechanism of action
  - Indications and contraindications
  - Side effects and adverse reactions
  - Pregnancy & lactation safety profiles
  - Dosage and frequency information
  - FDA integration for medication lookup
  - Medication images and visual identification
  - Active ingredient linking

### 📦 **Multi-Category Product Management**
- **Pharmaceuticals**: Full medication inventory with barcode tracking
- **Cosmetics**: Beauty and personal care products
- **Dental**: Dental care and oral hygiene products
- Manufacturer and company information
- Active ingredient linking to medication database
- Product image upload with file management
- Price management and inventory tracking
- Stock level monitoring

### 💰 **Point of Sale (POS) System**
- Complete checkout interface for sellers
- Barcode scanning integration
- Product search and manual entry
- Shopping cart management
- Multiple payment methods (Cash, Card, Mobile Money, Insurance)
- Automatic change calculation
- Receipt generation and printing
- Transaction history and tracking

### 📊 **Sales Analytics & Reporting**
- Real-time sales dashboard with interactive charts
- Daily, weekly, monthly sales statistics
- Product performance analysis
- AI-powered sales recommendations
- Revenue tracking and profit analysis
- Best-selling products identification
- Sales trend visualization
- Comprehensive reporting system

### 🔔 **Real-Time Notification System**
- Inventory alerts for out-of-stock items
- Low stock warnings with customizable thresholds
- Product expiration notifications
- Expiry warnings for items nearing expiration
- Smart notification filtering and categorization
- Individual and bulk notification dismissal
- 24-hour dismissal grace period
- Critical alert overrides for urgent items

### 🔗 **Smart Linking & Integration**
- Products linked to medication database via active ingredients
- Click active ingredient to view detailed medical information
- Seamless navigation between products and medications
- FDA API integration for medication data
- Cross-category barcode compatibility

### 📱 **Calculator & Utilities**
- Built-in calculator for pricing and dosage calculations
- Keyboard shortcuts for POS efficiency
- Quick access functions (F1-F4 shortcuts)
- Mobile-responsive design for tablets and phones

## Technology Stack

- **Backend**: PHP 7.4+ with PDO for database operations
- **Database**: MySQL 5.7+ with optimized schema and indexing
- **Frontend**: HTML5, CSS3, JavaScript ES6+
- **Barcode Scanning**: QuaggaJS with camera integration
- **Charts & Analytics**: Chart.js for data visualization
- **Icons**: Font Awesome 6
- **Responsive**: Mobile-first design with CSS Grid and Flexbox
- **APIs**: RESTful API architecture
- **Session Management**: Secure PHP sessions with role-based access
- **External Integration**: FDA Drug API for medication data

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser with camera support

### Setup Instructions

1. **Clone/Download the project**
   ```bash
   # Download and extract to your web server directory
   # Example: C:\xampp\htdocs\pharmacy or /var/www/html/pharmacy
   ```

2. **Database Setup**
   ```sql
   # Import the database schema
   mysql -u root -p < database/init.sql
   ```

3. **Configure Database Connection**
   - Edit `includes/database.php`
   - Update database credentials:
     ```php
     private $host = 'localhost';
     private $db_name = 'pharmacy_db';
     private $username = 'root';
     private $password = 'your_password';
     ```

4. **Web Server Configuration**
   - Ensure PHP is enabled
   - Set document root to project directory
   - Enable mod_rewrite (for Apache)

5. **Permissions**
   ```bash
   # Set appropriate permissions
   chmod 755 /path/to/pharmacy
   chmod 644 /path/to/pharmacy/includes/database.php
   ```

## Directory Structure

```
raz/
├── .github/
│   └── copilot-instructions.md
├── api/
│   ├── add-cosmetic.php
│   ├── add-dental.php
│   ├── add-product.php
│   ├── check-barcode.php
│   ├── delete-cosmetic.php
│   ├── delete-dental.php
│   ├── delete-medication.php
│   ├── delete-product.php
│   ├── fda-lookup.php
│   ├── notifications.php
│   ├── stats.php
│   └── uploads/
├── backups/
│   └── *.sql (database backups)
├── css/
│   └── style.css
├── database/
│   └── init.sql
├── images/
│   ├── default-medication.jpg
│   └── default-product.jpg
├── includes/
│   ├── auth.php
│   ├── config.php
│   ├── database.php
│   └── upload.php
├── js/
│   ├── barcode-scanner.js
│   ├── calculator.js
│   ├── checkout.js
│   ├── fda-lookup.js
│   ├── main.js
│   └── notifications.js
├── uploads/ (product images)
├── Manager Interface:
│   ├── index.php (dashboard)
│   ├── add-medication.php
│   ├── medications.php
│   ├── medication-detail.php
│   ├── products.php
│   ├── product-detail.php
│   ├── cosmetics-detail.php
│   ├── dental-detail.php
│   ├── statistics.php
│   └── calculator.php
├── Seller Interface:
│   ├── login.php
│   ├── checkout.php
│   ├── sales-history.php
│   └── sales-analytics.php
├── Utilities:
│   ├── migrate_database.php
│   ├── backup.php
│   └── various debug/test files
└── README.md
```

## Usage

### Manager Interface

#### Adding Medications
1. Navigate to "Add Medication" from dashboard
2. Fill in comprehensive medication information:
   - Basic details (name, class, mechanism)
   - Clinical information (indications, contraindications, side effects)
   - Safety profiles (pregnancy and lactation)
   - Dosage and frequency details
3. Optional: Upload medication image
4. Submit to save to database

#### Product Management
1. **Barcode Scanning**: Use camera or manual entry to lookup/add products
2. **Multi-Category Support**: Manage pharmaceuticals, cosmetics, and dental products
3. **Inventory Control**: Track stock levels, set reorder points
4. **Product Details**: Complete product profiles with images and specifications
5. **Active Ingredient Linking**: Connect products to medication database

#### Inventory Monitoring
1. **Real-time Notifications**: Get alerts for stock issues
2. **Expiration Tracking**: Monitor product expiry dates
3. **Smart Filtering**: View notifications by category and severity
4. **Bulk Actions**: Dismiss multiple notifications at once

### Seller/POS Interface

#### User Authentication
1. Login with seller credentials
2. Access streamlined POS dashboard
3. View sales statistics and quick actions

#### Checkout Process
1. **Product Entry**: Scan barcodes, search, or manual entry
2. **Shopping Cart**: Add/remove items, adjust quantities
3. **Payment Processing**: Support for multiple payment methods
4. **Receipt Generation**: Print customer receipts
5. **Transaction Recording**: Automatic sales tracking

#### Sales Management
1. **Transaction History**: View past sales with detailed breakdowns
2. **Sales Analytics**: Access performance metrics and trends
3. **Product Lookup**: Search inventory without adding to cart
4. **Daily Reporting**: Track daily sales and revenue

### Common Features

#### Smart Search
1. Use search bars on any page for quick product/medication lookup
2. Apply filters for pregnancy/lactation safety
3. Category-specific filtering
4. Real-time search suggestions

#### Barcode Integration
1. **Camera Scanning**: Point device camera at barcode
2. **Manual Entry**: Type barcode numbers directly
3. **Cross-System Compatibility**: Works in both manager and seller interfaces
4. **New Product Creation**: Add unknown barcodes with full product details

## API Endpoints

### Core APIs
- `api/stats.php` - Dashboard statistics and analytics
- `api/check-barcode.php` - Universal barcode lookup (supports both manager and seller)
- `api/notifications.php` - Real-time inventory notifications

### Product Management
- `api/add-product.php` - Add new pharmaceutical products
- `api/add-cosmetic.php` - Add cosmetic products
- `api/add-dental.php` - Add dental products
- `api/delete-product.php` - Remove products from inventory
- `api/delete-cosmetic.php` - Remove cosmetic products
- `api/delete-dental.php` - Remove dental products
- `api/delete-medication.php` - Remove medications

### External Integration
- `api/fda-lookup.php` - FDA Drug Database integration
- External FDA API for medication data validation

## Database Schema

### Core Tables

#### Medications Table
- Complete pharmaceutical information with clinical data
- Safety profiles for pregnancy and lactation
- Dosage, frequency, and pricing information
- Active ingredient and therapeutic class data
- Images and visual identification

#### Product Categories
- **Products**: Pharmaceutical inventory with barcode tracking
- **Cosmetics**: Beauty and personal care products
- **Dental**: Dental care and oral hygiene items
- Manufacturer details and supplier information
- Stock levels and reorder point management

#### User Management
- **Users**: Role-based user accounts (manager/seller)
- **Sessions**: Secure session management
- Authentication and access control

#### Sales System
- **Sales**: Transaction records with timestamps
- **Sale_Items**: Individual items within transactions
- **Payment tracking**: Multiple payment method support
- **Sales summaries**: Daily, weekly, monthly aggregations

#### Notification System
- **Dismissed_Notifications**: User dismissal tracking
- **User_Notification_Settings**: Personal notification preferences
- Smart filtering and alert management

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 18+
- Mobile browsers with camera support

## Security Features

- SQL injection protection using prepared statements
- XSS protection with HTML escaping
- Input validation and sanitization
- Secure database connections

## Development

### Adding New Features
1. Update database schema if needed
2. Add new API endpoints in `api/` directory
3. Update frontend components
4. Test thoroughly

### Customization
- Modify CSS variables in `css/style.css`
- Update branding in header sections
- Add new medication classes as needed

## Support

For technical support or feature requests:
1. Check existing documentation
2. Review database schema
3. Test API endpoints
4. Verify browser compatibility

## License

Professional Pharmacy Management System - All rights reserved.

## Key Features Summary

### For Managers 👨‍💼
- Complete pharmacy management dashboard
- Multi-category inventory control (pharmaceuticals, cosmetics, dental)
- Advanced barcode scanning with product creation
- Real-time inventory notifications and alerts
- Comprehensive medication database with FDA integration
- Statistical analysis and reporting
- Built-in calculator and utilities

### For Sellers/Cashiers 👩‍💻
- Streamlined POS interface
- Fast barcode-based checkout
- Multiple payment method support
- Sales history and analytics
- Product lookup without cart addition
- Receipt printing and transaction tracking
- Mobile-optimized design

### For Customers 👥
- Fast checkout experience
- Multiple payment options
- Detailed receipt printing
- Professional service interface

## Version History

- **v2.0.0** - Complete Dual-Role System *(Current)*
  - Dual-role architecture (Manager + Seller/POS)
  - Complete POS system with checkout and sales processing
  - Real-time notification system with smart dismissal
  - Multi-category product management (pharmaceuticals, cosmetics, dental)
  - Advanced barcode scanning across all interfaces
  - Sales analytics with AI-powered recommendations
  - FDA API integration for medication data
  - Comprehensive reporting and statistics
  - Mobile-responsive design for tablets and phones

- **v1.0.0** - Initial Core System
  - Basic medication database management
  - Simple barcode scanning capabilities
  - Search and filtering functionality
  - Basic product inventory management
  - Responsive web design foundation

## System Requirements

### Server Requirements
- PHP 7.4 or higher with PDO extension
- MySQL 5.7 or higher
- Web server (Apache/Nginx) with URL rewriting
- Minimum 2GB RAM, 10GB storage space
- SSL certificate recommended for production

### Client Requirements
- Modern web browser (Chrome 60+, Firefox 55+, Safari 11+, Edge 18+)
- Camera access for barcode scanning
- JavaScript enabled
- Responsive design supports desktop, tablet, and mobile devices

## Support & Maintenance

### Database Backups
- Automated backup system with versioning
- Multiple backup files for data recovery
- Migration scripts for schema updates

### Security Features
- SQL injection protection using prepared statements
- XSS protection with comprehensive input sanitization
- CSRF protection for form submissions
- Secure session management with role-based access
- Password hashing and authentication security

### Performance Optimization
- Optimized database queries with proper indexing
- Efficient API endpoints with caching
- Compressed assets and optimized images
- Mobile-first responsive design

---

*Built with ❤️ for modern pharmacy management - Razology Professional System*
