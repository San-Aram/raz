<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/upload.php';

$database = new Database();
$db = $database->connect();
$cosmetic = new Cosmetic($db);

$id = $_GET['id'] ?? null;
$message = '';
$messageType = '';

if (!$id) {
    header('Location: products.php?category=cosmetics');
    exit;
}

$item = $cosmetic->getById($id);
if (!$item) {
    header('Location: products.php?category=cosmetics');
    exit;
}

if ($_POST) {
    try {
        $data = [
            'barcode' => $_POST['barcode'],
            'name' => $_POST['name'],
            'company' => $_POST['company'],
            'notes' => $_POST['notes'] ?? '',
            'class' => $_POST['class'],
            'price' => $item['price'], // Keep existing price
            'image_url' => $item['image_url'] // Keep existing image by default
        ];

        // Handle custom class - if class is "Custom", use customClass value
        if ($data['class'] === 'Custom') {
            if (empty($_POST['customClass'])) {
                throw new Exception('Custom class is required when "Custom" is selected');
            }
            $data['class'] = trim($_POST['customClass']);
        }

        // Handle custom class - if class is "Custom", use customClass value
        if ($data['class'] === 'Custom') {
            if (empty($_POST['customClass'])) {
                throw new Exception('Custom class is required when "Custom" is selected');
            }
            $data['class'] = trim($_POST['customClass']);
        }

        // Handle image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleImageUpload($_FILES['image']);
            if ($uploadResult['success']) {
                // Delete old image if it exists
                if (!empty($item['image_url']) && file_exists($item['image_url'])) {
                    unlink($item['image_url']);
                }
                $data['image_url'] = 'uploads/' . $uploadResult['filename'];
            } else {
                throw new Exception('Image upload failed: ' . $uploadResult['message']);
            }
        }

        if ($cosmetic->update($id, $data)) {
            $message = "Cosmetic product updated successfully!";
            $messageType = "success";
            // Refresh the product data
            $item = $cosmetic->getById($id);
        } else {
            $message = "Error updating cosmetic product.";
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
    <title>Edit Cosmetic Product - Razology</title>
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
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <h2 class="form-title">
                    <i class="fas fa-palette"></i> Edit Cosmetic Product
                </h2>
                
                <form method="POST" enctype="multipart/form-data" class="medication-form">
                    <div class="form-group">
                        <label for="barcode">Barcode *</label>
                        <input type="text" id="barcode" name="barcode" 
                               class="form-control" value="<?php echo htmlspecialchars($item['barcode']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" 
                               class="form-control" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="company">Company *</label>
                        <input type="text" id="company" name="company" 
                               class="form-control" value="<?php echo htmlspecialchars($item['company']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="class">Class *</label>
                        <select id="class" name="class" class="form-control" required onchange="toggleCosmeticsClass(this)">
                            <option value="">Select Class</option>
                            <option value="Cleansers" <?php echo $item['class'] === 'Cleansers' ? 'selected' : ''; ?>>Cleansers</option>
                            <option value="Moisturizers" <?php echo $item['class'] === 'Moisturizers' ? 'selected' : ''; ?>>Moisturizers</option>
                            <option value="Serums" <?php echo $item['class'] === 'Serums' ? 'selected' : ''; ?>>Serums</option>
                            <option value="Face masks" <?php echo $item['class'] === 'Face masks' ? 'selected' : ''; ?>>Face masks</option>
                            <option value="Shampoos" <?php echo $item['class'] === 'Shampoos' ? 'selected' : ''; ?>>Shampoos</option>
                            <option value="Conditioners" <?php echo $item['class'] === 'Conditioners' ? 'selected' : ''; ?>>Conditioners</option>
                            <option value="Dyes" <?php echo $item['class'] === 'Dyes' ? 'selected' : ''; ?>>Dyes</option>
                            <option value="Hair masks" <?php echo $item['class'] === 'Hair masks' ? 'selected' : ''; ?>>Hair masks</option>
                            <option value="Sunscreen" <?php echo $item['class'] === 'Sunscreen' ? 'selected' : ''; ?>>Sunscreen</option>
                            <option value="Lip balm" <?php echo $item['class'] === 'Lip balm' ? 'selected' : ''; ?>>Lip balm</option>
                            <option value="Whiteners" <?php echo $item['class'] === 'Whiteners' ? 'selected' : ''; ?>>Whiteners</option>
                            <?php 
                            $isCustomClass = !in_array($item['class'], ['Cleansers', 'Moisturizers', 'Serums', 'Face masks', 'Shampoos', 'Conditioners', 'Dyes', 'Hair masks', 'Sunscreen', 'Lip balm', 'Whiteners']);
                            ?>
                            <option value="Custom" <?php echo $isCustomClass ? 'selected' : ''; ?>>Custom</option>
                        </select>
                    </div>

                    <div class="form-group" id="customClassGroup" style="display: <?php echo $isCustomClass ? 'block' : 'none'; ?>;">
                        <label for="customClass">Custom Class *</label>
                        <input type="text" id="customClass" name="customClass" class="form-control" 
                               value="<?php echo $isCustomClass ? htmlspecialchars($item['class']) : ''; ?>" 
                               <?php echo $isCustomClass ? 'required' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" 
                                  class="form-control" rows="4" placeholder="Additional details about the product (optional)"><?php echo htmlspecialchars($item['notes']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <?php if (!empty($item['image_url']) && file_exists($item['image_url'])): ?>
                            <div class="current-image" style="margin-bottom: 10px;">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="Current product image" 
                                     style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                                <p><small>Current image</small></p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*" onchange="previewNewImage(this)">
                        <small class="form-text text-muted">Upload a new image to replace the current one (optional)</small>
                        <div id="imagePreview" style="margin-top: 10px; display: none;">
                            <img id="previewImg" src="" alt="Preview" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                            <p><small>New image preview</small></p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                        <a href="products.php?category=cosmetics" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Cosmetics
                        </a>
                        <a href="cosmetics-detail.php?id=<?php echo $item['id']; ?>" class="btn btn-info">
                            <i class="fas fa-eye"></i> View Details
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
        function previewNewImage(input) {
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
    </script>
</body>
</html>
