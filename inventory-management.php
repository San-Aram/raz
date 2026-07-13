<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/language-functions.php';
initializeLanguage();

$database = new Database();
$db = $database->connect();

$productModel = new Product($db);
$cosmeticModel = new Cosmetic($db);
$dentalModel = new Dental($db);

// Get inventory statistics
$stats = [
    'products' => [
        'total' => count($productModel->getAll()),
        'low_stock' => count($productModel->getLowStockItems()),
        'out_of_stock' => count($productModel->getOutOfStockItems()),
        'expiring' => count($productModel->getExpiringItems(30)),
        'expired' => count($productModel->getExpiredItems())
    ],
    'cosmetics' => [
        'total' => count($cosmeticModel->getAll()),
        'low_stock' => count($cosmeticModel->getLowStockItems()),
        'out_of_stock' => count($cosmeticModel->getOutOfStockItems()),
        'expiring' => count($cosmeticModel->getExpiringItems(30)),
        'expired' => count($cosmeticModel->getExpiredItems())
    ],
    'dental' => [
        'total' => count($dentalModel->getAll()),
        'low_stock' => count($dentalModel->getLowStockItems()),
        'out_of_stock' => count($dentalModel->getOutOfStockItems()),
        'expiring' => count($dentalModel->getExpiringItems(30)),
        'expired' => count($dentalModel->getExpiredItems())
    ]
];

// Calculate totals
$totals = [
    'items' => $stats['products']['total'] + $stats['cosmetics']['total'] + $stats['dental']['total'],
    'low_stock' => $stats['products']['low_stock'] + $stats['cosmetics']['low_stock'] + $stats['dental']['low_stock'],
    'out_of_stock' => $stats['products']['out_of_stock'] + $stats['cosmetics']['out_of_stock'] + $stats['dental']['out_of_stock'],
    'expiring' => $stats['products']['expiring'] + $stats['cosmetics']['expiring'] + $stats['dental']['expiring'],
    'expired' => $stats['products']['expired'] + $stats['cosmetics']['expired'] + $stats['dental']['expired']
];

$alertCount = $totals['low_stock'] + $totals['out_of_stock'] + $totals['expiring'] + $totals['expired'];

// Get items that need attention
$actionItems = [
    'expired' => array_merge(
        $productModel->getExpiredItems(),
        $cosmeticModel->getExpiredItems(),
        $dentalModel->getExpiredItems()
    ),
    'expiring' => array_merge(
        $productModel->getExpiringItems(7), // Expiring in 7 days
        $cosmeticModel->getExpiringItems(7),
        $dentalModel->getExpiringItems(7)
    ),
    'out_of_stock' => array_merge(
        $productModel->getOutOfStockItems(),
        $cosmeticModel->getOutOfStockItems(),
        $dentalModel->getOutOfStockItems()
    ),
    'low_stock' => array_merge(
        $productModel->getLowStockItems(),
        $cosmeticModel->getLowStockItems(),
        $dentalModel->getLowStockItems()
    )
];
?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo getTextDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('inventory.title', 'Inventory Management'); ?> - Razology</title>
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
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-box"></i> <?php echo t('nav.products', 'Products'); ?>
                    </a>
                    <a href="add-medication.php" class="nav-link">
                        <i class="fas fa-plus"></i> <?php echo t('nav.add_medication', 'Add Medication'); ?>
                    </a>
                    <a href="inventory-management.php" class="nav-link active">
                        <i class="fas fa-boxes"></i> <?php echo t('nav.inventory', 'Inventory'); ?>
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
            <div class="page-header">
                <h1><i class="fas fa-boxes"></i> <?php echo t('inventory.title', 'Inventory Management Dashboard'); ?></h1>
                <p><?php echo t('inventory.subtitle', 'Monitor stock levels, expiry dates, and manage your pharmacy inventory'); ?></p>
            </div>

            <!-- Overview Statistics -->
            <div class="stats-overview">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $totals['items']; ?></div>
                        <div class="stat-label"><?php echo t('inventory.total_items', 'Total Items'); ?></div>
                    </div>
                </div>

                <div class="stat-card <?php echo $totals['expired'] > 0 ? 'critical' : 'good'; ?>">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $totals['expired']; ?></div>
                        <div class="stat-label"><?php echo t('inventory.expired_items', 'Expired Items'); ?></div>
                    </div>
                </div>

                <div class="stat-card <?php echo $totals['expiring'] > 0 ? 'warning' : 'good'; ?>">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $totals['expiring']; ?></div>
                        <div class="stat-label"><?php echo t('inventory.expiring_soon', 'Expiring Soon'); ?></div>
                    </div>
                </div>

                <div class="stat-card <?php echo $totals['out_of_stock'] > 0 ? 'critical' : 'good'; ?>">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $totals['out_of_stock']; ?></div>
                        <div class="stat-label"><?php echo t('inventory.out_of_stock', 'Out of Stock'); ?></div>
                    </div>
                </div>

                <div class="stat-card <?php echo $totals['low_stock'] > 0 ? 'warning' : 'good'; ?>">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $totals['low_stock']; ?></div>
                        <div class="stat-label"><?php echo t('inventory.low_stock', 'Low Stock'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="category-breakdown">
                <h2><i class="fas fa-chart-bar"></i> <?php echo t('inventory.by_category', 'Inventory by Category'); ?></h2>
                <div class="category-cards">
                    <div class="category-card">
                        <div class="category-header">
                            <h3><i class="fas fa-pills"></i> <?php echo t('products.pharmaceutics', 'Pharmaceutics'); ?></h3>
                            <span class="category-total"><?php echo $stats['products']['total']; ?> <?php echo t('inventory.items', 'items'); ?></span>
                        </div>
                        <div class="category-stats">
                            <div class="category-stat expired">
                                <span class="count"><?php echo $stats['products']['expired']; ?></span>
                                <span class="label"><?php echo t('inventory.expired', 'Expired'); ?></span>
                            </div>
                            <div class="category-stat expiring">
                                <span class="count"><?php echo $stats['products']['expiring']; ?></span>
                                <span class="label"><?php echo t('inventory.expiring', 'Expiring'); ?></span>
                            </div>
                            <div class="category-stat out-stock">
                                <span class="count"><?php echo $stats['products']['out_of_stock']; ?></span>
                                <span class="label"><?php echo t('inventory.out_of_stock', 'Out of Stock'); ?></span>
                            </div>
                            <div class="category-stat low-stock">
                                <span class="count"><?php echo $stats['products']['low_stock']; ?></span>
                                <span class="label"><?php echo t('inventory.low_stock', 'Low Stock'); ?></span>
                            </div>
                        </div>
                        <div class="category-actions">
                            <a href="products.php?category=pharmaceutics" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> <?php echo t('inventory.view_all', 'View All'); ?>
                            </a>
                        </div>
                    </div>

                    <div class="category-card">
                        <div class="category-header">
                            <h3><i class="fas fa-palette"></i> <?php echo t('products.cosmetics', 'Cosmetics'); ?></h3>
                            <span class="category-total"><?php echo $stats['cosmetics']['total']; ?> <?php echo t('inventory.items', 'items'); ?></span>
                        </div>
                        <div class="category-stats">
                            <div class="category-stat expired">
                                <span class="count"><?php echo $stats['cosmetics']['expired']; ?></span>
                                <span class="label"><?php echo t('inventory.expired', 'Expired'); ?></span>
                            </div>
                            <div class="category-stat expiring">
                                <span class="count"><?php echo $stats['cosmetics']['expiring']; ?></span>
                                <span class="label"><?php echo t('inventory.expiring', 'Expiring'); ?></span>
                            </div>
                            <div class="category-stat out-stock">
                                <span class="count"><?php echo $stats['cosmetics']['out_of_stock']; ?></span>
                                <span class="label"><?php echo t('inventory.out_of_stock', 'Out of Stock'); ?></span>
                            </div>
                            <div class="category-stat low-stock">
                                <span class="count"><?php echo $stats['cosmetics']['low_stock']; ?></span>
                                <span class="label"><?php echo t('inventory.low_stock', 'Low Stock'); ?></span>
                            </div>
                        </div>
                        <div class="category-actions">
                            <a href="products.php?category=cosmetics" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> <?php echo t('inventory.view_all', 'View All'); ?>
                            </a>
                        </div>
                    </div>

                    <div class="category-card">
                        <div class="category-header">
                            <h3><i class="fas fa-tooth"></i> <?php echo t('products.dental', 'Dental'); ?></h3>
                            <span class="category-total"><?php echo $stats['dental']['total']; ?> <?php echo t('inventory.items', 'items'); ?></span>
                        </div>
                        <div class="category-stats">
                            <div class="category-stat expired">
                                <span class="count"><?php echo $stats['dental']['expired']; ?></span>
                                <span class="label"><?php echo t('inventory.expired', 'Expired'); ?></span>
                            </div>
                            <div class="category-stat expiring">
                                <span class="count"><?php echo $stats['dental']['expiring']; ?></span>
                                <span class="label"><?php echo t('inventory.expiring', 'Expiring'); ?></span>
                            </div>
                            <div class="category-stat out-stock">
                                <span class="count"><?php echo $stats['dental']['out_of_stock']; ?></span>
                                <span class="label"><?php echo t('inventory.out_of_stock', 'Out of Stock'); ?></span>
                            </div>
                            <div class="category-stat low-stock">
                                <span class="count"><?php echo $stats['dental']['low_stock']; ?></span>
                                <span class="label"><?php echo t('inventory.low_stock', 'Low Stock'); ?></span>
                            </div>
                        </div>
                        <div class="category-actions">
                            <a href="products.php?category=dental" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> <?php echo t('inventory.view_all', 'View All'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($alertCount > 0): ?>
            <!-- Action Required Section -->
            <div class="action-required">
                <h2><i class="fas fa-exclamation-triangle"></i> <?php echo t('inventory.items_attention', 'Items Requiring Attention'); ?> (<?php echo $alertCount; ?>)</h2>
                
                <?php if (!empty($actionItems['expired'])): ?>
                <div class="alert-section expired">
                    <h3><i class="fas fa-times-circle"></i> <?php echo t('inventory.expired_items', 'Expired Items'); ?> (<?php echo count($actionItems['expired']); ?>)</h3>
                    <div class="alert-items">
                        <?php foreach (array_slice($actionItems['expired'], 0, 5) as $item): ?>
                        <div class="alert-item">
                            <div class="item-info">
                                <strong><?php echo htmlspecialchars($item['product_name'] ?? $item['name']); ?></strong>
                                <span class="item-meta">
                                    <?php echo htmlspecialchars($item['company']); ?> | 
                                    <?php echo t('inventory.expired_on', 'Expired'); ?> <?php echo date('M j, Y', strtotime($item['expiry_date'])); ?>
                                </span>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-sm btn-danger" onclick="markAsRemoved(<?php echo $item['id']; ?>, '<?php echo isset($item['product_name']) ? 'products' : (isset($item['name']) && isset($item['class']) ? 'cosmetics' : 'dental'); ?>')">
                                    <i class="fas fa-trash"></i> <?php echo t('inventory.remove', 'Remove'); ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($actionItems['expired']) > 5): ?>
                        <div class="show-more">
                            <button class="btn btn-sm btn-secondary" onclick="showMore('expired')">
                                <?php echo t('inventory.show_more', 'Show'); ?> <?php echo count($actionItems['expired']) - 5; ?> <?php echo t('inventory.more_items', 'more items'); ?>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($actionItems['out_of_stock'])): ?>
                <div class="alert-section out-of-stock">
                    <h3><i class="fas fa-exclamation-triangle"></i> <?php echo t('inventory.out_of_stock', 'Out of Stock'); ?> (<?php echo count($actionItems['out_of_stock']); ?>)</h3>
                    <div class="alert-items">
                        <?php foreach (array_slice($actionItems['out_of_stock'], 0, 5) as $item): ?>
                        <div class="alert-item">
                            <div class="item-info">
                                <strong><?php echo htmlspecialchars($item['product_name'] ?? $item['name']); ?></strong>
                                <span class="item-meta">
                                    <?php echo htmlspecialchars($item['company']); ?> | 
                                    <?php echo t('inventory.quantity', 'Quantity'); ?>: 0
                                </span>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-sm btn-primary" onclick="quickRestock(<?php echo $item['id']; ?>, '<?php echo isset($item['product_name']) ? 'products' : (isset($item['name']) && isset($item['class']) ? 'cosmetics' : 'dental'); ?>')">
                                    <i class="fas fa-plus"></i> <?php echo t('inventory.restock', 'Restock'); ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($actionItems['low_stock'])): ?>
                <div class="alert-section low-stock">
                    <h3><i class="fas fa-exclamation-triangle"></i> <?php echo t('inventory.low_stock', 'Low Stock'); ?> (<?php echo count($actionItems['low_stock']); ?>)</h3>
                    <div class="alert-items">
                        <?php foreach (array_slice($actionItems['low_stock'], 0, 5) as $item): ?>
                        <div class="alert-item">
                            <div class="item-info">
                                <strong><?php echo htmlspecialchars($item['product_name'] ?? $item['name']); ?></strong>
                                <span class="item-meta">
                                    <?php echo htmlspecialchars($item['company']); ?> | 
                                    <?php echo t('inventory.quantity', 'Quantity'); ?>: <?php echo $item['quantity']; ?> (<?php echo t('inventory.threshold', 'Threshold'); ?>: <?php echo $item['low_stock_threshold']; ?>)
                                </span>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-sm btn-warning" onclick="quickRestock(<?php echo $item['id']; ?>, '<?php echo isset($item['product_name']) ? 'products' : (isset($item['name']) && isset($item['class']) ? 'cosmetics' : 'dental'); ?>')">
                                    <i class="fas fa-plus"></i> <?php echo t('inventory.restock', 'Restock'); ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="no-alerts">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo t('inventory.all_good', 'All Good!'); ?></h3>
                    <p><?php echo t('inventory.no_action_needed', 'All inventory items are in good condition. No immediate action required.'); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2><i class="fas fa-bolt"></i> <?php echo t('inventory.quick_actions', 'Quick Actions'); ?></h2>
                <div class="action-buttons">
                    <button onclick="openBarcodeScanner('pharmaceutics')" class="btn btn-primary">
                        <i class="fas fa-barcode"></i> <?php echo t('inventory.scan_product', 'Scan Product'); ?>
                    </button>
                    <button onclick="window.location.href='statistics.php'" class="btn btn-info">
                        <i class="fas fa-chart-line"></i> <?php echo t('inventory.view_reports', 'View Reports'); ?>
                    </button>
                    
                    <button onclick="showBulkUpdateModal(event)" class="btn btn-warning">
                        <i class="fas fa-edit"></i> <?php echo t('inventory.bulk_update', 'Bulk Update'); ?>
                    </button>
                    <button onclick="showExpirationCalendar(event)" class="btn btn-success">
                        <i class="fas fa-calendar-alt"></i> <?php echo t('inventory.expiration_calendar', 'Expiration Calendar'); ?>
                    </button>
                </div>
            </div>

           

            <!-- Bulk Update Section -->
            <div id="bulkUpdateSection" class="inventory-section" style="display: none;">
                <h2><i class="fas fa-edit"></i> <?php echo t('inventory.bulk_update_title', 'Bulk Update Inventory'); ?></h2>
                <div class="bulk-controls">
                    <div class="selection-controls">
                        <button onclick="selectAllItems()" class="btn btn-sm btn-secondary"><?php echo t('inventory.select_all', 'Select All'); ?></button>
                        <button onclick="deselectAllItems()" class="btn btn-sm btn-secondary"><?php echo t('inventory.deselect_all', 'Deselect All'); ?></button>
                        <span id="selectedCount" class="selected-count">0 <?php echo t('inventory.items_selected', 'items selected'); ?></span>
                    </div>
                    
                    <div class="bulk-actions">
                        <select id="bulkActionType" class="form-control">
                            <option value=""><?php echo t('inventory.choose_action', 'Choose action...'); ?></option>
                            <option value="update_quantity"><?php echo t('inventory.set_quantity', 'Set Quantity'); ?></option>
                            <option value="adjust_quantity"><?php echo t('inventory.adjust_quantity', 'Adjust Quantity (+/-)'); ?></option>
                            <option value="update_price"><?php echo t('inventory.update_price', 'Update Price'); ?></option>
                            <option value="update_expiry"><?php echo t('inventory.update_expiry', 'Update Expiry Date'); ?></option>
                            <option value="update_threshold"><?php echo t('inventory.set_threshold', 'Set Low Stock Threshold'); ?></option>
                        </select>
                        
                        <div id="bulkActionInputs" class="bulk-inputs" style="display: none;">
                            <!-- Dynamic inputs will be inserted here -->
                        </div>
                        
                        <button onclick="executeBulkUpdate()" class="btn btn-warning" id="executeBulkBtn" disabled>
                            <i class="fas fa-play"></i> Execute Update
                        </button>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="bulk-items-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()"></th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Current Price</th>
                                <th>Current Quantity</th>
                                <th>Expiry Date</th>
                                <th>Low Stock Threshold</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="bulkItemsTableBody">
                            <!-- Items will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Expiration Calendar Section -->
            <div id="expirationCalendarSection" class="inventory-section" style="display: none;">
                <h2><i class="fas fa-calendar-alt"></i> Expiration Calendar</h2>
                
                <div class="calendar-controls">
                    <div class="month-navigation">
                        <button onclick="previousMonth()" class="btn btn-sm btn-secondary">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <h3 id="currentMonthYear">Loading...</h3>
                        <button onclick="nextMonth()" class="btn btn-sm btn-secondary">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    
                    <div class="calendar-legend">
                        <div class="legend-item">
                            <span class="legend-color" style="background: #dc3545;"></span>
                            <span>Expired/Critical (≤7 days)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background: #ffc107;"></span>
                            <span>Warning (≤30 days)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background: #28a745;"></span>
                            <span>Normal (>30 days)</span>
                        </div>
                    </div>
                </div>

                <div id="calendar" class="expiration-calendar">
                    <!-- Calendar will be generated here -->
                </div>

                <div id="calendarStats" class="calendar-stats">
                    <!-- Stats will be displayed here -->
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology.</p>
        </div>
    </footer>

    <!-- Quick Restock Modal -->
    <div id="restockModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeRestockModal()">&times;</span>
            <h3><i class="fas fa-plus"></i> Quick Restock</h3>
            <form id="restockForm" onsubmit="event.preventDefault(); submitRestock();">
                <input type="hidden" id="restockItemId" name="item_id">
                <input type="hidden" id="restockCategory" name="category">
                
                <div class="form-group">
                    <label for="restockQuantity">Add Quantity:</label>
                    <input type="number" id="restockQuantity" name="quantity" class="form-control" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="restockNotes">Notes (Optional):</label>
                    <textarea id="restockNotes" name="notes" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Stock
                    </button>
                    <button type="button" onclick="closeRestockModal()" class="btn btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
            border-left: 4px solid;
        }

        .stat-card.total { border-left-color: var(--primary-color); }
        .stat-card.good { border-left-color: var(--success-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.critical { border-left-color: var(--danger-color); }

        .stat-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-card.total .stat-icon { background: var(--blue-50); color: var(--primary-color); }
        .stat-card.good .stat-icon { background: var(--green-50); color: var(--success-color); }
        .stat-card.warning .stat-icon { background: #fff3cd; color: var(--warning-color); }
        .stat-card.critical .stat-icon { background: #f8d7da; color: var(--danger-color); }

        .category-breakdown {
            margin-bottom: 3rem;
        }

        .category-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .category-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .category-header {
            background: var(--gray-50);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray-200);
        }

        .category-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            padding: 1rem;
        }

        .category-stat {
            text-align: center;
            padding: 0.75rem;
            border-radius: var(--border-radius);
        }

        .category-stat.expired { background: #f8d7da; color: #721c24; }
        .category-stat.expiring { background: #fff3cd; color: #856404; }
        .category-stat.out-stock { background: #f8d7da; color: #721c24; }
        .category-stat.low-stock { background: #fff3cd; color: #856404; }

        .category-stat .count {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .category-stat .label {
            font-size: 0.8rem;
            font-weight: 500;
        }

        .category-actions {
            padding: 1rem;
            border-top: 1px solid var(--gray-200);
            text-align: center;
        }

        .action-required {
            margin-bottom: 3rem;
        }

        .alert-section {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .alert-section h3 {
            padding: 1rem;
            margin: 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .alert-section.expired h3 { background: #f8d7da; color: #721c24; }
        .alert-section.out-of-stock h3 { background: #f8d7da; color: #721c24; }
        .alert-section.low-stock h3 { background: #fff3cd; color: #856404; }

        .alert-items {
            padding: 1rem;
        }

        .alert-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .alert-item:last-child {
            border-bottom: none;
        }

        .item-meta {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        /* Inventory Management Page Specific Styles */
        .quick-actions {
            text-align: center;
            margin-bottom: 3rem;
        }

        .quick-actions .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Keep the default button styles from the main CSS file - only add positioning */
        .quick-actions .action-buttons .btn {
            position: relative;
            z-index: 10;
        }

        .no-alerts {
            text-align: center;
            margin: 3rem 0;
        }

        .no-alerts .alert {
            display: inline-block;
            padding: 2rem;
            max-width: 500px;
        }

        @media (max-width: 768px) {
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .category-cards {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
        
        /* Inventory Management Sections */
        .inventory-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }

        .feature-guide {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }

        .feature-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .feature-card {
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .feature-content h3 {
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .feature-content ul {
            margin-top: 1rem;
            padding-left: 1.5rem;
        }

        .feature-content li {
            margin-bottom: 0.5rem;
        }

        .feature-note {
            text-align: center;
            padding: 1rem;
            background: var(--blue-50);
            border-radius: 8px;
            margin-top: 1.5rem;
            color: var(--primary-color);
        }

        .feature-note i {
            margin-right: 0.5rem;
        }

        .bulk-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .selection-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .selected-count {
            color: var(--primary-color);
            font-weight: 600;
        }

        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .bulk-inputs {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .bulk-items-table {
            overflow-x: auto;
        }

        .bulk-items-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .bulk-items-table th,
        .bulk-items-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .bulk-items-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .category-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .category-products { background: #e3f2fd; color: #1976d2; }
        .category-cosmetics { background: #fce4ec; color: #c2185b; }
        .category-dental { background: #e8f5e8; color: #388e3c; }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-expired { background: #ffebee; color: #d32f2f; }
        .status-low-stock { background: #fff3e0; color: #f57c00; }

        /* Calendar Styles */
        .calendar-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .month-navigation {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .calendar-legend {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }

        .expiration-calendar {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: #f8f9fa;
        }

        .calendar-day-header {
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            border-right: 1px solid #ddd;
        }

        .calendar-day-header:last-child {
            border-right: none;
        }

        .calendar-body {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }

        .calendar-day {
            min-height: 80px;
            padding: 0.5rem;
            border-right: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            background: white;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .calendar-day:nth-child(7n) {
            border-right: none;
        }

        .calendar-day.empty {
            background: #f8f9fa;
        }

        .calendar-day.has-expirations {
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .calendar-day.has-expirations:hover {
            opacity: 0.8;
        }

        .day-number {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .item-count {
            font-size: 0.75rem;
            color: white;
            background: rgba(0,0,0,0.2);
            padding: 0.125rem 0.25rem;
            border-radius: 4px;
            text-align: center;
        }

        .calendar-stats {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #ddd;
        }

        .stat-item.critical { border-left-color: #dc3545; }
        .stat-item.warning { border-left-color: #ffc107; }
        .stat-item.normal { border-left-color: #28a745; }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .category-cards {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .bulk-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .bulk-actions {
                justify-content: center;
            }

            .calendar-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .calendar-legend {
                justify-content: center;
            }

            .calendar-day {
                min-height: 60px;
                padding: 0.25rem;
            }
        }
    </style>

    <script src="js/main.js"></script>
    <script src="js/barcode-scanner.js"></script>
    <script src="js/notifications.js"></script>
    
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
            window.location.href = window.location.pathname + '?lang=' + lang;
        }

        function quickRestock(itemId, category) {
            document.getElementById('restockItemId').value = itemId;
            document.getElementById('restockCategory').value = category;
            document.getElementById('restockQuantity').value = '';
            document.getElementById('restockNotes').value = '';
            document.getElementById('restockModal').style.display = 'block';
        }

        function closeRestockModal() {
            document.getElementById('restockModal').style.display = 'none';
        }

        function submitRestock() {
            const form = document.getElementById('restockForm');
            const formData = new FormData(form);
            formData.append('action', 'restock');
            
            const submitBtn = document.querySelector('#restockForm button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t("inventory.updating", "Updating..."); ?>';
            submitBtn.disabled = true;
            
            fetch('api/notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('<?php echo t("inventory.stock_updated", "Stock updated successfully!"); ?>');
                    location.reload(); // Refresh the page to show updated data
                } else {
                    alert('<?php echo t("inventory.error", "Error"); ?>: ' + (data.message || '<?php echo t("inventory.unknown_error", "Unknown error occurred"); ?>'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?php echo t("inventory.network_error", "Network error occurred. Please try again."); ?>');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                closeRestockModal();
            });
        }

        function markAsRemoved(itemId, category) {
            if (confirm('<?php echo t("inventory.confirm_remove", "Are you sure you want to mark this expired item as removed?"); ?>')) {
                // Here you would implement the removal logic
                alert('<?php echo t("inventory.remove_functionality", "Item marked as removed (functionality to be implemented)"); ?>');
            }
        }

        function exportInventory() {
            alert('<?php echo t("inventory.export_functionality", "Export functionality to be implemented"); ?>');
        }

        function bulkUpdate() {
            showBulkUpdateModal();
        }

        // Bulk Update Functionality
        let selectedItems = new Set();
        let allItems = [];
        let currentCalendarMonth = new Date().getMonth() + 1;
        let currentCalendarYear = new Date().getFullYear();

        function showBulkUpdateModal(event) {
            console.log('showBulkUpdateModal called', event);
            
            if (event) {
                event.preventDefault();
                event.stopPropagation();
                console.log('Event prevented and stopped');
            }
            
            // Hide other sections
            const calendarSection = document.getElementById('expirationCalendarSection');
            if (calendarSection) {
                calendarSection.style.display = 'none';
                console.log('Calendar section hidden');
            }
            
            // Show bulk update section
            const bulkSection = document.getElementById('bulkUpdateSection');
            if (bulkSection) {
                bulkSection.style.display = 'block';
                console.log('Bulk section shown');
                
                // Scroll to the section
                setTimeout(() => {
                    bulkSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            } else {
                console.error('Bulk update section not found');
            }
            
            loadAllItems();
            return false;
        }

        function showExpirationCalendar(event) {
            console.log('showExpirationCalendar called', event);
            
            if (event) {
                event.preventDefault();
                event.stopPropagation();
                console.log('Event prevented and stopped');
            }
            
            // Hide other sections
            const bulkSection = document.getElementById('bulkUpdateSection');
            if (bulkSection) {
                bulkSection.style.display = 'none';
                console.log('Bulk section hidden');
            }
            
            // Show calendar section
            const calendarSection = document.getElementById('expirationCalendarSection');
            if (calendarSection) {
                calendarSection.style.display = 'block';
                console.log('Calendar section shown');
                
                // Scroll to the section
                setTimeout(() => {
                    calendarSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            } else {
                console.error('Calendar section not found');
            }
            
            loadExpirationCalendar();
            return false;
        }

        async function loadAllItems() {
            try {
                // Load all products, cosmetics, and dental items
                const [productsResponse, cosmeticsResponse, dentalResponse] = await Promise.all([
                    fetch('api/notifications.php?category=products&include_all=1'),
                    fetch('api/notifications.php?category=cosmetics&include_all=1'),
                    fetch('api/notifications.php?category=dental&include_all=1')
                ]);

                // For now, let's create a simple API call to get all items
                const response = await fetch('api/get-all-inventory.php');
                const data = await response.json();
                
                if (data.success) {
                    allItems = data.items;
                    renderBulkItemsTable();
                } else {
                    // Fallback: create mock data structure
                    console.log('Using mock data for bulk update');
                    allItems = []; // We'll populate this in a moment
                    renderBulkItemsTable();
                }
            } catch (error) {
                console.error('Error loading items:', error);
            }
        }

        function renderBulkItemsTable() {
            const tbody = document.getElementById('bulkItemsTableBody');
            tbody.innerHTML = '';

            // Mock some data for demonstration
            const mockItems = [
                { id: 1, name: 'Paracetamol 500mg', category: 'products', price: 3.50, quantity: 50, expiry_date: '2025-06-15', low_stock_threshold: 10 },
                { id: 2, name: 'Vitamin C Serum', category: 'cosmetics', price: 12.99, quantity: 25, expiry_date: '2025-08-20', low_stock_threshold: 5 },
                { id: 3, name: 'Dental Floss', category: 'dental', price: 2.25, quantity: 100, expiry_date: '2026-01-10', low_stock_threshold: 15 }
            ];

            allItems = allItems.length > 0 ? allItems : mockItems;

            allItems.forEach(item => {
                const row = document.createElement('tr');
                const isExpired = new Date(item.expiry_date) < new Date();
                const isLowStock = item.quantity <= item.low_stock_threshold;
                
                let statusClass = '';
                let statusText = 'Normal';
                
                if (isExpired) {
                    statusClass = 'status-expired';
                    statusText = 'Expired';
                } else if (isLowStock) {
                    statusClass = 'status-low-stock';
                    statusText = 'Low Stock';
                }

                row.innerHTML = `
                    <td><input type="checkbox" class="item-checkbox" data-id="${item.id}" data-category="${item.category}" onchange="updateSelectedCount()"></td>
                    <td>${item.name}</td>
                    <td><span class="category-badge category-${item.category}">${item.category}</span></td>
                    <td>${Number(item.price || 0).toFixed(2)}</td>
                    <td>${item.quantity}</td>
                    <td>${item.expiry_date || 'N/A'}</td>
                    <td>${item.low_stock_threshold}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                `;
                tbody.appendChild(row);
            });

            updateSelectedCount();
        }

        function selectAllItems() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
            updateSelectedCount();
        }

        function deselectAllItems() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            updateSelectedCount();
        }

        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const count = checkboxes.length;
            document.getElementById('selectedCount').textContent = `${count} <?php echo t('inventory.items_selected', 'items selected'); ?>`;
            
            // Update bulk action type selector
            const actionType = document.getElementById('bulkActionType');
            const executeBtn = document.getElementById('executeBulkBtn');
            executeBtn.disabled = count === 0 || !actionType.value;
        }

        // Handle bulk action type change
        document.getElementById('bulkActionType').addEventListener('change', function() {
            const actionType = this.value;
            const inputsContainer = document.getElementById('bulkActionInputs');
            const executeBtn = document.getElementById('executeBulkBtn');
            
            inputsContainer.innerHTML = '';
            inputsContainer.style.display = actionType ? 'block' : 'none';
            
            switch (actionType) {
                case 'update_quantity':
                    inputsContainer.innerHTML = `
                        <label>New Quantity:</label>
                        <input type="number" id="newQuantity" min="0" placeholder="Enter new quantity" class="form-control">
                    `;
                    break;
                case 'adjust_quantity':
                    inputsContainer.innerHTML = `
                        <label>Adjustment (+/-):</label>
                        <input type="number" id="quantityAdjustment" placeholder="e.g., +10 or -5" class="form-control">
                    `;
                    break;
                case 'update_price':
                    inputsContainer.innerHTML = `
                        <label>New Price:</label>
                        <input type="number" id="newPrice" min="0" step="0.01" placeholder="Enter new price" class="form-control">
                    `;
                    break;
                case 'update_expiry':
                    inputsContainer.innerHTML = `
                        <label>New Expiry Date:</label>
                        <input type="date" id="newExpiryDate" class="form-control">
                    `;
                    break;
                case 'update_threshold':
                    inputsContainer.innerHTML = `
                        <label>Low Stock Threshold:</label>
                        <input type="number" id="newThreshold" min="0" placeholder="Enter threshold" class="form-control">
                    `;
                    break;
            }
            
            updateSelectedCount();
        });

        async function executeBulkUpdate() {
            const actionType = document.getElementById('bulkActionType').value;
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            
            if (checkboxes.length === 0) {
                alert('Please select at least one item');
                return;
            }
            
            if (!actionType) {
                alert('Please select an action type');
                return;
            }
            
            // Get selected items
            const selectedItems = Array.from(checkboxes).map(cb => ({
                id: cb.dataset.id,
                category: cb.dataset.category
            }));
            
            // Get values based on action type
            let values = {};
            switch (actionType) {
                case 'update_quantity':
                    const newQuantity = document.getElementById('newQuantity').value;
                    if (!newQuantity || newQuantity < 0) {
                        alert('Please enter a valid quantity');
                        return;
                    }
                    values.quantity = parseInt(newQuantity);
                    break;
                case 'adjust_quantity':
                    const adjustment = document.getElementById('quantityAdjustment').value;
                    if (!adjustment) {
                        alert('Please enter an adjustment value');
                        return;
                    }
                    values.adjustment = parseInt(adjustment);
                    break;
                case 'update_price':
                    const newPrice = document.getElementById('newPrice').value;
                    if (newPrice === '' || isNaN(newPrice) || parseFloat(newPrice) < 0) {
                        alert('Please enter a valid price');
                        return;
                    }
                    values.price = parseFloat(newPrice);
                    break;
                case 'update_expiry':
                    const expiryDate = document.getElementById('newExpiryDate').value;
                    if (!expiryDate) {
                        alert('Please select an expiry date');
                        return;
                    }
                    values.expiry_date = expiryDate;
                    break;
                case 'update_threshold':
                    const threshold = document.getElementById('newThreshold').value;
                    if (!threshold || threshold < 0) {
                        alert('Please enter a valid threshold');
                        return;
                    }
                    values.threshold = parseInt(threshold);
                    break;
            }
            
            try {
                const response = await fetch('api/bulk-update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: actionType,
                        items: selectedItems,
                        values: values
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`Success! ${result.message}`);
                    loadAllItems(); // Reload the table
                    deselectAllItems();
                } else {
                    alert(`Error: ${result.message}`);
                }
            } catch (error) {
                console.error('Bulk update error:', error);
                alert('An error occurred while updating items');
            }
        }

        // Calendar functionality
        async function loadExpirationCalendar() {
            try {
                const response = await fetch(`api/expiration-calendar.php?month=${currentCalendarMonth}&year=${currentCalendarYear}`);
                const data = await response.json();
                
                if (data.success) {
                    renderCalendar(data);
                } else {
                    console.error('Error loading calendar:', data.error);
                }
            } catch (error) {
                console.error('Calendar error:', error);
            }
        }

        function renderCalendar(data) {
            document.getElementById('currentMonthYear').textContent = `${data.month_name} ${data.year}`;
            
            const calendar = document.getElementById('calendar');
            calendar.innerHTML = '';
            
            // Create calendar grid
            const daysInMonth = new Date(data.stats.year, data.stats.month, 0).getDate();
            const firstDayOfMonth = new Date(data.stats.year, data.stats.month - 1, 1).getDay();
            
            // Create header
            const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const headerRow = document.createElement('div');
            headerRow.className = 'calendar-header';
            daysOfWeek.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'calendar-day-header';
                dayHeader.textContent = day;
                headerRow.appendChild(dayHeader);
            });
            calendar.appendChild(headerRow);
            
            // Create calendar body
            const calendarBody = document.createElement('div');
            calendarBody.className = 'calendar-body';
            
            // Add empty cells for days before the first day of the month
            for (let i = 0; i < firstDayOfMonth; i++) {
                const emptyDay = document.createElement('div');
                emptyDay.className = 'calendar-day empty';
                calendarBody.appendChild(emptyDay);
            }
            
            // Add days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';
                
                const dayNumber = document.createElement('div');
                dayNumber.className = 'day-number';
                dayNumber.textContent = day;
                dayElement.appendChild(dayNumber);
                
                // Check if this day has expiring items
                if (data.calendar_data[day]) {
                    const dayData = data.calendar_data[day];
                    dayElement.style.backgroundColor = dayData.dominant_color;
                    dayElement.classList.add('has-expirations');
                    
                    const itemCount = document.createElement('div');
                    itemCount.className = 'item-count';
                    itemCount.textContent = `${dayData.total_count} items`;
                    dayElement.appendChild(itemCount);
                    
                    // Add click handler to show details
                    dayElement.addEventListener('click', () => showDayDetails(day, dayData));
                }
                
                calendarBody.appendChild(dayElement);
            }
            
            calendar.appendChild(calendarBody);
            
            // Update stats
            renderCalendarStats(data.stats);
        }

        function renderCalendarStats(stats) {
            const statsContainer = document.getElementById('calendarStats');
            statsContainer.innerHTML = `
                <h3>Month Summary</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">${stats.total_items}</div>
                        <div class="stat-label">Total Items</div>
                    </div>
                    <div class="stat-item critical">
                        <div class="stat-number">${stats.expired_count + stats.critical_count}</div>
                        <div class="stat-label">Critical</div>
                    </div>
                    <div class="stat-item warning">
                        <div class="stat-number">${stats.warning_count}</div>
                        <div class="stat-label">Warning</div>
                    </div>
                    <div class="stat-item normal">
                        <div class="stat-number">${stats.normal_count}</div>
                        <div class="stat-label">Normal</div>
                    </div>
                </div>
            `;
        }

        function showDayDetails(day, dayData) {
            let itemsList = dayData.items.map(item => 
                `<li class="expiry-item ${item.severity}">
                    <strong>${item.name}</strong> (${item.category})
                    <br>Quantity: ${item.quantity}
                    <br>Status: ${item.days_until_expiry <= 0 ? 'EXPIRED' : `${item.days_until_expiry} days left`}
                </li>`
            ).join('');
            
            alert(`Items expiring on ${dayData.date}:\n\n${dayData.items.map(item => 
                `• ${item.name} (${item.category}) - ${item.quantity} units`
            ).join('\n')}`);
        }

        function previousMonth() {
            currentCalendarMonth--;
            if (currentCalendarMonth < 1) {
                currentCalendarMonth = 12;
                currentCalendarYear--;
            }
            loadExpirationCalendar();
        }

        function nextMonth() {
            currentCalendarMonth++;
            if (currentCalendarMonth > 12) {
                currentCalendarMonth = 1;
                currentCalendarYear++;
            }
            loadExpirationCalendar();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('restockModal');
            if (event.target == modal) {
                closeRestockModal();
            }
        }
    </script>
</body>
</html>