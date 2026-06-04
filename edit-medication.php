<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

$database = new Database();
$db = $database->connect();
$medication = new Medication($db);

$id = $_GET['id'] ?? null;
$message = '';
$messageType = '';

if (!$id) {
    header('Location: medications.php');
    exit;
}

$med = $medication->getById($id);
if (!$med) {
    header('Location: medications.php');
    exit;
}

if ($_POST) {
    try {
        $data = [
            'active_ingredient' => $_POST['active_ingredient'],
            'class' => $_POST['class'],
            'mechanism_of_action' => $_POST['mechanism_of_action'],
            'indication' => $_POST['indication'],
            'side_effects' => $_POST['side_effects'],
            'contraindication' => $_POST['contraindication'],
            'pregnancy_safe' => isset($_POST['pregnancy_safe']),
            'lactation_safe' => isset($_POST['lactation_safe']),
            'adult_dosage_1' => $_POST['adult_dosage_1'] ?? '',
            'adult_frequency_1' => $_POST['adult_frequency_1'] ?? '',
            'adult_dosage_2' => $_POST['adult_dosage_2'] ?? '',
            'adult_frequency_2' => $_POST['adult_frequency_2'] ?? '',
            'children_dosage_1' => $_POST['children_dosage_1'] ?? '',
            'children_frequency_1' => $_POST['children_frequency_1'] ?? '',
            'children_dosage_2' => $_POST['children_dosage_2'] ?? '',
            'children_frequency_2' => $_POST['children_frequency_2'] ?? ''
        ];

        if ($medication->update($id, $data)) {
            $message = "Medication updated successfully!";
            $messageType = "success";
            // Refresh the medication data
            $med = $medication->getById($id);
        } else {
            $message = "Error updating medication.";
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
    <title>Edit Medication - Razology</title>
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
                    <i class="fas fa-edit"></i> Edit Medication
                </h2>
                
                <form method="POST" class="medication-form">
                    <div class="form-group">
                        <label for="active_ingredient">Active Ingredient *</label>
                        <input type="text" id="active_ingredient" name="active_ingredient" 
                               class="form-control" value="<?php echo htmlspecialchars($med['active_ingredient']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="class">Class of Medication *</label>
                        <input type="text" id="class" name="class" 
                               class="form-control" value="<?php echo htmlspecialchars($med['class']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="mechanism_of_action">Mechanism of Action *</label>
                        <textarea id="mechanism_of_action" name="mechanism_of_action" 
                                  class="form-control" rows="3" required><?php echo htmlspecialchars($med['mechanism_of_action']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="indication">Indication *</label>
                        <textarea id="indication" name="indication" 
                                  class="form-control" rows="3" required><?php echo htmlspecialchars($med['indication']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="side_effects">Side Effects *</label>
                        <textarea id="side_effects" name="side_effects" 
                                  class="form-control" rows="3" required><?php echo htmlspecialchars($med['side_effects']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="contraindication">Contraindications *</label>
                        <textarea id="contraindication" name="contraindication" 
                                  class="form-control" rows="3" required><?php echo htmlspecialchars($med['contraindication']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="pregnancy_safe" 
                                   <?php echo $med['pregnancy_safe'] ? 'checked' : ''; ?>>
                            Safe during pregnancy
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="lactation_safe" 
                                   <?php echo $med['lactation_safe'] ? 'checked' : ''; ?>>
                            Safe during lactation
                        </label>
                    </div>

                    <!-- Dosage Information -->
                    <div class="dosage-section">
                        <h3><i class="fas fa-pills"></i> Dosage Information</h3>
                        <p>Provide dosage information for adults and children. At least one adult dosage is required.</p>
                        
                        <!-- Adult Dosages -->
                        <div class="dosage-category">
                            <h4><i class="fas fa-user"></i> Adult Dosages</h4>
                            
                            <div class="dosage-pair" id="adult-dosage-1">
                                <div class="dosage-pair-header">
                                    <h5><i class="fas fa-pills"></i> Adult Dosage Option 1 *</h5>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="adult_dosage_1">Dosage *</label>
                                        <input type="text" id="adult_dosage_1" name="adult_dosage_1" class="form-control" 
                                               placeholder="e.g., 500mg" 
                                               value="<?php echo htmlspecialchars($med['adult_dosage_1'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="adult_frequency_1">Frequency *</label>
                                        <input type="text" id="adult_frequency_1" name="adult_frequency_1" class="form-control" 
                                               placeholder="e.g., Twice daily" 
                                               value="<?php echo htmlspecialchars($med['adult_frequency_1'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="dosage-pair" id="adult-dosage-2" <?php echo empty($med['adult_dosage_2']) ? 'style="display: none;"' : ''; ?>>
                                <div class="dosage-pair-header">
                                    <h5><i class="fas fa-pills"></i> Adult Dosage Option 2</h5>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeAdultDosage2()" style="margin-left: auto;">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="adult_dosage_2">Dosage</label>
                                        <input type="text" id="adult_dosage_2" name="adult_dosage_2" class="form-control" 
                                               placeholder="e.g., 1000mg" 
                                               value="<?php echo htmlspecialchars($med['adult_dosage_2'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="adult_frequency_2">Frequency</label>
                                        <input type="text" id="adult_frequency_2" name="adult_frequency_2" class="form-control" 
                                               placeholder="e.g., Once daily" 
                                               value="<?php echo htmlspecialchars($med['adult_frequency_2'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div style="text-align: center; margin-top: 1rem;" id="add-adult-btn-container" <?php echo !empty($med['adult_dosage_2']) ? 'style="display: none;"' : ''; ?>>
                                <button type="button" class="btn btn-info" onclick="showAdultDosage2()">
                                    <i class="fas fa-plus"></i> Add Adult Dosage Option 2
                                </button>
                            </div>
                        </div>
                        
                        <!-- Children Dosages -->
                        <div class="dosage-category">
                            <h4><i class="fas fa-child"></i> Children Dosages</h4>
                            
                            <div class="dosage-pair" id="children-dosage-1" <?php echo empty($med['children_dosage_1']) ? 'style="display: none;"' : ''; ?>>
                                <div class="dosage-pair-header">
                                    <h5><i class="fas fa-pills"></i> Children Dosage Option 1</h5>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeChildrenDosage1()" style="margin-left: auto;">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="children_dosage_1">Dosage</label>
                                        <input type="text" id="children_dosage_1" name="children_dosage_1" class="form-control" 
                                               placeholder="e.g., 250mg" 
                                               value="<?php echo htmlspecialchars($med['children_dosage_1'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="children_frequency_1">Frequency</label>
                                        <input type="text" id="children_frequency_1" name="children_frequency_1" class="form-control" 
                                               placeholder="e.g., Twice daily" 
                                               value="<?php echo htmlspecialchars($med['children_frequency_1'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="dosage-pair" id="children-dosage-2" <?php echo empty($med['children_dosage_2']) ? 'style="display: none;"' : ''; ?>>
                                <div class="dosage-pair-header">
                                    <h5><i class="fas fa-pills"></i> Children Dosage Option 2</h5>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeChildrenDosage2()" style="margin-left: auto;">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="children_dosage_2">Dosage</label>
                                        <input type="text" id="children_dosage_2" name="children_dosage_2" class="form-control" 
                                               placeholder="e.g., 125mg" 
                                               value="<?php echo htmlspecialchars($med['children_dosage_2'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="children_frequency_2">Frequency</label>
                                        <input type="text" id="children_frequency_2" name="children_frequency_2" class="form-control" 
                                               placeholder="e.g., Three times daily" 
                                               value="<?php echo htmlspecialchars($med['children_frequency_2'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div style="text-align: center; margin-top: 1rem;">
                                <button type="button" class="btn btn-info" onclick="showChildrenDosage1()" id="add-children-1-btn" <?php echo !empty($med['children_dosage_1']) ? 'style="display: none;"' : ''; ?>>
                                    <i class="fas fa-plus"></i> Add Children Dosage Option 1
                                </button>
                                <button type="button" class="btn btn-info" onclick="showChildrenDosage2()" id="add-children-2-btn" <?php echo empty($med['children_dosage_1']) || !empty($med['children_dosage_2']) ? 'style="display: none;"' : ''; ?>>
                                    <i class="fas fa-plus"></i> Add Children Dosage Option 2
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Medication
                        </button>
                        <a href="medications.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Medications
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
        // Adult dosage functions
        function showAdultDosage2() {
            document.getElementById('adult-dosage-2').style.display = 'block';
            document.getElementById('add-adult-btn-container').style.display = 'none';
        }
        
        function removeAdultDosage2() {
            document.getElementById('adult-dosage-2').style.display = 'none';
            document.getElementById('add-adult-btn-container').style.display = 'block';
            document.getElementById('adult_dosage_2').value = '';
            document.getElementById('adult_frequency_2').value = '';
        }
        
        // Children dosage functions
        function showChildrenDosage1() {
            document.getElementById('children-dosage-1').style.display = 'block';
            document.getElementById('add-children-1-btn').style.display = 'none';
            document.getElementById('add-children-2-btn').style.display = 'inline-block';
        }
        
        function removeChildrenDosage1() {
            document.getElementById('children-dosage-1').style.display = 'none';
            document.getElementById('add-children-1-btn').style.display = 'inline-block';
            document.getElementById('add-children-2-btn').style.display = 'none';
            document.getElementById('children_dosage_1').value = '';
            document.getElementById('children_frequency_1').value = '';
            // Also hide and clear dosage 2 if it was visible
            removeChildrenDosage2();
        }
        
        function showChildrenDosage2() {
            document.getElementById('children-dosage-2').style.display = 'block';
            document.getElementById('add-children-2-btn').style.display = 'none';
        }
        
        function removeChildrenDosage2() {
            document.getElementById('children-dosage-2').style.display = 'none';
            const dosage1Visible = document.getElementById('children-dosage-1').style.display !== 'none';
            if (dosage1Visible) {
                document.getElementById('add-children-2-btn').style.display = 'inline-block';
            }
            document.getElementById('children_dosage_2').value = '';
            document.getElementById('children_frequency_2').value = '';
        }
    </script>
</body>
</html>
