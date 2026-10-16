// Client-side Validation with Immediate Visual Feedback
// Provides real-time validation for forms with visual feedback

class FormValidator {
    constructor(formId, options = {}) {
        this.form = document.getElementById(formId);
        this.options = {
            validateOnBlur: options.validateOnBlur !== false,
            validateOnInput: options.validateOnInput !== false,
            validateOnChange: options.validateOnChange !== false,
            showErrors: options.showErrors !== false,
            errorClass: options.errorClass || 'error',
            successClass: options.successClass || 'success',
            errorElement: options.errorElement || 'span',
            ...options
        };
        
        this.validators = {};
        this.init();
    }

    init() {
        if (!this.form) return;

        // Add event listeners to all form fields
        const fields = this.form.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            const fieldName = field.name || field.id;
            
            if (this.options.validateOnBlur) {
                field.addEventListener('blur', () => this.validateField(field));
            }
            
            if (this.options.validateOnInput) {
                field.addEventListener('input', () => this.validateField(field));
            }
            
            if (this.options.validateOnChange) {
                field.addEventListener('change', () => this.validateField(field));
            }
        });

        // Validate form on submit
        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Add custom validator for a field
    addValidator(fieldName, validatorFn, errorMessage) {
        this.validators[fieldName] = this.validators[fieldName] || [];
        this.validators[fieldName].push({
            fn: validatorFn,
            message: errorMessage
        });
    }

    // Validate a single field
    validateField(field) {
        const fieldName = field.name || field.id;
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Check required fields
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }

        // Check custom validators
        if (isValid && this.validators[fieldName]) {
            for (const validator of this.validators[fieldName]) {
                if (!validator.fn(value, field)) {
                    isValid = false;
                    errorMessage = validator.message;
                    break;
                }
            }
        }

        // Check HTML5 validation attributes
        if (isValid) {
            if (field.type === 'email' && value && !this.validateEmail(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
            
            if (field.type === 'tel' && value && !this.validatePhone(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number';
            }
            
            if (field.type === 'url' && value && !this.validateUrl(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid URL';
            }
            
            if (field.pattern && value && !new RegExp(field.pattern).test(value)) {
                isValid = false;
                errorMessage = field.dataset.patternMessage || 'Invalid format';
            }
            
            if (field.minLength && value.length < field.minLength) {
                isValid = false;
                errorMessage = `Minimum ${field.minLength} characters required`;
            }
            
            if (field.maxLength && value.length > field.maxLength) {
                isValid = false;
                errorMessage = `Maximum ${field.maxLength} characters allowed`;
            }
            
            if (field.min && value && parseFloat(value) < parseFloat(field.min)) {
                isValid = false;
                errorMessage = `Minimum value is ${field.min}`;
            }
            
            if (field.max && value && parseFloat(value) > parseFloat(field.max)) {
                isValid = false;
                errorMessage = `Maximum value is ${field.max}`;
            }
        }

        // Update visual feedback
        this.updateFieldFeedback(field, isValid, errorMessage);
        
        return isValid;
    }

    // Validate entire form
    validateForm() {
        const fields = this.form.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    // Update visual feedback for a field
    updateFieldFeedback(field, isValid, errorMessage) {
        // Remove existing classes
        field.classList.remove(this.options.errorClass, this.options.successClass);
        
        // Find or create error element
        let errorElement = field.parentElement.querySelector(`.${this.options.errorClass}-message`);
        
        if (!isValid && this.options.showErrors) {
            // Add error class
            field.classList.add(this.options.errorClass);
            
            // Create or update error message
            if (!errorElement) {
                errorElement = document.createElement(this.options.errorElement);
                errorElement.className = `${this.options.errorClass}-message`;
                field.parentElement.appendChild(errorElement);
            }
            errorElement.textContent = errorMessage;
            errorElement.style.display = 'block';
        } else {
            // Add success class if field has value
            if (field.value.trim()) {
                field.classList.add(this.options.successClass);
            }
            
            // Hide error message
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        }
    }

    // Built-in validators
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    validatePhone(phone) {
        // South African phone format: 10 digits starting with 0
        const phoneRegex = /^0\d{9}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }

    validateUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    validatePostalCode(code) {
        // South African postal code: 4 digits
        const postalRegex = /^\d{4}$/;
        return postalRegex.test(code);
    }

    // Reset form validation
    reset() {
        const fields = this.form.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            field.classList.remove(this.options.errorClass, this.options.successClass);
            const errorElement = field.parentElement.querySelector(`.${this.options.errorClass}-message`);
            if (errorElement) {
                errorElement.remove();
            }
        });
    }
}

// Common validation rules
const ValidationRules = {
    email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
    phone: (value) => /^0\d{9}$/.test(value.replace(/\s/g, '')),
    postalCode: (value) => /^\d{4}$/.test(value),
    required: (value) => value.trim().length > 0,
    minLength: (min) => (value) => value.length >= min,
    maxLength: (max) => (value) => value.length <= max,
    pattern: (regex) => (value) => new RegExp(regex).test(value),
    match: (fieldName) => (value, field) => {
        const form = field.form;
        const matchField = form.querySelector(`[name="${fieldName}"], #${fieldName}`);
        return matchField && value === matchField.value;
    },
    date: (value) => !isNaN(Date.parse(value)),
    futureDate: (value) => new Date(value) > new Date(),
    pastDate: (value) => new Date(value) < new Date(),
    min: (min) => (value) => parseFloat(value) >= min,
    max: (max) => (value) => parseFloat(value) <= max,
    integer: (value) => Number.isInteger(parseFloat(value)),
    positive: (value) => parseFloat(value) > 0
};

// Add CSS styles for validation feedback
const validationStyles = document.createElement('style');
validationStyles.textContent = `
    .error {
        border-color: #e74c3c !important;
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1) !important;
    }
    
    .success {
        border-color: #27ae60 !important;
        box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1) !important;
    }
    
    .error-message {
        color: #e74c3c;
        font-size: 12px;
        margin-top: 5px;
        display: none;
    }
    
    .error-message::before {
        content: '⚠ ';
    }
    
    /* Animation for error appearance */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    .error {
        animation: shake 0.3s ease-in-out;
    }
    
    /* Focus styles */
    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }
    
    input:focus.error, select:focus.error, textarea:focus.error {
        border-color: #e74c3c;
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
    }
    
    input:focus.success, select:focus.success, textarea:focus.success {
        border-color: #27ae60;
        box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
    }
`;

document.head.appendChild(validationStyles);

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FormValidator, ValidationRules };
}
