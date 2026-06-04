<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/language-functions.php';
initializeLanguage();

$database = new Database();
$db = $database->connect();
$dental = new Dental($db);

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: products.php?category=dental');
    exit;
}

$item = $dental->getById($id);
if (!$item) {
    header('Location: products.php?category=dental');
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
                    <a href="products.php?category=dental" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <?php echo t('common.backToDental', 'Back to Dental Products'); ?>
                    </a>
                </div>

                <div class="detail-content">
                    <div class="detail-info-section">
                        <h1 class="detail-title"><?php echo htmlspecialchars($item['name']); ?></h1>
                        <p class="detail-company">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($item['company']); ?>
                        </p>

                        <div class="detail-badges">
                            <span class="badge badge-primary">
                                <i class="fas fa-tooth"></i> <?php echo t('product.dental', 'Dental'); ?>
                            </span>
                            <?php if (!empty($item['class'])): ?>
                                <span class="badge badge-info"><?php echo htmlspecialchars($item['class']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['subcategory'])): ?>
                                <span class="badge badge-info">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['subcategory']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($item['custom_class'])): ?>
                                <span class="badge badge-info">
                                    <i class="fas fa-tags"></i> <?php echo htmlspecialchars($item['custom_class']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($item['custom_size'])): ?>
                                <span class="badge badge-secondary">
                                    <i class="fas fa-ruler"></i> Size: <?php echo htmlspecialchars($item['custom_size']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($item['age_group'])): ?>
                                <span class="badge badge-secondary">
                                    <i class="fas fa-users"></i> 
                                    <?php 
                                    switch($item['age_group']) {
                                        case 'kids': echo t('product.kidsOnly', 'Kids Only'); break;
                                        case 'adults': echo t('product.adultsOnly', 'Adults Only'); break;
                                        case 'both': default: echo t('product.kidsAdults', 'Kids & Adults'); break;
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($item['contains_fluoride'] ?? false): ?>
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle"></i> <?php echo t('product.containsFluoride', 'Contains Fluoride'); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-warning">
                                    <i class="fas fa-times-circle"></i> <?php echo t('product.noFluoride', 'No Fluoride'); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="detail-quick-info">
                            <div class="quick-info-item">
                                <strong><?php echo t('product.productName', 'Product Name'); ?>:</strong> <?php echo htmlspecialchars($item['name']); ?>
                            </div>
                            <div class="quick-info-item">
                                <strong><?php echo t('common.company', 'Company'); ?>:</strong> <?php echo htmlspecialchars($item['company']); ?>
                            </div>
                            <div class="quick-info-item">
                                <strong><?php echo t('common.class', 'Class'); ?>:</strong> <?php echo htmlspecialchars($item['class']); ?>
                            </div>
                            <?php if (!empty($item['subcategory'])): ?>
                            <div class="quick-info-item">
                                <strong><?php echo t('product.subcategory', 'Subcategory'); ?>:</strong> <?php echo htmlspecialchars($item['subcategory']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($item['custom_class'])): ?>
                            <div class="quick-info-item">
                                <strong><?php echo t('product.customClass', 'Custom Class'); ?>:</strong> <?php echo htmlspecialchars($item['custom_class']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($item['custom_size'])): ?>
                            <div class="quick-info-item">
                                <strong><?php echo t('product.customSize', 'Custom Size'); ?>:</strong> <?php echo htmlspecialchars($item['custom_size']); ?>
                            </div>
                            <?php endif; ?>
                            <div class="quick-info-item">
                                <strong><?php echo t('product.barcode', 'Barcode'); ?>:</strong> <?php echo htmlspecialchars($item['barcode']); ?>
                            </div>
                            <div class="quick-info-item">
                                <strong><?php echo t('product.ageGroup', 'Age Group'); ?>:</strong> 
                                <?php 
                                switch($item['age_group'] ?? 'both') {
                                    case 'kids': echo t('product.kidsOnly', 'Kids Only'); break;
                                    case 'adults': echo t('product.adultsOnly', 'Adults Only'); break;
                                    case 'both': default: echo t('product.kidsAdults', 'Kids & Adults'); break;
                                }
                                ?>
                            </div>
                            <div class="quick-info-item">
                                <strong><?php echo t('product.containsFluoride', 'Contains Fluoride'); ?>:</strong> 
                                <?php echo ($item['contains_fluoride'] ?? false) ? t('common.yes', 'Yes') : t('common.no', 'No'); ?>
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
                                $alerts[] = ['type' => 'danger', 'message' => t('product.outOfStockAlert', 'This dental product is out of stock!')];
                            } elseif ($item['quantity'] <= $item['low_stock_threshold']) {
                                $alerts[] = ['type' => 'warning', 'message' => t('product.lowStockAlert', 'This dental product is running low on stock.')];
                            }
                            
                            if ($item['expiry_date']) {
                                $daysUntilExpiry = floor((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
                                if ($daysUntilExpiry < 0) {
                                    $alerts[] = ['type' => 'danger', 'message' => t('product.expiredAlert', 'This dental product has expired!')];
                                } elseif ($daysUntilExpiry <= 7) {
                                    $alerts[] = ['type' => 'danger', 'message' => t('product.expiringSoonAlert', 'This dental product expires very soon!')];
                                } elseif ($daysUntilExpiry <= 30) {
                                    $alerts[] = ['type' => 'warning', 'message' => t('product.expiresWithinMonthAlert', 'This dental product expires within 30 days.')];
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
                    <a href="edit-dental.php?id=<?php echo $item['id']; ?>" class="btn btn-warning">
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

    <style>
        .product-detail {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 2rem 0;
        }

        .detail-header {
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #dee2e6;
        }

        .detail-content {
            padding: 2.5rem;
        }

        .detail-info-section {
            margin-bottom: 2rem;
        }

        .detail-title {
            color: var(--primary-color);
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .detail-company {
            color: #6c757d;
            font-size: 1.3rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-badges {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
        }

        .badge {
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .badge-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
            color: white;
        }

        .badge-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .badge-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
            color: white;
        }

        .badge-success {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #1e7e34 100%);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .detail-quick-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 2rem;
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .quick-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #dee2e6;
            color: var(--gray-700);
        }

        .quick-info-item:last-child {
            border-bottom: none;
        }

        .quick-info-item strong {
            color: var(--primary-color);
            font-weight: 600;
            min-width: 140px;
        }

        .detail-sections {
            margin-top: 3rem;
        }

        .detail-section {
            margin-bottom: 2.5rem;
            padding: 2rem;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .detail-section h3 {
            color: var(--primary-color);
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }

        .detail-section p {
            color: var(--gray-700);
            line-height: 1.8;
            font-size: 1.1rem;
            margin: 0;
        }

        .detail-actions {
            padding: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
            border-top: 1px solid #dee2e6;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
            color: white;
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

        @media (max-width: 768px) {
            .detail-content {
                padding: 1.5rem;
            }

            .detail-title {
                font-size: 2.2rem;
            }

            .detail-company {
                font-size: 1.1rem;
            }

            .detail-badges {
                justify-content: center;
            }

            .quick-info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
                text-align: left;
            }

            .detail-section {
                padding: 1.5rem;
            }

            .detail-actions {
                flex-direction: column;
                align-items: center;
                gap: 0.75rem;
            }

            .btn {
                width: 200px;
                justify-content: center;
            }
        }

        /* Add some nice animations */
        .product-detail {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .detail-section {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
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
