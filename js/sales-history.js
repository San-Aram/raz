// Sales History JavaScript Functions
class SalesHistory {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupKeyboardShortcuts();
    }

    setupEventListeners() {
        // Modal close events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeSaleDetails();
            }
        });

        // Filter form auto-submit on change
        const filterInputs = document.querySelectorAll('.filters-form input, .filters-form select');
        filterInputs.forEach(input => {
            if (input.type === 'date' || input.tagName === 'SELECT') {
                input.addEventListener('change', () => {
                    // Auto-submit after short delay
                    setTimeout(() => {
                        document.querySelector('.filters-form').submit();
                    }, 300);
                });
            }
        });
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Escape to close modal
            if (e.key === 'Escape') {
                this.closeSaleDetails();
            }
            
            // Ctrl+N for new sale
            if (e.ctrlKey && e.key.toLowerCase() === 'n') {
                e.preventDefault();
                window.location.href = 'checkout.php';
            }
            
            // Ctrl+S for stats
            if (e.ctrlKey && e.key.toLowerCase() === 's') {
                e.preventDefault();
                window.location.href = 'sales-stats.php';
            }
            
            // F5 to refresh (default behavior, but show loading)
            if (e.key === 'F5') {
                this.showLoading();
            }
        });
    }

    async viewSaleDetails(saleId) {
        try {
            this.showLoading();
            
            const response = await fetch(`api/sale-details.php?id=${saleId}`);
            const data = await response.json();
            
            if (data.success) {
                this.displaySaleDetails(data.sale, data.items);
                document.getElementById('saleDetailsModal').style.display = 'block';
            } else {
                this.showAlert('Failed to load sale details: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading sale details:', error);
            this.showAlert('Error loading sale details', 'error');
        } finally {
            this.hideLoading();
        }
    }

    displaySaleDetails(sale, items) {
        const content = document.getElementById('saleDetailsContent');
        
        const customerInfo = sale.customer_name ? `
            <div class="detail-section">
                <h4><i class="fas fa-user"></i> Customer Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Name:</label>
                        <span>${this.escapeHtml(sale.customer_name)}</span>
                    </div>
                    ${sale.customer_phone ? `
                        <div class="detail-item">
                            <label>Phone:</label>
                            <span>${this.escapeHtml(sale.customer_phone)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        ` : '';

        content.innerHTML = `
            <div class="sale-details">
                <div class="detail-header">
                    <div class="sale-number">
                        <h3>Sale #${this.escapeHtml(sale.sale_number)}</h3>
                        <span class="sale-date">${new Date(sale.sale_date).toLocaleString()}</span>
                    </div>
                    <div class="sale-status">
                        <span class="status-badge status-${sale.payment_status}">${sale.payment_status.toUpperCase()}</span>
                        <span class="payment-method payment-${sale.payment_method}">
                            <i class="fas fa-${this.getPaymentIcon(sale.payment_method)}"></i>
                            ${this.capitalize(sale.payment_method)}
                        </span>
                    </div>
                </div>

                ${customerInfo}

                <div class="detail-section">
                    <h4><i class="fas fa-shopping-cart"></i> Items (${items.length})</h4>
                    <div class="items-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${items.map(item => `
                                    <tr>
                                        <td>
                                            <strong>${this.escapeHtml(item.product_name)}</strong>
                                            ${item.product_barcode ? `<br><small>Barcode: ${item.product_barcode}</small>` : ''}
                                        </td>
                                        <td>
                                            <span class="product-type">${this.capitalize(item.product_type)}</span>
                                        </td>
                                        <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                                        <td>${item.quantity}</td>
                                        <td><strong>₱${parseFloat(item.line_total).toFixed(2)}</strong></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="detail-section">
                    <h4><i class="fas fa-calculator"></i> Payment Summary</h4>
                    <div class="payment-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>₱${parseFloat(sale.subtotal).toFixed(2)}</span>
                        </div>
                        ${sale.discount_amount > 0 ? `
                            <div class="summary-row">
                                <span>Discount:</span>
                                <span class="discount">-₱${parseFloat(sale.discount_amount).toFixed(2)}</span>
                            </div>
                        ` : ''}
                        <div class="summary-row">
                            <span>Tax (12%):</span>
                            <span>₱${parseFloat(sale.tax_amount).toFixed(2)}</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total Amount:</span>
                            <span>₱${parseFloat(sale.total_amount).toFixed(2)}</span>
                        </div>
                    </div>
                </div>

                <div class="detail-actions">
                    <button class="btn btn-primary" onclick="salesHistory.printReceipt(${sale.id})">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                    <button class="btn btn-outline" onclick="salesHistory.closeSaleDetails()">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        `;
    }

    async printReceipt(saleId) {
        try {
            const response = await fetch(`api/sale-details.php?id=${saleId}`);
            const data = await response.json();
            
            if (data.success) {
                this.generateReceiptPrint(data.sale, data.items);
            } else {
                this.showAlert('Failed to load receipt data', 'error');
            }
        } catch (error) {
            console.error('Error loading receipt data:', error);
            this.showAlert('Error loading receipt data', 'error');
        }
    }

    generateReceiptPrint(sale, items) {
        const receiptWindow = window.open('', '_blank', 'width=300,height=600');
        const receiptHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Receipt - ${sale.sale_number}</title>
                <style>
                    body { font-family: monospace; margin: 10px; font-size: 12px; }
                    .header { text-align: center; margin-bottom: 15px; }
                    .receipt-line { display: flex; justify-content: space-between; margin: 2px 0; }
                    .total-line { border-top: 1px dashed #000; font-weight: bold; margin-top: 10px; padding-top: 5px; }
                    .footer { margin-top: 15px; text-align: center; font-size: 10px; }
                    .items { margin: 10px 0; }
                    .item-line { margin: 3px 0; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h3>RAZOLOGY PHARMACY</h3>
                    <p>Receipt #${sale.sale_number}</p>
                    <p>${new Date(sale.sale_date).toLocaleString()}</p>
                </div>
                
                <div class="items">
                    ${items.map(item => `
                        <div class="item-line">
                            <div>${item.product_name}</div>
                            <div class="receipt-line">
                                <span>${item.quantity} x ₱${parseFloat(item.unit_price).toFixed(2)}</span>
                                <span>₱${parseFloat(item.line_total).toFixed(2)}</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
                
                <div class="totals">
                    <div class="receipt-line">
                        <span>Subtotal:</span>
                        <span>₱${parseFloat(sale.subtotal).toFixed(2)}</span>
                    </div>
                    ${sale.discount_amount > 0 ? `
                        <div class="receipt-line">
                            <span>Discount:</span>
                            <span>-₱${parseFloat(sale.discount_amount).toFixed(2)}</span>
                        </div>
                    ` : ''}
                    <div class="receipt-line">
                        <span>Tax (12%):</span>
                        <span>₱${parseFloat(sale.tax_amount).toFixed(2)}</span>
                    </div>
                    <div class="receipt-line total-line">
                        <span>TOTAL:</span>
                        <span>₱${parseFloat(sale.total_amount).toFixed(2)}</span>
                    </div>
                    <div class="receipt-line">
                        <span>Payment:</span>
                        <span>${this.capitalize(sale.payment_method)}</span>
                    </div>
                </div>
                
                ${sale.customer_name ? `
                    <div class="customer">
                        <p>Customer: ${sale.customer_name}</p>
                        ${sale.customer_phone ? `<p>Phone: ${sale.customer_phone}</p>` : ''}
                    </div>
                ` : ''}
                
                <div class="footer">
                    <p>Thank you for your purchase!</p>
                    <p>Razology Pharmacy POS System</p>
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

    closeSaleDetails() {
        document.getElementById('saleDetailsModal').style.display = 'none';
    }

    getPaymentIcon(method) {
        const icons = {
            'cash': 'money-bill-wave',
            'card': 'credit-card',
            'mobile': 'mobile-alt',
            'insurance': 'shield-alt'
        };
        return icons[method] || 'credit-card';
    }

    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    showAlert(message, type = 'info') {
        // Create alert if it doesn't exist
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

        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }

    showLoading() {
        // Create loading overlay if it doesn't exist
        let loadingOverlay = document.getElementById('loadingOverlay');
        if (!loadingOverlay) {
            loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'loadingOverlay';
            loadingOverlay.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading...</p>
                </div>
            `;
            loadingOverlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;
            document.body.appendChild(loadingOverlay);
        }
        loadingOverlay.style.display = 'flex';
    }

    hideLoading() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }
}

// Global functions for onclick handlers
let salesHistory;

function viewSaleDetails(saleId) {
    salesHistory.viewSaleDetails(saleId);
}

function printReceipt(saleId) {
    salesHistory.printReceipt(saleId);
}

function closeSaleDetails() {
    salesHistory.closeSaleDetails();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    salesHistory = new SalesHistory();
});

// Add styles for the components
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    .loading-spinner {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        text-align: center;
        color: var(--primary-color);
    }

    .loading-spinner i {
        font-size: 2rem;
        margin-bottom: 1rem;
    }

    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--gray-200);
    }

    .sale-number h3 {
        margin: 0 0 0.5rem 0;
        color: var(--primary-color);
    }

    .sale-date {
        color: var(--gray-600);
        font-size: 0.9rem;
    }

    .sale-status {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-paid {
        background: #d4edda;
        color: #155724;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-refunded {
        background: #f8d7da;
        color: #721c24;
    }

    .detail-section {
        margin-bottom: 2rem;
    }

    .detail-section h4 {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0 0 1rem 0;
        color: var(--dark-color);
        font-size: 1.1rem;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem;
        background: var(--gray-50);
        border-radius: 4px;
    }

    .detail-item label {
        font-weight: 600;
        color: var(--gray-700);
    }

    .items-table table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    .items-table th,
    .items-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--gray-200);
    }

    .items-table th {
        background: var(--gray-50);
        font-weight: 600;
        color: var(--dark-color);
    }

    .product-type {
        padding: 0.25rem 0.5rem;
        background: var(--primary-color);
        color: var(--white);
        border-radius: 12px;
        font-size: 0.8rem;
    }

    .payment-summary {
        background: var(--gray-50);
        padding: 1.5rem;
        border-radius: 8px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
        padding: 0.5rem 0;
    }

    .summary-row.total {
        border-top: 2px solid var(--gray-300);
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--primary-color);
        margin-top: 1rem;
        padding-top: 1rem;
    }

    .discount {
        color: #dc3545;
    }

    .detail-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid var(--gray-200);
    }

    @media (max-width: 768px) {
        .detail-header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }

        .sale-status {
            align-items: center;
        }

        .detail-grid {
            grid-template-columns: 1fr;
        }

        .detail-actions {
            flex-direction: column;
        }

        .items-table {
            overflow-x: auto;
        }
    }
`;
document.head.appendChild(style);