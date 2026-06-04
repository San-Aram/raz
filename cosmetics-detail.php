<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/language-functions.php';
initializeLanguage();

$database = new Database();
$db = $database->connect();
$cosmetic = new Cosmetic($db);

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: products.php?category=cosmetics');
    exit;
}

$item = $cosmetic->getById($id);
if (!$item) {
    header('Location: products.php?category=cosmetics');
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo getTextDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['name']); ?> - Razology</title>
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
                        <i class="fas fa-home"></i> <?php echo t('header.home', 'Home'); ?>
                    </a>
                    <a href="medications.php" class="nav-link">
                        <i class="fas fa-capsules"></i> <?php echo t('header.medications', 'Medications'); ?>
                    </a>
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-box"></i> <?php echo t('header.products', 'Products'); ?>
                    </a>
                    <a href="add-medication.php" class="nav-link">
                        <i class="fas fa-plus"></i> <?php echo t('header.addMedication', 'Add Medication'); ?>
                    </a>
                    <div class="nav-language-selector">
                        <select id="languageSelector" class="language-select" onchange="changeLanguage(this.value)">
                            <?php foreach (SUPPORTED_LANGUAGES as $lang): ?>
                                <option value="<?php echo $lang; ?>" <?php echo getCurrentLanguage() === $lang ? 'selected' : ''; ?>>
                                    <?php echo getLanguageDisplayName($lang); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <a href="logout.php" class="nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i> <?php echo t('header.logout', 'Logout'); ?>
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="product-detail">
                <div class="detail-header">
                    <a href="products.php?category=cosmetics" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <?php echo t('common.backToCosmetics', 'Back to Cosmetics'); ?>
                    </a>
                </div>

                <div class="detail-content">
                    <div class="detail-image-section">
                        <?php if (!empty($item['image_url']) && file_exists($item['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="product-image">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-image"></i>
                                <p><?php echo t('product.noImageAvailable', 'No image available'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="detail-info-section">
                        <h1 class="detail-title"><?php echo htmlspecialchars($item['name']); ?></h1>
                        <p class="detail-company">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($item['company']); ?>
                        </p>

                        <div class="detail-badges">
                            <span class="badge badge-primary">
                                <i class="fas fa-palette"></i> <?php echo t('product.cosmetic', 'Cosmetic'); ?>
                            </span>
                            <?php if (!empty($item['class'])): ?>
                                <span class="badge badge-info"><?php echo htmlspecialchars($item['class']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="detail-quick-info">
                            <div class="quick-info-item">
                                <strong><?php echo t('product.productName', 'Product Name'); ?>:</strong> 
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                            </div>
                            <div class="quick-info-item">
                                <strong><?php echo t('common.company', 'Company'); ?>:</strong> 
                                <span><?php echo htmlspecialchars($item['company']); ?></span>
                            </div>
                            <div class="quick-info-item">
                                <strong><?php echo t('common.class', 'Class'); ?>:</strong> 
                                <span><?php echo htmlspecialchars($item['class']); ?></span>
                            </div>
                            <div class="quick-info-item">
                                <strong><?php echo t('product.barcode', 'Barcode'); ?>:</strong> 
                                <span><?php echo htmlspecialchars($item['barcode']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-sections">
                    <!-- Inventory Information Section -->
                    <div class="detail-section">
                        <h3><i class="fas fa-boxes"></i> <?php echo t('product.inventoryInfo', 'Inventory Information'); ?></h3>
                        <div class="inventory-info-container">
                            <div class="inventory-grid">
                                <div class="inventory-item">
                                    <label><?php echo t('product.price', 'Price'); ?>:</label>
                                    <span class="price-amount">
                                        <?php if ($item['price'] > 0): ?>
                                            $<?php echo number_format($item['price'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo t('common.notSet', 'Not set'); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="inventory-item">
                                    <label><?php echo t('product.currentStock', 'Current Stock'); ?>:</label>
                                    <span class="stock-amount <?php 
                                        if ($item['quantity'] == 0) echo 'out-of-stock';
                                        elseif ($item['quantity'] <= $item['low_stock_threshold']) echo 'low-stock';
                                        else echo 'in-stock';
                                    ?>">
                                        <?php echo $item['quantity']; ?> <?php echo t('product.units', 'units'); ?>
                                        <?php if ($item['quantity'] == 0): ?>
                                            <i class="fas fa-times-circle" title="<?php echo t('product.outOfStock', 'Out of Stock'); ?>"></i>
                                        <?php elseif ($item['quantity'] <= $item['low_stock_threshold']): ?>
                                            <i class="fas fa-exclamation-triangle" title="<?php echo t('product.lowStock', 'Low Stock'); ?>"></i>
                                        <?php else: ?>
                                            <i class="fas fa-check-circle" title="<?php echo t('product.inStock', 'In Stock'); ?>"></i>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="inventory-item">
                                    <label><?php echo t('product.lowStockAlert', 'Low Stock Alert'); ?>:</label>
                                    <span><?php echo t('product.whenBelow', 'When below'); ?> <?php echo $item['low_stock_threshold']; ?> <?php echo t('product.units', 'units'); ?></span>
                                </div>
                                <div class="inventory-item">
                                    <label><?php echo t('product.expiryDate', 'Expiry Date'); ?>:</label>
                                    <span class="expiry-date <?php 
                                        if ($item['expiry_date']) {
                                            $daysUntilExpiry = floor((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
                                            if ($daysUntilExpiry < 0) echo 'expired';
                                            elseif ($daysUntilExpiry <= 7) echo 'expiring-critical';
                                            elseif ($daysUntilExpiry <= 30) echo 'expiring-warning';
                                            else echo 'expiry-good';
                                        }
                                    ?>">
                                        <?php if ($item['expiry_date']): ?>
                                            <?php 
                                            $expiryDate = date('M j, Y', strtotime($item['expiry_date']));
                                            $daysUntilExpiry = floor((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
                                            echo $expiryDate;
                                            
                                            if ($daysUntilExpiry < 0): ?>
                                                <span class="expiry-status expired">
                                                    <i class="fas fa-times-circle"></i> <?php echo t('product.expired', 'Expired'); ?> <?php echo abs($daysUntilExpiry); ?> <?php echo t('product.daysAgo', 'days ago'); ?>
                                                </span>
                                            <?php elseif ($daysUntilExpiry <= 7): ?>
                                                <span class="expiry-status critical">
                                                    <i class="fas fa-exclamation-triangle"></i> <?php echo t('product.expiresIn', 'Expires in'); ?> <?php echo $daysUntilExpiry; ?> <?php echo t('product.days', 'days'); ?>
                                                </span>
                                            <?php elseif ($daysUntilExpiry <= 30): ?>
                                                <span class="expiry-status warning">
                                                    <i class="fas fa-clock"></i> <?php echo t('product.expiresIn', 'Expires in'); ?> <?php echo $daysUntilExpiry; ?> <?php echo t('product.days', 'days'); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo t('common.notSet', 'Not set'); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php 
                            // Check for alerts
                            $alerts = [];
                            if ($item['quantity'] == 0) {
                                $alerts[] = ['type' => 'danger', 'message' => t('product.outOfStockAlert', 'This cosmetic product is out of stock!')];
                            } elseif ($item['quantity'] <= $item['low_stock_threshold']) {
                                $alerts[] = ['type' => 'warning', 'message' => t('product.lowStockAlert', 'This cosmetic product is running low on stock.')];
                            }
                            
                            if ($item['expiry_date']) {
                                $daysUntilExpiry = floor((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
                                if ($daysUntilExpiry < 0) {
                                    $alerts[] = ['type' => 'danger', 'message' => t('product.expiredAlert', 'This cosmetic product has expired!')];
                                } elseif ($daysUntilExpiry <= 7) {
                                    $alerts[] = ['type' => 'danger', 'message' => t('product.expiringSoonAlert', 'This cosmetic product expires very soon!')];
                                } elseif ($daysUntilExpiry <= 30) {
                                    $alerts[] = ['type' => 'warning', 'message' => t('product.expiresWithinMonthAlert', 'This cosmetic product expires within 30 days.')];
                                }
                            }
                            ?>
                            
                            <?php if (!empty($alerts)): ?>
                                <div class="inventory-alerts">
                                    <?php foreach ($alerts as $alert): ?>
                                        <div class="alert alert-<?php echo $alert['type']; ?>">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <?php echo $alert['message']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h3><i class="fas fa-sticky-note"></i> <?php echo t('product.notes', 'Notes'); ?></h3>
                        <p><?php echo !empty($item['notes']) ? nl2br(htmlspecialchars($item['notes'])) : t('product.noNotes', 'No notes available'); ?></p>
                    </div>
                </div>

                <div class="detail-actions">
                    <a href="edit-cosmetics.php?id=<?php echo $item['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> <?php echo t('common.edit', 'Edit Product'); ?>
                    </a>
                    <button onclick="printPage()" class="btn btn-info">
                        <i class="fas fa-print"></i> <?php echo t('common.print', 'Print'); ?>
                    </button>
                    <button onclick="copyToClipboard(window.location.href)" class="btn btn-secondary">
                        <i class="fas fa-link"></i> <?php echo t('common.copyLink', 'Copy Link'); ?>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <style>
        .product-detail {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .detail-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-content {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .detail-image-section {
            display: flex;
            justify-content: center;
        }
        
        .product-image {
            width: 100%;
            max-width: 350px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
        }
        
        .no-image {
            width: 350px;
            height: 250px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        
        .no-image i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .no-image p {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .detail-info-section {
            padding: 1rem 0;
        }

        .detail-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .detail-company {
            font-size: 1.3rem;
            color: #6c757d;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-company i {
            color: #007bff;
        }

        .detail-badges {
            margin-bottom: 2.5rem;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .badge {
            padding: 0.75rem 1.25rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .badge-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .badge-info {
            background: linear-gradient(135deg, #17a2b8, #117a8b);
            color: white;
        }

        .detail-quick-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 2rem;
            border-left: 4px solid #007bff;
        }

        .quick-info-item {
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 1rem;
            align-items: center;
        }

        .quick-info-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .quick-info-item strong {
            color: #495057;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .quick-info-item span {
            color: #2c3e50;
            font-size: 1rem;
        }

        .detail-sections {
            margin-bottom: 3rem;
        }

        .detail-section {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .detail-section h3 {
            color: #2c3e50;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }

        .detail-section h3 i {
            color: #007bff;
            font-size: 1.2rem;
        }

        .detail-section p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #495057;
            margin: 0;
        }

        .detail-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }

        .detail-actions .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .detail-actions .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Inventory Information Styling */
        .inventory-info-container {
            background: var(--blue-50);
            border: 1px solid #cce7ff;
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }

        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .inventory-item {
            background: var(--white);
            padding: 1rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
        }

        .inventory-item label {
            display: block;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .inventory-item span {
            font-weight: 500;
            font-size: 1rem;
        }

        .price-amount {
            color: var(--success-color);
        }

        .stock-amount {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stock-amount.in-stock {
            color: var(--success-color);
        }

        .stock-amount.low-stock {
            color: var(--warning-color);
        }

        .stock-amount.out-of-stock {
            color: var(--danger-color);
        }

        .expiry-date {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .expiry-status {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        .expiry-status.expired {
            color: var(--white);
            background: var(--danger-color);
        }

        .expiry-status.critical {
            color: var(--white);
            background: var(--danger-color);
        }

        .expiry-status.warning {
            color: var(--dark-color);
            background: var(--warning-color);
        }

        .inventory-alerts {
            margin-top: 1rem;
        }

        .inventory-alerts .alert {
            background: var(--white);
            border: 1px solid;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .alert-warning {
            border-color: var(--warning-color);
            color: #856404;
            background-color: #fff3cd;
        }

        .alert-danger {
            border-color: var(--danger-color);
            color: #721c24;
            background-color: #f8d7da;
        }
        
        @media (max-width: 1024px) {
            .detail-content {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }
            
            .detail-image-section {
                order: -1;
            }

            .quick-info-item {
                grid-template-columns: 1fr;
                text-align: left;
                gap: 0.5rem;
            }

            .detail-title {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .product-detail {
                padding: 1.5rem;
            }

            .detail-content {
                gap: 1.5rem;
            }
            
            .product-image, .no-image {
                max-width: 100%;
                width: 280px;
            }

            .detail-title {
                font-size: 1.8rem;
            }

            .detail-company {
                font-size: 1.1rem;
            }

            .detail-actions {
                flex-direction: column;
            }

            .detail-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <script src="js/main.js"></script>
    <script>
        function changeLanguage(lang) {
            // Redirect with language parameter
            const url = new URL(window.location);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
