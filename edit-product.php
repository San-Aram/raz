<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/upload.php';

$database = new Database();
$db = $database->connect();
$product = new Product($db);

$id = $_GET['id'] ?? null;
$message = '';
$messageType = '';

if (!$id) {
    header('Location: products.php');
    exit;
}

$prod = $product->getById($id);
if (!$prod) {
    header('Location: products.php');
    exit;
}

if ($_POST) {
    try {
        // Collect multiple active ingredients and doses
        $activeIngredients = [];
        $doses = [];
        
        // Loop through all possible ingredient/dose pairs
        $index = 1;
        while (isset($_POST["active_ingredient_$index"]) || isset($_POST["dose_$index"])) {
            $ingredient = trim($_POST["active_ingredient_$index"] ?? '');
            $dose = trim($_POST["dose_$index"] ?? '');
            
            if (!empty($ingredient) && !empty($dose)) {
                $activeIngredients[] = $ingredient;
                $doses[] = $dose;
            }
            $index++;
        }
        
        // Validate that we have at least one ingredient/dose pair
        if (empty($activeIngredients)) {
            throw new Exception('At least one active ingredient with dose is required');
        }
        
        // Handle custom form type
        $formValue = $_POST['form'];
        if ($formValue === 'Custom' && !empty($_POST['customForm'])) {
            $formValue = trim($_POST['customForm']);
        }
        
        $data = [
            'barcode' => $_POST['barcode'],
            'product_name' => $_POST['product_name'],
            'company' => $_POST['company'],
            'active_ingredient' => implode(' | ', $activeIngredients),
            'dose' => implode(' | ', $doses),
            'form' => $formValue,
            'price' => 0, // Default price to 0
            'image_url' => $prod['image_url'] // Keep existing image by default
        ];
        
        // Handle image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleImageUpload($_FILES['image']);
            if ($uploadResult['success']) {
                // Delete old image if it exists
                if (!empty($prod['image_url']) && file_exists($prod['image_url'])) {
                    unlink($prod['image_url']);
                }
                $data['image_url'] = 'uploads/' . $uploadResult['filename'];
            } else {
                $message = 'Product updated but image upload failed: ' . $uploadResult['message'];
                $messageType = "warning";
            }
        }
        
        // Validate required fields
        $requiredFields = ['barcode', 'product_name', 'company', 'active_ingredient', 'dose', 'form'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
            }
        }
        
        // Check if barcode already exists for another product
        $existingProduct = $product->getByBarcode($data['barcode']);
        if ($existingProduct && $existingProduct['id'] != $id) {
            throw new Exception('A product with this barcode already exists');
        }
        
        if ($product->update($id, $data)) {
            if (empty($message)) {
                $message = "Product updated successfully!";
                $messageType = "success";
            }
            // Refresh the product data
            $prod = $product->getById($id);
        } else {
            $message = "Error updating product.";
            $messageType = "danger";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Razology</title>
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
                        <i class="fas fa-home"></i> Home
                    </a>
                    <a href="medications.php" class="nav-link">
                        <i class="fas fa-capsules"></i> Medications
                    </a>
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-box"></i> Products
                    </a>
                    <a href="calculator.php" class="nav-link">
                        <i class="fas fa-calculator"></i> Calculator
                    </a>
                    <a href="add-medication.php" class="nav-link">
                        <i class="fas fa-plus"></i> Add Medication
                    </a>
                    <a href="logout.php" class="nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <h2 class="form-title">
                    <i class="fas fa-edit"></i> Edit Product
                </h2>
                
                <form method="POST" enctype="multipart/form-data" class="product-form" novalidate>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="barcode">Barcode *</label>
                            <input type="text" id="barcode" name="barcode" 
                                   class="form-control" value="<?php echo htmlspecialchars($prod['barcode']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="product_name">Product Name *</label>
                            <input type="text" id="product_name" name="product_name" 
                                   class="form-control" value="<?php echo htmlspecialchars($prod['product_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="company">Company *</label>
                            <input type="text" id="company" name="company" 
                                   class="form-control" value="<?php echo htmlspecialchars($prod['company']); ?>" required>
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
                            <?php 
                            // Parse existing active ingredients and doses
                            $activeIngredients = explode(' | ', $prod['active_ingredient']);
                            $doses = explode(' | ', $prod['dose']);
                            
                            // Ensure we have at least one ingredient
                            if (empty($activeIngredients[0])) {
                                $activeIngredients = [''];
                                $doses = [''];
                            }
                            
                            // Pad doses array if it's shorter than ingredients
                            while (count($doses) < count($activeIngredients)) {
                                $doses[] = '';
                            }
                            
                            foreach ($activeIngredients as $index => $ingredient): 
                                $displayIndex = $index + 1;
                            ?>
                                <div class="ingredient-row" data-index="<?php echo $displayIndex; ?>">
                                    <div class="form-row">
                                        <div class="form-group" style="flex: 2;">
                                            <label for="active_ingredient_<?php echo $displayIndex; ?>">Active Ingredient <?php echo $displayIndex; ?> *</label>
                                            <input type="text" id="active_ingredient_<?php echo $displayIndex; ?>" name="active_ingredient_<?php echo $displayIndex; ?>" 
                                                   class="form-control" value="<?php echo htmlspecialchars(trim($ingredient)); ?>">
                                        </div>
                                        <div class="form-group" style="flex: 1;">
                                            <label for="dose_<?php echo $displayIndex; ?>">Dose <?php echo $displayIndex; ?> *</label>
                                            <input type="text" id="dose_<?php echo $displayIndex; ?>" name="dose_<?php echo $displayIndex; ?>" 
                                                   class="form-control" placeholder="e.g., 500mg" 
                                                   value="<?php echo htmlspecialchars(trim($doses[$index] ?? '')); ?>">
                                        </div>
                                        <div class="form-group" style="flex: 0 0 auto; display: flex; align-items: end;">
                                            <button type="button" onclick="removeActiveIngredient(<?php echo $displayIndex; ?>)" 
                                                    class="btn btn-sm btn-danger" style="margin-bottom: 0; <?php echo count($activeIngredients) <= 1 ? 'display: none;' : ''; ?>" 
                                                    id="remove_<?php echo $displayIndex; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                        <div class="form-group">
                            <label for="form">Form *</label>
                            <select id="form" name="form" class="form-select" onchange="handleFormChange(this)">
                                <option value="">Select form</option>
                                <option value="Tablet" <?php echo $prod['form'] === 'Tablet' ? 'selected' : ''; ?>>Tablet</option>
                                <option value="Capsule" <?php echo $prod['form'] === 'Capsule' ? 'selected' : ''; ?>>Capsule</option>
                                <option value="Syrup" <?php echo $prod['form'] === 'Syrup' ? 'selected' : ''; ?>>Syrup</option>
                                <option value="Injection" <?php echo $prod['form'] === 'Injection' ? 'selected' : ''; ?>>Injection</option>
                                <option value="Cream" <?php echo $prod['form'] === 'Cream' ? 'selected' : ''; ?>>Cream</option>
                                <option value="Ointment" <?php echo $prod['form'] === 'Ointment' ? 'selected' : ''; ?>>Ointment</option>
                                <option value="Drops" <?php echo $prod['form'] === 'Drops' ? 'selected' : ''; ?>>Drops</option>
                                <option value="Inhaler" <?php echo $prod['form'] === 'Inhaler' ? 'selected' : ''; ?>>Inhaler</option>
                                <option value="Patch" <?php echo $prod['form'] === 'Patch' ? 'selected' : ''; ?>>Patch</option>
                                <option value="Suppository" <?php echo $prod['form'] === 'Suppository' ? 'selected' : ''; ?>>Suppository</option>
                                <?php 
                                // Check if current form is not in the predefined list
                                $predefinedForms = ['Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream', 'Ointment', 'Drops', 'Inhaler', 'Patch', 'Suppository'];
                                $isCustomForm = !in_array($prod['form'], $predefinedForms) && !empty($prod['form']);
                                ?>
                                <option value="Custom" <?php echo $isCustomForm ? 'selected' : ''; ?>>Custom</option>
                            </select>
                            <div id="customFormGroup" class="form-group" style="display: <?php echo $isCustomForm ? 'block' : 'none'; ?>; margin-top: 1rem;">
                                <label for="customForm">Custom Form *</label>
                                <input type="text" id="customForm" name="customForm" class="form-control" 
                                       placeholder="Enter custom form type" 
                                       value="<?php echo $isCustomForm ? htmlspecialchars($prod['form']) : ''; ?>"
                                       <?php echo $isCustomForm ? 'required' : ''; ?>>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <?php if (!empty($prod['image_url'])): ?>
                            <div class="current-image">
                                <p><strong>Current Image:</strong></p>
                                <img src="<?php echo htmlspecialchars($prod['image_url']); ?>" 
                                     alt="Current product image" 
                                     style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                        <div class="file-upload-area" id="fileUploadArea">
                            <input type="file" id="image" name="image" accept="image/*" class="form-control">
                            <div class="upload-placeholder">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Choose new image or drag and drop here</p>
                                <small>Max file size: 5MB. Supported: JPEG, PNG, GIF, WebP</small>
                            </div>
                        </div>
                        <div id="imagePreview" class="image-preview"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script>
        // Image preview functionality
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '200px';
                    img.style.maxHeight = '200px';
                    img.style.borderRadius = '8px';
                    img.style.marginTop = '10px';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Add event listener for image input
        document.getElementById('image').addEventListener('change', function() {
            previewImage(this);
        });

        // Drag and drop functionality
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('image');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            fileUploadArea.classList.add('drag-over');
        }

        function unhighlight(e) {
            fileUploadArea.classList.remove('drag-over');
        }

        fileUploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                previewImage(fileInput);
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

        // Handle form submission to process custom form input
        document.querySelector('form').addEventListener('submit', function(e) {
            const formSelect = document.getElementById('form');
            const customFormInput = document.getElementById('customForm');
            
            // Custom validation
            if (formSelect.value === 'Custom') {
                if (!customFormInput.value.trim()) {
                    e.preventDefault();
                    alert('Please enter a custom form type.');
                    customFormInput.focus();
                    return false;
                }
            } else if (!formSelect.value) {
                e.preventDefault();
                alert('Please select a form type.');
                formSelect.focus();
                return false;
            }
            
            // Validate active ingredients and doses
            const ingredientRows = document.querySelectorAll('.ingredient-row');
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
                        e.preventDefault();
                        alert(`Please enter Active Ingredient ${index}.`);
                        ingredientInput.focus();
                        return false;
                    } else {
                        e.preventDefault();
                        alert(`Please enter Dose ${index}.`);
                        doseInput.focus();
                        return false;
                    }
                }
            }
            
            if (!hasValidIngredient) {
                e.preventDefault();
                alert('Please enter at least one active ingredient with its dose.');
                document.querySelector('#active_ingredient_1').focus();
                return false;
            }
            
            // Validate other required fields
            const requiredFields = ['barcode', 'product_name', 'company'];
            for (let fieldName of requiredFields) {
                const field = document.getElementById(fieldName);
                if (!field.value.trim()) {
                    e.preventDefault();
                    alert('Please fill in the ' + fieldName.replace('_', ' ') + ' field.');
                    field.focus();
                    return false;
                }
            }
        });

        // Active ingredient management functions
        let ingredientCounter = <?php echo count($activeIngredients); ?>;

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

        // Initialize form state on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set up initial state for custom form if needed
            const formSelect = document.getElementById('form');
            if (formSelect.value === 'Custom') {
                const customFormGroup = document.getElementById('customFormGroup');
                customFormGroup.style.display = 'block';
            }
            
            // Update remove buttons visibility
            updateRemoveButtons();
        });
    </script>
</body>
</html>
