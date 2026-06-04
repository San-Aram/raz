// Dosage Calculator Functionality
document.addEventListener('DOMContentLoaded', function() {
    const calculateBtn = document.getElementById('calculate-btn');
    const clearBtn = document.getElementById('clear-btn');
    const resultsSection = document.getElementById('results-section');
    
    // Input fields
    const dosageMgPerKg = document.getElementById('dosage_mg_per_kg');
    const weightKg = document.getElementById('weight_kg');
    const doseFrequency = document.getElementById('dose_frequency');
    const medAmountMg = document.getElementById('med_amount_mg');
    const perVolumeMl = document.getElementById('per_volume_mL');
    
    // Result elements
    const singleDoseElement = document.getElementById('single-dose');
    const dailyDoseElement = document.getElementById('daily-dose');
    const dosesPerDayElement = document.getElementById('doses-per-day');
    const concentrationElement = document.getElementById('concentration');
    const liquidVolumeElement = document.getElementById('liquid-volume');
    const dailyLiquidVolumeElement = document.getElementById('daily-liquid-volume');
    const concentrationCard = document.getElementById('concentration-card');
    const liquidVolumeCard = document.getElementById('liquid-volume-card');
    const dailyLiquidVolumeCard = document.getElementById('daily-liquid-volume-card');
    const calculationSteps = document.getElementById('calculation-steps');
    
    // Calculate button click handler
    calculateBtn.addEventListener('click', function() {
        calculateDosage();
    });
    
    // Clear button click handler
    clearBtn.addEventListener('click', function() {
        clearAll();
    });
    
    // Enter key handlers for inputs (but no auto-calculation)
    [dosageMgPerKg, weightKg, medAmountMg, perVolumeMl].forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                calculateDosage();
            }
        });
    });
    
    // Remove auto-calculation - only calculate when button is clicked or Enter is pressed
    
    function calculateDosage() {
        // Get input values
        const dosage = parseFloat(dosageMgPerKg.value);
        const weight = parseFloat(weightKg.value);
        const frequencyHours = parseFloat(doseFrequency.value);
        const medAmount = parseFloat(medAmountMg.value);
        const volume = parseFloat(perVolumeMl.value);
        
        // Validate required inputs
        if (!dosage || !weight || !frequencyHours) {
            showAlert('Please enter dosage (mg/kg), patient weight (kg), and dose frequency', 'warning');
            return;
        }
        
        if (dosage <= 0 || weight <= 0) {
            showAlert('Dosage and weight must be greater than 0', 'danger');
            return;
        }
        
        // Validate liquid inputs if provided
        if ((medAmount && !volume) || (!medAmount && volume)) {
            showAlert('For liquid calculations, please provide both medication amount (mg) and volume (mL)', 'warning');
            return;
        }
        
        if (medAmount && volume && (medAmount <= 0 || volume <= 0)) {
            showAlert('Medication amount and volume must be greater than 0', 'danger');
            return;
        }
        
        // Perform calculations
        const results = performCalculations(dosage, weight, frequencyHours, medAmount, volume);
        
        // Display results
        displayResults(results);
        
        // Show results section without auto-scrolling
        resultsSection.style.display = 'block';
    }
    
    function performCalculations(dosage, weight, frequencyHours, medAmount, volume) {
        const results = {};
        const steps = [];
        
        // Calculate single dose (mg per dose)
        results.singleDose = dosage * weight;
        steps.push(`Single Dose = ${dosage} mg/kg × ${weight} kg = ${results.singleDose.toFixed(2)} mg per dose`);
        
        // Calculate doses per day based on frequency
        results.dosesPerDay = 24 / frequencyHours;
        const frequencyText = getFrequencyText(frequencyHours);
        steps.push(`Doses per Day = 24 hours ÷ ${frequencyHours} hours = ${results.dosesPerDay} doses per day (${frequencyText})`);
        
        // Calculate total daily dose
        results.dailyDose = results.singleDose * results.dosesPerDay;
        steps.push(`Total Daily Dose = ${results.singleDose.toFixed(2)} mg × ${results.dosesPerDay} doses = ${results.dailyDose.toFixed(2)} mg per day`);
        
        // Calculate concentration and liquid volume if liquid data provided
        if (medAmount && volume) {
            results.concentration = medAmount / volume;
            steps.push(`Concentration = ${medAmount} mg ÷ ${volume} mL = ${results.concentration.toFixed(2)} mg/mL`);
            
            results.liquidVolumePerDose = results.singleDose / results.concentration;
            steps.push(`Liquid Volume per Dose = ${results.singleDose.toFixed(2)} mg ÷ ${results.concentration.toFixed(2)} mg/mL = ${results.liquidVolumePerDose.toFixed(2)} mL per dose`);
            
            results.dailyLiquidVolume = results.liquidVolumePerDose * results.dosesPerDay;
            steps.push(`Total Daily Liquid = ${results.liquidVolumePerDose.toFixed(2)} mL × ${results.dosesPerDay} doses = ${results.dailyLiquidVolume.toFixed(2)} mL per day`);
        }
        
        results.steps = steps;
        results.frequencyText = frequencyText;
        return results;
    }
    
    function getFrequencyText(hours) {
        const frequencies = {
            24: 'Once daily (qDay)',
            12: 'Twice daily (BID)',
            8: 'Three times daily (TID)', 
            6: 'Four times daily (QID)',
            4: 'Every 4 hours',
            3: 'Every 3 hours',
            2: 'Every 2 hours',
            1: 'Every hour'
        };
        return frequencies[hours] || `Every ${hours} hours`;
    }
    
    function displayResults(results) {
        // Display single dose
        singleDoseElement.textContent = results.singleDose.toFixed(2);
        
        // Display daily dose and frequency
        dailyDoseElement.textContent = results.dailyDose.toFixed(2);
        dosesPerDayElement.textContent = results.dosesPerDay;
        
        // Display concentration and liquid volume if calculated
        if (results.concentration !== undefined) {
            concentrationElement.textContent = results.concentration.toFixed(2);
            concentrationCard.style.display = 'block';
            
            liquidVolumeElement.textContent = results.liquidVolumePerDose.toFixed(2);
            liquidVolumeCard.style.display = 'block';
            
            dailyLiquidVolumeElement.textContent = results.dailyLiquidVolume.toFixed(2);
            dailyLiquidVolumeCard.style.display = 'block';
        } else {
            concentrationCard.style.display = 'none';
            liquidVolumeCard.style.display = 'none';
            dailyLiquidVolumeCard.style.display = 'none';
        }
        
        // Display calculation steps
        calculationSteps.innerHTML = results.steps.map(step => 
            `<div class="calculation-step">${step}</div>`
        ).join('');
        
        // Add success message
        showAlert('Calculation completed successfully!', 'success');
    }
    
    function clearAll() {
        // Clear all inputs
        dosageMgPerKg.value = '';
        weightKg.value = '';
        doseFrequency.value = '';
        medAmountMg.value = '';
        perVolumeMl.value = '';
        
        // Hide results
        resultsSection.style.display = 'none';
        
        // Focus on first input
        dosageMgPerKg.focus();
        
        showAlert('All fields cleared', 'info');
    }
    
    // Utility function for alerts (if not already defined)
    if (typeof showAlert === 'undefined') {
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
            alertDiv.style.position = 'fixed';
            alertDiv.style.top = '20px';
            alertDiv.style.right = '20px';
            alertDiv.style.zIndex = '9999';
            alertDiv.style.maxWidth = '400px';
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 4000);
        }
    }
    
    // Auto-focus on first input when page loads
    dosageMgPerKg.focus();
});
