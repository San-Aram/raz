<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();
$dental = new Dental($db);

$id = $_GET['id'] ?? null;
$message = '';
$messageType = '';

if (!$id) {
    header('Location: products.php?category=dental');
    exit;
}

$item = $dental->getById($id);
if (!$item) {
    header('Location: products.php?category=dental');
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
            'subcategory' => $_POST['subcategory'] ?? '',
            'custom_size' => $_POST['customSize'] ?? '',
            'custom_class' => $_POST['customClass'] ?? '',
            'price' => $item['price'], // Keep existing price
            'age_group' => $_POST['age_group'] ?? 'both',
            'contains_fluoride' => isset($_POST['contains_fluoride'])
        ];

        if ($dental->update($id, $data)) {
            $message = "Dental product updated successfully!";
            $messageType = "success";
            // Refresh the product data
            $item = $dental->getById($id);
        } else {
            $message = "Error updating dental product.";
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
    <title>Edit Dental Product - Razology</title>
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
                    <i class="fas fa-tooth"></i> Edit Dental Product
                </h2>
                
                <form method="POST" class="medication-form">
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
                        <select id="class" name="class" class="form-control" required onchange="toggleDentalSubcategory(this)">
                            <option value="">Select Class</option>
                            <option value="Toothbrush" <?php echo ($item['class'] ?? '') === 'Toothbrush' ? 'selected' : ''; ?>>Toothbrush</option>
                            <option value="Toothpick" <?php echo ($item['class'] ?? '') === 'Toothpick' ? 'selected' : ''; ?>>Toothpick</option>
                            <option value="Mouth brush" <?php echo ($item['class'] ?? '') === 'Mouth brush' ? 'selected' : ''; ?>>Mouth brush</option>
                            <option value="Interdental brush" <?php echo ($item['class'] ?? '') === 'Interdental brush' ? 'selected' : ''; ?>>Interdental brush</option>
                            <option value="Interdental angled brush" <?php echo ($item['class'] ?? '') === 'Interdental angled brush' ? 'selected' : ''; ?>>Interdental angled brush</option>
                            <option value="Toothpaste" <?php echo ($item['class'] ?? '') === 'Toothpaste' ? 'selected' : ''; ?>>Toothpaste</option>
                            <option value="Mouthwash" <?php echo ($item['class'] ?? '') === 'Mouthwash' ? 'selected' : ''; ?>>Mouthwash</option>
                            <option value="Oral Spray" <?php echo ($item['class'] ?? '') === 'Oral Spray' ? 'selected' : ''; ?>>Oral Spray</option>
                            <option value="Oral Gel" <?php echo ($item['class'] ?? '') === 'Oral Gel' ? 'selected' : ''; ?>>Oral Gel</option>
                            <option value="Floss" <?php echo ($item['class'] ?? '') === 'Floss' ? 'selected' : ''; ?>>Floss</option>
                            <option value="Dental tape" <?php echo ($item['class'] ?? '') === 'Dental tape' ? 'selected' : ''; ?>>Dental tape</option>
                            <option value="Dental wax" <?php echo ($item['class'] ?? '') === 'Dental wax' ? 'selected' : ''; ?>>Dental wax</option>
                            <option value="Dental glue" <?php echo ($item['class'] ?? '') === 'Dental glue' ? 'selected' : ''; ?>>Dental glue</option>
                            <option value="Other" <?php echo ($item['class'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="customClassGroup" style="display: <?php echo ($item['class'] ?? '') === 'Other' ? 'block' : 'none'; ?>;">
                        <label for="customClass">Custom Class *</label>
                        <input type="text" id="customClass" name="customClass" class="form-control" value="<?php echo htmlspecialchars($item['custom_class'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group" id="subcategoryGroup" style="display: <?php echo ($item['class'] ?? '') === 'Toothbrush' ? 'block' : 'none'; ?>;">
                        <label for="subcategory" id="subcategoryLabel">Subcategory *</label>
                        <select id="subcategory" name="subcategory" class="form-control">
                            <option value="">Select Subcategory</option>
                            <?php if (($item['class'] ?? '') === 'Toothbrush'): ?>
                                <option value="Hard" <?php echo ($item['subcategory'] ?? '') === 'Hard' ? 'selected' : ''; ?>>Hard</option>
                                <option value="Medium" <?php echo ($item['subcategory'] ?? '') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="Soft" <?php echo ($item['subcategory'] ?? '') === 'Soft' ? 'selected' : ''; ?>>Soft</option>
                                <option value="Extrasoft" <?php echo ($item['subcategory'] ?? '') === 'Extrasoft' ? 'selected' : ''; ?>>Extrasoft</option>
                                <option value="Orthodontics" <?php echo ($item['subcategory'] ?? '') === 'Orthodontics' ? 'selected' : ''; ?>>Orthodontics</option>
                                <option value="Interspace" <?php echo ($item['subcategory'] ?? '') === 'Interspace' ? 'selected' : ''; ?>>Interspace</option>
                                <option value="Denture care" <?php echo ($item['subcategory'] ?? '') === 'Denture care' ? 'selected' : ''; ?>>Denture care</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="customSizeGroup" style="display: <?php echo in_array($item['class'] ?? '', ['Interdental brush', 'Interdental angled brush']) ? 'block' : 'none'; ?>;">
                        <label for="customSize">Custom Size *</label>
                        <input type="text" id="customSize" name="customSize" class="form-control" placeholder="Enter custom size" value="<?php echo htmlspecialchars($item['custom_size'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" 
                                  class="form-control" rows="4"><?php echo htmlspecialchars($item['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="age_group">Age Group *</label>
                            <select id="age_group" name="age_group" class="form-control" required>
                                <option value="both" <?php echo ($item['age_group'] ?? 'both') === 'both' ? 'selected' : ''; ?>>Kids & Adults</option>
                                <option value="kids" <?php echo ($item['age_group'] ?? 'both') === 'kids' ? 'selected' : ''; ?>>Kids Only</option>
                                <option value="adults" <?php echo ($item['age_group'] ?? 'both') === 'adults' ? 'selected' : ''; ?>>Adults Only</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="contains_fluoride" name="contains_fluoride" 
                                       <?php echo ($item['contains_fluoride'] ?? false) ? 'checked' : ''; ?>>
                                <label for="contains_fluoride">Contains Fluoride</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                        <a href="products.php?category=dental" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dental Products
                        </a>
                        <a href="dental-detail.php?id=<?php echo $item['id']; ?>" class="btn btn-info">
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
    </script>
</body>
</html>
