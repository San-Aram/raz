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
        
        // Process the barcode
        processBarcode(code);
        
        // Stop scanning after successful detection
        stopScanning();
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
        return;
    }
    
    const barcodeValue = barcode || document.getElementById('manualBarcode').value.trim();
    
    if (!barcodeValue) {
        showAlert('Please enter a barcode number.', 'warning');
        return;
    }
    
    isProcessingBarcode = true;
    
    // Show loading
    const processBtn = document.querySelector('.manual-input button');
    const originalText = processBtn.innerHTML;
    processBtn.innerHTML = '<div class="loading"></div> Processing...';
    processBtn.disabled = true;
    
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
    .then(response => response.json())
    .then(data => {
        isProcessingBarcode = false;
        processBtn.innerHTML = originalText;
        processBtn.disabled = false;
        
        if (data.success) {
            if (data.exists) {
                // Product exists, redirect to product page
                closeBarcodeScanner();
                window.location.href = `${data.detail_page}?id=${data.product.id}`;
            } else {
                // Product doesn't exist, open form to create new
                closeBarcodeScanner();
                openNewProductForm(barcodeValue, data.category);
            }
        } else {
            showAlert(data.message || 'Error processing barcode.', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        isProcessingBarcode = false;
        processBtn.innerHTML = originalText;
        processBtn.disabled = false;
        showAlert('Network error. Please try again.', 'danger');
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
            
            <div class="form-row">
                <div class="form-group">
                    <label for="active_ingredient">Active Ingredient *</label>
                    <input type="text" id="active_ingredient" name="active_ingredient" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="dose">Dose *</label>
                    <input type="text" id="dose" name="dose" class="form-control" placeholder="e.g., 500mg" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="form">Form *</label>
                <select id="form" name="form" class="form-control" required>
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
                </select>
            </div>`;
    } else {
        // For cosmetics and dental products
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
                <input type="text" id="class" name="class" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="indication">Indication *</label>
                <textarea id="indication" name="indication" class="form-control" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes *</label>
                <textarea id="notes" name="notes" class="form-control" rows="3" required></textarea>
            </div>`;
    }
    
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close" onclick="closeProductForm()">&times;</span>
            <div class="product-form">
                <h2><i class="${formIcon}"></i> ${formTitle}</h2>
                
                <form id="newProductForm" novalidate>
                    <input type="hidden" name="barcode" value="${barcode}">
                    <input type="hidden" name="category" value="${category}">
                    
                    ${formFields}
                    
                    <div class="form-group">
                        <label>Product Image</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                            <label for="image" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Choose Image or Drag & Drop</span>
                                <small style="color: var(--gray-500); margin-top: 0.5rem; display: block;">JPEG, PNG, GIF, WebP - max 5MB</small>
                            </label>
                        </div>
                        <div class="image-preview-container" id="imagePreview" style="display: none;">
                            <img id="previewImg" class="image-preview" alt="Preview">
                        </div>
                    </div>
                    
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
});

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

// Image preview function
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}
