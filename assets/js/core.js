/**
 * Basketball Bingo - Core JavaScript Functions
 * Provides shared functionality for all pages
 */

/**
 * AJAX request helper
 * 
 * @param {string} url - URL to request
 * @param {string} method - HTTP method (GET, POST, etc.)
 * @param {object|FormData} data - Data to send
 * @returns {Promise} Promise that resolves with response
 */
function ajaxRequest(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        
        // Show loading indicator
        document.body.classList.add('loading');
        
        xhr.open(method, url, true);
        
        if (method === 'POST' && !(data instanceof FormData)) {
            xhr.setRequestHeader('Content-Type', 'application/json');
        }
        
        xhr.onload = function() {
            // Hide loading indicator
            document.body.classList.remove('loading');
            
            if (xhr.status >= 200 && xhr.status < 300) {
                let response;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch (e) {
                    response = xhr.responseText;
                }
                resolve(response);
            } else {
                reject({
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText
                });
            }
        };
        
        xhr.onerror = function() {
            // Hide loading indicator
            document.body.classList.remove('loading');
            
            reject({
                status: xhr.status,
                statusText: 'Network Error',
                responseText: xhr.responseText
            });
        };
        
        if (data instanceof FormData) {
            xhr.send(data);
        } else if (data) {
            xhr.send(JSON.stringify(data));
        } else {
            xhr.send();
        }
    });
}

/**
 * Show notification message
 * 
 * @param {string} message - Message to show
 * @param {string} type - Notification type (info, success, error, warning)
 * @param {number} duration - Duration in milliseconds
 */
function showNotification(message, type = 'info', duration = 3000) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Add to DOM
    document.body.appendChild(notification);
    
    // Show notification with animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Remove after duration
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, duration);
}

/**
 * Validate a form
 * 
 * @param {HTMLFormElement} form - Form to validate
 * @returns {boolean} Is form valid
 */
function validateForm(form) {
    const inputs = form.querySelectorAll('[required]');
    let valid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            const errorMessage = input.dataset.errorMessage || 'Dieses Feld ist erforderlich';
            
            // Create or update error message
            let errorEl = input.parentNode.querySelector('.error-message');
            if (!errorEl) {
                errorEl = document.createElement('div');
                errorEl.className = 'error-message';
                input.parentNode.appendChild(errorEl);
            }
            errorEl.textContent = errorMessage;
            
            valid = false;
        } else {
            input.classList.remove('error');
            const errorEl = input.parentNode.querySelector('.error-message');
            if (errorEl) {
                errorEl.remove();
            }
        }
    });
    
    return valid;
}

/**
 * Format date to local format
 * 
 * @param {string|Date} date - Date to format
 * @param {boolean} includeTime - Include time in format
 * @returns {string} Formatted date
 */
function formatDate(date, includeTime = false) {
    if (!date) return '';
    
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    
    if (isNaN(dateObj.getTime())) return '';
    
    const options = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    };
    
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    
    return dateObj.toLocaleDateString('de-DE', options);
}

/**
 * Toggle password visibility
 * 
 * @param {HTMLInputElement} input - Password input
 * @param {HTMLElement} toggle - Toggle button
 */
function togglePasswordVisibility(input, toggle) {
    if (input.type === 'password') {
        input.type = 'text';
        toggle.innerHTML = '<i class="bi bi-eye-slash"></i>';
        toggle.title = 'Passwort verbergen';
    } else {
        input.type = 'password';
        toggle.innerHTML = '<i class="bi bi-eye"></i>';
        toggle.title = 'Passwort anzeigen';
    }
}

/**
 * Initialize mobile navigation
 */
function initMobileNav() {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.getElementById('navLinks');
    
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form.validate');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Initialize password toggles
 */
function initPasswordToggles() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        // Add toggle button if not already present
        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('password-toggle')) {
            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'password-toggle';
            toggle.innerHTML = '<i class="bi bi-eye"></i>';
            toggle.title = 'Passwort anzeigen';
            
            input.parentNode.insertBefore(toggle, input.nextSibling);
            
            toggle.addEventListener('click', function() {
                togglePasswordVisibility(input, toggle);
            });
        }
    });
}

/**
 * Document ready function
 * 
 * @param {Function} fn - Function to run when document is ready
 */
function ready(fn) {
    if (document.readyState !== 'loading') {
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}

// Initialize functionality when document is ready
ready(function() {
    initMobileNav();
    initFormValidation();
    initPasswordToggles();
    
    // Add loading indicator to body
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-indicator';
    loadingDiv.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loadingDiv);
    
    // Initialize dropdowns
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        if (toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                dropdown.classList.toggle('active');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    });
});