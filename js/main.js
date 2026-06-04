// Main JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    // Only load stats if the elements exist on the page
    if (document.getElementById('totalMedications')) {
        loadStats();
    }
});

// Load dashboard statistics (only if elements exist)
function loadStats() {
    const totalMedications = document.getElementById('totalMedications');
    const totalProducts = document.getElementById('totalProducts');
    const totalCosmetics = document.getElementById('totalCosmetics');
    const totalDental = document.getElementById('totalDental');
    const pregnancySafe = document.getElementById('pregnancySafe');
    const lactationSafe = document.getElementById('lactationSafe');
    
    // Only proceed if at least some elements exist
    if (!totalMedications && !totalProducts && !pregnancySafe && !lactationSafe) {
        return;
    }
    
    fetch('api/stats.php')
        .then(response => response.json())
        .then(data => {
            console.log('Stats API Response:', data); // Debug log
            
            if (data.success && data.data) {
                // Update elements if they exist
                if (totalMedications) {
                    totalMedications.textContent = data.data.total_medications || data.data.medications || 0;
                }
                if (totalProducts) {
                    totalProducts.textContent = data.data.total_products || data.data.products || 0;
                }
                if (totalCosmetics) {
                    totalCosmetics.textContent = data.data.total_cosmetics || data.data.cosmetics || 0;
                }
                if (totalDental) {
                    totalDental.textContent = data.data.total_dental || data.data.dental || 0;
                }
                if (pregnancySafe) {
                    pregnancySafe.textContent = data.data.pregnancy_safe || 0;
                }
                if (lactationSafe) {
                    lactationSafe.textContent = data.data.lactation_safe || 0;
                }
                
                // Animate the numbers
                animateNumbers();
            } else {
                console.error('Stats API returned error or no data:', data);
                // Set fallback values to prevent NaN
                if (totalMedications) totalMedications.textContent = '0';
                if (totalProducts) totalProducts.textContent = '0';
                if (totalCosmetics) totalCosmetics.textContent = '0';
                if (totalDental) totalDental.textContent = '0';
                if (pregnancySafe) pregnancySafe.textContent = '0';
                if (lactationSafe) lactationSafe.textContent = '0';
            }
        })
        .catch(error => {
            console.error('Error loading stats:', error);
            // Set fallback values on error to prevent NaN
            if (totalMedications) totalMedications.textContent = '0';
            if (totalProducts) totalProducts.textContent = '0';
            if (totalCosmetics) totalCosmetics.textContent = '0';
            if (totalDental) totalDental.textContent = '0';
            if (pregnancySafe) pregnancySafe.textContent = '0';
            if (lactationSafe) lactationSafe.textContent = '0';
        });
}

// Animate number counting
function animateNumbers() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    statNumbers.forEach(stat => {
        const target = parseInt(stat.textContent);
        const increment = target / 50;
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            stat.textContent = Math.floor(current);
        }, 20);
    });
}

// Show alert messages
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
    
    // Try multiple selectors to find a suitable container
    let container = document.querySelector('.main .container') || 
                   document.querySelector('.container') || 
                   document.querySelector('main') || 
                   document.body;
    
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    } else {
        // Fallback to simple alert if no container found
        alert(message);
        return;
    }
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Form validation enhancement
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--danger-color)';
            isValid = false;
        } else {
            field.style.borderColor = 'var(--gray-300)';
        }
    });
    
    return isValid;
}

// Add form validation to all forms
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (!validateForm(this)) {
            e.preventDefault();
            showAlert('Please fill in all required fields.', 'danger');
        }
    });
});

// Image error handling
document.querySelectorAll('img').forEach(img => {
    img.addEventListener('error', function() {
        if (this.classList.contains('product-image')) {
            this.src = 'images/default-product.jpg';
        }
    });
});

// Loading state management
function showLoading(element) {
    element.innerHTML = '<div class="loading"></div>';
}

function hideLoading(element, originalContent) {
    element.innerHTML = originalContent;
}

// Price formatting
function formatPrice(price) {
    return new Intl.NumberFormat('en-IQ', {
        style: 'currency',
        currency: 'IQD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(price);
}

// Date formatting
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Copy to clipboard functionality
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy: ', err);
        showAlert('Failed to copy to clipboard.', 'danger');
    });
}

// Modal functionality
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    // Close modal with Escape key
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal[style*="block"]');
        if (openModal) {
            openModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
});

// Responsive navigation toggle
function toggleNavigation() {
    const nav = document.querySelector('.nav');
    nav.classList.toggle('nav-open');
}

// Add mobile menu button if needed
if (window.innerWidth <= 768) {
    const header = document.querySelector('.header-content');
    const menuButton = document.createElement('button');
    menuButton.innerHTML = '<i class="fas fa-bars"></i>';
    menuButton.className = 'mobile-menu-btn';
    menuButton.onclick = toggleNavigation;
    header.appendChild(menuButton);
}

// Window resize handler
window.addEventListener('resize', function() {
    // Hide mobile menu on larger screens
    if (window.innerWidth > 768) {
        const nav = document.querySelector('.nav');
        nav.classList.remove('nav-open');
    }
});

// Print functionality
function printPage() {
    window.print();
}

// Export functionality (placeholder)
function exportData(format) {
    showAlert(`Export to ${format} functionality will be implemented.`, 'info');
}

// Search suggestions (for future enhancement)
function initSearchSuggestions() {
    const searchInputs = document.querySelectorAll('input[type="text"][placeholder*="search"]');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Implementation for search suggestions
            // This would connect to an API endpoint for suggestions
        });
    });
}

// Initialize search suggestions
initSearchSuggestions();
