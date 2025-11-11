/**
 * Authentication JavaScript Functions
 * Dental Clinic Management System
 */

// Show/hide loading states
function showLoading(button) {
    if (!button) {
        console.error('showLoading: button element not found');
        return;
    }
    button.disabled = true;
    button.innerHTML = '<span class="spinner"></span>Processing...';
}

function hideLoading(button, originalText) {
    if (!button) {
        console.error('hideLoading: button element not found');
        return;
    }
    button.disabled = false;
    button.innerHTML = originalText || 'Submit';
}

// Form validation helpers
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function validatePassword(password) {
    return password.length >= 6;
}

// Alert functions
function showAlert(message, type = 'info') {
    const alertBox = document.getElementById('alert');
    if (alertBox) {
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
        alertBox.style.display = 'block';
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 3000);
        }
    }
}

function hideAlert() {
    const alertBox = document.getElementById('alert');
    if (alertBox) {
        alertBox.style.display = 'none';
    }
}

// Initialize form handlers when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Auth.js loaded successfully');
    
    // Clear any existing alerts when form fields are focused
    const inputs = document.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('focus', hideAlert);
    });
});