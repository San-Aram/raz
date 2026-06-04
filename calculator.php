<?php 
require_once 'includes/language-functions.php';
initializeLanguage();
require_once 'includes/auth.php';
require_once 'includes/admin-settings-helper.php';

// Check maintenance mode (non-admins will be redirected to maintenance page)
if (isMaintenanceMode() && !isAdmin()) {
    header('Location: maintenance.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dosage Calculator - <?php echo getSiteName(); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-pills"></i>
                    <h1><?php echo getSiteName(); ?></h1>
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
                    <a href="statistics.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> Statistics
                    </a>
                    <a href="calculator.php" class="nav-link active">
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
            <div class="calculator-container">
                <h2 class="form-title">
                    <i class="fas fa-calculator"></i> Medication Dosage Calculator
                </h2>
                
                <p class="calculator-description">
                    Calculate the correct medication dosage based on patient weight and prescribed mg/kg dosage.
                </p>

                <div class="calculator-form">
                    <div class="input-section">
                        <h3><i class="fas fa-weight"></i> Required Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dosage_mg_per_kg">Dosage (mg/kg) *</label>
                                <input type="number" id="dosage_mg_per_kg" class="form-control" 
                                       step="0.01" min="0" placeholder="e.g., 10.5">
                                <small>Prescribed dosage in milligrams per kilogram</small>
                            </div>
                            <div class="form-group">
                                <label for="weight_kg">Patient Weight (kg) *</label>
                                <input type="number" id="weight_kg" class="form-control" 
                                       step="0.1" min="0" placeholder="e.g., 70.5">
                                <small>Patient weight in kilograms</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dose_frequency">Frequency of Dose *</label>
                                <select id="dose_frequency" class="form-control">
                                    <option value="">Select frequency...</option>
                                    <option value="24">q24hr (qDay) - Once daily</option>
                                    <option value="12">q12hr (BID) - Twice daily</option>
                                    <option value="8">q8hr (TID) - Three times daily</option>
                                    <option value="6">q6hr (QID) - Four times daily</option>
                                    <option value="4">q4hr - Every 4 hours</option>
                                    <option value="3">q3hr - Every 3 hours</option>
                                    <option value="2">q2hr - Every 2 hours</option>
                                    <option value="1">q1hr - Every hour</option>
                                </select>
                                <small>How often the medication should be given</small>
                            </div>
                        </div>
                    </div>

                    <div class="input-section liquid-section">
                        <h3><i class="fas fa-flask"></i> Liquid Medication (Optional)</h3>
                        <p class="section-description">Fill these fields if you need to calculate liquid volume needed</p>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="med_amount_mg">Medication Amount (mg)</label>
                                <input type="number" id="med_amount_mg" class="form-control" 
                                       step="0.01" min="0" placeholder="e.g., 250">
                                <small>Amount of active ingredient in mg</small>
                            </div>
                            <div class="form-group">
                                <label for="per_volume_mL">Per Volume (mL)</label>
                                <input type="number" id="per_volume_mL" class="form-control" 
                                       step="0.1" min="0" placeholder="e.g., 5">
                                <small>Volume in milliliters</small>
                            </div>
                        </div>
                    </div>

                    <div class="calculator-actions">
                        <button type="button" id="calculate-btn" class="btn btn-primary">
                            <i class="fas fa-calculator"></i> Calculate Dosage
                        </button>
                        <button type="button" id="clear-btn" class="btn btn-secondary">
                            <i class="fas fa-eraser"></i> Clear All
                        </button>
                    </div>

                    <div class="results-section" id="results-section" style="display: none;">
                        <h3><i class="fas fa-chart-bar"></i> Calculation Results</h3>
                        
                        <div class="results-grid">
                            <div class="result-card primary-result">
                                <div class="result-icon">
                                    <i class="fas fa-pills"></i>
                                </div>
                                <div class="result-content">
                                    <h4>Single Dose</h4>
                                    <div class="result-value" id="single-dose">-</div>
                                    <div class="result-unit">mg</div>
                                </div>
                            </div>

                            <div class="result-card" id="daily-dose-card">
                                <div class="result-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="result-content">
                                    <h4>Total Daily Dose</h4>
                                    <div class="result-value" id="daily-dose">-</div>
                                    <div class="result-unit">mg/day</div>
                                </div>
                            </div>

                            <div class="result-card" id="frequency-card">
                                <div class="result-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="result-content">
                                    <h4>Doses per Day</h4>
                                    <div class="result-value" id="doses-per-day">-</div>
                                    <div class="result-unit">times</div>
                                </div>
                            </div>

                            <div class="result-card" id="concentration-card" style="display: none;">
                                <div class="result-icon">
                                    <i class="fas fa-flask"></i>
                                </div>
                                <div class="result-content">
                                    <h4>Concentration</h4>
                                    <div class="result-value" id="concentration">-</div>
                                    <div class="result-unit">mg/mL</div>
                                </div>
                            </div>

                            <div class="result-card" id="liquid-volume-card" style="display: none;">
                                <div class="result-icon">
                                    <i class="fas fa-tint"></i>
                                </div>
                                <div class="result-content">
                                    <h4>Liquid Volume per Dose</h4>
                                    <div class="result-value" id="liquid-volume">-</div>
                                    <div class="result-unit">mL</div>
                                </div>
                            </div>

                            <div class="result-card" id="daily-liquid-volume-card" style="display: none;">
                                <div class="result-icon">
                                    <i class="fas fa-bottle-droplet"></i>
                                </div>
                                <div class="result-content">
                                    <h4>Total Daily Liquid</h4>
                                    <div class="result-value" id="daily-liquid-volume">-</div>
                                    <div class="result-unit">mL/day</div>
                                </div>
                            </div>
                        </div>

                        <div class="calculation-details" id="calculation-details">
                            <h4><i class="fas fa-info-circle"></i> Calculation Steps</h4>
                            <div id="calculation-steps"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script src="js/calculator.js"></script>
</body>
</html>
