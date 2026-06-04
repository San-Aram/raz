<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/language-functions.php';
initializeLanguage();

$database = new Database();
$db = $database->connect();
$product = new Product($db);
$medication = new Medication($db);

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: products.php');
    exit;
}

$prod = $product->getById($id);

if (!$prod) {
    header('Location: products.php');
    exit;
}

// Check if medication information exists for active ingredients
$linkedMedications = [];
$activeIngredientDoses = [];

if (!empty($prod['active_ingredient'])) {
    // Split multiple active ingredients and doses
    $activeIngredients = explode(' | ', $prod['active_ingredient']);
    $doses = explode(' | ', $prod['dose']);
    
    // Ensure doses array has same length as ingredients
    while (count($doses) < count($activeIngredients)) {
        $doses[] = '';
    }
    
    foreach ($activeIngredients as $index => $ingredient) {
        $ingredient = trim($ingredient);
        $dose = trim($doses[$index] ?? '');
        
        if (!empty($ingredient)) {
            $activeIngredientDoses[] = [
                'ingredient' => $ingredient,
                'dose' => $dose
            ];
            
            // Look for linked medication
            $linkedMed = $medication->getByActiveIngredient($ingredient);
            if ($linkedMed) {
                $linkedMedications[] = $linkedMed;
            }
        }
    }
}

// Keep backward compatibility
$linkedMedication = !empty($linkedMedications) ? $linkedMedications[0] : null;
?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo getTextDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($prod['product_name']); ?> - Razology</title>
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
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <?php echo t('common.backToProducts', 'Back to Products'); ?>
                    </a>
                </div>

                <div class="detail-content">
                    <div class="detail-info-section">
                        <h1 class="detail-title"><?php echo htmlspecialchars($prod['product_name']); ?></h1>
                        <p class="detail-company"><?php echo htmlspecialchars($prod['company']); ?></p>
                        
                        <div class="detail-badges">
                            <span class="badge badge-info"><?php echo htmlspecialchars($prod['form']); ?></span>
                        </div>
                        
                        <div class="detail-quick-info">
                            <div class="quick-info-item">
                                <strong><?php echo t('product.barcode', 'Barcode'); ?>:</strong> 
                                <span class="barcode-display" onclick="copyToClipboard('<?php echo $prod['barcode']; ?>')">
                                    <?php echo htmlspecialchars($prod['barcode']); ?>
                                </span>
                            </div>
                            <div class="quick-info-item">
                                <strong><?php echo t('common.company', 'Company'); ?>:</strong> <?php echo htmlspecialchars($prod['company']); ?>
                            </div>
                            <div class="quick-info-item">
                                <strong><?php echo t('product.form', 'Form'); ?>:</strong> <?php echo htmlspecialchars($prod['form']); ?>
                            </div>
                            <div class="quick-info-item">
                                <strong><?php echo t('product.added', 'Added'); ?>:</strong> <?php echo date('F j, Y', strtotime($prod['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-sections">
                    <!-- Active Ingredients Section -->
                    <div class="detail-section">
                        <h3><i class="fas fa-flask"></i> <?php echo t('product.activeIngredientsAndDosages', 'Active Ingredients & Dosages'); ?></h3>
                        <div class="ingredients-dosages-container">
                            <?php if (!empty($activeIngredientDoses)): ?>
                                <?php foreach ($activeIngredientDoses as $index => $ingredientDose): ?>
                                    <div class="ingredient-dosage-detail">
                                        <div class="option-number">
                                            <i class="fas fa-pills"></i>
                                            <strong><?php echo t('product.ingredient', 'Ingredient'); ?> <?php echo $index + 1; ?></strong>
                                        </div>
                                        <div class="ingredient-info-grid">
                                            <div class="ingredient-info-item">
                                                <label><?php echo t('medication.activeIngredient', 'Active Ingredient'); ?>:</label>
                                                <span class="ingredient-name"><?php echo htmlspecialchars($ingredientDose['ingredient']); ?></span>
                                            </div>
                                            <div class="ingredient-info-item">
                                                <label><?php echo t('product.dose', 'Dose'); ?>:</label>
                                                <span class="dose-amount"><?php echo htmlspecialchars($ingredientDose['dose']); ?></span>
                                            </div>
                                        </div>
                                        <?php if (isset($linkedMedications[$index])): ?>
                                        <div class="medication-link-section">
                                            <a href="medication-detail.php?active_ingredient=<?php echo urlencode($ingredientDose['ingredient']); ?>" 
                                               class="medication-link-btn">
                                                <i class="fas fa-external-link-alt"></i>
                                                <?php echo t('product.viewMedicationDetails', 'View Medication Details'); ?>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?php echo t('product.noActiveIngredientInfo', 'No active ingredient information available'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Inventory Information Section -->
                    <div class="detail-section">
                        <h3><i class="fas fa-boxes"></i> <?php echo t('product.inventoryInfo', 'Inventory Information'); ?></h3>
                        <div class="inventory-info-container">
                            <div class="inventory-grid">
                                <div class="inventory-item">
                                    <label><?php echo t('product.price', 'Price'); ?>:</label>
                                    <span class="price-amount">
                                        <?php if ($prod['price'] > 0): ?>
                                            $<?php echo number_format($prod['price'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo t('common.notSet', 'Not set'); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="inventory-item">
                                    <label><?php echo t('product.currentStock', 'Current Stock'); ?>:</label>
                                    <span class="stock-amount <?php 
                                        if ($prod['quantity'] == 0) echo 'out-of-stock';
                                        elseif ($prod['quantity'] <= $prod['low_stock_threshold']) echo 'low-stock';
                                        else echo 'in-stock';
                                    ?>">
                                        <?php echo $prod['quantity']; ?> <?php echo t('product.units', 'units'); ?>
                                        <?php if ($prod['quantity'] == 0): ?>
                                            <i class="fas fa-times-circle" title="<?php echo t('product.outOfStock', 'Out of Stock'); ?>"></i>
                                        <?php elseif ($prod['quantity'] <= $prod['low_stock_threshold']): ?>
                                            <i class="fas fa-exclamation-triangle" title="<?php echo t('product.lowStock', 'Low Stock'); ?>"></i>
                                        <?php else: ?>
                                            <i class="fas fa-check-circle" title="<?php echo t('product.inStock', 'In Stock'); ?>"></i>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="inventory-item">
                                    <label><?php echo t('product.lowStockAlert', 'Low Stock Alert'); ?>:</label>
                                    <span><?php echo t('product.whenBelow', 'When below'); ?> <?php echo $prod['low_stock_threshold']; ?> <?php echo t('product.units', 'units'); ?></span>
                                </div>
                                <div class="inventory-item">
                                    <label><?php echo t('product.expiryDate', 'Expiry Date'); ?>:</label>
                                    <span class="expiry-date <?php 
                                        if ($prod['expiry_date']) {
                                            $daysUntilExpiry = floor((strtotime($prod['expiry_date']) - time()) / (60 * 60 * 24));
                                            if ($daysUntilExpiry < 0) echo 'expired';
                                            elseif ($daysUntilExpiry <= 7) echo 'expiring-critical';
                                            elseif ($daysUntilExpiry <= 30) echo 'expiring-warning';
                                            else echo 'expiry-good';
                                        }
                                    ?>">
                                        <?php if ($prod['expiry_date']): ?>
                                            <?php 
                                            $expiryDate = date('M j, Y', strtotime($prod['expiry_date']));
                                            $daysUntilExpiry = floor((strtotime($prod['expiry_date']) - time()) / (60 * 60 * 24));
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
                            if ($prod['quantity'] == 0) {
                                $alerts[] = ['type' => 'danger', 'message' => t('product.outOfStockAlert', 'This product is out of stock!')];
                            } elseif ($prod['quantity'] <= $prod['low_stock_threshold']) {
                                $alerts[] = ['type' => 'warning', 'message' => t('product.lowStockAlert', 'This product is running low on stock.')];
                            }
                            
                            if ($prod['expiry_date']) {
                                $daysUntilExpiry = floor((strtotime($prod['expiry_date']) - time()) / (60 * 60 * 24));
                                if ($daysUntilExpiry < 0) {
                                    $alerts[] = ['type' => 'danger', 'message' => t('product.expiredAlert', 'This product has expired!')];
                                } elseif ($daysUntilExpiry <= 7) {
                                    $alerts[] = ['type' => 'danger', 'message' => t('product.expiringSoonAlert', 'This product expires very soon!')];
                                } elseif ($daysUntilExpiry <= 30) {
                                    $alerts[] = ['type' => 'warning', 'message' => t('product.expiresWithinMonthAlert', 'This product expires within 30 days.')];
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

                    <!-- Medication Information Section -->
                    <?php if (!empty($linkedMedications)): ?>
                        <div class="detail-section">
                            <h3><i class="fas fa-book-medical"></i> <?php echo t('product.medicationInfoAvailable', 'Medication Information Available'); ?></h3>
                            <div class="medication-availability-info">
                                <?php if (count($linkedMedications) === 1): ?>
                                    <p><?php echo t('product.detailedMedicalInfoAvailable', 'Detailed medical information is available for'); ?> <strong><?php echo htmlspecialchars($linkedMedications[0]['active_ingredient']); ?></strong>.</p>
                                    <a href="medication-detail.php?id=<?php echo $linkedMedications[0]['id']; ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-book-medical"></i> <?php echo t('product.viewMedicationDetails', 'View Medication Details'); ?>
                                    </a>
                                <?php else: ?>
                                    <p><?php echo t('product.detailedMedicalInfoMultiple', 'Detailed medical information is available for multiple active ingredients'); ?>:</p>
                                    <div class="medication-links-grid">
                                        <?php foreach ($linkedMedications as $linkedMed): ?>
                                            <a href="medication-detail.php?id=<?php echo $linkedMed['id']; ?>" 
                                               class="medication-info-card">
                                                <i class="fas fa-book-medical"></i>
                                                <span><?php echo htmlspecialchars($linkedMed['active_ingredient']); ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="detail-section">
                            <h3><i class="fas fa-info-circle"></i> <?php echo t('product.medicationInfo', 'Medication Information'); ?></h3>
                            <div class="no-medication-info">
                                <p class="info-message">
                                    <i class="fas fa-info-circle"></i>
                                    <?php echo t('product.noMedicationInfoAvailable', 'No detailed medication information available for the active ingredient(s) in this product.'); ?>
                                </p>
                                <a href="add-medication.php" class="btn btn-outline-primary">
                                    <i class="fas fa-plus"></i> <?php echo t('product.addMedicationInfo', 'Add Medication Information'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="detail-actions">
                    <button onclick="printPage()" class="btn btn-info">
                        <i class="fas fa-print"></i> <?php echo t('common.print', 'Print'); ?>
                    </button>
                    <button onclick="copyToClipboard(window.location.href)" class="btn btn-secondary">
                        <i class="fas fa-link"></i> <?php echo t('common.copyLink', 'Copy Link'); ?>
                    </button>
                    <button onclick="copyToClipboard('<?php echo $prod['barcode']; ?>')" class="btn btn-warning">
                        <i class="fas fa-barcode"></i> <?php echo t('product.copyBarcode', 'Copy Barcode'); ?>
                    </button>
                    <a href="edit-product.php?id=<?php echo $prod['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> <?php echo t('common.edit', 'Edit Product'); ?>
                    </a>
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
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .detail-header {
            padding: 1rem 2rem;
            background: var(--gray-100);
            border-bottom: 1px solid var(--gray-200);
        }

        .detail-content {
            padding: 2rem;
        }

        .detail-title {
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .detail-company {
            color: var(--gray-600);
            font-size: 1.2rem;
            font-style: italic;
            margin-bottom: 1.5rem;
        }

        .detail-badges {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .detail-quick-info {
            background: var(--gray-100);
            padding: 1rem;
            border-radius: var(--border-radius);
        }

        .quick-info-item {
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }

        .quick-info-item:last-child {
            margin-bottom: 0;
        }

        .barcode-display {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            background: var(--white);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--gray-300);
            display: inline-block;
            margin-left: 0.5rem;
        }

        .barcode-display:hover {
            border-color: var(--primary-color);
            background: var(--gray-50);
        }

        .detail-sections {
            padding: 0 2rem 2rem;
        }

        .detail-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .detail-section:last-child {
            border-bottom: none;
        }

        .detail-section h3 {
            color: var(--primary-color);
            font-size: 1.3rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ingredients-dosages-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .ingredient-dosage-detail {
            background: var(--gray-100);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }

        .option-number {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .ingredient-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .ingredient-info-item {
            background: var(--white);
            padding: 0.75rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
        }

        .ingredient-info-item label {
            display: block;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .ingredient-name, .dose-amount {
            color: var(--dark-color);
            font-weight: 500;
            font-size: 1rem;
        }

        .medication-link-section {
            text-align: center;
        }

        .medication-link-btn {
            background: var(--primary-color);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            transition: var(--transition);
            display: inline-block;
            font-size: 0.9rem;
        }

        .medication-link-btn:hover {
            background: var(--accent-color);
            color: var(--white);
            text-decoration: none;
        }

        .medication-availability-info {
            text-align: center;
        }

        .medication-availability-info p {
            color: var(--gray-700);
            margin-bottom: 1rem;
        }

        .medication-links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .medication-info-card {
            background: var(--primary-color);
            color: var(--white);
            padding: 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .medication-info-card:hover {
            background: var(--accent-color);
            color: var(--white);
            text-decoration: none;
            transform: translateY(-2px);
        }

        .no-medication-info {
            text-align: center;
            padding: 2rem;
            background: var(--gray-100);
            border-radius: var(--border-radius);
        }

        .info-message {
            color: var(--gray-600);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .detail-actions {
            padding: 1rem 2rem 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
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
            .detail-title {
                font-size: 2rem;
            }

            .ingredient-info-grid {
                grid-template-columns: 1fr;
            }

            .medication-links-grid {
                grid-template-columns: 1fr;
            }

            .detail-actions {
                flex-direction: column;
                align-items: center;
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
