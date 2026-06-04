<?php 
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/language-functions.php';
require_once 'includes/admin-settings-helper.php';
initializeLanguage();

// Check maintenance mode (non-admins will be redirected to maintenance page)
if (isMaintenanceMode() && !isAdmin()) {
    header('Location: maintenance.php');
    exit;
}

$database = new Database();
$db = $database->connect();
$medication = new Medication($db);

// Get filter parameters
$search = $_GET['search'] ?? '';
$pregnancy_filter = $_GET['pregnancy'] ?? '';
$lactation_filter = $_GET['lactation'] ?? '';

$medications = $medication->getAll($search, $pregnancy_filter, $lactation_filter);
?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo getTextDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('medications.title', 'Medications'); ?> - <?php echo getSiteName(); ?></title>
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
                        <i class="fas fa-home"></i> <?php echo t('nav.home', 'Home'); ?>
                    </a>
                    <a href="medications.php" class="nav-link active">
                        <i class="fas fa-capsules"></i> <?php echo t('nav.medications', 'Medications'); ?>
                    </a>
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-box"></i> <?php echo t('nav.products', 'Products'); ?>
                    </a>
                    <a href="statistics.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> <?php echo t('nav.statistics', 'Statistics'); ?>
                    </a>
                    <a href="calculator.php" class="nav-link">
                        <i class="fas fa-calculator"></i> <?php echo t('nav.calculator', 'Calculator'); ?>
                    </a>
                    <a href="add-medication.php" class="nav-link">
                        <i class="fas fa-plus"></i> <?php echo t('nav.add_medication', 'Add Medication'); ?>
                    </a>
                    <div class="nav-language-selector">
                        <select id="languageSelect" class="language-select" onchange="changeLanguage(this.value)">
                            <option value="en">English</option>
                            <option value="ckb">سۆرانی</option>
                            <option value="ar">العربية</option>
                        </select>
                    </div>
                    <a href="logout.php" class="nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i> <?php echo t('nav.logout', 'Logout'); ?>
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="search-filter-section">
                <h2><i class="fas fa-search"></i> <?php echo t('medications.search_title', 'Search Medications'); ?></h2>
                <form method="GET" class="search-form">
                    <div class="form-group">
                        <label for="search"><?php echo t('medications.medication_name', 'Medication Name'); ?></label>
                        <input type="text" id="search" name="search" class="form-control" 
                               placeholder="<?php echo t('medications.search_placeholder', 'Search by medication name...'); ?>" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="pregnancy"><?php echo t('medications.pregnancy_safe', 'Pregnancy Safe'); ?></label>
                        <select id="pregnancy" name="pregnancy" class="form-select">
                            <option value=""><?php echo t('medications.all', 'All'); ?></option>
                            <option value="yes" <?php echo $pregnancy_filter === 'yes' ? 'selected' : ''; ?>><?php echo t('medications.safe', 'Safe'); ?></option>
                            <option value="no" <?php echo $pregnancy_filter === 'no' ? 'selected' : ''; ?>><?php echo t('medications.not_safe', 'Not Safe'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="lactation"><?php echo t('medications.lactation_safe', 'Lactation Safe'); ?></label>
                        <select id="lactation" name="lactation" class="form-select">
                            <option value=""><?php echo t('medications.all', 'All'); ?></option>
                            <option value="yes" <?php echo $lactation_filter === 'yes' ? 'selected' : ''; ?>><?php echo t('medications.safe', 'Safe'); ?></option>
                            <option value="no" <?php echo $lactation_filter === 'no' ? 'selected' : ''; ?>><?php echo t('medications.not_safe', 'Not Safe'); ?></option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> <?php echo t('medications.search_button', 'Search'); ?>
                    </button>
                </form>
            </div>

            <div class="medications-grid">
                <?php foreach ($medications as $med): ?>
                    <div class="medication-card">
                        <div class="medication-content">
                            <h3 class="medication-title"><?php echo htmlspecialchars($med['active_ingredient']); ?></h3>
                            <p class="medication-class"><?php echo htmlspecialchars($med['class']); ?></p>
                            
                            <div class="medication-badges">
                                <?php if ($med['pregnancy_safe']): ?>
                                    <span class="badge badge-success"><?php echo t('medications.pregnancy_safe', 'Pregnancy Safe'); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?php echo t('medications.pregnancy_risk', 'Pregnancy Risk'); ?></span>
                                <?php endif; ?>
                                
                                <?php if ($med['lactation_safe']): ?>
                                    <span class="badge badge-success"><?php echo t('medications.lactation_safe', 'Lactation Safe'); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?php echo t('medications.lactation_risk', 'Lactation Risk'); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="medication-info">
                                <h4><?php echo t('medications.indication', 'Indication'); ?></h4>
                                <p><?php echo htmlspecialchars($med['indication']); ?></p>
                            </div>

                            <div class="medication-info">
                                <h4><?php echo t('medications.dosage', 'Dosage'); ?></h4>
                                <?php 
                                // Check for adult dosages first
                                $hasAdultDosage = !empty($med['adult_dosage_1']) && !empty($med['adult_frequency_1']);
                                $hasChildrenDosage = !empty($med['children_dosage_1']) && !empty($med['children_frequency_1']);
                                
                                if ($hasAdultDosage || $hasChildrenDosage):
                                ?>
                                    <?php if ($hasAdultDosage): ?>
                                        <div class="dosage-option-row">
                                            <div class="option-header">
                                                <strong><i class="fas fa-user"></i> Adult:</strong>
                                            </div>
                                            <div class="dosage-details">
                                                <span class="dosage-label">Dosage:</span>
                                                <span class="dosage-value"><?php echo htmlspecialchars($med['adult_dosage_1']); ?></span>
                                                <span class="frequency-label">Frequency:</span>
                                                <span class="frequency-value"><?php echo htmlspecialchars($med['adult_frequency_1']); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($hasChildrenDosage): ?>
                                        <div class="dosage-option-row">
                                            <div class="option-header">
                                                <strong><i class="fas fa-child"></i> Children:</strong>
                                            </div>
                                            <div class="dosage-details">
                                                <span class="dosage-label">Dosage:</span>
                                                <span class="dosage-value"><?php echo htmlspecialchars($med['children_dosage_1']); ?></span>
                                                <span class="frequency-label">Frequency:</span>
                                                <span class="frequency-value"><?php echo htmlspecialchars($med['children_frequency_1']); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Show indicator if there are more dosage options
                                    $totalAdultOptions = (!empty($med['adult_dosage_1']) ? 1 : 0) + (!empty($med['adult_dosage_2']) ? 1 : 0);
                                    $totalChildrenOptions = (!empty($med['children_dosage_1']) ? 1 : 0) + (!empty($med['children_dosage_2']) ? 1 : 0);
                                    $totalExtra = ($totalAdultOptions > 1 ? ($totalAdultOptions - 1) : 0) + ($totalChildrenOptions > 1 ? ($totalChildrenOptions - 1) : 0);
                                    
                                    if ($totalExtra > 0):
                                    ?>
                                        <p class="more-options"><small><i class="fas fa-info-circle"></i> +<?php echo $totalExtra; ?> <?php echo t('medications.more_dosage_options', 'more dosage option(s) available'); ?></small></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p><?php echo t('medications.no_dosage', 'No dosage information available'); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="medication-info">
                                <h4><?php echo t('medications.mechanism_of_action', 'Mechanism of Action'); ?></h4>
                                <p><?php echo htmlspecialchars($med['mechanism_of_action']); ?></p>
                            </div>

                            <div class="medication-info">
                                <h4><?php echo t('medications.side_effects', 'Side Effects'); ?></h4>
                                <p><?php echo htmlspecialchars($med['side_effects']); ?></p>
                            </div>

                            <div class="medication-info">
                                <h4><?php echo t('medications.contraindications', 'Contraindications'); ?></h4>
                                <p><?php echo htmlspecialchars($med['contraindication']); ?></p>
                            </div>

                            <div class="medication-actions">
                                <a href="medication-detail.php?id=<?php echo $med['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> <?php echo t('medications.view_details', 'View Details'); ?>
                                </a>
                                
                                <a href="edit-medication.php?id=<?php echo $med['id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> <?php echo t('medications.edit', 'Edit'); ?>
                                </a>
                                <button onclick="deleteMedication(<?php echo $med['id']; ?>)" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> <?php echo t('medications.delete', 'Delete'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($medications)): ?>
                <div class="alert alert-info" style="text-align: center; margin: 3rem 0;">
                    <i class="fas fa-info-circle"></i>
                    <?php echo t('medications.no_found', 'No medications found matching your criteria. Try adjusting your search filters.'); ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script>
        // Set current language in selector
        document.addEventListener('DOMContentLoaded', function() {
            const currentLang = '<?php echo getCurrentLanguage(); ?>';
            const selector = document.getElementById('languageSelect');
            if (selector) {
                selector.value = currentLang;
            }
        });

        function changeLanguage(lang) {
            window.location.href = window.location.pathname + '?lang=' + lang;
        }

        function deleteMedication(id) {
            if (confirm('<?php echo t('medications.delete_confirm', 'Are you sure you want to delete this medication? This action cannot be undone.'); ?>')) {
                fetch('api/delete-medication.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({id: id})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        // Reload page after short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showAlert(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('<?php echo t('medications.delete_error', 'An error occurred while deleting the medication.'); ?>', 'danger');
                });
            }
        }
    </script>
    <script src="js/fda-lookup.js"></script>
</body>
</html>
