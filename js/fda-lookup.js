/**
 * FDA and DrugBank Drug Information Lookup
 * Fetches drug information from both FDA and DrugBank databases
 */

function lookupDrugInfo(medicationName) {
    // Create and show loading modal
    const loadingModal = createLoadingModal();
    document.body.appendChild(loadingModal);
    loadingModal.style.display = 'flex';
    
    // Make API request
    fetch(`api/fda-lookup.php?medication=${encodeURIComponent(medicationName)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Remove loading modal
            document.body.removeChild(loadingModal);
            
            if (data.success) {
                displayDrugInfoModal(data.data, medicationName, data);
            } else {
                displayErrorModal(data.message || 'No drug information found', data);
            }
        })
        .catch(error => {
            // Remove loading modal
            document.body.removeChild(loadingModal);
            console.error('Error fetching drug info:', error);
            displayErrorModal('Network error occurred. Please check your connection and try again.', null, error);
        });
}

function createLoadingModal() {
    const modal = document.createElement('div');
    modal.className = 'fda-modal-overlay';
    modal.innerHTML = `
        <div class="fda-modal-content loading">
            <div class="fda-loading-spinner"></div>
            <h3>Fetching Drug Information...</h3>
            <p>Searching FDA and DrugBank databases</p>
        </div>
    `;
    return modal;
}

function displayDrugInfoModal(data, medicationName) {
    const modal = document.createElement('div');
    modal.className = 'fda-modal-overlay';
    
    // Determine which data sources are available
    const hasFDA = data.fda && Object.keys(data.fda).length > 0;
    const hasDrugBank = data.drugbank && Object.keys(data.drugbank).length > 0;
    
    let tabsHtml = '';
    let contentHtml = '';
    
    if (hasFDA && hasDrugBank) {
        // Both sources available - create tabbed interface
        tabsHtml = `
            <div class="fda-tabs">
                <button class="fda-tab-btn active" onclick="switchTab(event, 'fda-tab')">
                    <i class="fas fa-shield-alt"></i> FDA Information
                </button>
                <button class="fda-tab-btn" onclick="switchTab(event, 'drugbank-tab')">
                    <i class="fas fa-database"></i> DrugBank Information
                </button>
            </div>
        `;
        
        contentHtml = `
            <div id="fda-tab" class="fda-tab-content active">
                ${formatFDAData(data.fda)}
            </div>
            <div id="drugbank-tab" class="fda-tab-content">
                ${formatDrugBankData(data.drugbank)}
            </div>
        `;
    } else if (hasFDA) {
        // Only FDA data available
        tabsHtml = `
            <div class="fda-single-source">
                <h4><i class="fas fa-shield-alt"></i> FDA Information</h4>
            </div>
        `;
        contentHtml = formatFDAData(data.fda);
    } else if (hasDrugBank) {
        // Only DrugBank data available
        tabsHtml = `
            <div class="fda-single-source">
                <h4><i class="fas fa-database"></i> DrugBank Information</h4>
            </div>
        `;
        contentHtml = formatDrugBankData(data.drugbank);
    } else {
        // No data available
        displayErrorModal('No drug information found in FDA or DrugBank databases');
        return;
    }
    
    modal.innerHTML = `
        <div class="fda-modal-content">
            <div class="fda-modal-header">
                <h2><i class="fas fa-pills"></i> Drug Information: ${escapeHtml(medicationName)}</h2>
                <button class="fda-close-btn" onclick="closeDrugInfoModal(this)">&times;</button>
            </div>
            ${tabsHtml}
            <div class="fda-modal-body">
                ${contentHtml}
            </div>
            <div class="fda-modal-footer">
                <p><strong>Disclaimer:</strong> This information is for educational purposes only. Always consult healthcare professionals for medical advice.</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'flex';
    
    // Add click outside to close
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeDrugInfoModal(modal.querySelector('.fda-close-btn'));
        }
    });
}

function formatFDAData(fdaData) {
    if (!fdaData) return '<p>No FDA data available</p>';
    
    return `
        <div class="fda-info-section">
            <div class="fda-info-grid">
                <div class="fda-info-item">
                    <strong>Brand Name:</strong>
                    <span>${escapeHtml(fdaData.brand_name || 'Not available')}</span>
                </div>
                <div class="fda-info-item">
                    <strong>Generic Name:</strong>
                    <span>${escapeHtml(fdaData.generic_name || 'Not available')}</span>
                </div>
                <div class="fda-info-item">
                    <strong>Manufacturer:</strong>
                    <span>${escapeHtml(fdaData.manufacturer_name || 'Not available')}</span>
                </div>
                <div class="fda-info-item">
                    <strong>Product Type:</strong>
                    <span>${escapeHtml(fdaData.product_type || 'Not available')}</span>
                </div>
                <div class="fda-info-item">
                    <strong>Route:</strong>
                    <span>${escapeHtml(fdaData.route || 'Not available')}</span>
                </div>
                <div class="fda-info-item">
                    <strong>NDC:</strong>
                    <span>${escapeHtml(truncateText(fdaData.ndc || 'Not available', 100))}</span>
                </div>
            </div>
            
            <div class="fda-detailed-info">
                <div class="fda-info-block">
                    <h4><i class="fas fa-info-circle"></i> Purpose</h4>
                    <p>${escapeHtml(truncateText(fdaData.purpose || 'Not available', 300))}</p>
                </div>
                
                <div class="fda-info-block">
                    <h4><i class="fas fa-stethoscope"></i> Indications and Usage</h4>
                    <p>${escapeHtml(truncateText(fdaData.indications_and_usage || 'Not available', 400))}</p>
                </div>
                
                <div class="fda-info-block">
                    <h4><i class="fas fa-prescription-bottle"></i> Dosage and Administration</h4>
                    <p>${escapeHtml(truncateText(fdaData.dosage_and_administration || 'Not available', 400))}</p>
                </div>
                
                <div class="fda-info-block warning">
                    <h4><i class="fas fa-exclamation-triangle"></i> Warnings</h4>
                    <p>${escapeHtml(truncateText(fdaData.warnings || 'Not available', 400))}</p>
                </div>
                
                <div class="fda-info-block">
                    <h4><i class="fas fa-allergies"></i> Adverse Reactions</h4>
                    <p>${escapeHtml(truncateText(fdaData.adverse_reactions || 'Not available', 300))}</p>
                </div>
                
                <div class="fda-info-block">
                    <h4><i class="fas fa-link"></i> Drug Interactions</h4>
                    <p>${escapeHtml(truncateText(fdaData.drug_interactions || 'Not available', 300))}</p>
                </div>
            </div>
        </div>
    `;
}

function formatDrugBankData(drugBankData) {
    if (!drugBankData) return '<p>No DrugBank data available</p>';
    
    return `
        <div class="drugbank-info-section">
            <div class="fda-info-grid">
                <div class="fda-info-item">
                    <strong>DrugBank ID:</strong>
                    <span>${escapeHtml(drugBankData.drugbank_id || 'Not available')}</span>
                </div>
                <div class="fda-info-item">
                    <strong>Name:</strong>
                    <span>${escapeHtml(drugBankData.name || 'Not available')}</span>
                </div>
                <div class="fda-info-item">
                    <strong>CAS Number:</strong>
                    <span>${escapeHtml(drugBankData.cas_number || 'Not available')}</span>
                </div>
                <div class="fda-info-item">
                    <strong>UNII:</strong>
                    <span>${escapeHtml(drugBankData.unii || 'Not available')}</span>
                </div>
                <div class="fda-info-item">
                    <strong>State:</strong>
                    <span>${escapeHtml(drugBankData.state || 'Not available')}</span>
                </div>
                <div class="fda-info-item">
                    <strong>Half-life:</strong>
                    <span>${escapeHtml(drugBankData.half_life || 'Not available')}</span>
                </div>
            </div>
            
            <div class="fda-detailed-info">
                <div class="fda-info-block">
                    <h4><i class="fas fa-info-circle"></i> Description</h4>
                    <p>${escapeHtml(truncateText(drugBankData.description || 'Not available', 400))}</p>
                </div>
                
                <div class="fda-info-block">
                    <h4><i class="fas fa-stethoscope"></i> Indication</h4>
                    <p>${escapeHtml(truncateText(drugBankData.indication || 'Not available', 400))}</p>
                </div>
                
                <div class="fda-info-block">
                    <h4><i class="fas fa-cogs"></i> Mechanism of Action</h4>
                    <p>${escapeHtml(truncateText(drugBankData.mechanism_of_action || 'Not available', 400))}</p>
                </div>
                
                <div class="fda-info-block">
                    <h4><i class="fas fa-chart-line"></i> Pharmacodynamics</h4>
                    <p>${escapeHtml(truncateText(drugBankData.pharmacodynamics || 'Not available', 400))}</p>
                </div>
                
                <div class="fda-info-block">
                    <h4><i class="fas fa-absorption"></i> Absorption</h4>
                    <p>${escapeHtml(truncateText(drugBankData.absorption || 'Not available', 300))}</p>
                </div>
                
                <div class="fda-info-block">
                    <h4><i class="fas fa-recycle"></i> Metabolism</h4>
                    <p>${escapeHtml(truncateText(drugBankData.metabolism || 'Not available', 300))}</p>
                </div>
                
                <div class="fda-info-block warning">
                    <h4><i class="fas fa-skull-crossbones"></i> Toxicity</h4>
                    <p>${escapeHtml(truncateText(drugBankData.toxicity || 'Not available', 400))}</p>
                </div>
                
                <div class="fda-info-block">
                    <h4><i class="fas fa-link"></i> Protein Binding</h4>
                    <p>${escapeHtml(truncateText(drugBankData.protein_binding || 'Not available', 200))}</p>
                </div>
            </div>
        </div>
    `;
}

function switchTab(event, tabId) {
    // Remove active class from all tabs and content
    document.querySelectorAll('.fda-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.fda-tab-content').forEach(content => content.classList.remove('active'));
    
    // Add active class to clicked tab and corresponding content
    event.target.classList.add('active');
    document.getElementById(tabId).classList.add('active');
}

function displayErrorModal(message, apiData = null, error = null) {
    const modal = document.createElement('div');
    modal.className = 'fda-modal-overlay';
    
    let suggestionsList = '';
    if (apiData && apiData.suggestions) {
        suggestionsList = '<ul>' + apiData.suggestions.map(s => `<li>${escapeHtml(s)}</li>`).join('') + '</ul>';
    } else {
        suggestionsList = `
            <ul>
                <li>Check the spelling of the medication name</li>
                <li>Try using the generic name instead of brand name</li>
                <li>Search for the active ingredient only (without dosage)</li>
                <li>Try common alternative names (e.g., "Paracetamol" or "Acetaminophen")</li>
            </ul>
        `;
    }
    
    let debugInfo = '';
    if (apiData && apiData.searched_terms) {
        debugInfo = `
            <div class="debug-info" style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; font-size: 0.9rem;">
                <strong>Searched terms:</strong> ${apiData.searched_terms.join(', ')}
            </div>
        `;
    }
    
    modal.innerHTML = `
        <div class="fda-modal-content error">
            <div class="fda-modal-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Drug Information Not Found</h2>
                <button class="fda-close-btn" onclick="closeDrugInfoModal(this)">&times;</button>
            </div>
            <div class="fda-modal-body">
                <div class="error-message">
                    <p><strong>Issue:</strong> ${escapeHtml(message)}</p>
                </div>
                
                <div class="suggestions">
                    <h4><i class="fas fa-lightbulb"></i> Suggestions to try:</h4>
                    ${suggestionsList}
                </div>
                
                <div class="alternative-options">
                    <h4><i class="fas fa-search"></i> Quick alternatives:</h4>
                    <div class="quick-alternatives">
                        <button onclick="closeDrugInfoModal(this); setTimeout(() => openFDALookup('Paracetamol'), 100);" class="btn btn-sm btn-secondary">
                            Try Paracetamol
                        </button>
                        <button onclick="closeDrugInfoModal(this); setTimeout(() => openFDALookup('Ibuprofen'), 100);" class="btn btn-sm btn-secondary">
                            Try Ibuprofen
                        </button>
                        <button onclick="closeDrugInfoModal(this); setTimeout(() => openFDALookup('Aspirin'), 100);" class="btn btn-sm btn-secondary">
                            Try Aspirin
                        </button>
                    </div>
                </div>
                
                ${debugInfo}
                
                <div class="help-note" style="margin-top: 1rem; padding: 1rem; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
                    <strong>Note:</strong> This system searches both FDA and DrugBank databases. 
                    Some medications may not be available in these databases, especially newer drugs or region-specific medications.
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'flex';
    
    // Add click outside to close
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeDrugInfoModal(modal.querySelector('.fda-close-btn'));
        }
    });
}

function closeDrugInfoModal(button) {
    const modal = button.closest('.fda-modal-overlay');
    if (modal) {
        document.body.removeChild(modal);
    }
}

function truncateText(text, maxLength) {
    if (!text || text === 'Not available') return text;
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Legacy function support for existing buttons
function openFDALookup(medicationName) {
    lookupDrugInfo(medicationName);
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('fda-modal-overlay')) {
        const closeBtn = event.target.querySelector('.fda-close-btn');
        if (closeBtn) {
            closeDrugInfoModal(closeBtn);
        }
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const openModal = document.querySelector('.fda-modal-overlay');
        if (openModal) {
            const closeBtn = openModal.querySelector('.fda-close-btn');
            if (closeBtn) {
                closeDrugInfoModal(closeBtn);
            }
        }
    }
});
