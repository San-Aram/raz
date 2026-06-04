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
    const videoRect = video.getBoundingClientRect();
    
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

// Process barcode from uploaded image
function processBarcodeFromImage() {
    const fileInput = document.getElementById('barcodeImageInput');
    const file = fileInput.files[0];
    
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
                
                // Fill manual input field
                document.getElementById('manualBarcode').value = barcode;
                
                // Process the detected barcode
                processBarcode(barcode);
            } else {
                showAlert('No barcode detected in image. Please try a clearer image or manual entry.', 'warning');
            }
        });
    };

    img.onerror = function() {
        uploadBtn.innerHTML = originalText;
        uploadBtn.disabled = false;
        showAlert('Error loading image. Please try another image.', 'danger');
    };

    // Read file as data URL
    const reader = new FileReader();
    reader.onload = function(e) {
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
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
                    <input type="hidden" name="barcode" value="${barcode}">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_name">Product Name *</label>
                            <input type="text" id="product_name" name="product_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="company">Company *</label>
                            <input type="text" id="company" name="company" class="form-control">
                        </div>
                    </div>
                    
                    <!-- Active Ingredients Section -->
                    <div class="form-section">
                        <div class="section-header">
                            <label><i class="fas fa-flask"></i> Active Ingredients *</label>
                            <button type="button" onclick="addActiveIngredient()" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Add Another
                            </button>
                        </div>
                        <div id="activeIngredientsContainer">
                            <!-- First ingredient row (default) -->
                            <div class="ingredient-row" data-index="1">
                                <div class="form-row">
                                    <div class="form-group" style="flex: 2;">
                                        <label for="active_ingredient_1">Active Ingredient 1 *</label>
                                        <input type="text" id="active_ingredient_1" name="active_ingredient_1" class="form-control">
                                    </div>
                                    <div class="form-group" style="flex: 1;">
                                        <label for="dose_1">Dose 1 *</label>
                                        <input type="text" id="dose_1" name="dose_1" class="form-control" placeholder="e.g., 500mg">
                                    </div>
                                    <div class="form-group" style="flex: 0 0 auto; display: flex; align-items: end;">
                                        <button type="button" onclick="removeActiveIngredient(1)" class="btn btn-sm btn-danger" style="margin-bottom: 0; display: none;" id="remove_1">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="form">Form *</label>
                            <select id="form" name="form" class="form-select" onchange="handleFormChange(this)">
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
                            <div id="customFormGroup" class="form-group" style="display: none; margin-top: 1rem;">
                                <label for="customForm">Custom Form *</label>
                                <input type="text" id="customForm" name="customForm" class="form-control" placeholder="Enter custom form type">
                            </div>
                        </div>
                    </div>
                    
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
        
        // Custom validation logic
        const formSelect = this.querySelector('#form');
        const customFormInput = this.querySelector('#customForm');
        
        // Validate all required fields
        const requiredFields = [
            { id: 'product_name', name: 'Product Name' },
            { id: 'company', name: 'Company' }
        ];
        
        for (let field of requiredFields) {
            const input = this.querySelector('#' + field.id);
            if (!input.value.trim()) {
                showAlert(`Please enter ${field.name}.`, 'warning');
                input.focus();
                return false;
            }
        }
        
        // Validate active ingredients and doses
        const ingredientRows = this.querySelectorAll('.ingredient-row');
        let hasValidIngredient = false;
        
        for (let row of ingredientRows) {
            const index = row.dataset.index;
            const ingredientInput = row.querySelector(`#active_ingredient_${index}`);
            const doseInput = row.querySelector(`#dose_${index}`);
            
            if (ingredientInput.value.trim() && doseInput.value.trim()) {
                hasValidIngredient = true;
            } else if (ingredientInput.value.trim() || doseInput.value.trim()) {
                // If one field is filled but not the other
                if (!ingredientInput.value.trim()) {
                    showAlert(`Please enter Active Ingredient ${index}.`, 'warning');
                    ingredientInput.focus();
                    return false;
                } else {
                    showAlert(`Please enter Dose ${index}.`, 'warning');
                    doseInput.focus();
                    return false;
                }
            }
        }
        
        if (!hasValidIngredient) {
            showAlert('Please enter at least one active ingredient with its dose.', 'warning');
            this.querySelector('#active_ingredient_1').focus();
            return false;
        }
        
        // Check form validation
        if (formSelect.value === 'Custom') {
            if (!customFormInput.value.trim()) {
                showAlert('Please enter a custom form type.', 'warning');
                customFormInput.focus();
                return false;
            }
        } else if (!formSelect.value) {
            showAlert('Please select a form type.', 'warning');
            formSelect.focus();
            return false;
        }
        
        saveNewProduct(this);
    });
    
    // Setup drag and drop after the modal is added to DOM
    setTimeout(() => {
        setupDragAndDrop();
    }, 100);
    
    return modal;
}

// Save new product
function saveNewProduct(form) {
    // Prevent rapid submissions
    if (isCreatingProduct) {
        return;
    }
    
    const formData = new FormData(form);
    
    // Handle custom form type
    const formSelect = form.querySelector('#form');
    const customFormInput = form.querySelector('#customForm');
    
    if (formSelect.value === 'Custom' && customFormInput.value.trim()) {
        // Replace the form value with the custom input
        formData.set('form', customFormInput.value.trim());
    }
    
    // Collect multiple active ingredients and doses
    const ingredientRows = form.querySelectorAll('.ingredient-row');
    let activeIngredients = [];
    let doses = [];
    
    for (let row of ingredientRows) {
        const index = row.dataset.index;
        const ingredientInput = row.querySelector(`#active_ingredient_${index}`);
        const doseInput = row.querySelector(`#dose_${index}`);
        
        if (ingredientInput.value.trim() && doseInput.value.trim()) {
            activeIngredients.push(ingredientInput.value.trim());
            doses.push(doseInput.value.trim());
        }
    }
    
    // Add combined active ingredients and doses to form data
    if (activeIngredients.length > 0) {
        formData.set('active_ingredient', activeIngredients.join(' | '));
        formData.set('dose', doses.join(' | '));
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    isCreatingProduct = true;
    submitBtn.innerHTML = '<div class="loading"></div> Saving...';
    submitBtn.disabled = true;
    
    fetch('api/add-product.php', {
        method: 'POST',
        body: formData // FormData automatically handles file uploads
    })
    .then(response => response.json())
    .then(data => {
        isCreatingProduct = false;
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            closeProductForm();
            showAlert('Product added successfully!', 'success');
            
            // Redirect to product page after short delay
            setTimeout(() => {
                window.location.href = `product-detail.php?id=${data.product_id}`;
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
}

// Handle form dropdown change (show/hide custom form input)
function handleFormChange(selectElement) {
    const customFormGroup = document.getElementById('customFormGroup');
    const customFormInput = document.getElementById('customForm');
    
    if (selectElement.value === 'Custom') {
        customFormGroup.style.display = 'block';
        customFormInput.focus();
    } else {
        customFormGroup.style.display = 'none';
        customFormInput.value = '';
    }
}

// Handle manual barcode input
document.addEventListener('DOMContentLoaded', function() {
    const manualBarcodeInput = document.getElementById('manualBarcode');
    if (manualBarcodeInput) {
        manualBarcodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                processBarcode();
            }
        });
    }
});

// Utility function to show alerts (if not already defined in main.js)
if (typeof showAlert === 'undefined') {
    function showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '20px';
        alertDiv.style.right = '20px';
        alertDiv.style.zIndex = '9999';
        alertDiv.style.maxWidth = '400px';
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

// Image preview function
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const fileLabel = input.parentElement.querySelector('.file-input-label');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showAlert('File too large. Maximum size is 5MB.', 'danger');
            input.value = '';
            preview.style.display = 'none';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showAlert('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.', 'danger');
            input.value = '';
            preview.style.display = 'none';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
            
            // Update file label to show selected file
            fileLabel.innerHTML = `
                <i class="fas fa-check-circle" style="color: var(--secondary-color);"></i>
                <span>${file.name}</span>
                <small style="color: var(--gray-500); margin-top: 0.5rem; display: block;">Click to change image</small>
            `;
        };
        
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        // Reset file label
        fileLabel.innerHTML = `
            <i class="fas fa-cloud-upload-alt"></i>
            <span>Choose Image or Drag & Drop</span>
            <small style="color: var(--gray-500); margin-top: 0.5rem; display: block;">JPEG, PNG, GIF, WebP - max 5MB</small>
        `;
    }
}

// Add drag and drop functionality when product form is created
function setupDragAndDrop() {
    const fileInput = document.getElementById('image');
    const fileLabel = document.querySelector('.file-input-label');
    
    if (!fileInput || !fileLabel) return;
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileLabel.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        fileLabel.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        fileLabel.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight(e) {
        fileLabel.style.background = 'var(--gray-200)';
        fileLabel.style.borderColor = 'var(--primary-color)';
    }
    
    function unhighlight(e) {
        fileLabel.style.background = 'var(--gray-100)';
        fileLabel.style.borderColor = 'var(--gray-300)';
    }
    
    fileLabel.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
            fileInput.files = files;
            previewImage(fileInput);
        }
    }
}

// Handle manual barcode input and new camera/gallery buttons
document.addEventListener('DOMContentLoaded', function() {
    const manualInput = document.getElementById('manualBarcode');
    if (manualInput) {
        manualInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                processBarcode();
            }
        });
    }

    // Handle camera input
    const cameraInput = document.getElementById('camera-input');
    if (cameraInput) {
        cameraInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                // Set this as the active input for processBarcodeFromImage
                if (!document.getElementById('barcodeImageInput')) {
                    createHiddenInput();
                }
                document.getElementById('barcodeImageInput').files = e.target.files;
                processBarcodeFromImage();
            }
        });
    }

    // Handle gallery input
    const galleryInput = document.getElementById('gallery-input');
    if (galleryInput) {
        galleryInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                // Set this as the active input for processBarcodeFromImage
                if (!document.getElementById('barcodeImageInput')) {
                    createHiddenInput();
                }
                document.getElementById('barcodeImageInput').files = e.target.files;
                processBarcodeFromImage();
            }
        });
    }

    // Create a hidden input for backward compatibility
    function createHiddenInput() {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'file';
        hiddenInput.id = 'barcodeImageInput';
        hiddenInput.accept = 'image/*';
        hiddenInput.style.display = 'none';
        document.body.appendChild(hiddenInput);
    }

    // Initialize hidden input if it doesn't exist
    if (!document.getElementById('barcodeImageInput')) {
        createHiddenInput();
    }
});

// Active ingredient management functions
let ingredientCounter = 1;

function addActiveIngredient() {
    ingredientCounter++;
    const container = document.getElementById('activeIngredientsContainer');
    
    const newRow = document.createElement('div');
    newRow.className = 'ingredient-row';
    newRow.setAttribute('data-index', ingredientCounter);
    
    newRow.innerHTML = `
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label for="active_ingredient_${ingredientCounter}">Active Ingredient ${ingredientCounter} *</label>
                <input type="text" id="active_ingredient_${ingredientCounter}" name="active_ingredient_${ingredientCounter}" class="form-control">
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="dose_${ingredientCounter}">Dose ${ingredientCounter} *</label>
                <input type="text" id="dose_${ingredientCounter}" name="dose_${ingredientCounter}" class="form-control" placeholder="e.g., 500mg">
            </div>
            <div class="form-group" style="flex: 0 0 auto; display: flex; align-items: end;">
                <button type="button" onclick="removeActiveIngredient(${ingredientCounter})" class="btn btn-sm btn-danger" style="margin-bottom: 0;" id="remove_${ingredientCounter}">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(newRow);
    
    // Show remove button for all rows if there's more than one
    updateRemoveButtons();
    
    // Focus on the new ingredient input
    document.getElementById(`active_ingredient_${ingredientCounter}`).focus();
}

function removeActiveIngredient(index) {
    const row = document.querySelector(`.ingredient-row[data-index="${index}"]`);
    if (row) {
        row.remove();
        updateRemoveButtons();
        
        // If no rows left, add a default one
        const container = document.getElementById('activeIngredientsContainer');
        if (container.children.length === 0) {
            addDefaultIngredientRow();
        }
    }
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.ingredient-row');
    rows.forEach((row, index) => {
        const removeBtn = row.querySelector('.btn-danger');
        if (removeBtn) {
            // Show remove button only if there's more than one row
            if (rows.length > 1) {
                removeBtn.style.display = 'block';
            } else {
                removeBtn.style.display = 'none';
            }
        }
    });
}

function addDefaultIngredientRow() {
    ingredientCounter = 1;
    const container = document.getElementById('activeIngredientsContainer');
    
    const defaultRow = document.createElement('div');
    defaultRow.className = 'ingredient-row';
    defaultRow.setAttribute('data-index', '1');
    
    defaultRow.innerHTML = `
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label for="active_ingredient_1">Active Ingredient 1 *</label>
                <input type="text" id="active_ingredient_1" name="active_ingredient_1" class="form-control">
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="dose_1">Dose 1 *</label>
                <input type="text" id="dose_1" name="dose_1" class="form-control" placeholder="e.g., 500mg">
            </div>
            <div class="form-group" style="flex: 0 0 auto; display: flex; align-items: end;">
                <button type="button" onclick="removeActiveIngredient(1)" class="btn btn-sm btn-danger" style="margin-bottom: 0; display: none;" id="remove_1">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(defaultRow);
}

// Handle image preview
function previewImage(input) {
    const previewContainer = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        previewContainer.style.display = 'none';
    }
}
