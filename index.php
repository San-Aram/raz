<?php 
require_once 'includes/auth.php';
require_once 'includes/language-functions.php';
require_once 'includes/admin-settings-helper.php';
initializeLanguage();
?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo getTextDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getSiteName(); ?> - Professional Pharmacy Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-pills"></i>
                    <h1><?php echo getSiteName(); ?></h1>
                </div>
                <nav class="nav">
                    <a href="index.php" class="nav-link active">
                        <i class="fas fa-home"></i> <?php echo t('header.home'); ?>
                    </a>
                    <a href="medications.php" class="nav-link">
                        <i class="fas fa-capsules"></i> <?php echo t('header.medications'); ?>
                    </a>
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-box"></i> <?php echo t('header.products'); ?>
                    </a>
                    <a href="statistics.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> <?php echo t('header.statistics'); ?>
                    </a>
                    <a href="calculator.php" class="nav-link">
                        <i class="fas fa-calculator"></i> <?php echo t('header.calculator'); ?>
                    </a>
                    <a href="add-medication.php" class="nav-link">
                        <i class="fas fa-plus"></i> <?php echo t('header.addMedication'); ?>
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="admin-dashboard.php" class="nav-link admin-link" style="background: linear-gradient(135deg, #ffd700, #ffed4e); color: #333; font-weight: bold;">
                            <i class="fas fa-user-shield"></i> <?php echo t('header.adminPanel'); ?>
                        </a>
                    <?php endif; ?>
                    <div class="nav-language-selector">
                        <select id="languageSelect" class="language-select" onchange="changeLanguage(this.value)">
                            <option value="en">English</option>
                            <option value="ckb">سۆرانی</option>
                            <option value="ar">العربية</option>
                        </select>
                    </div>
                    <a href="logout.php" class="nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i> <?php echo t('header.logout'); ?>
                        <?php if (isAdmin()): ?>
                            <small style="display: block; font-size: 0.7rem; opacity: 0.8;">(<?php echo t('header.admin'); ?>)</small>
                        <?php endif; ?>
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3><?php echo t('header.medications'); ?></h3>
                    <p>Search medications with advanced filters for pregnancy and lactation safety</p>
                    <a href="medications.php" class="btn btn-primary"><?php echo t('products.search'); ?></a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-barcode"></i>
                    </div>
                    <h3><?php echo t('checkout.barcode'); ?></h3>
                    <p>Scan product barcodes to quickly access or add product information</p>
                    <button onclick="openBarcodeScanner()" class="btn btn-secondary">
                        <i class="fas fa-camera"></i> <?php echo t('checkout.scan'); ?>
                    </button>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3><?php echo t('inventory.title'); ?></h3>
                    <p>Complete medication profiles with detailed medical information</p>
                    <a href="add-medication.php" class="btn btn-success"><?php echo t('header.addMedication'); ?></a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h3><?php echo t('header.calculator'); ?></h3>
                    <p>Calculate precise medication dosages based on patient weight and prescribed mg/kg</p>
                    <a href="calculator.php" class="btn btn-info"><?php echo t('header.calculator'); ?></a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3><?php echo t('header.products'); ?></h3>
                    <p>Manage product inventory with manufacturer and pricing details</p>
                    <a href="products.php" class="btn btn-info">View Products</a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3>Inventory Management</h3>
                    <p>Monitor stock levels, expiry dates, and receive alerts for inventory management</p>
                    <a href="inventory-management.php" class="btn btn-warning">
                        <i class="fas fa-warehouse"></i> Manage Inventory
                    </a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Analytics Dashboard</h3>
                    <p>Comprehensive statistics and insights with interactive charts and diagrams</p>
                    <a href="statistics.php" class="btn btn-primary">View Statistics</a>
                </div>
            </div>

            <!-- Statistics Overview Section -->
            <div class="stats-section">
                <h3><i class="fas fa-chart-line"></i> Quick Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number" id="totalMedications">Loading...</div>
                        <div class="stat-label">Total Medications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="totalProducts">Loading...</div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="totalCosmetics">Loading...</div>
                        <div class="stat-label">Cosmetic Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="totalDental">Loading...</div>
                        <div class="stat-label">Dental Products</div>
                    </div>
                   
                </div>
                <div class="stats-actions">
                    <a href="statistics.php" class="btn btn-primary">
                        <i class="fas fa-chart-bar"></i> View Detailed Statistics
                    </a>
                </div>
            </div>

            <div class="additional-features">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <h3>Backup Data</h3>
                    <p>Download a complete SQL backup of all medications, products, cosmetics, and dental data.</p>
                    <a href="backup.php" class="btn btn-warning">
                        <i class="fas fa-download"></i> Backup Data
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Barcode Scanner Modal -->
    <div id="barcodeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBarcodeScanner()">&times;</span>
            <h2><i class="fas fa-barcode"></i> Barcode Scanner</h2>
            
            <!-- Mobile Instructions -->
            <div class="mobile-instructions" style="display: none;">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Mobile Tips:</strong> Allow camera access when prompted. Hold device steady with good lighting for best results.
                </div>
            </div>
            
            <!-- Video Scanner Section -->
            <div id="scanner-container">
                <video id="scanner" autoplay playsinline></video>
                <div class="scanner-overlay">
                    <div class="scanner-line"></div>
                </div>
            </div>
            
            <!-- Image Upload Section -->
            <div class="image-input">
                <h3><i class="fas fa-camera"></i> Or scan from image:</h3>
                <div class="camera-buttons">
                    <label for="camera-input" class="btn-camera">
                        <i class="fas fa-camera"></i>
                        <span>Take Photo</span>
                    </label>
                    <label for="gallery-input" class="btn-gallery">
                        <i class="fas fa-images"></i>
                        <span>Choose from Gallery</span>
                    </label>
                </div>
                <input type="file" id="camera-input" accept="image/*" capture="environment" style="display: none;">
                <input type="file" id="gallery-input" accept="image/*" style="display: none;">
                <button onclick="processBarcodeFromImage()" class="btn btn-info image-upload-btn" style="margin-top: 1rem;">
                    <i class="fas fa-search"></i> Scan from Image
                </button>
            </div>
            
            <!-- Manual Input Section -->
            <div class="manual-input">
                <h3><i class="fas fa-keyboard"></i> Or enter barcode manually:</h3>
                <input type="text" id="manualBarcode" placeholder="Enter barcode number" inputmode="numeric">
                <button onclick="processBarcode()" class="btn btn-primary">
                    <i class="fas fa-search"></i> Process Barcode
                </button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script src="js/barcode-scanner.js"></script>
    <script src="js/main.js"></script>
    <script src="js/notifications.js"></script>
    
    <script>
        // Debug: Check if processBarcode function is available
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, checking functions...');
            console.log('processBarcode function available:', typeof processBarcode);
            console.log('openBarcodeScanner function available:', typeof openBarcodeScanner);
            
            // Add click event listener as backup
            const processBtn = document.querySelector('.manual-input button');
            if (processBtn) {
                console.log('Found process button, adding backup event listener');
                processBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Process button clicked via event listener');
                    console.log('Button element:', processBtn);
                    console.log('Event:', e);
                    

                    
                    if (typeof processBarcode === 'function') {
                        processBarcode();
                    } else {
                        console.error('processBarcode function not found');
                        alert('Error: processBarcode function not found. Please refresh the page.');
                    }
                });
            } else {
                console.error('Process button not found');
            }
        });
        
        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('JavaScript error:', e.error);
        });
    </script>
    <script>
        // Language switcher
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
                } else {
                    alert('Failed to change language');
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Set language selector to current language
        document.addEventListener('DOMContentLoaded', function() {
            const langSelect = document.getElementById('languageSelect');
            if (langSelect) {
                langSelect.value = '<?php echo getCurrentLanguage(); ?>';
                
                // Apply RTL if needed
                if ('<?php echo getTextDirection(); ?>' === 'rtl') {
                    document.documentElement.dir = 'rtl';
                }
            }
        });
    </script>
</body>
</html>
