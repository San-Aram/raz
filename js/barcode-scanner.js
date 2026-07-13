// Barcode Scanner Functionality
let currentStream = null;
let isScanning = false;
let currentCategory = 'pharmaceutics'; // Default category

// Open barcode scanner modal
function openBarcodeScanner(category = 'pharmaceutics') {
    currentCategory = category;
    const modal = document.getElementById('barcodeModal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Add mobile-specific class and show instructions
    if (window.innerWidth <= 768) {
        modal.classList.add('mobile-modal');
        const mobileInstructions = document.querySelector('.mobile-instructions');
        if (mobileInstructions) {
            mobileInstructions.style.display = 'block';
        }
    }
    
    // Request camera permission explicitly on mobile
    requestCameraPermission();
}

// Request camera permission explicitly
async function requestCameraPermission() {
    try {
        // Check if we're on HTTPS or localhost (required for camera access)
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            showAlert('Camera access requires HTTPS or localhost. Please use the manual barcode entry or image upload.', 'warning');
            return;
        }

        // Check if getUserMedia is supported
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showAlert('Camera not supported on this device or browser. Please use manual barcode entry or image upload.', 'warning');
            return;
        }

        // On mobile, show loading message
        if (window.innerWidth <= 768) {
            showAlert('Requesting camera access... Please allow when prompted.', 'info');
        }

        // Try to start camera directly (will prompt for permission)
        await startCamera();
        
    } catch (error) {
        console.error('Error requesting camera permission:', error);
        
        let errorMessage = 'Unable to access camera. ';
        
        if (error.name === 'NotAllowedError') {
            errorMessage += 'Camera permission was denied. Please:';
            showAlert(errorMessage, 'warning');
            
            // Show specific instructions for mobile
            if (window.innerWidth <= 768) {
                setTimeout(() => {
                    showAlert('To enable camera: Go to browser settings → Site settings → Camera → Allow for this site, then try again.', 'info');
                }, 2000);
            }
        } else if (error.name === 'NotFoundError') {
            errorMessage += 'No camera found on this device. Please use manual barcode entry.';
            showAlert(errorMessage, 'warning');
        } else if (error.name === 'NotSupportedError') {
            errorMessage += 'Camera not supported in this browser. Please use manual barcode entry.';
            showAlert(errorMessage, 'warning');
        } else {
            errorMessage += 'Please check permissions or use manual barcode entry.';
            showAlert(errorMessage, 'warning');
        }
    }
}

// Close barcode scanner modal
function closeBarcodeScanner() {
    const modal = document.getElementById('barcodeModal');
    modal.style.display = 'none';
    modal.classList.remove('mobile-modal');
    document.body.style.overflow = 'auto';
    
    // Hide mobile instructions
    const mobileInstructions = document.querySelector('.mobile-instructions');
    if (mobileInstructions) {
        mobileInstructions.style.display = 'none';
    }
    
    stopCamera();
    stopScanning();
}

// Start camera for barcode scanning
async function startCamera() {
    try {
        const video = document.getElementById('scanner');
        
        // Enhanced mobile camera constraints
        const constraints = {
            video: {
                width: { ideal: 1280, max: 1920 },
                height: { ideal: 720, max: 1080 },
                facingMode: 'environment', // Use rear camera on mobile
                focusMode: 'continuous',
                exposureMode: 'continuous',
                whiteBalanceMode: 'continuous'
            }
        };
        
        // Try to get video stream
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        
        currentStream = stream;
        video.srcObject = stream;
        
        // Wait for video to load and start playing
        await new Promise((resolve, reject) => {
            video.onloadedmetadata = () => {
                video.play()
                    .then(resolve)
                    .catch(reject);
            };
            video.onerror = reject;
        });
        
        // Start Quagga scanner after video starts playing
        setTimeout(() => {
            initQuaggaScanner();
        }, 500);
        
        showAlert('Camera started successfully. Point camera at barcode.', 'success');
        
    } catch (error) {
        console.error('Error accessing camera:', error);
        
        let errorMessage = 'Unable to access camera. ';
        
        if (error.name === 'NotAllowedError') {
            errorMessage += 'Please allow camera access when prompted, then try again.';
        } else if (error.name === 'NotFoundError') {
            errorMessage += 'No camera found on this device.';
        } else if (error.name === 'NotSupportedError') {
            errorMessage += 'Camera not supported in this browser.';
        } else {
            errorMessage += 'Please check permissions or use manual barcode entry.';
        }
        
        showAlert(errorMessage, 'warning');
    }
}

// Stop camera
function stopCamera() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
        currentStream = null;
    }
    
    const video = document.getElementById('scanner');
    video.srcObject = null;
}

// Initialize Quagga barcode scanner
function initQuaggaScanner() {
    if (isScanning) return;
    
    const video = document.getElementById('scanner');
    
    Quagga.init({
        inputStream: {
            name: "Live",
            type: "LiveStream",
            target: video,
            constraints: {
                width: { min: 640, ideal: 1280 },
                height: { min: 480, ideal: 720 },
                facingMode: "environment",
                aspectRatio: { min: 1, max: 2 }
            }
        },
        locator: {
            patchSize: "medium",
            halfSample: true
        },
        numOfWorkers: navigator.hardwareConcurrency || 2,
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
        locate: true
    }, function(err) {
        if (err) {
            console.error('Error initializing Quagga:', err);
            showAlert('Barcode scanner initialization failed. Please enter barcode manually.', 'danger');
            return;
        }
        console.log("Quagga initialization finished. Ready to start");
        Quagga.start();
        isScanning = true;
        
        // Add mobile-specific hint
        if (window.innerWidth <= 768) {
            showAlert('Hold your device steady and ensure good lighting for best results.', 'info');
        }
    });

    // Handle successful barcode detection
    Quagga.onDetected(function(data) {
        const code = data.codeResult.code;
        console.log("Barcode detected:", code);
        
        // Fill manual input field
        document.getElementById('manualBarcode').value = code;
        
        // Process the barcode (do NOT stop scanning here so camera can continue trying)
        processBarcode(code);
    });
}

// Stop Quagga scanner
function stopScanning() {
    if (isScanning) {
        Quagga.stop();
        isScanning = false;
    }
}

// Add debounce mechanism to prevent rapid submissions
let isProcessingBarcode = false;
let isCreatingProduct = false;

// Process barcode (check if exists or create new product)
function processBarcode(barcode = null) {
    // Prevent rapid submissions
    if (isProcessingBarcode) {
        console.log('Already processing barcode, returning...');
        return;
    }
    
    const barcodeValue = barcode || document.getElementById('manualBarcode').value.trim();
    
    console.log('Processing barcode:', barcodeValue);
    console.log('Current category:', currentCategory);
    
    if (!barcodeValue) {
        console.log('No barcode value provided');
        if (typeof showAlert === 'function') {
            showAlert('Please enter a barcode number.', 'warning');
        } else {
            alert('Please enter a barcode number.');
        }
        return;
    }
    
    isProcessingBarcode = true;
    
    // Show loading
    const processBtn = document.querySelector('.manual-input button') || document.querySelector('button[onclick*="processBarcode"]');
    let originalText = 'Process Barcode';
    
    if (processBtn) {
        originalText = processBtn.innerHTML;
        processBtn.innerHTML = '<div class="loading"></div> Processing...';
        processBtn.disabled = true;
    } else {
        console.warn('Process button not found, continuing without loading state');
    }
    
    console.log('Sending request to API with data:', { barcode: barcodeValue, category: currentCategory });
    
    // Check if barcode exists
    fetch('api/check-barcode.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            barcode: barcodeValue,
            category: currentCategory 
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('API response:', data);
        isProcessingBarcode = false;
        
        // Reset button state
        if (processBtn) {
            processBtn.innerHTML = originalText;
            processBtn.disabled = false;
        }
        
        if (data.success) {
            if (data.exists) {
                // Product exists, redirect to product page
                console.log('Product exists, redirecting to:', `${data.detail_page}?id=${data.product.id}`);
                
                if (typeof showAlert === 'function') {
                    try {
                        showAlert('Product found! Redirecting...', 'success');
                    } catch (e) {
                        console.warn('showAlert failed, using alert fallback:', e);
                        alert('Product found! Redirecting...');
                    }
                }
                
                // Close scanner and redirect
                setTimeout(() => {
                    closeBarcodeScanner();
                    window.location.href = `${data.detail_page}?id=${data.product.id}`;
                }, 1000);
                
            } else {
                // Product doesn't exist — show barcode and allow creating while scanner continues
                console.log('Product does not exist for barcode:', barcodeValue, 'category:', data.category);

                // Ensure a detected-info area exists in the modal
                let detectedArea = document.getElementById('detectedInfo');
                if (!detectedArea) {
                    detectedArea = document.createElement('div');
                    detectedArea.id = 'detectedInfo';
                    detectedArea.className = 'detected-info';
                    // Insert detected area right after manual-input section if possible
                    const manualInput = document.querySelector('#barcodeModal .manual-input');
                    if (manualInput && manualInput.parentNode) {
                        manualInput.parentNode.insertBefore(detectedArea, manualInput.nextSibling);
                    } else {
                        const modalContent = document.querySelector('#barcodeModal .modal-content') || document.body;
                        modalContent.appendChild(detectedArea);
                    }
                }

                // Populate the detected-info with barcode and create button
                detectedArea.innerHTML = `
                    <div class="alert alert-warning" style="margin-top:0.75rem">
                        Product not found for barcode <strong>${barcodeValue}</strong>.
                    </div>
                    <div style="display:flex;gap:0.5rem;margin-top:0.5rem;align-items:center">
                        <button id="createProductBtn" class="btn btn-success">Create product for ${barcodeValue}</button>
                        <button id="clearDetectedBtn" class="btn btn-secondary">Clear</button>
                        <small style="margin-left:0.5rem;color:#666">Scanner remains active to try again.</small>
                    </div>
                `;

                // Attach handlers
                const createBtn = document.getElementById('createProductBtn');
                if (createBtn) {
                    createBtn.onclick = function() {
                        if (isCreatingProduct) return;
                        isCreatingProduct = true;
                        try {
                            // Open new product form with the barcode prefilled. Keep scanner running in background.
                            openNewProductForm(barcodeValue, data.category);
                        } catch (error) {
                            console.error('Error opening new product form:', error);
                            alert('Error opening product form: ' + error.message);
                        } finally {
                            isCreatingProduct = false;
                        }
                    };
                }

                const clearBtn = document.getElementById('clearDetectedBtn');
                if (clearBtn) {
                    clearBtn.onclick = function() {
                        const el = document.getElementById('detectedInfo');
                        if (el) el.remove();
                        // Clear manual input but keep scanner running
                        const manual = document.getElementById('manualBarcode');
                        if (manual) manual.value = '';
                    };
                }

                // Inform user subtly that scanner remains active
                if (typeof showAlert === 'function') {
                    try { showAlert('No matching product found — you can create one for this barcode or continue scanning.', 'info'); } catch(e) { /* ignore */ }
                }
            }
        } else {
            console.log('API returned error:', data.message);
            if (typeof showAlert === 'function') {
                showAlert(data.message || 'Error processing barcode.', 'danger');
            } else {
                alert(data.message || 'Error processing barcode.');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        isProcessingBarcode = false;
        
        // Reset button state
        if (processBtn) {
            processBtn.innerHTML = originalText;
            processBtn.disabled = false;
        }
        
        if (typeof showAlert === 'function') {
            showAlert('Network error: ' + error.message, 'danger');
        } else {
            alert('Network error: ' + error.message);
        }
    });
}

// Open new product form with barcode pre-filled
function openNewProductForm(barcode, category = 'pharmaceutics') {
    // Create and show product form modal
    const formModal = createProductFormModal(barcode, category);
    document.body.appendChild(formModal);
    formModal.style.display = 'block';
}

// Create product form modal
function createProductFormModal(barcode, category = 'pharmaceutics') {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = 'productFormModal';
    
    let formTitle = '';
    let formIcon = '';
    let formFields = '';
    
    // Set title and icon based on category
    switch (category) {
        case 'cosmetics':
            formTitle = 'Add New Cosmetic Product';
            formIcon = 'fas fa-palette';
            break;
        case 'dental':
            formTitle = 'Add New Dental Product';
            formIcon = 'fas fa-tooth';
            break;
        default:
            formTitle = 'Add New Pharmaceutical Product';
            formIcon = 'fas fa-pills';
            break;
    }
    
    // Generate form fields based on category
    if (category === 'pharmaceutics') {
        formFields = `
            <div class="form-row">
                <div class="form-group">
                    <label for="product_name">Product Name *</label>
                    <input type="text" id="product_name" name="product_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="company">Company *</label>
                    <input type="text" id="company" name="company" class="form-control" required>
                </div>
            </div>
            
            <div class="ingredients-section">
                <h4><i class="fas fa-flask"></i> Active Ingredients & Doses</h4>
                <div id="ingredients-container">
                    <div class="ingredient-group" data-index="0">
                        <div class="ingredient-header">
                            <h5>Ingredient 1</h5>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="active_ingredient_0">Active Ingredient *</label>
                                <input type="text" id="active_ingredient_0" name="active_ingredient_0" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="dose_0">Dose *</label>
                                <input type="text" id="dose_0" name="dose_0" class="form-control" placeholder="e.g., 500mg" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 1rem;">
                    <button type="button" class="btn btn-info btn-sm" onclick="addIngredient()">
                        <i class="fas fa-plus"></i> Add Another Ingredient
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="form">Form *</label>
                <select id="form" name="form" class="form-control" required onchange="toggleCustomForm(this)">
                    <option value="">Select form</option>
                    <option value="Tablet">Tablet</option>
                    <option value="Capsule">Capsule</option>
                    <option value="Syrup">Syrup</option>
                    <option value="Injection">Injection</option>
                    <option value="Cream">Cream</option>
                    <option value="Ointment">Ointment</option>
                    <option value="Drops">Drops</option>
                    <option value="Inhaler">Inhaler</option>
                    <option value="Patch">Patch</option>
                    <option value="Suppository">Suppository</option>
                    <option value="Custom">Custom</option>
                </select>
            </div>
            
            <div class="form-group" id="customFormGroup" style="display: none;">
                <label for="customForm">Custom Form *</label>
                <input type="text" id="customForm" name="customForm" class="form-control" placeholder="Enter custom form type">
            </div>
            
            <div class="inventory-section">
                <h4><i class="fas fa-boxes"></i> Inventory Management</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($)</label>
                        <input type="number" id="price" name="price" class="form-control" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="quantity">Current Stock</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" min="0" value="0" placeholder="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date" class="form-control" min="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="form-group">
                        <label for="low_stock_threshold">Low Stock Alert (when quantity falls below)</label>
                        <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="form-control" min="0" value="10" placeholder="10">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" id="image" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                <small class="form-text text-muted">Upload an image of the product (optional)</small>
                <div id="imagePreview" style="margin-top: 10px; display: none;">
                    <img id="previewImg" src="" alt="Preview" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>`;
    } else if (category === 'cosmetics') {
        // For cosmetics products
        formFields = `
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="company">Company *</label>
                    <input type="text" id="company" name="company" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="class">Class *</label>
                <select id="class" name="class" class="form-control" required onchange="toggleCosmeticsClass(this)">
                    <option value="">Select Class</option>
                    <option value="Cleansers">Cleansers</option>
                    <option value="Moisturizers">Moisturizers</option>
                    <option value="Serums">Serums</option>
                    <option value="Face masks">Face masks</option>
                    <option value="Shampoos">Shampoos</option>
                    <option value="Conditioners">Conditioners</option>
                    <option value="Dyes">Dyes</option>
                    <option value="Hair masks">Hair masks</option>
                    <option value="Sunscreen">Sunscreen</option>
                    <option value="Lip balm">Lip balm</option>
                    <option value="Whiteners">Whiteners</option>
                    <option value="Custom">Custom</option>
                </select>
            </div>
            
            <div class="form-group" id="customClassGroup" style="display: none;">
                <label for="customClass">Custom Class *</label>
                <input type="text" id="customClass" name="customClass" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Additional details about the product (optional)"></textarea>
            </div>
            
            <div class="inventory-section">
                <h4><i class="fas fa-boxes"></i> Inventory Management</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($)</label>
                        <input type="number" id="price" name="price" class="form-control" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="quantity">Current Stock</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" min="0" value="0" placeholder="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date" class="form-control" min="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="form-group">
                        <label for="low_stock_threshold">Low Stock Alert (when quantity falls below)</label>
                        <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="form-control" min="0" value="10" placeholder="10">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" id="image" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                <small class="form-text text-muted">Upload an image of the cosmetic product (optional)</small>
                <div id="imagePreview" style="margin-top: 10px; display: none;">
                    <img id="previewImg" src="" alt="Preview" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>`;
    } else {
        // For dental products
        formFields = `
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="company">Company *</label>
                    <input type="text" id="company" name="company" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="class">Class *</label>
                <select id="class" name="class" class="form-control" required onchange="toggleDentalSubcategory(this)">
                    <option value="">Select Class</option>
                    <option value="Toothbrush">Toothbrush</option>
                    <option value="Toothpick">Toothpick</option>
                    <option value="Mouth brush">Mouth brush</option>
                    <option value="Interdental brush">Interdental brush</option>
                    <option value="Interdental angled brush">Interdental angled brush</option>
                    <option value="Toothpaste">Toothpaste</option>
                    <option value="Mouthwash">Mouthwash</option>
                    <option value="Oral Spray">Oral Spray</option>
                    <option value="Oral Gel">Oral Gel</option>
                    <option value="Floss">Floss</option>
                    <option value="Dental tape">Dental tape</option>
                    <option value="Dental wax">Dental wax</option>
                    <option value="Dental glue">Dental glue</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group" id="customClassGroup" style="display: none;">
                <label for="customClass">Custom Class *</label>
                <input type="text" id="customClass" name="customClass" class="form-control">
            </div>
            
            <div class="form-group" id="subcategoryGroup" style="display: none;">
                <label for="subcategory" id="subcategoryLabel">Subcategory *</label>
                <select id="subcategory" name="subcategory" class="form-control">
                    <option value="">Select Subcategory</option>
                </select>
            </div>
            
            <div class="form-group" id="customSizeGroup" style="display: none;">
                <label for="customSize">Custom Size *</label>
                <input type="text" id="customSize" name="customSize" class="form-control" placeholder="Enter custom size">
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="inventory-section">
                <h4><i class="fas fa-boxes"></i> Inventory Management</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($)</label>
                        <input type="number" id="price" name="price" class="form-control" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="quantity">Current Stock</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" min="0" value="0" placeholder="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date" class="form-control" min="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="form-group">
                        <label for="low_stock_threshold">Low Stock Alert (when quantity falls below)</label>
                        <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="form-control" min="0" value="10" placeholder="10">
                    </div>
                </div>
            </div>`;
        
        // Add dental-specific fields
        if (category === 'dental') {
            formFields += `
            <div class="form-row">
                <div class="form-group">
                    <label for="age_group">Age Group *</label>
                    <select id="age_group" name="age_group" class="form-control" required>
                        <option value="both">Kids & Adults</option>
                        <option value="kids">Kids Only</option>
                        <option value="adults">Adults Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="contains_fluoride">
                        <input type="checkbox" id="contains_fluoride" name="contains_fluoride" style="margin-right: 8px;">
                        Contains Fluoride
                    </label>
                </div>
            </div>`;
        }
    }
    
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close" onclick="closeProductForm()">&times;</span>
            <div class="product-form">
                <h2><i class="${formIcon}"></i> ${formTitle}</h2>
                
                <form id="newProductForm" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="barcode" value="${barcode}">
                    <input type="hidden" name="category" value="${category}">
                    
                    ${formFields}
                    
                    <div class="form-group">
                        <label><strong>Barcode:</strong> ${barcode}</label>
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--gray-200);">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Product
                        </button>
                        <button type="button" onclick="closeProductForm()" class="btn btn-secondary" style="margin-left: 1rem;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
            
            <style>
                .ingredients-section {
                    margin: 1rem 0;
                    padding: 1rem;
                    background: #f8f9fa;
                    border-radius: 8px;
                    border: 1px solid #e9ecef;
                }
                
                .ingredients-section h4 {
                    color: #007bff;
                    margin-bottom: 1rem;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }
                
                .ingredient-group {
                    background: white;
                    border: 1px solid #dee2e6;
                    border-radius: 6px;
                    padding: 1rem;
                    margin-bottom: 1rem;
                }
                
                .ingredient-group:last-child {
                    margin-bottom: 0;
                }
                
                .ingredient-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 1rem;
                    padding-bottom: 0.5rem;
                    border-bottom: 1px solid #e9ecef;
                }
                
                .ingredient-header h5 {
                    margin: 0;
                    color: #495057;
                    font-weight: 600;
                }
                
                .btn-sm {
                    padding: 0.25rem 0.5rem;
                    font-size: 0.875rem;
                }
            </style>
        </div>
    `;
    
    // Add form submit handler
    const form = modal.querySelector('#newProductForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        saveNewProduct(this, category);
    });
    
    return modal;
}

// Save new product
function saveNewProduct(form, category) {
    // Prevent rapid submissions
    if (isCreatingProduct) {
        return;
    }
    
    isCreatingProduct = true;
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<div class="loading"></div> Saving...';
    submitBtn.disabled = true;
    
    // Prepare form data
    const formData = new FormData(form);
    
    // Handle custom form for pharmaceutics
    if (category === 'pharmaceutics') {
        const formSelect = form.querySelector('#form');
        const customFormInput = form.querySelector('#customForm');
        
        if (formSelect && formSelect.value === 'Custom' && customFormInput && customFormInput.value.trim()) {
            // Replace the form value with the custom input value
            formData.set('form', customFormInput.value.trim());
        }
        
        // Handle multiple ingredients for pharmaceutics
        const ingredientsContainer = form.querySelector('#ingredients-container');
        if (ingredientsContainer) {
            const ingredientGroups = ingredientsContainer.children;
            let activeIngredients = [];
            let doses = [];
            
            for (let i = 0; i < ingredientGroups.length; i++) {
                const activeIngredientInput = ingredientGroups[i].querySelector(`input[name="active_ingredient_${i}"]`);
                const doseInput = ingredientGroups[i].querySelector(`input[name="dose_${i}"]`);
                
                if (activeIngredientInput && doseInput && activeIngredientInput.value.trim() && doseInput.value.trim()) {
                    activeIngredients.push(activeIngredientInput.value.trim());
                    doses.push(doseInput.value.trim());
                }
            }
            
            // Join multiple ingredients and doses with " | "
            formData.set('active_ingredient', activeIngredients.join(' | '));
            formData.set('dose', doses.join(' | '));
            
            // Remove individual ingredient fields
            for (let i = 0; i < ingredientGroups.length; i++) {
                formData.delete(`active_ingredient_${i}`);
                formData.delete(`dose_${i}`);
            }
        }
    }
    
    // Determine API endpoint based on category
    let apiEndpoint = 'api/add-product.php';
    if (category === 'cosmetics') {
        apiEndpoint = 'api/add-cosmetic.php';
    } else if (category === 'dental') {
        apiEndpoint = 'api/add-dental.php';
    }
    
    // Submit form data
    fetch(apiEndpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        isCreatingProduct = false;
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            showAlert(data.message, 'success');
            closeProductForm();
            // Redirect to the appropriate products page
            setTimeout(() => {
                window.location.href = `products.php?category=${category}`;
            }, 1500);
        } else {
            showAlert(data.message || 'Error saving product.', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        isCreatingProduct = false;
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        showAlert('Network error. Please try again.', 'danger');
    });
}

// Process barcode from uploaded image
function processBarcodeFromImage() {
    const cameraInput = document.getElementById('camera-input');
    const galleryInput = document.getElementById('gallery-input');
    
    let fileInput = null;
    let file = null;
    
    // Check which input has a file
    if (cameraInput && cameraInput.files && cameraInput.files[0]) {
        fileInput = cameraInput;
        file = cameraInput.files[0];
    } else if (galleryInput && galleryInput.files && galleryInput.files[0]) {
        fileInput = galleryInput;
        file = galleryInput.files[0];
    }
    
    if (!file) {
        showAlert('Please select an image file', 'warning');
        return;
    }

    // Show loading
    const uploadBtn = document.querySelector('.image-upload-btn');
    const originalText = uploadBtn.innerHTML;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning...';
    uploadBtn.disabled = true;

    // Create canvas to process image
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const img = new Image();

    img.onload = function() {
        // Set canvas size to image size
        canvas.width = img.width;
        canvas.height = img.height;
        
        // Draw image on canvas
        ctx.drawImage(img, 0, 0);

        // Use Quagga to decode barcode from canvas
        Quagga.decodeSingle({
            src: canvas.toDataURL(),
            numOfWorkers: 0,
            inputStream: {
                size: 800
            },
            locator: {
                patchSize: "medium",
                halfSample: true
            },
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
            }
        }, function(result) {
            uploadBtn.innerHTML = originalText;
            uploadBtn.disabled = false;
            
            if (result && result.codeResult) {
                const barcode = result.codeResult.code;
                showAlert(`Barcode detected: ${barcode}`, 'success');
                
                // Clear the file inputs
                if (cameraInput) cameraInput.value = '';
                if (galleryInput) galleryInput.value = '';
                
                // Process the detected barcode
                processBarcode(barcode);
            } else {
                showAlert('No barcode found in the image. Please try with a clearer image or different angle.', 'warning');
            }
        });
    };

    img.onerror = function() {
        uploadBtn.innerHTML = originalText;
        uploadBtn.disabled = false;
        showAlert('Error loading image. Please try a different image.', 'danger');
    };

    // Create a blob URL for the image
    const url = URL.createObjectURL(file);
    img.src = url;
}

// Close product form modal
function closeProductForm() {
    const modal = document.getElementById('productFormModal');
    if (modal) {
        modal.remove();
    }
    document.body.style.overflow = 'auto';
}

// Handle manual barcode input
document.addEventListener('DOMContentLoaded', function() {
    const manualInput = document.getElementById('manualBarcode');
    if (manualInput) {
        manualInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                processBarcode();
            }
        });
    }
    
    // Handle file inputs for image scanning
    const cameraInput = document.getElementById('camera-input');
    const galleryInput = document.getElementById('gallery-input');
    
    if (cameraInput) {
        cameraInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Clear the other input
                if (galleryInput) galleryInput.value = '';
            }
        });
    }
    
    if (galleryInput) {
        galleryInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Clear the other input
                if (cameraInput) cameraInput.value = '';
            }
        });
    }
});

// Toggle custom form input visibility
function toggleCustomForm(selectElement) {
    const customFormGroup = document.getElementById('customFormGroup');
    const customFormInput = document.getElementById('customForm');
    
    if (selectElement.value === 'Custom') {
        customFormGroup.style.display = 'block';
        customFormInput.required = true;
    } else {
        customFormGroup.style.display = 'none';
        customFormInput.required = false;
        customFormInput.value = '';
    }
}

// Add ingredient function
function addIngredient() {
    const container = document.getElementById('ingredients-container');
    const currentCount = container.children.length;
    
    if (currentCount >= 5) {
        showAlert('Maximum 5 ingredients allowed', 'warning');
        return;
    }
    
    const ingredientGroup = document.createElement('div');
    ingredientGroup.className = 'ingredient-group';
    ingredientGroup.setAttribute('data-index', currentCount);
    
    ingredientGroup.innerHTML = `
        <div class="ingredient-header">
            <h5>Ingredient ${currentCount + 1}</h5>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeIngredient(${currentCount})" style="margin-left: auto;">
                <i class="fas fa-times"></i> Remove
            </button>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="active_ingredient_${currentCount}">Active Ingredient *</label>
                <input type="text" id="active_ingredient_${currentCount}" name="active_ingredient_${currentCount}" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="dose_${currentCount}">Dose *</label>
                <input type="text" id="dose_${currentCount}" name="dose_${currentCount}" class="form-control" placeholder="e.g., 500mg" required>
            </div>
        </div>
    `;
    
    container.appendChild(ingredientGroup);
}

// Remove ingredient function
function removeIngredient(index) {
    const container = document.getElementById('ingredients-container');
    const ingredientGroup = container.querySelector(`[data-index="${index}"]`);
    
    if (ingredientGroup && container.children.length > 1) {
        ingredientGroup.remove();
        
        // Update indices and labels for remaining ingredients
        Array.from(container.children).forEach((group, newIndex) => {
            group.setAttribute('data-index', newIndex);
            const header = group.querySelector('h5');
            if (header) {
                header.textContent = `Ingredient ${newIndex + 1}`;
            }
            
            // Update input names and IDs
            const inputs = group.querySelectorAll('input');
            inputs.forEach(input => {
                const baseName = input.name.replace(/_\d+$/, '');
                const baseId = input.id.replace(/_\d+$/, '');
                input.name = `${baseName}_${newIndex}`;
                input.id = `${baseId}_${newIndex}`;
            });
            
            // Update labels
            const labels = group.querySelectorAll('label');
            labels.forEach(label => {
                const baseFor = label.getAttribute('for').replace(/_\d+$/, '');
                label.setAttribute('for', `${baseFor}_${newIndex}`);
            });
            
            // Update remove button
            const removeBtn = group.querySelector('button[onclick]');
            if (removeBtn) {
                removeBtn.setAttribute('onclick', `removeIngredient(${newIndex})`);
            }
        });
    } else if (container.children.length === 1) {
        showAlert('At least one ingredient is required', 'warning');
    }
}

// Utility function to show alerts (if not already defined in main.js)
if (typeof showAlert === 'undefined') {
    function showAlert(message, type = 'info') {
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'}"></i>
            ${message}
            <button type="button" class="close" onclick="this.parentElement.remove()">
                <span>&times;</span>
            </button>
        `;
        
        // Add to page
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(alert, container.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
}

// Image preview function (removed)

// Image preview function for cosmetics
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (preview && previewImg) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        const preview = document.getElementById('imagePreview');
        if (preview) {
            preview.style.display = 'none';
        }
    }
}

// Toggle dental subcategory based on class selection
function toggleDentalSubcategory(selectElement) {
    const value = selectElement.value;
    const subcategoryGroup = document.getElementById('subcategoryGroup');
    const customClassGroup = document.getElementById('customClassGroup');
    const customSizeGroup = document.getElementById('customSizeGroup');
    const subcategorySelect = document.getElementById('subcategory');
    const subcategoryLabel = document.getElementById('subcategoryLabel');
    
    // Hide all conditional groups initially
    subcategoryGroup.style.display = 'none';
    customClassGroup.style.display = 'none';
    customSizeGroup.style.display = 'none';
    
    // Clear subcategory options
    subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
    
    if (value === 'Toothbrush') {
        // Show subcategory for toothbrush
        subcategoryGroup.style.display = 'block';
        subcategoryLabel.textContent = 'Toothbrush Type *';
        
        const options = [
            'Hard',
            'Medium',
            'Soft',
            'Extrasoft',
            'Orthodontics',
            'Interspace',
            'Denture care'
        ];
        
        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option;
            optionElement.textContent = option;
            subcategorySelect.appendChild(optionElement);
        });
        
    } else if (value === 'Interdental brush' || value === 'Interdental angled brush') {
        // Show custom size field for interdental brushes
        customSizeGroup.style.display = 'block';
        
    } else if (value === 'Other') {
        // Show custom class field for "Other"
        customClassGroup.style.display = 'block';
        document.getElementById('customClass').required = true;
    } else {
        // Remove required attribute from custom class
        document.getElementById('customClass').required = false;
    }
}

// Toggle cosmetics class based on selection
function toggleCosmeticsClass(selectElement) {
    const value = selectElement.value;
    const customClassGroup = document.getElementById('customClassGroup');
    
    if (value === 'Custom') {
        // Show custom class field for "Custom"
        customClassGroup.style.display = 'block';
        document.getElementById('customClass').required = true;
    } else {
        // Hide custom class field and remove required attribute
        customClassGroup.style.display = 'none';
        document.getElementById('customClass').required = false;
    }
}
