// Razology POS Checkout System
class CheckoutSystem {
    constructor() {
        this.cart = [];
        this.currentSaleNumber = document.querySelector('.sale-number').textContent.replace('Sale #', '');
        this.taxRate = 0.12; // 12% tax
        this.discountAmount = 0;
        this.paymentMethod = 'cash';
        this.scanner = null;
        this.currentStream = null;
        this.isBarcodeLookupInProgress = false;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupKeyboardShortcuts();
        this.updateCartDisplay();
        this.updateTime();
        setInterval(() => this.updateTime(), 1000);
    }

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.switchTab(e.target.dataset.tab));
        });

        // Barcode entry
        const manualBarcodeInput = document.getElementById('manualBarcode') || document.getElementById('barcodeInput');
        if (manualBarcodeInput) {
            manualBarcodeInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.processBarcodeEntry(e.target.value, { autoAdd: true });
                }
            });
        }

        document.getElementById('scanBarcodeBtn').addEventListener('click', () => {
            this.openBarcodeScanner();
        });

        const cameraInput = document.getElementById('camera-input');
        const galleryInput = document.getElementById('gallery-input');
        if (cameraInput) {
            cameraInput.addEventListener('change', function() {
                if (this.files && this.files[0] && galleryInput) {
                    galleryInput.value = '';
                }
            });
        }
        if (galleryInput) {
            galleryInput.addEventListener('change', function() {
                if (this.files && this.files[0] && cameraInput) {
                    cameraInput.value = '';
                }
            });
        }

        // Product search
        document.getElementById('productSearch').addEventListener('input', (e) => {
            this.debounce(() => this.searchProducts(e.target.value), 300)();
        });

        // Manual entry
        document.getElementById('addManualItem').addEventListener('click', () => {
            this.addManualItem();
        });

        // Quick actions
        document.getElementById('clearCartBtn').addEventListener('click', () => {
            this.clearCart();
        });

        document.getElementById('holdSaleBtn').addEventListener('click', () => {
            this.holdSale();
        });

        document.getElementById('recallSaleBtn').addEventListener('click', () => {
            this.recallSale();
        });

        // Payment methods
        document.querySelectorAll('.payment-method').forEach(btn => {
            btn.addEventListener('click', () => this.selectPaymentMethod(btn.dataset.method));
        });

        // Cash payment
        document.getElementById('cashReceived').addEventListener('input', () => {
            this.calculateChange();
        });

        // Process payment
        document.getElementById('processPaymentBtn').addEventListener('click', () => {
            this.processPayment();
        });

        // Scanner modal
        document.getElementById('closeScannerBtn').addEventListener('click', () => {
            this.closeBarcodeScanner();
        });

        const startScannerBtn = document.getElementById('startScannerBtn');
        if (startScannerBtn) {
            startScannerBtn.addEventListener('click', () => {
                this.startScanner();
            });
        }

        const stopScannerBtn = document.getElementById('stopScannerBtn');
        if (stopScannerBtn) {
            stopScannerBtn.addEventListener('click', () => {
                this.stopScanner();
            });
        }

        // Modal close on outside click
        document.getElementById('barcodeModal').addEventListener('click', (e) => {
            if (e.target.id === 'barcodeModal') {
                this.closeBarcodeScanner();
            }
        });
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // F1 - Focus barcode input
            if (e.key === 'F1') {
                e.preventDefault();
                document.getElementById('barcodeInput').focus();
            }
            
            // F2 - Open scanner
            if (e.key === 'F2') {
                e.preventDefault();
                this.openBarcodeScanner();
            }
            
            // F3 - Focus search
            if (e.key === 'F3') {
                e.preventDefault();
                this.switchTab('search');
                document.getElementById('productSearch').focus();
            }
            
            // F4 - Process payment
            if (e.key === 'F4') {
                e.preventDefault();
                const btn = document.getElementById('processPaymentBtn');
                if (!btn.disabled) {
                    this.processPayment();
                }
            }
            
            // Ctrl+D - Clear cart
            if (e.ctrlKey && e.key.toLowerCase() === 'd') {
                e.preventDefault();
                this.clearCart();
            }
            
            // Ctrl+H - Hold sale
            if (e.ctrlKey && e.key.toLowerCase() === 'h') {
                e.preventDefault();
                this.holdSale();
            }
            
            // Escape - Close modals
            if (e.key === 'Escape') {
                this.closeBarcodeScanner();
            }
        });
    }

    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });

        // Update tab panels
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.toggle('active', panel.id === `${tabName}-panel`);
        });

        // Focus appropriate input
        setTimeout(() => {
            if (tabName === 'barcode') {
                const barcodeInput = document.getElementById('manualBarcode') || document.getElementById('barcodeInput');
                if (barcodeInput) {
                    barcodeInput.focus();
                }
            } else if (tabName === 'search') {
                document.getElementById('productSearch').focus();
            } else if (tabName === 'manual') {
                document.getElementById('manualProduct').focus();
            }
        }, 100);
    }

    async processBarcodeEntry(barcode, options = {}) {
        const autoAdd = options.autoAdd === true;
        if (!barcode.trim()) return;

        const resultDiv = document.getElementById('barcodeResult');
        if (resultDiv) {
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Looking up product...</div>';
        }

        try {
            const response = await fetch('api/check-barcode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ barcode: barcode })
            });

            const data = await response.json();

            if (data.success && data.product) {
                if (autoAdd) {
                    this.addProductToCart(data.product);
                    if (resultDiv) {
                        resultDiv.style.display = 'none';
                    }
                } else {
                    if (resultDiv) {
                        this.displayProductResult(data.product, resultDiv);
                    }
                }
            } else {
                if (resultDiv) {
                    resultDiv.innerHTML = `
                        <div class="no-product">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Product not found</p>
                            <small>Barcode: ${barcode}</small>
                            <button type="button" class="btn btn-outline btn-sm" onclick="checkout.switchTab('manual')">
                                Add Manually
                            </button>
                        </div>
                    `;
                } else {
                    this.showAlert('Product not found', 'error');
                }
            }

            this.clearBarcodeInputs();
            return data;
        } catch (error) {
            console.error('Barcode lookup error:', error);
            if (resultDiv) {
                resultDiv.innerHTML = `
                    <div class="error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error looking up product</p>
                        <small>Please try again</small>
                    </div>
                `;
            } else {
                this.showAlert('Error looking up product', 'error');
            }
            this.clearBarcodeInputs();
            return null;
        }
    }

    clearBarcodeInputs() {
        const manualBarcodeInput = document.getElementById('manualBarcode') || document.getElementById('barcodeInput');
        if (manualBarcodeInput) {
            manualBarcodeInput.value = '';
        }

        const cameraInput = document.getElementById('camera-input');
        const galleryInput = document.getElementById('gallery-input');
        if (cameraInput) {
            cameraInput.value = '';
        }
        if (galleryInput) {
            galleryInput.value = '';
        }
    }

    displayProductResult(product, container) {
        const availableStock = typeof product.stock_quantity === 'number' ? product.stock_quantity : 999;
        container.innerHTML = `
            <div class="product-result">
                <div class="product-info">
                    <h4>${product.name}</h4>
                    <p class="product-details">
                        ${product.brand ? `Brand: ${product.brand}` : ''}
                        ${product.dosage ? ` | Dosage: ${product.dosage}` : ''}
                        ${product.size ? ` | Size: ${product.size}` : ''}
                    </p>
                    <p class="product-price">₱${parseFloat(product.price).toFixed(2)}</p>
                    <p class="product-stock">Stock: ${typeof product.stock_quantity === 'number' ? product.stock_quantity : 0} available</p>
                </div>
                <div class="add-controls">
                    <div class="quantity-input-group">
                        <label>Quantity:</label>
                        <input type="number" id="productQuantity" value="1" min="1" max="${availableStock}" class="form-control">
                    </div>
                    <button type="button" class="btn btn-primary" onclick="checkout.addProductToCart(${JSON.stringify(product).replace(/"/g, '&quot;')})">
                        <i class="fas fa-plus"></i> Add to Cart
                    </button>
                </div>
            </div>
        `;
    }

    async searchProducts(query) {
        if (!query.trim() || query.length < 2) {
            document.getElementById('searchResults').innerHTML = '';
            return;
        }

        const resultsDiv = document.getElementById('searchResults');
        resultsDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';

        try {
            const response = await fetch(`api/product-search.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();

            if (data.success && data.products.length > 0) {
                resultsDiv.innerHTML = data.products.map(product => `
                    <div class="search-result-item" onclick="checkout.selectSearchProduct(${JSON.stringify(product).replace(/"/g, '&quot;')})">
                        <div class="result-info">
                            <h5>${product.name}</h5>
                            <p class="result-details">
                                ${product.brand ? `${product.brand}` : ''}
                                ${product.dosage ? ` - ${product.dosage}` : ''}
                            </p>
                            <span class="result-price">₱${parseFloat(product.price).toFixed(2)}</span>
                        </div>
                        <div class="result-stock">
                            <span class="stock-badge ${product.stock_quantity > 0 ? 'in-stock' : 'out-of-stock'}">
                                ${product.stock_quantity || 0} in stock
                            </span>
                        </div>
                    </div>
                `).join('');
            } else {
                resultsDiv.innerHTML = '<div class="no-results">No products found</div>';
            }
        } catch (error) {
            console.error('Search error:', error);
            resultsDiv.innerHTML = '<div class="error">Search error occurred</div>';
        }
    }

    selectSearchProduct(product) {
        // Switch to manual tab and pre-fill with selected product
        this.switchTab('manual');
        document.getElementById('manualProduct').value = product.name;
        document.getElementById('manualPrice').value = product.price;
        document.getElementById('manualQuantity').focus();
        
        // Store product data for later use
        document.getElementById('manualProduct').dataset.productData = JSON.stringify(product);
    }

    addManualItem() {
        const name = document.getElementById('manualProduct').value.trim();
        const price = parseFloat(document.getElementById('manualPrice').value) || 0;
        const quantity = parseInt(document.getElementById('manualQuantity').value) || 1;

        if (!name || price <= 0) {
            this.showAlert('Please enter valid product name and price', 'error');
            return;
        }

        const productData = document.getElementById('manualProduct').dataset.productData;
        let product;

        if (productData) {
            product = JSON.parse(productData);
            product.quantity = quantity;
        } else {
            product = {
                id: 'manual_' + Date.now(),
                name: name,
                price: price,
                quantity: quantity,
                type: 'manual',
                stock_quantity: 999
            };
        }

        this.addProductToCart(product);

        // Clear manual form
        document.getElementById('manualProduct').value = '';
        document.getElementById('manualPrice').value = '';
        document.getElementById('manualQuantity').value = '1';
        document.getElementById('manualProduct').removeAttribute('data-product-data');
    }

    addProductToCart(product) {
        const quantityInput = document.getElementById('productQuantity');
        const quantity = quantityInput ?
            (parseInt(quantityInput.value, 10) || 1) :
            (product.quantity || 1);

        const availableStock = typeof product.stock_quantity === 'number' ? product.stock_quantity : 999;

        if (quantity > availableStock) {
            this.showAlert('Quantity exceeds available stock', 'error');
            return;
        }

        // Check if product already in cart
        const existingIndex = this.cart.findIndex(item => item.id === product.id);
        
        if (existingIndex >= 0) {
            // Update quantity of existing item
            const newQuantity = this.cart[existingIndex].quantity + quantity;
            if (newQuantity > availableStock) {
                this.showAlert('Total quantity would exceed available stock', 'error');
                return;
            }
            this.cart[existingIndex].quantity = newQuantity;
        } else {
            // Add new item to cart
            this.cart.push({
                ...product,
                quantity: quantity,
                cartId: Date.now() + Math.random() // Unique cart ID
            });
        }

        this.updateCartDisplay();
        this.showAlert(`${product.name} added to cart`, 'success');

        // Clear result displays
        const barcodeResult = document.getElementById('barcodeResult');
        if (barcodeResult) {
            setTimeout(() => {
                barcodeResult.style.display = 'none';
            }, 1000);
        }
    }

    removeFromCart(cartId) {
        this.cart = this.cart.filter(item => item.cartId !== cartId);
        this.updateCartDisplay();
    }

    updateCartQuantity(cartId, newQuantity) {
        const item = this.cart.find(item => item.cartId === cartId);
        if (item) {
            if (newQuantity <= 0) {
                this.removeFromCart(cartId);
            } else if (newQuantity <= (typeof item.stock_quantity === 'number' ? item.stock_quantity : 999)) {
                item.quantity = newQuantity;
                this.updateCartDisplay();
            } else {
                this.showAlert('Quantity exceeds available stock', 'error');
            }
        }
    }

    updateCartDisplay() {
        const cartItems = document.getElementById('cartItems');
        const itemCount = document.getElementById('cartItemCount');

        if (this.cart.length === 0) {
            cartItems.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Cart is empty</p>
                    <small>Scan or search for products to add</small>
                </div>
            `;
            itemCount.textContent = '0 items';
        } else {
            cartItems.innerHTML = this.cart.map(item => `
                <div class="cart-item">
                    <div class="item-info">
                        <div class="item-name">${item.name}</div>
                        <div class="item-details">
                            ${item.brand ? `${item.brand}` : ''}
                            ${item.dosage ? ` | ${item.dosage}` : ''}
                            ₱${parseFloat(item.price).toFixed(2)} each
                        </div>
                    </div>
                    <div class="item-controls">
                        <div class="quantity-control">
                            <button class="quantity-btn" onclick="checkout.updateCartQuantity(${item.cartId}, ${item.quantity - 1})">-</button>
                            <input type="number" class="quantity-input" value="${item.quantity}" 
                                   onchange="checkout.updateCartQuantity(${item.cartId}, parseInt(this.value))"
                                   min="1" max="${typeof item.stock_quantity === 'number' ? item.stock_quantity : 999}">
                            <button class="quantity-btn" onclick="checkout.updateCartQuantity(${item.cartId}, ${item.quantity + 1})">+</button>
                        </div>
                        <div class="item-total">₱${(item.price * item.quantity).toFixed(2)}</div>
                        <button class="remove-item" onclick="checkout.removeFromCart(${item.cartId})" title="Remove item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');

            const totalQuantity = this.cart.reduce((sum, item) => sum + item.quantity, 0);
            itemCount.textContent = `${totalQuantity} item${totalQuantity === 1 ? '' : 's'}`;
        }

        this.updateTotals();
    }

    updateTotals() {
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const discountAmount = this.discountAmount;
        const discountedSubtotal = subtotal - discountAmount;
        const taxAmount = discountedSubtotal * this.taxRate;
        const total = discountedSubtotal + taxAmount;

        document.getElementById('subtotalAmount').textContent = `₱${subtotal.toFixed(2)}`;
        document.getElementById('discountAmount').textContent = `₱${discountAmount.toFixed(2)}`;
        document.getElementById('taxAmount').textContent = `₱${taxAmount.toFixed(2)}`;
        document.getElementById('totalAmount').textContent = `₱${total.toFixed(2)}`;

        // Update payment button state
        const paymentBtn = document.getElementById('processPaymentBtn');
        paymentBtn.disabled = this.cart.length === 0;

        this.calculateChange();
    }

    clearCart() {
        if (this.cart.length === 0) return;

        if (confirm('Are you sure you want to clear the cart?')) {
            this.cart = [];
            this.updateCartDisplay();
            this.showAlert('Cart cleared', 'info');
        }
    }

    selectPaymentMethod(method) {
        this.paymentMethod = method;
        
        // Update UI
        document.querySelectorAll('.payment-method').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.method === method);
        });

        // Show/hide cash payment controls
        const cashPayment = document.getElementById('cashPayment');
        cashPayment.style.display = method === 'cash' ? 'block' : 'none';

        this.calculateChange();
    }

    calculateChange() {
        if (this.paymentMethod !== 'cash') return;

        const total = this.getCartTotal();
        const cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
        const change = cashReceived - total;

        document.getElementById('changeAmount').textContent = `₱${Math.max(0, change).toFixed(2)}`;

        // Enable/disable payment button based on sufficient payment
        const paymentBtn = document.getElementById('processPaymentBtn');
        paymentBtn.disabled = this.cart.length === 0 || (this.paymentMethod === 'cash' && cashReceived < total);
    }

    getCartTotal() {
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const discountedSubtotal = subtotal - this.discountAmount;
        return discountedSubtotal + (discountedSubtotal * this.taxRate);
    }

    async processPayment() {
        if (this.cart.length === 0) return;

        const total = this.getCartTotal();
        
        if (this.paymentMethod === 'cash') {
            const cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
            if (cashReceived < total) {
                this.showAlert('Insufficient cash received', 'error');
                return;
            }
        }

        // Show processing modal
        document.getElementById('paymentModal').style.display = 'block';

        try {
            const saleData = {
                sale_number: this.currentSaleNumber,
                customer_name: document.getElementById('customerName').value.trim() || null,
                customer_phone: document.getElementById('customerPhone').value.trim() || null,
                payment_method: this.paymentMethod,
                subtotal: this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
                discount_amount: this.discountAmount,
                tax_amount: (this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) - this.discountAmount) * this.taxRate,
                total_amount: total,
                cash_received: this.paymentMethod === 'cash' ? parseFloat(document.getElementById('cashReceived').value) : total,
                change_amount: this.paymentMethod === 'cash' ? Math.max(0, parseFloat(document.getElementById('cashReceived').value) - total) : 0,
                items: this.cart.map(item => ({
                    product_id: item.id,
                    product_name: item.name,
                    product_type: item.type || 'product',
                    price: item.price,
                    quantity: item.quantity,
                    total: item.price * item.quantity
                }))
            };

            const response = await fetch('api/process-sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(saleData)
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('Payment processed successfully!', 'success');
                
                // Generate receipt
                this.generateReceipt(result.sale_id, saleData);
                
                // Reset for next sale
                setTimeout(() => {
                    this.resetCheckout();
                }, 2000);
            } else {
                throw new Error(result.message || 'Payment processing failed');
            }
        } catch (error) {
            console.error('Payment processing error:', error);
            this.showAlert('Payment processing failed: ' + error.message, 'error');
        } finally {
            document.getElementById('paymentModal').style.display = 'none';
        }
    }

    generateReceipt(saleId, saleData) {
        const receiptWindow = window.open('', '_blank', 'width=300,height=600');
        const receiptHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Receipt - ${saleData.sale_number}</title>
                <style>
                    body { font-family: monospace; margin: 10px; font-size: 12px; }
                    .header { text-align: center; margin-bottom: 15px; }
                    .receipt-line { display: flex; justify-content: space-between; margin: 2px 0; }
                    .total-line { border-top: 1px dashed #000; font-weight: bold; }
                    .footer { margin-top: 15px; text-align: center; font-size: 10px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h3>RAZOLOGY PHARMACY</h3>
                    <p>Receipt #${saleData.sale_number}</p>
                    <p>${new Date().toLocaleString()}</p>
                </div>
                
                <div class="items">
                    ${saleData.items.map(item => `
                        <div class="receipt-line">
                            <span>${item.product_name}</span>
                        </div>
                        <div class="receipt-line">
                            <span>${item.quantity} x ₱${item.price.toFixed(2)}</span>
                            <span>₱${item.total.toFixed(2)}</span>
                        </div>
                    `).join('')}
                </div>
                
                <div class="totals">
                    <div class="receipt-line">
                        <span>Subtotal:</span>
                        <span>₱${saleData.subtotal.toFixed(2)}</span>
                    </div>
                    ${saleData.discount_amount > 0 ? `
                        <div class="receipt-line">
                            <span>Discount:</span>
                            <span>-₱${saleData.discount_amount.toFixed(2)}</span>
                        </div>
                    ` : ''}
                    <div class="receipt-line">
                        <span>Tax (12%):</span>
                        <span>₱${saleData.tax_amount.toFixed(2)}</span>
                    </div>
                    <div class="receipt-line total-line">
                        <span>TOTAL:</span>
                        <span>₱${saleData.total_amount.toFixed(2)}</span>
                    </div>
                    
                    ${saleData.payment_method === 'cash' ? `
                        <div class="receipt-line">
                            <span>Cash:</span>
                            <span>₱${saleData.cash_received.toFixed(2)}</span>
                        </div>
                        <div class="receipt-line">
                            <span>Change:</span>
                            <span>₱${saleData.change_amount.toFixed(2)}</span>
                        </div>
                    ` : `
                        <div class="receipt-line">
                            <span>Payment:</span>
                            <span>${saleData.payment_method.toUpperCase()}</span>
                        </div>
                    `}
                </div>
                
                ${saleData.customer_name ? `
                    <div class="customer">
                        <p>Customer: ${saleData.customer_name}</p>
                        ${saleData.customer_phone ? `<p>Phone: ${saleData.customer_phone}</p>` : ''}
                    </div>
                ` : ''}
                
                <div class="footer">
                    <p>Thank you for your purchase!</p>
                    <p>Served by: ${document.querySelector('.user-welcome').textContent.replace('👤', '').trim()}</p>
                </div>
                
                <script>
                    window.onload = function() {
                        window.print();
                    }
                </script>
            </body>
            </html>
        `;
        
        receiptWindow.document.write(receiptHTML);
        receiptWindow.document.close();
    }

    resetCheckout() {
        // Clear cart
        this.cart = [];
        this.discountAmount = 0;
        
        // Reset forms
        this.clearBarcodeInputs();
        document.getElementById('productSearch').value = '';
        document.getElementById('manualProduct').value = '';
        document.getElementById('manualPrice').value = '';
        document.getElementById('manualQuantity').value = '1';
        document.getElementById('customerName').value = '';
        document.getElementById('customerPhone').value = '';
        document.getElementById('cashReceived').value = '';
        
        // Reset UI
        const barcodeResult = document.getElementById('barcodeResult');
        if (barcodeResult) {
            barcodeResult.style.display = 'none';
        }
        document.getElementById('searchResults').innerHTML = '';
        this.updateCartDisplay();
        
        // Generate new sale number
        this.generateNewSaleNumber();
        
        // Focus barcode input
        this.switchTab('barcode');
        const barcodeInput = document.getElementById('manualBarcode') || document.getElementById('barcodeInput');
        if (barcodeInput) {
            barcodeInput.focus();
        }
        
        this.showAlert('Ready for next sale', 'info');
    }

    async generateNewSaleNumber() {
        try {
            const response = await fetch('api/generate-sale-number.php');
            const data = await response.json();
            
            if (data.success) {
                this.currentSaleNumber = data.sale_number;
                document.querySelector('.sale-number').textContent = `Sale #${this.currentSaleNumber}`;
            }
        } catch (error) {
            console.error('Error generating sale number:', error);
        }
    }

    holdSale() {
        if (this.cart.length === 0) {
            this.showAlert('No items in cart to hold', 'error');
            return;
        }

        const heldSales = JSON.parse(localStorage.getItem('heldSales') || '[]');
        const saleData = {
            sale_number: this.currentSaleNumber,
            timestamp: Date.now(),
            cart: [...this.cart],
            customer_name: document.getElementById('customerName').value.trim(),
            customer_phone: document.getElementById('customerPhone').value.trim(),
            discount_amount: this.discountAmount
        };

        heldSales.push(saleData);
        localStorage.setItem('heldSales', JSON.stringify(heldSales));
        
        this.resetCheckout();
        this.showAlert('Sale held successfully', 'success');
    }

    recallSale() {
        const heldSales = JSON.parse(localStorage.getItem('heldSales') || '[]');
        
        if (heldSales.length === 0) {
            this.showAlert('No held sales available', 'info');
            return;
        }

        // For now, recall the most recent held sale
        // In a full implementation, you'd show a selection dialog
        const mostRecent = heldSales[heldSales.length - 1];
        
        // Restore sale data
        this.cart = [...mostRecent.cart];
        this.discountAmount = mostRecent.discount_amount;
        document.getElementById('customerName').value = mostRecent.customer_name || '';
        document.getElementById('customerPhone').value = mostRecent.customer_phone || '';
        
        // Remove from held sales
        heldSales.pop();
        localStorage.setItem('heldSales', JSON.stringify(heldSales));
        
        this.updateCartDisplay();
        this.showAlert(`Recalled sale #${mostRecent.sale_number}`, 'success');
    }

    // Barcode Scanner Functions
    openBarcodeScanner() {
        document.getElementById('barcodeModal').style.display = 'block';
        setTimeout(() => this.startScanner(), 500);
    }

    closeBarcodeScanner() {
        this.stopScanner();
        document.getElementById('barcodeModal').style.display = 'none';
    }

    async startScanner() {
        if (this.scanner) {
            this.stopScanner();
        }

        const scannerVideo = document.getElementById('scanner');
        if (!scannerVideo) {
            this.showAlert('Scanner video element not found', 'error');
            return;
        }
        scannerVideo.muted = true;
        
        // Check if HTTPS is required for camera access
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            this.showAlert('Camera access requires HTTPS or localhost', 'error');
            return;
        }

        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Camera API not supported');
            }

            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1280, max: 1920 },
                    height: { ideal: 720, max: 1080 }
                },
                audio: false
            });

            this.currentStream = stream;
            scannerVideo.srcObject = stream;
            await scannerVideo.play();
        } catch (error) {
            console.error('Camera permission error:', error);
            let errorMessage = 'Camera access denied or not available';
            
            if (error.name === 'NotAllowedError') {
                errorMessage = 'Camera permission denied. Please allow camera access and try again.';
            } else if (error.name === 'NotFoundError') {
                errorMessage = 'No camera found on this device.';
            } else if (error.name === 'NotSupportedError') {
                errorMessage = 'Camera not supported on this device.';
            }
            
            this.showAlert(errorMessage, 'error');
            return;
        }

        // Initialize Quagga scanner
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: scannerVideo,
                constraints: {
                    width: { min: 640, ideal: 1280, max: 1920 },
                    height: { min: 480, ideal: 720, max: 1080 },
                    facingMode: "environment",
                    aspectRatio: { min: 1, max: 2 }
                }
            },
            locator: {
                patchSize: "medium",
                halfSample: true
            },
            numOfWorkers: 2,
            frequency: 10,
            decoder: {
                readers: [
                    "code_128_reader",
                    "ean_reader",
                    "ean_8_reader",
                    "code_39_reader",
                    "code_39_vin_reader",
                    "codabar_reader",
                    "upc_reader",
                    "upc_e_reader",
                    "i2of5_reader"
                ]
            },
            locate: true,
            debug: {
                showCanvas: true,
                showPatches: false,
                showFoundPatches: false,
                showSkeleton: false,
                showLabels: false,
                showPatchLabels: false,
                showRemainingPatchLabels: false,
                boxFromPatches: {
                    showTransformed: true,
                    showTransformedBox: true,
                    showBB: true
                }
            }
        }, (err) => {
            if (err) {
                console.error('Scanner initialization error:', err);
                let errorMessage = 'Failed to initialize camera scanner';
                
                if (err.name === 'NotAllowedError') {
                    errorMessage = 'Camera permission denied. Please allow camera access in your browser settings.';
                } else if (err.name === 'NotFoundError') {
                    errorMessage = 'No camera device found.';
                } else if (err.name === 'NotReadableError') {
                    errorMessage = 'Camera is being used by another application.';
                } else if (err.name === 'OverconstrainedError') {
                    errorMessage = 'Camera constraints not supported by your device.';
                }
                
                this.showAlert(errorMessage, 'error');
                return;
            }
            
            try {
                Quagga.start();
                this.scanner = true;
                
                const startScannerBtn = document.getElementById('startScannerBtn');
                const stopScannerBtn = document.getElementById('stopScannerBtn');
                if (startScannerBtn) {
                    startScannerBtn.style.display = 'none';
                }
                if (stopScannerBtn) {
                    stopScannerBtn.style.display = 'inline-block';
                }
                
                this.showAlert('Scanner started - point camera at barcode', 'info');
            } catch (startError) {
                console.error('Scanner start error:', startError);
                this.showAlert('Failed to start camera scanner', 'error');
            }
        });

        // Handle barcode detection
        Quagga.onDetected((result) => {
            const code = result.codeResult.code;
            console.log('Barcode detected:', code);
            
            // Add some validation to ensure we have a valid barcode
            if (code && code.length >= 8 && !this.isBarcodeLookupInProgress) {
                this.isBarcodeLookupInProgress = true;
                this.clearBarcodeInputs();
                const scannerStatus = document.getElementById('scannerStatus');
                if (scannerStatus) {
                    scannerStatus.textContent = 'Barcode detected. Looking up product...';
                }
                const manualBarcodeInput = document.getElementById('manualBarcode') || document.getElementById('barcodeInput');
                if (manualBarcodeInput) {
                    manualBarcodeInput.value = code;
                }
                
                // Vibrate if supported
                if (navigator.vibrate) {
                    navigator.vibrate(200);
                }
                
                this.processBarcodeEntry(code, { autoAdd: true }).then((data) => {
                    this.isBarcodeLookupInProgress = false;

                    if (data && data.success && data.product) {
                        this.closeBarcodeScanner();
                    } else if (scannerStatus) {
                        scannerStatus.textContent = 'No matching product found. Try another barcode.';
                    }
                }).catch(() => {
                    this.isBarcodeLookupInProgress = false;
                    if (scannerStatus) {
                        scannerStatus.textContent = 'Unable to look up barcode. Try again.';
                    }
                });
            }
        });

        // Handle processing errors
        Quagga.onProcessed((result) => {
            const drawingCtx = Quagga.canvas.ctx.overlay;
            const drawingCanvas = Quagga.canvas.dom.overlay;

            if (result) {
                if (result.boxes) {
                    drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")));
                    result.boxes.filter(function (box) {
                        return box !== result.box;
                    }).forEach(function (box) {
                        Quagga.ImageDebug.drawPath(box, {x: 0, y: 1}, drawingCtx, {color: "green", lineWidth: 2});
                    });
                }

                if (result.box) {
                    Quagga.ImageDebug.drawPath(result.box, {x: 0, y: 1}, drawingCtx, {color: "#00F", lineWidth: 2});
                }

                if (result.codeResult && result.codeResult.code) {
                    Quagga.ImageDebug.drawPath(result.line, {x: 'x', y: 'y'}, drawingCtx, {color: 'red', lineWidth: 3});
                }
            }
        });
    }

    stopScanner() {
        if (this.scanner) {
            Quagga.stop();
            this.scanner = null;
        }

        if (this.currentStream) {
            this.currentStream.getTracks().forEach(track => track.stop());
            this.currentStream = null;
        }

        const scannerVideo = document.getElementById('scanner');
        if (scannerVideo) {
            scannerVideo.srcObject = null;
        }
    }

    async processBarcodeFromImage() {
        const cameraInput = document.getElementById('camera-input');
        const galleryInput = document.getElementById('gallery-input');
        const imageButton = document.querySelector('.image-upload-btn');

        const file = (cameraInput && cameraInput.files && cameraInput.files[0]) ||
            (galleryInput && galleryInput.files && galleryInput.files[0]);

        if (!file) {
            this.showAlert('Please select an image file', 'error');
            return;
        }

        const originalText = imageButton ? imageButton.innerHTML : '';
        if (imageButton) {
            imageButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning...';
            imageButton.disabled = true;
        }

        const img = new Image();
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        img.onload = () => {
            canvas.width = img.width;
            canvas.height = img.height;
            ctx.drawImage(img, 0, 0);

            Quagga.decodeSingle({
                src: canvas.toDataURL(),
                numOfWorkers: 0,
                inputStream: { size: 800 },
                locator: {
                    patchSize: 'medium',
                    halfSample: true
                },
                decoder: {
                    readers: [
                        'code_128_reader',
                        'ean_reader',
                        'ean_8_reader',
                        'code_39_reader',
                        'code_39_vin_reader',
                        'codabar_reader',
                        'upc_reader',
                        'upc_e_reader',
                        'i2of5_reader'
                    ]
                }
            }, (result) => {
                if (imageButton) {
                    imageButton.innerHTML = originalText;
                    imageButton.disabled = false;
                }

                if (result && result.codeResult && result.codeResult.code) {
                    this.processBarcodeEntry(result.codeResult.code, { autoAdd: true });
                } else {
                    this.showAlert('No barcode found in the image. Please try a clearer image.', 'error');
                }
            });
        };

        img.onerror = () => {
            if (imageButton) {
                imageButton.innerHTML = originalText;
                imageButton.disabled = false;
            }
            this.showAlert('Error loading image. Please try again.', 'error');
        };

        img.src = URL.createObjectURL(file);
    }

    updateTime() {
        const now = new Date();
        document.getElementById('saleTime').textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    showAlert(message, type = 'info') {
        // Create alert element if it doesn't exist
        let alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'alertContainer';
            alertContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 300px;
            `;
            document.body.appendChild(alertContainer);
        }

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
            ${message}
        `;
        alert.style.cssText = `
            background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1'};
            color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
            border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#bee5eb'};
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideInRight 0.3s ease;
        `;

        alertContainer.appendChild(alert);

        // Auto remove after 3 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 3000);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize checkout system when page loads
let checkout;
document.addEventListener('DOMContentLoaded', function() {
    checkout = new CheckoutSystem();
});

function processBarcode() {
    if (!checkout) {
        return;
    }

    const manualBarcodeInput = document.getElementById('manualBarcode') || document.getElementById('barcodeInput');
    const barcode = manualBarcodeInput ? manualBarcodeInput.value.trim() : '';
    checkout.processBarcodeEntry(barcode, { autoAdd: true });
}

function processBarcodeFromImage() {
    if (checkout) {
        checkout.processBarcodeFromImage();
    }
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .loading {
        text-align: center;
        padding: 1rem;
        color: var(--gray-600);
    }
    
    .loading i {
        margin-right: 0.5rem;
    }
    
    .search-result-item {
        padding: 1rem;
        border: 1px solid var(--gray-200);
        border-radius: var(--border-radius);
        margin-bottom: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .search-result-item:hover {
        background: var(--gray-50);
        border-color: var(--primary-color);
    }
    
    .result-info h5 {
        margin: 0 0 0.25rem 0;
        color: var(--dark-color);
    }
    
    .result-details {
        font-size: 0.9rem;
        color: var(--gray-600);
        margin: 0;
    }
    
    .result-price {
        font-weight: 600;
        color: var(--primary-color);
    }
    
    .stock-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .stock-badge.in-stock {
        background: #d4edda;
        color: #155724;
    }
    
    .stock-badge.out-of-stock {
        background: #f8d7da;
        color: #721c24;
    }
    
    .no-results, .error {
        text-align: center;
        padding: 2rem;
        color: var(--gray-500);
    }
    
    .product-result {
        padding: 1rem;
        border: 1px solid var(--gray-200);
        border-radius: var(--border-radius);
        background: var(--white);
    }
    
    .product-info h4 {
        margin: 0 0 0.5rem 0;
        color: var(--dark-color);
    }
    
    .product-details {
        font-size: 0.9rem;
        color: var(--gray-600);
        margin: 0 0 0.5rem 0;
    }
    
    .product-price {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--primary-color);
        margin: 0 0 0.5rem 0;
    }
    
    .product-stock {
        font-size: 0.9rem;
        color: var(--gray-600);
        margin: 0 0 1rem 0;
    }
    
    .add-controls {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
    }
    
    .quantity-input-group {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .quantity-input-group label {
        font-size: 0.9rem;
        color: var(--gray-700);
    }
    
    .quantity-input-group input {
        width: 80px;
    }
    
    .no-product, .error {
        text-align: center;
        padding: 2rem;
    }
    
    .no-product i, .error i {
        font-size: 2rem;
        margin-bottom: 1rem;
        color: var(--warning-color);
    }
`;
document.head.appendChild(style);