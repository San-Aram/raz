<?php
require_once 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixed FDA API Test - Razology</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-pills"></i>
                    <h1>Razology - Fixed FDA API Test</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="form-container">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <strong>FDA API Fixed!</strong> The following improvements have been made:
                    <ul style="margin-top: 0.5rem;">
                        <li>Enhanced medication name matching with multiple search strategies</li>
                        <li>Expanded local DrugBank database with common medications</li>
                        <li>Better error handling with helpful suggestions</li>
                        <li>Improved user interface with quick alternatives</li>
                    </ul>
                </div>
                
                <h2>Test Fixed FDA API</h2>
                <p>The API now has an enhanced local database and better search algorithms. Try these medications:</p>
                
                <div class="test-sections">
                    <div class="test-section">
                        <h3><i class="fas fa-check-circle text-success"></i> Should Work Now - Enhanced Local Database</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0;">
                            <button onclick="openFDALookup('Paracetamol')" class="btn-fda">
                                <i class="fas fa-shield-alt"></i> Paracetamol
                            </button>
                            <button onclick="openFDALookup('Acetaminophen')" class="btn-fda">
                                <i class="fas fa-shield-alt"></i> Acetaminophen
                            </button>
                            <button onclick="openFDALookup('Amoxicillin')" class="btn-fda">
                                <i class="fas fa-shield-alt"></i> Amoxicillin
                            </button>
                            <button onclick="openFDALookup('Metformin')" class="btn-fda">
                                <i class="fas fa-shield-alt"></i> Metformin
                            </button>
                            <button onclick="openFDALookup('Lisinopril')" class="btn-fda">
                                <i class="fas fa-shield-alt"></i> Lisinopril
                            </button>
                            <button onclick="openFDALookup('Omeprazole')" class="btn-fda">
                                <i class="fas fa-shield-alt"></i> Omeprazole
                            </button>
                            <button onclick="openFDALookup('Ibuprofen')" class="btn-fda">
                                <i class="fas fa-shield-alt"></i> Ibuprofen
                            </button>
                            <button onclick="openFDALookup('Aspirin')" class="btn-fda">
                                <i class="fas fa-shield-alt"></i> Aspirin
                            </button>
                            <button onclick="openFDALookup('Atorvastatin')" class="btn-fda">
                                <i class="fas fa-shield-alt"></i> Atorvastatin
                            </button>
                        </div>
                    </div>
                    
                    <div class="test-section">
                        <h3><i class="fas fa-exclamation-triangle text-warning"></i> May Still Show Error - But with Better Handling</h3>
                        <p>These will show the improved error modal with suggestions:</p>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0;">
                            <button onclick="openFDALookup('RandomDrug123')" class="btn btn-secondary">
                                <i class="fas fa-question"></i> RandomDrug123
                            </button>
                            <button onclick="openFDALookup('XyzMedication')" class="btn btn-secondary">
                                <i class="fas fa-question"></i> XyzMedication
                            </button>
                            <button onclick="openFDALookup('')" class="btn btn-secondary">
                                <i class="fas fa-question"></i> Empty String
                            </button>
                        </div>
                    </div>
                    
                    <div class="test-section">
                        <h3><i class="fas fa-keyboard"></i> Custom Test</h3>
                        <div class="form-group">
                            <label for="customMedication">Test Your Own Medication:</label>
                            <div style="display: flex; gap: 1rem;">
                                <input type="text" id="customMedication" class="form-control" placeholder="Enter medication name..." />
                                <button onclick="testCustomMedication()" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Test FDA API
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="improvements-section">
                    <h3><i class="fas fa-wrench"></i> What Was Fixed</h3>
                    <div class="improvements-grid">
                        <div class="improvement-card">
                            <h4><i class="fas fa-database"></i> Enhanced Local Database</h4>
                            <p>Added comprehensive medication data for 9 common drugs with detailed pharmacological information</p>
                        </div>
                        <div class="improvement-card">
                            <h4><i class="fas fa-search"></i> Multiple Search Strategies</h4>
                            <p>System now tries different search terms, alternative names, and common drug mappings</p>
                        </div>
                        <div class="improvement-card">
                            <h4><i class="fas fa-exclamation-triangle"></i> Better Error Handling</h4>
                            <p>Improved error messages with actionable suggestions and quick alternative options</p>
                        </div>
                        <div class="improvement-card">
                            <h4><i class="fas fa-network-wired"></i> Robust API Calls</h4>
                            <p>Multiple FDA search strategies with fallback to local database if external APIs fail</p>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <a href="medications.php" class="btn btn-primary">
                        <i class="fas fa-capsules"></i> Back to Medications
                    </a>
                    <a href="fda-demo.php" class="btn btn-secondary">
                        <i class="fas fa-presentation"></i> View Demo Page
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology. FDA API Enhanced and Fixed.</p>
        </div>
    </footer>

    <style>
        .test-sections {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .test-section {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .test-section h3 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .text-success {
            color: var(--secondary-color);
        }
        
        .text-warning {
            color: #ff9800;
        }
        
        .improvements-section {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-top: 2rem;
        }
        
        .improvements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .improvement-card {
            background: var(--gray-100);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-color);
        }
        
        .improvement-card h4 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .improvement-card p {
            color: var(--gray-700);
            margin: 0;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .improvements-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script src="js/fda-lookup.js"></script>
    <script>
        function testCustomMedication() {
            const medication = document.getElementById('customMedication').value.trim();
            if (medication) {
                openFDALookup(medication);
                document.getElementById('customMedication').value = '';
            } else {
                alert('Please enter a medication name to test.');
            }
        }
        
        // Allow Enter key to trigger test
        document.getElementById('customMedication').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                testCustomMedication();
            }
        });
    </script>
</body>
</html>