<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/language-functions.php';
initializeLanguage();

$database = new Database();
$db = $database->connect();
$medication = new Medication($db);

$id = $_GET['id'] ?? null;
$active_ingredient = $_GET['active_ingredient'] ?? null;

$med = null;
if ($id) {
    $med = $medication->getById($id);
} elseif ($active_ingredient) {
    $med = $medication->getByActiveIngredientName($active_ingredient);
}

if (!$med) {
    header('Location: medications.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLang(); ?>" dir="<?php echo getTextDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($med['active_ingredient']); ?> - Razology</title>
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
                        <i class="fas fa-home"></i> <?php echo t('header.home', 'Home'); ?>
                    </a>
                    <a href="medications.php" class="nav-link">
                        <i class="fas fa-capsules"></i> <?php echo t('header.medications', 'Medications'); ?>
                    </a>
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-box"></i> <?php echo t('header.products', 'Products'); ?>
                    </a>
                    <a href="add-medication.php" class="nav-link">
                        <i class="fas fa-plus"></i> <?php echo t('header.addMedication', 'Add Medication'); ?>
                    </a>
                    <div class="nav-language-selector">
                        <select id="languageSelector" class="language-select" onchange="changeLanguage(this.value)">
                            <?php foreach (SUPPORTED_LANGUAGES as $lang): ?>
                                <option value="<?php echo $lang; ?>" <?php echo getCurrentLanguage() === $lang ? 'selected' : ''; ?>>
                                    <?php echo getLanguageDisplayName($lang); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <a href="logout.php" class="nav-link logout-link">
                        <i class="fas fa-sign-out-alt"></i> <?php echo t('header.logout', 'Logout'); ?>
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="medication-detail">
                <div class="detail-header">
                    <a href="medications.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <?php echo t('common.backToMedications', 'Back to Medications'); ?>
                    </a>
                </div>

                <div class="detail-content">
                    <div class="detail-info-section">
                        <h1 class="detail-title"><?php echo htmlspecialchars($med['active_ingredient']); ?></h1>
                        <p class="detail-class"><?php echo htmlspecialchars($med['class']); ?></p>

                        <div class="detail-badges">
                            <?php if ($med['pregnancy_safe']): ?>
                                <span class="badge badge-success"><?php echo t('medication.pregnancySafe', 'Pregnancy Safe'); ?></span>
                            <?php else: ?>
                                <span class="badge badge-danger"><?php echo t('medication.pregnancyRisk', 'Pregnancy Risk'); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($med['lactation_safe']): ?>
                                <span class="badge badge-success"><?php echo t('medication.lactationSafe', 'Lactation Safe'); ?></span>
                            <?php else: ?>
                                <span class="badge badge-danger"><?php echo t('medication.lactationRisk', 'Lactation Risk'); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="detail-quick-info">
                            <div class="quick-info-item">
                                <strong><?php echo t('medication.activeIngredient', 'Active Ingredient'); ?>:</strong> <?php echo htmlspecialchars($med['active_ingredient']); ?>
                            </div>
                            <div class="quick-info-item">
                                <strong><?php echo t('medication.availableDosages', 'Available Dosages'); ?>:</strong> 
                                <?php 
                                if (!empty($med['dosage'])) {
                                    $dosages = explode('|', $med['dosage']);
                                    echo htmlspecialchars(implode(', ', array_filter($dosages)));
                                } else {
                                    echo t('common.notSpecified', 'Not specified');
                                }
                                ?>
                            </div>
                            <div class="quick-info-item">
                                <strong><?php echo t('common.class', 'Class'); ?>:</strong> <?php echo htmlspecialchars($med['class']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-sections">
                    <div class="detail-section">
                        <h3><i class="fas fa-bullseye"></i> <?php echo t('medication.indication', 'Indication'); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($med['indication'])); ?></p>
                    </div>

                    <div class="detail-section">
                        <h3><i class="fas fa-cogs"></i> <?php echo t('medication.mechanismOfAction', 'Mechanism of Action'); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($med['mechanism_of_action'])); ?></p>
                    </div>

                    <div class="detail-section">
                        <h3><i class="fas fa-exclamation-triangle"></i> <?php echo t('medication.sideEffects', 'Side Effects'); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($med['side_effects'])); ?></p>
                    </div>

                    <div class="detail-section">
                        <h3><i class="fas fa-ban"></i> <?php echo t('medication.contraindications', 'Contraindications'); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($med['contraindication'])); ?></p>
                    </div>

                    <div class="detail-section">
                        <h3><i class="fas fa-prescription-bottle"></i> <?php echo t('medication.dosageInformation', 'Dosage Information'); ?></h3>
                        <div class="dosage-options-container">
                            <!-- Adult Dosages -->
                            <?php if (!empty($med['adult_dosage_1']) && !empty($med['adult_frequency_1'])): ?>
                                <div class="dosage-category-section">
                                    <h4><i class="fas fa-user"></i> <?php echo t('medication.adultDosages', 'Adult Dosages'); ?></h4>
                                    
                                    <div class="dosage-option-detail">
                                        <div class="option-number">
                                            <i class="fas fa-pills"></i>
                                            <strong><?php echo t('common.option1', 'Option 1'); ?></strong>
                                        </div>
                                        <div class="dosage-info-grid">
                                            <div class="dosage-info-item">
                                                <label><?php echo t('medication.dosage', 'Dosage'); ?>:</label>
                                                <span class="dosage-amount"><?php echo htmlspecialchars($med['adult_dosage_1']); ?></span>
                                            </div>
                                            <div class="dosage-info-item">
                                                <label><?php echo t('medication.frequency', 'Frequency'); ?>:</label>
                                                <span class="dosage-frequency"><?php echo htmlspecialchars($med['adult_frequency_1']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($med['adult_dosage_2']) && !empty($med['adult_frequency_2'])): ?>
                                    <div class="dosage-option-detail">
                                        <div class="option-number">
                                            <i class="fas fa-pills"></i>
                                            <strong><?php echo t('common.option2', 'Option 2'); ?></strong>
                                        </div>
                                        <div class="dosage-info-grid">
                                            <div class="dosage-info-item">
                                                <label><?php echo t('medication.dosage', 'Dosage'); ?>:</label>
                                                <span class="dosage-amount"><?php echo htmlspecialchars($med['adult_dosage_2']); ?></span>
                                            </div>
                                            <div class="dosage-info-item">
                                                <label><?php echo t('medication.frequency', 'Frequency'); ?>:</label>
                                                <span class="dosage-frequency"><?php echo htmlspecialchars($med['adult_frequency_2']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Children Dosages -->
                            <?php if (!empty($med['children_dosage_1']) && !empty($med['children_frequency_1'])): ?>
                                <div class="dosage-category-section">
                                    <h4><i class="fas fa-child"></i> <?php echo t('medication.childrenDosages', 'Children Dosages'); ?></h4>
                                    
                                    <div class="dosage-option-detail">
                                        <div class="option-number">
                                            <i class="fas fa-pills"></i>
                                            <strong><?php echo t('common.option1', 'Option 1'); ?></strong>
                                        </div>
                                        <div class="dosage-info-grid">
                                            <div class="dosage-info-item">
                                                <label><?php echo t('medication.dosage', 'Dosage'); ?>:</label>
                                                <span class="dosage-amount"><?php echo htmlspecialchars($med['children_dosage_1']); ?></span>
                                            </div>
                                            <div class="dosage-info-item">
                                                <label><?php echo t('medication.frequency', 'Frequency'); ?>:</label>
                                                <span class="dosage-frequency"><?php echo htmlspecialchars($med['children_frequency_1']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($med['children_dosage_2']) && !empty($med['children_frequency_2'])): ?>
                                    <div class="dosage-option-detail">
                                        <div class="option-number">
                                            <i class="fas fa-pills"></i>
                                            <strong><?php echo t('common.option2', 'Option 2'); ?></strong>
                                        </div>
                                        <div class="dosage-info-grid">
                                            <div class="dosage-info-item">
                                                <label><?php echo t('medication.dosage', 'Dosage'); ?>:</label>
                                                <span class="dosage-amount"><?php echo htmlspecialchars($med['children_dosage_2']); ?></span>
                                            </div>
                                            <div class="dosage-info-item">
                                                <label><?php echo t('medication.frequency', 'Frequency'); ?>:</label>
                                                <span class="dosage-frequency"><?php echo htmlspecialchars($med['children_frequency_2']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (empty($med['adult_dosage_1']) && empty($med['children_dosage_1'])): ?>
                                <p><?php echo t('medication.noDosageInfo', 'No dosage information available'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h3><i class="fas fa-baby"></i> <?php echo t('medication.pregnancyLactation', 'Pregnancy & Lactation Profile'); ?></h3>
                        <div class="pregnancy-lactation-grid">
                            <div class="pregnancy-lactation-item">
                                <strong><?php echo t('medication.pregnancy', 'Pregnancy'); ?>:</strong>
                                <span class="safety-status <?php echo $med['pregnancy_safe'] ? 'safe' : 'unsafe'; ?>">
                                    <?php echo $med['pregnancy_safe'] ? t('medication.safe', 'Safe') : t('medication.notRecommended', 'Not Recommended'); ?>
                                </span>
                            </div>
                            <div class="pregnancy-lactation-item">
                                <strong><?php echo t('medication.lactation', 'Lactation'); ?>:</strong>
                                <span class="safety-status <?php echo $med['lactation_safe'] ? 'safe' : 'unsafe'; ?>">
                                    <?php echo $med['lactation_safe'] ? t('medication.safe', 'Safe') : t('medication.notRecommended', 'Not Recommended'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-actions">
                    <button onclick="openFDALookup('<?php echo htmlspecialchars($med['active_ingredient']); ?>')" class="btn-fda" title="Get official FDA information for <?php echo htmlspecialchars($med['active_ingredient']); ?>">
                        <i class="fas fa-shield-alt"></i> <?php echo t('common.fdaDrugInfo', 'FDA Drug Information'); ?>
                    </button>
                    <button onclick="printPage()" class="btn btn-info">
                        <i class="fas fa-print"></i> <?php echo t('common.print', 'Print'); ?>
                    </button>
                    <button onclick="copyToClipboard(window.location.href)" class="btn btn-secondary">
                        <i class="fas fa-link"></i> <?php echo t('common.copyLink', 'Copy Link'); ?>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology.</p>
        </div>
    </footer>

    <style>
        .medication-detail {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .detail-header {
            padding: 1rem 2rem;
            background: var(--gray-100);
            border-bottom: 1px solid var(--gray-200);
        }

        .detail-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            padding: 2rem;
        }

        .detail-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .detail-title {
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .detail-class {
            color: var(--gray-600);
            font-size: 1.2rem;
            font-style: italic;
            margin-bottom: 1.5rem;
        }

        .detail-badges {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .detail-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
        }

        .detail-quick-info {
            background: var(--gray-100);
            padding: 1rem;
            border-radius: var(--border-radius);
        }

        .quick-info-item {
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }

        .detail-sections {
            padding: 0 2rem 2rem;
        }

        .detail-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .detail-section:last-child {
            border-bottom: none;
        }

        .detail-section h3 {
            color: var(--primary-color);
            font-size: 1.3rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-section p {
            color: var(--gray-700);
            line-height: 1.6;
        }

        .dosage-grid, .pregnancy-lactation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .dosage-item, .pregnancy-lactation-item {
            background: var(--gray-100);
            padding: 1rem;
            border-radius: var(--border-radius);
        }

        .safety-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .safety-status.safe {
            background-color: var(--secondary-color);
            color: var(--white);
        }

        .safety-status.unsafe {
            background-color: var(--danger-color);
            color: var(--white);
        }

        .detail-actions {
            padding: 1rem 2rem 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .detail-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .detail-title {
                font-size: 2rem;
            }

            .detail-price {
                font-size: 2rem;
            }

            .dosage-grid, .pregnancy-lactation-grid {
                grid-template-columns: 1fr;
            }

            .detail-actions {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>

    <script src="js/main.js"></script>
    <script src="js/fda-lookup.js"></script>
    <script>
        function changeLanguage(lang) {
            // Redirect with language parameter
            const url = new URL(window.location);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
