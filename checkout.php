<?php
require_once 'includes/seller-auth.php';
require_once 'includes/database.php';
require_once 'includes/admin-settings-helper.php';

// Check maintenance mode (non-admins will be redirected to maintenance page)
// Note: Use session admin check since seller-auth doesn't have isAdmin function
if (isMaintenanceMode() && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    header('Location: maintenance.php');
    exit;
}

$database = new Database();
$db = $database->connect();
$saleModel = new Sale($db);

// Generate sale number for this transaction
$saleNumber = $saleModel->generateSaleNumber();

// Check if we should auto-open scanner
$autoScan = isset($_GET['scan']) && $_GET['scan'] == '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo getSiteName(); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <header class="header seller-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-cash-register"></i>
                    <h1>Razology POS</h1>
                </div>
                <nav class="nav">
                    <a href="seller-dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="checkout.php" class="nav-link active">
                        <i class="fas fa-shopping-cart"></i> Checkout
                    </a>
                    <a href="sales-history.php" class="nav-link">
                        <i class="fas fa-receipt"></i> Sales
                    </a>
                    <a href="product-lookup.php" class="nav-link">
                        <i class="fas fa-search"></i> Products
                    </a>
                    <div class="user-info">
                        <span class="user-welcome">
                            <i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </span>
                        <a href="seller-logout.php" class="nav-link logout-link">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <main class="main checkout-main">
        <div class="container">
            <div class="checkout-container">
                <!-- Left Panel: Product Entry & Scanner -->
                <div class="checkout-left">
                    <div class="checkout-header">
                        <h2><i class="fas fa-shopping-cart"></i> New Sale</h2>
                        <div class="sale-info">
                            <span class="sale-number">Sale #<?php echo $saleNumber; ?></span>
                            <span class="sale-time" id="saleTime"><?php echo date('H:i'); ?></span>
                        </div>
                    </div>

                    <!-- Product Entry Methods -->
                    <div class="product-entry-section">
                        <div class="entry-tabs">
                            <button class="tab-btn active" data-tab="barcode">
                                <i class="fas fa-barcode"></i> Barcode
                            </button>
                            <button class="tab-btn" data-tab="search">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <button class="tab-btn" data-tab="manual">
                                <i class="fas fa-keyboard"></i> Manual
                            </button>
                        </div>

                        <div class="entry-content">
                            <!-- Barcode Tab -->
                            <div class="tab-panel active" id="barcode-panel">
                                <div class="barcode-entry">
                                    <div class="form-group">
                                        <label for="barcodeInput">
                                            <i class="fas fa-barcode"></i> Scan or Enter Barcode
                                        </label>
                                        <div class="barcode-input-group">
                                            <input type="text" id="barcodeInput" class="form-control" 
                                                   placeholder="Scan barcode or type manually" autofocus>
                                            <button type="button" id="scanBarcodeBtn" class="btn btn-secondary">
                                                <i class="fas fa-camera"></i> Scan
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div id="barcodeResult" class="barcode-result" style="display: none;">
                                        <!-- Product info will be displayed here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Search Tab -->
                            <div class="tab-panel" id="search-panel">
                                <div class="product-search">
                                    <div class="form-group">
                                        <label for="productSearch">
                                            <i class="fas fa-search"></i> Search Products
                                        </label>
                                        <input type="text" id="productSearch" class="form-control" 
                                               placeholder="Search by name, brand, or ingredient">
                                    </div>
                                    
                                    <div id="searchResults" class="search-results">
                                        <!-- Search results will be displayed here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Manual Tab -->
                            <div class="tab-panel" id="manual-panel">
                                <div class="manual-entry">
                                    <div class="form-group">
                                        <label for="manualProduct">
                                            <i class="fas fa-tag"></i> Product Name
                                        </label>
                                        <input type="text" id="manualProduct" class="form-control" 
                                               placeholder="Enter product name">
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="manualPrice">
                                                <i class="fas fa-peso-sign"></i> Price
                                            </label>
                                            <input type="number" id="manualPrice" class="form-control" 
                                                   placeholder="0.00" step="0.01" min="0">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="manualQuantity">
                                                <i class="fas fa-hashtag"></i> Quantity
                                            </label>
                                            <input type="number" id="manualQuantity" class="form-control" 
                                                   placeholder="1" min="1" value="1">
                                        </div>
                                    </div>
                                    
                                    <button type="button" id="addManualItem" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <button type="button" id="clearCartBtn" class="btn btn-outline btn-sm">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                        <button type="button" id="holdSaleBtn" class="btn btn-outline btn-sm">
                            <i class="fas fa-pause"></i> Hold Sale
                        </button>
                        <button type="button" id="recallSaleBtn" class="btn btn-outline btn-sm">
                            <i class="fas fa-play"></i> Recall Sale
                        </button>
                    </div>
                </div>

                <!-- Right Panel: Cart & Payment -->
                <div class="checkout-right">
                    <!-- Shopping Cart -->
                    <div class="cart-section">
                        <div class="cart-header">
                            <h3><i class="fas fa-shopping-basket"></i> Shopping Cart</h3>
                            <span class="item-count" id="cartItemCount">0 items</span>
                        </div>
                        
                        <div class="cart-items" id="cartItems">
                            <div class="empty-cart">
                                <i class="fas fa-shopping-cart"></i>
                                <p>Cart is empty</p>
                                <small>Scan or search for products to add</small>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Totals -->
                    <div class="cart-totals">
                        <div class="total-row subtotal">
                            <span>Subtotal:</span>
                            <span id="subtotalAmount">₱0.00</span>
                        </div>
                        <div class="total-row discount">
                            <span>Discount:</span>
                            <span id="discountAmount">₱0.00</span>
                        </div>
                        <div class="total-row tax">
                            <span>Tax (12%):</span>
                            <span id="taxAmount">₱0.00</span>
                        </div>
                        <div class="total-row total">
                            <span>Total:</span>
                            <span id="totalAmount">₱0.00</span>
                        </div>
                    </div>

                    <!-- Customer Info (Optional) -->
                    <div class="customer-section">
                        <h4><i class="fas fa-user"></i> Customer Info (Optional)</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <input type="text" id="customerName" class="form-control" 
                                       placeholder="Customer name">
                            </div>
                            <div class="form-group">
                                <input type="tel" id="customerPhone" class="form-control" 
                                       placeholder="Phone number">
                            </div>
                        </div>
                    </div>

                    <!-- Payment Section -->
                    <div class="payment-section">
                        <h4><i class="fas fa-credit-card"></i> Payment</h4>
                        
                        <div class="payment-methods">
                            <button class="payment-method active" data-method="cash">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Cash</span>
                            </button>
                            <button class="payment-method" data-method="card">
                                <i class="fas fa-credit-card"></i>
                                <span>Card</span>
                            </button>
                            <button class="payment-method" data-method="mobile">
                                <i class="fas fa-mobile-alt"></i>
                                <span>Mobile</span>
                            </button>
                            <button class="payment-method" data-method="insurance">
                                <i class="fas fa-shield-alt"></i>
                                <span>Insurance</span>
                            </button>
                        </div>

                        <div class="cash-payment" id="cashPayment">
                            <div class="form-group">
                                <label for="cashReceived">Cash Received</label>
                                <input type="number" id="cashReceived" class="form-control" 
                                       placeholder="0.00" step="0.01" min="0">
                            </div>
                            <div class="change-display">
                                <span>Change: <strong id="changeAmount">₱0.00</strong></span>
                            </div>
                        </div>

                        <div class="checkout-actions">
                            <button type="button" id="processPaymentBtn" class="btn btn-success btn-large" disabled>
                                <i class="fas fa-check"></i> Process Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Barcode Scanner Modal -->
    <div id="barcodeModal" class="modal">
        <div class="modal-content scanner-modal">
            <div class="modal-header">
                <h3><i class="fas fa-barcode"></i> Barcode Scanner</h3>
                <span class="close" id="closeScannerBtn">&times;</span>
            </div>
            <div class="modal-body">
                <div class="scanner-instructions">
                    <div class="instruction-item">
                        <i class="fas fa-camera"></i>
                        <span>Allow camera access when prompted</span>
                    </div>
                    <div class="instruction-item">
                        <i class="fas fa-barcode"></i>
                        <span>Point camera at barcode to scan</span>
                    </div>
                    <div class="instruction-item">
                        <i class="fas fa-info-circle"></i>
                        <span>Works best with good lighting</span>
                    </div>
                </div>
                <div id="scanner" class="scanner-container"></div>
                <div class="scanner-controls">
                    <button type="button" id="startScannerBtn" class="btn btn-primary">
                        <i class="fas fa-play"></i> Start Scanner
                    </button>
                    <button type="button" id="stopScannerBtn" class="btn btn-secondary">
                        <i class="fas fa-stop"></i> Stop Scanner
                    </button>
                    <button type="button" id="closeScannerBtn2" class="btn btn-outline">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
                <div class="scanner-status" id="scannerStatus"></div>
            </div>
        </div>
    </div>

    <!-- Payment Processing Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content payment-modal">
            <div class="modal-header">
                <h3><i class="fas fa-credit-card"></i> Processing Payment</h3>
            </div>
            <div class="modal-body">
                <div class="payment-processing">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Processing your payment...</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer seller-footer">
        <div class="container">
            <p>&copy; 2025 Razology POS - Checkout Terminal</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script src="js/checkout.js"></script>
    
    <?php if ($autoScan): ?>
    <script>
        // Auto-open scanner if scan=1 parameter is present
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.getElementById('scanBarcodeBtn').click();
            }, 500);
        });
    </script>
    <?php endif; ?>

    <style>
        .checkout-main {
            padding: 1rem 0;
        }

        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 500px;
            gap: 2rem;
            min-height: calc(100vh - 200px);
        }

        .checkout-left, .checkout-right {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .checkout-left {
            padding: 1.5rem;
        }

        .checkout-right {
            padding: 1.5rem;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .checkout-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .checkout-header h2 {
            margin: 0;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sale-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.25rem;
        }

        .sale-number {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .sale-time {
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .entry-tabs {
            display: flex;
            border-bottom: 2px solid var(--gray-200);
            margin-bottom: 1rem;
        }

        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 1rem;
            cursor: pointer;
            color: var(--gray-600);
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }

        .tab-btn:hover {
            background: var(--gray-50);
            color: var(--primary-color);
        }

        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            background: var(--gray-50);
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        .barcode-input-group {
            display: flex;
            gap: 0.5rem;
        }

        .barcode-input-group input {
            flex: 1;
        }

        .barcode-result {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .quick-actions {
            margin-top: 2rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .cart-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .item-count {
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .cart-items {
            min-height: 200px;
            margin-bottom: 1.5rem;
        }

        .empty-cart {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray-500);
        }

        .empty-cart i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-400);
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .item-details {
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .item-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            border: 1px solid var(--gray-300);
            border-radius: 4px;
            overflow: hidden;
        }

        .quantity-btn {
            background: var(--gray-100);
            border: none;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
            color: var(--gray-700);
        }

        .quantity-btn:hover {
            background: var(--gray-200);
        }

        .quantity-input {
            border: none;
            text-align: center;
            width: 50px;
            padding: 0.25rem;
        }

        .item-total {
            font-weight: 600;
            color: var(--primary-color);
            min-width: 80px;
            text-align: right;
        }

        .remove-item {
            background: var(--danger-color);
            color: var(--white);
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
        }

        .cart-totals {
            border-top: 2px solid var(--gray-200);
            padding-top: 1rem;
            margin-bottom: 1.5rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .total-row.total {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            border-top: 1px solid var(--gray-200);
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }

        .customer-section, .payment-section {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--border-radius);
        }

        .customer-section h4, .payment-section h4 {
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark-color);
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .payment-method {
            background: var(--white);
            border: 2px solid var(--gray-300);
            padding: 0.75rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .payment-method:hover {
            border-color: var(--primary-color);
        }

        .payment-method.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: var(--white);
        }

        .payment-method i {
            font-size: 1.2rem;
        }

        .payment-method span {
            font-size: 0.8rem;
        }

        .cash-payment {
            margin-bottom: 1rem;
        }

        .change-display {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background: var(--white);
            border-radius: var(--border-radius);
            text-align: center;
            font-size: 1.1rem;
        }

        .btn-large {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .scanner-modal {
            max-width: 700px;
        }

        .scanner-instructions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .instruction-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-color);
        }

        .instruction-item i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .instruction-item span {
            font-size: 0.9rem;
            color: var(--gray-700);
        }

        .scanner-container {
            width: 100%;
            height: 350px;
            background: var(--gray-100);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
        }

        .scanner-container canvas, .scanner-container video {
            max-width: 100%;
            max-height: 100%;
            border-radius: var(--border-radius);
        }

        .scanner-status {
            text-align: center;
            padding: 0.5rem;
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .scanner-controls {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .payment-processing {
            text-align: center;
            padding: 2rem;
        }

        .payment-processing i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        @media (max-width: 1200px) {
            .checkout-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .checkout-right {
                max-height: none;
            }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .payment-methods {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-actions {
                justify-content: center;
            }
        }
    </style>
</body>
</html>