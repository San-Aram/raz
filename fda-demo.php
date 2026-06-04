<?php
require_once 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FDA API Integration Demo - Razology</title>
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
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="form-container">
                <div class="demo-header">
                    <h1><i class="fas fa-shield-alt"></i> FDA API Integration Demo</h1>
                    <p class="demo-subtitle">Experience real-time FDA drug information lookup</p>
                </div>

                <div class="demo-features">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <h3>Comprehensive Database</h3>
                        <p>Access to both FDA OpenFDA and DrugBank databases for complete drug information</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Smart Ingredient Search</h3>
                        <p>Intelligent search using RxNorm API for ingredient name normalization</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Official FDA Data</h3>
                        <p>Real-time access to official FDA drug labels and safety information</p>
                    </div>
                </div>

                <div class="demo-section">
                    <h2><i class="fas fa-vial"></i> Try Popular Medications</h2>
                    <p>Click any button below to see FDA information for common medications:</p>
                    
                    <div class="demo-buttons">
                        <button onclick="openFDALookup('Paracetamol')" class="btn-fda">
                            <i class="fas fa-shield-alt"></i> Paracetamol (Acetaminophen)
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
                        <button onclick="openFDALookup('Atorvastatin')" class="btn-fda">
                            <i class="fas fa-shield-alt"></i> Atorvastatin
                        </button>
                        <button onclick="openFDALookup('Ibuprofen')" class="btn-fda">
                            <i class="fas fa-shield-alt"></i> Ibuprofen
                        </button>
                        <button onclick="openFDALookup('Aspirin')" class="btn-fda">
                            <i class="fas fa-shield-alt"></i> Aspirin
                        </button>
                    </div>
                </div>

                <div class="demo-section">
                    <h2><i class="fas fa-keyboard"></i> Test Custom Medication</h2>
                    <p>Enter any active ingredient name to lookup FDA information:</p>
                    
                    <div class="custom-search">
                        <div class="form-group">
                            <label for="customIngredient">Active Ingredient Name:</label>
                            <div class="search-input-group">
                                <input type="text" id="customIngredient" class="form-control" placeholder="e.g., Metformin, Ibuprofen, Simvastatin..." />
                                <button onclick="testCustomIngredient()" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search FDA
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="demo-section">
                    <h2><i class="fas fa-info-circle"></i> How It Works</h2>
                    <div class="workflow-steps">
                        <div class="workflow-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Ingredient Normalization</h4>
                                <p>The system uses RxNorm API to normalize and standardize drug names</p>
                            </div>
                        </div>
                        <div class="workflow-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>FDA Database Search</h4>
                                <p>Searches the official FDA OpenFDA database for drug labels and safety information</p>
                            </div>
                        </div>
                        <div class="workflow-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>DrugBank Integration</h4>
                                <p>Retrieves additional drug information from DrugBank database</p>
                            </div>
                        </div>
                        <div class="workflow-step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h4>Comprehensive Display</h4>
                                <p>Presents organized information in an easy-to-read tabbed interface</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="demo-section">
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Pro Tip:</strong> This FDA integration is available on every medication detail page. 
                        Simply click the "FDA Drug Information" button when viewing any medication to get official FDA data.
                    </div>
                </div>

                <div class="demo-actions">
                    <a href="medications.php" class="btn btn-primary">
                        <i class="fas fa-capsules"></i> View All Medications
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology. FDA data provided by OpenFDA API.</p>
        </div>
    </footer>

    <style>
        .demo-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .demo-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .demo-subtitle {
            font-size: 1.2rem;
            color: var(--gray-600);
            font-style: italic;
        }

        .demo-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .feature-card {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            color: white;
            font-size: 2rem;
        }

        .demo-section {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }

        .demo-section h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .demo-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .search-input-group {
            display: flex;
            gap: 0.5rem;
        }

        .search-input-group input {
            flex: 1;
        }

        .workflow-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .workflow-step {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .step-number {
            background: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .step-content h4 {
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .demo-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .demo-header h1 {
                font-size: 2rem;
            }

            .demo-features {
                grid-template-columns: 1fr;
            }

            .demo-buttons {
                grid-template-columns: 1fr;
            }

            .search-input-group {
                flex-direction: column;
            }

            .workflow-steps {
                grid-template-columns: 1fr;
            }

            .demo-actions {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>

    <script src="js/fda-lookup.js"></script>
    <script>
        function testCustomIngredient() {
            const ingredient = document.getElementById('customIngredient').value.trim();
            if (ingredient) {
                openFDALookup(ingredient);
                // Clear the input after search
                document.getElementById('customIngredient').value = '';
            } else {
                alert('Please enter an active ingredient name to search.');
            }
        }
        
        // Allow Enter key to trigger search
        document.getElementById('customIngredient').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                testCustomIngredient();
            }
        });

        // Add some interactive feedback
        document.querySelectorAll('.btn-fda').forEach(button => {
            button.addEventListener('click', function() {
                // Add a subtle feedback effect
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    </script>
</body>
</html>