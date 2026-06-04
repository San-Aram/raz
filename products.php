<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/language-functions.php';
require_once 'includes/admin-settings-helper.php';
initializeLanguage();

// Check maintenance mode (non-admins will be redirected to maintenance page)
if (isMaintenanceMode() && !isAdmin()) {
    header('Location: maintenance.php');
    exit;
}

$database = new Database();
$db = $database->connect();

// Get category parameter (default to pharmaceutics)
$category = $_GET['category'] ?? 'pharmaceutics';
$search = $_GET['search'] ?? '';

// Initialize the appropriate class based on category
$items = [];
$categoryName = '';
$categoryIcon = '';

switch ($category) {
    case 'cosmetics':
        $cosmetic = new Cosmetic($db);
        $items = $cosmetic->getAll($search);
        $categoryName = 'Cosmetics';
        $categoryIcon = 'fas fa-palette';
        break;
    case 'dental':
        $dental = new Dental($db);
        $items = $dental->getAll($search);
        $categoryName = 'Dental';
        $categoryIcon = 'fas fa-tooth';
        break;
    case 'pharmaceutics':
    default:
        $product = new Product($db);
        $medication = new Medication($db);
        $items = $product->getAll($search);
        $categoryName = 'Pharmaceutics';
        $categoryIcon = 'fas fa-pills';
        
        // For pharmaceutics, add medication linking (existing functionality)
        foreach ($items as &$prod) {
            $linkedMedications = [];
            if (!empty($prod['active_ingredient'])) {
                $activeIngredients = explode(' | ', $prod['active_ingredient']);
                
                foreach ($activeIngredients as $ingredient) {
                    $ingredient = trim($ingredient);
                    if (!empty($ingredient)) {
                        $linkedMed = $medication->getByActiveIngredient($ingredient);
                        if ($linkedMed) {
                            $linkedMedications[] = $linkedMed;
                        }
                    }
                }
            }
            $prod['linked_medications'] = $linkedMedications;
            $prod['linked_medication'] = !empty($linkedMedications) ? $linkedMedications[0] : null;
        }
        unset($prod);
        break;
}
?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo getTextDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $categoryName; ?> - <?php echo getSiteName(); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-pills"></i>
                    <h1>Razology</h1>
                </div>
                <nav class="nav">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i> <?php echo t('nav.home', 'Home'); ?>
                    </a>
                    <a href="medications.php" class="nav-link">
                        <i class="fas fa-capsules"></i> <?php echo t('nav.medications', 'Medications'); ?>
                    </a>
                    <a href="products.php" class="nav-link active">
                        <i class="fas fa-box"></i> <?php echo t('nav.products', 'Products'); ?>
                    </a>
                    <a href="statistics.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> <?php echo t('nav.statistics', 'Statistics'); ?>
                    </a>
                    <a href="calculator.php" class="nav-link">
                        <i class="fas fa-calculator"></i> <?php echo t('nav.calculator', 'Calculator'); ?>
                    </a>
                    <a href="add-medication.php" class="nav-link">
                        <i class="fas fa-plus"></i> <?php echo t('nav.add_medication', 'Add Medication'); ?>
                    </a>
                    <div class="nav-language-selector">
                        <select id="languageSelect" class="language-select" onchange="changeLanguage(this.value)">
                            <option value="en">English</option>
                            <option value="ckb">سۆرانی</option>
                            <option value="ar">العربية</option>
                        </select>
                    </div>
                    <a href="logout.php" class="nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i> <?php echo t('nav.logout', 'Logout'); ?>
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <!-- Category Selection -->
            <div class="category-selection">
                <h2><i class="fas fa-list"></i> <?php echo t('products.categories', 'Product Categories'); ?></h2>
                <div class="category-tabs">
                    <a href="products.php?category=pharmaceutics" class="category-tab <?php echo $category === 'pharmaceutics' ? 'active' : ''; ?>">
                        <i class="fas fa-pills"></i>
                        <span><?php echo t('products.pharmaceutics', 'Pharmaceutics'); ?></span>
                    </a>
                    <a href="products.php?category=cosmetics" class="category-tab <?php echo $category === 'cosmetics' ? 'active' : ''; ?>">
                        <i class="fas fa-palette"></i>
                        <span><?php echo t('products.cosmetics', 'Cosmetics'); ?></span>
                    </a>
                    <a href="products.php?category=dental" class="category-tab <?php echo $category === 'dental' ? 'active' : ''; ?>">
                        <i class="fas fa-tooth"></i>
                        <span><?php echo t('products.dental', 'Dental'); ?></span>
                    </a>
                </div>
            </div>

            <div class="hero-section">
                <div class="hero-content">
                    <h2><i class="<?php echo $categoryIcon; ?>"></i> <?php echo t('products.' . strtolower($categoryName), $categoryName); ?> <?php echo t('products.inventory', 'Inventory'); ?></h2>
                    <p><?php echo t('products.manage_inventory', 'Manage your'); ?> <?php echo strtolower(t('products.' . strtolower($categoryName), $categoryName)); ?> <?php echo t('products.with_barcode', 'inventory with barcode scanning'); ?></p>
                    <button onclick="openBarcodeScanner('<?php echo $category; ?>')" class="btn btn-primary">
                        <i class="fas fa-barcode"></i> <?php echo t('products.scan_new', 'Scan New'); ?> <?php echo t('products.' . strtolower($categoryName), $categoryName); ?> <?php echo t('products.product', 'Product'); ?>
                    </button>
                </div>
            </div>

            <div class="search-filter-section">
                <h2><i class="fas fa-search"></i> <?php echo t('products.search', 'Search'); ?> <?php echo t('products.' . strtolower($categoryName), $categoryName); ?></h2>
                <form method="GET" class="search-form">
                    <input type="hidden" name="category" value="<?php echo $category; ?>">
                    <div class="form-group">
                        <label for="search"><?php echo t('products.search', 'Search'); ?> <?php echo t('products.' . strtolower($categoryName), $categoryName); ?></label>
                        <input type="text" 
                               id="search" 
                               name="search" 
                               class="form-control" 
                               placeholder="<?php echo t('products.search_placeholder', 'Search by name, company, or barcode...'); ?>"
                               value="<?php echo htmlspecialchars($search); ?>">
                        <small><?php echo t('products.search_help', 'Search across name, manufacturer, and barcode'); ?></small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> <?php echo t('products.search_button', 'Search'); ?>
                    </button>
                    <a href="products.php?category=<?php echo $category; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> <?php echo t('products.clear', 'Clear'); ?>
                    </a>
                </form>
            </div>

            <?php if (!empty($search)): ?>
                <div class="search-results-info">
                    <p><i class="fas fa-info-circle"></i> 
                        <?php echo t('products.found', 'Found'); ?> <?php echo count($items); ?> <?php echo strtolower(t('products.' . strtolower($categoryName), $categoryName)); ?> <?php echo t('products.product', 'product'); ?><?php echo count($items) !== 1 ? 's' : ''; ?> 
                        <?php echo t('products.matching', 'matching'); ?> "<?php echo htmlspecialchars($search); ?>"
                        <a href="products.php?category=<?php echo $category; ?>" class="btn btn-sm btn-secondary" style="margin-left: 1rem;">
                            <i class="fas fa-times"></i> <?php echo t('products.clear_search', 'Clear Search'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <div class="products-grid">
                <?php foreach ($items as $item): ?>
                    <div class="product-card" data-product-id="<?php echo $item['id']; ?>" data-product-barcode="<?php echo htmlspecialchars($item['barcode']); ?>">
                        <div class="product-content">
                            <?php if ($category === 'pharmaceutics'): ?>
                                <h3 class="product-title"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                <p class="product-company">
                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($item['company']); ?>
                                </p>
                                <p class="product-barcode">
                                    <strong>Barcode:</strong> <?php echo htmlspecialchars($item['barcode']); ?>
                                </p>
                                <p class="product-ingredient">
                                    <strong>Active Ingredient:</strong> <?php echo htmlspecialchars($item['active_ingredient']); ?>
                                </p>
                                <p class="product-dose">
                                    <strong>Dose:</strong> <?php echo htmlspecialchars($item['dose']); ?>
                                </p>
                            <?php else: ?>
                                <div class="product-image-container">
                                    <?php if ($category === 'cosmetics' && !empty($item['image_url']) && file_exists($item['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="product-image-thumbnail">
                                    <?php elseif ($category === 'cosmetics'): ?>
                                        <div class="no-image-thumbnail">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h3 class="product-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="product-company">
                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($item['company']); ?>
                                </p>
                                <p class="product-barcode">
                                    <strong>Barcode:</strong> <?php echo htmlspecialchars($item['barcode']); ?>
                                </p>
                                <?php if (!empty($item['class'])): ?>
                                <p class="product-class">
                                    <strong>Class:</strong> <?php echo htmlspecialchars($item['class']); ?>
                                </p>
                                <?php endif; ?>
                                <?php if (!empty($item['indication'])): ?>
                                <p class="product-indication">
                                    <strong>Indication:</strong> <?php echo htmlspecialchars(substr($item['indication'], 0, 100)) . (strlen($item['indication']) > 100 ? '...' : ''); ?>
                                </p>
                                <?php endif; ?>
                                
                                <?php if ($category === 'dental'): ?>
                                    <div class="product-dental-info">
                                        <div class="product-badges">
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-users"></i>
                                                <?php 
                                                switch($item['age_group'] ?? 'both') {
                                                    case 'kids': echo 'Kids'; break;
                                                    case 'adults': echo 'Adults'; break;
                                                    case 'both': default: echo 'All Ages'; break;
                                                }
                                                ?>
                                            </span>
                                            <?php if ($item['contains_fluoride'] ?? false): ?>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Fluoride
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-light">
                                                    <i class="fas fa-times"></i> No Fluoride
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Inventory Status Indicators -->
                            <div class="inventory-status">
                                <?php
                                // Calculate inventory status
                                $stockStatus = '';
                                $expiryStatus = '';
                                $stockClass = '';
                                $expiryClass = '';
                                $stockIcon = '';
                                $expiryIcon = '';

                                // Stock Status
                                if ($item['quantity'] == 0) {
                                    $stockStatus = t('products.out_of_stock', 'Out of Stock');
                                    $stockClass = 'status-critical';
                                    $stockIcon = 'fas fa-times-circle';
                                } elseif ($item['quantity'] <= $item['low_stock_threshold']) {
                                    $stockStatus = t('products.low_stock', 'Low Stock') . ' (' . $item['quantity'] . ')';
                                    $stockClass = 'status-warning';
                                    $stockIcon = 'fas fa-exclamation-triangle';
                                } else {
                                    $stockStatus = t('products.in_stock', 'In Stock') . ' (' . $item['quantity'] . ')';
                                    $stockClass = 'status-good';
                                    $stockIcon = 'fas fa-check-circle';
                                }

                                // Expiry Status
                                if ($item['expiry_date']) {
                                    $daysUntilExpiry = floor((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
                                    if ($daysUntilExpiry < 0) {
                                        $expiryStatus = t('products.expired', 'Expired');
                                        $expiryClass = 'status-critical';
                                        $expiryIcon = 'fas fa-times-circle';
                                    } elseif ($daysUntilExpiry <= 7) {
                                        $expiryStatus = t('products.expires_soon', 'Expires Soon');
                                        $expiryClass = 'status-critical';
                                        $expiryIcon = 'fas fa-exclamation-triangle';
                                    } elseif ($daysUntilExpiry <= 30) {
                                        $expiryStatus = t('products.expires_in', 'Expires in') . ' ' . $daysUntilExpiry . 'd';
                                        $expiryClass = 'status-warning';
                                        $expiryIcon = 'fas fa-clock';
                                    }
                                }
                                ?>
                                
                                <div class="status-badges">
                                    <span class="status-badge <?php echo $stockClass; ?>">
                                        <i class="<?php echo $stockIcon; ?>"></i>
                                        <?php echo $stockStatus; ?>
                                    </span>
                                    
                                    <?php if ($item['price'] > 0): ?>
                                        <span class="status-badge status-info">
                                            <i class="fas fa-dollar-sign"></i>
                                            $<?php echo number_format($item['price'], 2); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($expiryStatus): ?>
                                        <span class="status-badge <?php echo $expiryClass; ?>">
                                            <i class="<?php echo $expiryIcon; ?>"></i>
                                            <?php echo $expiryStatus; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="product-actions">
                                <?php if ($category === 'pharmaceutics'): ?>
                                    <a href="product-detail.php?id=<?php echo $item['id']; ?>" class="btn btn-info">
                                        <i class="fas fa-eye"></i> <?php echo t('products.view_details', 'View Details'); ?>
                                    </a>
                                    <a href="edit-product.php?id=<?php echo $item['id']; ?>" class="btn btn-warning">
                                        <i class="fas fa-edit"></i> <?php echo t('products.edit', 'Edit'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo $category; ?>-detail.php?id=<?php echo $item['id']; ?>" class="btn btn-info">
                                        <i class="fas fa-eye"></i> <?php echo t('products.view_details', 'View Details'); ?>
                                    </a>
                                    <a href="edit-<?php echo $category; ?>.php?id=<?php echo $item['id']; ?>" class="btn btn-warning">
                                        <i class="fas fa-edit"></i> <?php echo t('products.edit', 'Edit'); ?>
                                    </a>
                                <?php endif; ?>
                                <button onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo $category; ?>')" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> <?php echo t('products.delete', 'Delete'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($items)): ?>
                <div class="alert alert-info" style="text-align: center; margin: 3rem 0;">
                    <i class="fas fa-info-circle"></i>
                    <?php if (!empty($search)): ?>
                        <?php echo t('products.no_found_search', 'No'); ?> <?php echo strtolower(t('products.' . strtolower($categoryName), $categoryName)); ?> <?php echo t('products.products_found', 'products found matching your search criteria for'); ?> "<?php echo htmlspecialchars($search); ?>".
                        <br><a href="products.php?category=<?php echo $category; ?>" class="btn btn-secondary" style="margin-top: 1rem;">
                            <i class="fas fa-list"></i> <?php echo t('products.view_all', 'View All'); ?> <?php echo t('products.' . strtolower($categoryName), $categoryName); ?> <?php echo t('products.products', 'Products'); ?>
                        </a>
                    <?php else: ?>
                        <?php echo t('products.no_found', 'No'); ?> <?php echo strtolower(t('products.' . strtolower($categoryName), $categoryName)); ?> <?php echo t('products.no_found_suffix', 'products found. Start by scanning a barcode to add your first product.'); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Barcode Scanner Modal -->
    <div id="barcodeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBarcodeScanner()">&times;</span>
            <h2><i class="fas fa-barcode"></i> <?php echo t('products.barcode_scanner', 'Barcode Scanner'); ?></h2>
            
            <!-- Mobile Instructions -->
            <div class="mobile-instructions" style="display: none;">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong><?php echo t('products.mobile_tips', 'Mobile Tips'); ?>:</strong> <?php echo t('products.mobile_instructions', 'Allow camera access when prompted. Hold device steady with good lighting for best results.'); ?>
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
                <h3><i class="fas fa-camera"></i> <?php echo t('products.or_scan_image', 'Or scan from image'); ?>:</h3>
                <div class="camera-buttons">
                    <label for="camera-input" class="btn-camera">
                        <i class="fas fa-camera"></i>
                        <span><?php echo t('products.take_photo', 'Take Photo'); ?></span>
                    </label>
                    <label for="gallery-input" class="btn-gallery">
                        <i class="fas fa-images"></i>
                        <span><?php echo t('products.choose_gallery', 'Choose from Gallery'); ?></span>
                    </label>
                </div>
                <input type="file" id="camera-input" accept="image/*" capture="environment" style="display: none;">
                <input type="file" id="gallery-input" accept="image/*" style="display: none;">
                <button onclick="processBarcodeFromImage()" class="btn btn-info image-upload-btn" style="margin-top: 1rem;">
                    <i class="fas fa-search"></i> <?php echo t('products.scan_image', 'Scan from Image'); ?>
                </button>
            </div>

            <!-- Manual Input Section -->
            <div class="manual-input">
                <h3><i class="fas fa-keyboard"></i> <?php echo t('products.or_enter_manually', 'Or enter barcode manually'); ?>:</h3>
                <input type="text" id="manualBarcode" placeholder="<?php echo t('products.enter_barcode', 'Enter barcode number'); ?>" inputmode="numeric">
                <button onclick="processBarcode()" class="btn btn-primary">
                    <i class="fas fa-search"></i> <?php echo t('products.process_barcode', 'Process Barcode'); ?>
                </button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy;Created by Sanology</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script src="js/barcode-scanner.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Set current language in selector
        document.addEventListener('DOMContentLoaded', function() {
            const currentLang = '<?php echo getCurrentLanguage(); ?>';
            const selector = document.getElementById('languageSelect');
            if (selector) {
                selector.value = currentLang;
            }
        });

        function changeLanguage(lang) {
            const currentCategory = '<?php echo $category; ?>';
            const currentSearch = '<?php echo htmlspecialchars($search); ?>';
            let url = window.location.pathname + '?lang=' + lang + '&category=' + currentCategory;
            if (currentSearch) {
                url += '&search=' + encodeURIComponent(currentSearch);
            }
            window.location.href = url;
        }

        function deleteItem(id, category) {
            const categoryName = category.charAt(0).toUpperCase() + category.slice(1);
            if (confirm(`<?php echo t('products.delete_confirm', 'Are you sure you want to delete this'); ?> ${category} <?php echo t('products.product', 'product'); ?>? <?php echo t('products.cannot_undo', 'This action cannot be undone.'); ?>`)) {
                // Prevent double submission
                const deleteBtn = event.target;
                if (deleteBtn.disabled) {
                    return;
                }
                
                const originalText = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<div class="loading"></div>';
                deleteBtn.disabled = true;
                
                let apiEndpoint = 'api/delete-product.php';
                if (category === 'cosmetics') {
                    apiEndpoint = 'api/delete-cosmetic.php';
                } else if (category === 'dental') {
                    apiEndpoint = 'api/delete-dental.php';
                }
                
                fetch(apiEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({id: id})
                })
                .then(response => response.json())
                .then(data => {
                    deleteBtn.innerHTML = originalText;
                    deleteBtn.disabled = false;
                    
                    if (data.success) {
                        showAlert(data.message, 'success');
                        // Reload page after short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showAlert(data.message || `Error deleting ${category} product`, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    deleteBtn.innerHTML = originalText;
                    deleteBtn.disabled = false;
                    showAlert(`<?php echo t('products.error_occurred', 'An error occurred while deleting the'); ?> ${category} <?php echo t('products.product', 'product'); ?>.`, 'danger');
                });
            }
        }
    </script>
    
    <style>
        .category-selection {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .category-selection h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .category-tabs {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .category-tab {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem 2rem;
            background: var(--gray-100);
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--gray-700);
            transition: all 0.3s ease;
            flex: 1;
            min-width: 150px;
        }
        
        .category-tab:hover {
            background: var(--gray-200);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .category-tab.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--white);
        }
        
        .category-tab i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .category-tab span {
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Inventory Status Styling */
        .inventory-status {
            margin: 1rem 0;
            padding: 1rem 0;
            border-top: 1px solid var(--gray-200);
            border-bottom: 1px solid var(--gray-200);
        }

        .status-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge i {
            font-size: 0.9rem;
        }

        .status-good {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-critical {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-info {
            background-color: #cce7ff;
            color: #004085;
            border: 1px solid #b3d7ff;
        }
        
        @media (max-width: 768px) {
            .category-tabs {
                flex-direction: column;
            }
            
            .category-tab {
                flex-direction: row;
                justify-content: center;
                gap: 1rem;
            }
            
            .category-tab i {
                font-size: 1.5rem;
                margin-bottom: 0;
            }
        }
        
        .product-image-container {
            width: 100%;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            border-radius: var(--border-radius);
            overflow: hidden;
            background: var(--gray-100);
        }
        
        .product-image-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: var(--border-radius);
        }
        
        .no-image-thumbnail {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: var(--gray-400);
            font-size: 2rem;
        }
        
        .product-dental-info {
            margin-top: 1rem;
        }
        
        .product-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-light {
            background-color: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }
    </style>

    <script src="js/main.js"></script>
    <script src="js/barcode-scanner.js"></script>
    <script src="js/notifications.js"></script>
</body>
</html>
