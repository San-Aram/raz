<?php
// Simple test page for FDA API
require_once 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FDA API Test - Razology</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-pills"></i>
                    <h1>Razology - FDA API Test</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="form-container">
                <h2>Test FDA API Integration</h2>
                <p>Click the buttons below to test FDA data retrieval for common medications:</p>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin: 2rem 0;">
                    <button onclick="openFDALookup('Paracetamol')" class="btn-fda">
                        <i class="fas fa-shield-alt"></i> Test Paracetamol
                    </button>
                    <button onclick="openFDALookup('Amoxicillin')" class="btn-fda">
                        <i class="fas fa-shield-alt"></i> Test Amoxicillin
                    </button>
                    <button onclick="openFDALookup('Metformin')" class="btn-fda">
                        <i class="fas fa-shield-alt"></i> Test Metformin
                    </button>
                    <button onclick="openFDALookup('Lisinopril')" class="btn-fda">
                        <i class="fas fa-shield-alt"></i> Test Lisinopril
                    </button>
                    <button onclick="openFDALookup('Omeprazole')" class="btn-fda">
                        <i class="fas fa-shield-alt"></i> Test Omeprazole
                    </button>
                </div>
                
                <div class="form-group">
                    <label for="customIngredient">Test Custom Active Ingredient:</label>
                    <div style="display: flex; gap: 1rem;">
                        <input type="text" id="customIngredient" class="form-control" placeholder="Enter active ingredient name..." />
                        <button onclick="testCustomIngredient()" class="btn btn-primary">
                            <i class="fas fa-search"></i> Test
                        </button>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> This test page allows you to verify the FDA API integration. 
                    The system will first try to normalize ingredient names using RxNorm API, then search the FDA database.
                </div>
                
                <div style="margin-top: 2rem;">
                    <a href="medications.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Medications
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; Created by Sanology.</p>
        </div>
    </footer>

    <script src="js/fda-lookup.js"></script>
    <script>
        function testCustomIngredient() {
            const ingredient = document.getElementById('customIngredient').value.trim();
            if (ingredient) {
                openFDALookup(ingredient);
            } else {
                alert('Please enter an active ingredient name to test.');
            }
        }
        
        // Allow Enter key to trigger test
        document.getElementById('customIngredient').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                testCustomIngredient();
            }
        });
    </script>
</body>
</html>
