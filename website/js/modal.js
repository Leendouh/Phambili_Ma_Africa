// Modal Confirmation Dialogs Utility
// Provides reusable modal dialogs for confirmations, alerts, and custom content

class Modal {
    constructor(options = {}) {
        this.options = {
            title: options.title || 'Confirm',
            message: options.message || 'Are you sure?',
            type: options.type || 'confirm', // confirm, alert, info, warning, danger, success
            confirmText: options.confirmText || 'Confirm',
            cancelText: options.cancelText || 'Cancel',
            showCancel: options.showCancel !== false,
            closeOnOverlay: options.closeOnOverlay !== false,
            closeOnEscape: options.closeOnEscape !== false,
            onConfirm: options.onConfirm || null,
            onCancel: options.onCancel || null,
            onClose: options.onClose || null,
            customContent: options.customContent || null,
            ...options
        };
        
        this.modalElement = null;
    }

    // Show the modal
    show() {
        this.createModal();
        document.body.appendChild(this.modalElement);
        
        // Trigger animation
        setTimeout(() => {
            this.modalElement.classList.add('active');
        }, 10);
        
        // Focus confirm button
        const confirmBtn = this.modalElement.querySelector('.modal-confirm-btn');
        if (confirmBtn) {
            setTimeout(() => confirmBtn.focus(), 100);
        }
        
        return this;
    }

    // Hide the modal
    hide() {
        if (this.modalElement) {
            this.modalElement.classList.remove('active');
            
            setTimeout(() => {
                if (this.modalElement && this.modalElement.parentNode) {
                    this.modalElement.parentNode.removeChild(this.modalElement);
                }
                if (this.options.onClose) {
                    this.options.onClose();
                }
            }, 300);
        }
        
        return this;
    }

    // Set loading state on confirm button
    setLoading(isLoading) {
        const confirmBtn = this.modalElement.querySelector('.modal-confirm-btn');
        if (confirmBtn) {
            if (isLoading) {
                confirmBtn.classList.add('loading');
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner"></span> Loading...';
            } else {
                confirmBtn.classList.remove('loading');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = this.escapeHtml(this.options.confirmText);
            }
        }
        return this;
    }

    // Create the modal element
    createModal() {
        // Create modal container
        this.modalElement = document.createElement('div');
        this.modalElement.className = `modal modal-${this.options.type}`;
        this.modalElement.setAttribute('role', 'dialog');
        this.modalElement.setAttribute('aria-modal', 'true');
        this.modalElement.setAttribute('aria-labelledby', 'modal-title');
        
        // Build modal content
        let content = `
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modal-title">${this.escapeHtml(this.options.title)}</h2>
                    <button class="modal-close-btn" aria-label="Close modal">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
        `;
        
        // Add icon based on type
        content += this.getIconHtml();
        
        // Add message or custom content
        if (this.options.customContent) {
            content += this.options.customContent;
        } else {
            content += `<p class="modal-message">${this.escapeHtml(this.options.message)}</p>`;
        }
        
        content += `
                </div>
                <div class="modal-footer">
        `;
        
        // Add cancel button if needed
        if (this.options.showCancel) {
            content += `
                <button class="modal-cancel-btn">${this.escapeHtml(this.options.cancelText)}</button>
            `;
        }
        
        // Add confirm button
        content += `
                <button class="modal-confirm-btn">${this.escapeHtml(this.options.confirmText)}</button>
            </div>
        </div>
        `;
        
        this.modalElement.innerHTML = content;
        
        // Add event listeners
        this.addEventListeners();
    }

    // Get icon HTML based on modal type
    getIconHtml() {
        const icons = {
            confirm: '<div class="modal-icon modal-icon-confirm"><i class="fas fa-question-circle"></i></div>',
            alert: '<div class="modal-icon modal-icon-alert"><i class="fas fa-exclamation-triangle"></i></div>',
            info: '<div class="modal-icon modal-icon-info"><i class="fas fa-info-circle"></i></div>',
            warning: '<div class="modal-icon modal-icon-warning"><i class="fas fa-exclamation-circle"></i></div>',
            danger: '<div class="modal-icon modal-icon-danger"><i class="fas fa-times-circle"></i></div>',
            success: '<div class="modal-icon modal-icon-success"><i class="fas fa-check-circle"></i></div>'
        };
        
        return icons[this.options.type] || icons.confirm;
    }

    // Add event listeners to modal
    addEventListeners() {
        // Close button
        const closeBtn = this.modalElement.querySelector('.modal-close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.hide();
                if (this.options.onCancel) {
                    this.options.onCancel();
                }
            });
        }
        
        // Cancel button
        const cancelBtn = this.modalElement.querySelector('.modal-cancel-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.hide();
                if (this.options.onCancel) {
                    this.options.onCancel();
                }
            });
        }
        
        // Confirm button
        const confirmBtn = this.modalElement.querySelector('.modal-confirm-btn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                if (this.options.onConfirm) {
                    const result = this.options.onConfirm();
                    if (result !== false) {
                        this.hide();
                    }
                } else {
                    this.hide();
                }
            });
        }
        
        // Overlay click
        if (this.options.closeOnOverlay) {
            const overlay = this.modalElement.querySelector('.modal-overlay');
            if (overlay) {
                overlay.addEventListener('click', () => {
                    this.hide();
                    if (this.options.onCancel) {
                        this.options.onCancel();
                    }
                });
            }
        }
        
        // Keyboard support
        if (this.options.closeOnEscape) {
            const keyHandler = (e) => {
                if (!this.modalElement.classList.contains('active')) return;
                
                if (e.key === 'Escape') {
                    this.hide();
                    if (this.options.onCancel) {
                        this.options.onCancel();
                    }
                    document.removeEventListener('keydown', keyHandler);
                }
                
                // Enter key to confirm
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (confirmBtn && this.options.onConfirm) {
                        const result = this.options.onConfirm();
                        if (result !== false) {
                            this.hide();
                        }
                    }
                }
            };
            document.addEventListener('keydown', keyHandler);
        }
    }

    // Escape HTML to prevent XSS
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Update modal content dynamically
    update(options) {
        this.options = { ...this.options, ...options };
        if (this.modalElement) {
            this.modalElement.remove();
        }
        this.show();
        return this;
    }
}

// Convenience functions for common modal types
const ModalDialog = {
    confirm(message, onConfirm, options = {}) {
        return new Modal({
            title: options.title || 'Confirm',
            message: message,
            type: 'confirm',
            confirmText: options.confirmText || 'Confirm',
            cancelText: options.cancelText || 'Cancel',
            onConfirm: onConfirm,
            ...options
        }).show();
    },
    
    alert(message, options = {}) {
        return new Modal({
            title: options.title || 'Alert',
            message: message,
            type: 'alert',
            showCancel: false,
            confirmText: options.confirmText || 'OK',
            ...options
        }).show();
    },
    
    info(message, options = {}) {
        return new Modal({
            title: options.title || 'Information',
            message: message,
            type: 'info',
            showCancel: false,
            confirmText: options.confirmText || 'OK',
            ...options
        }).show();
    },
    
    warning(message, onConfirm, options = {}) {
        return new Modal({
            title: options.title || 'Warning',
            message: message,
            type: 'warning',
            confirmText: options.confirmText || 'Proceed',
            cancelText: options.cancelText || 'Cancel',
            onConfirm: onConfirm,
            ...options
        }).show();
    },
    
    danger(message, onConfirm, options = {}) {
        return new Modal({
            title: options.title || 'Are you sure?',
            message: message,
            type: 'danger',
            confirmText: options.confirmText || 'Delete',
            cancelText: options.cancelText || 'Cancel',
            onConfirm: onConfirm,
            ...options
        }).show();
    },
    
    success(message, options = {}) {
        return new Modal({
            title: options.title || 'Success',
            message: message,
            type: 'success',
            showCancel: false,
            confirmText: options.confirmText || 'OK',
            ...options
        }).show();
    },
    
    custom(options) {
        return new Modal(options).show();
    }
};

// Add CSS styles for modals
const modalStyles = document.createElement('style');
modalStyles.textContent = `
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    
    .modal.active {
        opacity: 1;
        visibility: visible;
    }
    
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .modal.active .modal-overlay {
        opacity: 1;
    }
    
    .modal-content {
        position: relative;
        background: white;
        border-radius: 16px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transform: scale(0.9) translateY(20px);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    
    .modal.active .modal-content {
        transform: scale(1) translateY(0);
    }
    
    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 600;
        color: #111827;
    }
    
    .modal-close-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        color: #6b7280;
        transition: all 0.2s ease;
    }
    
    .modal-close-btn:hover {
        background: #f3f4f6;
        color: #111827;
    }
    
    .modal-body {
        padding: 24px;
    }
    
    .modal-icon {
        font-size: 64px;
        text-align: center;
        margin-bottom: 20px;
        animation: iconBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
    
    @keyframes iconBounce {
        0% { transform: scale(0); opacity: 0; }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .modal-icon-confirm { color: #3b82f6; }
    .modal-icon-alert { color: #f59e0b; }
    .modal-icon-info { color: #3b82f6; }
    .modal-icon-warning { color: #f59e0b; }
    .modal-icon-danger { color: #ef4444; }
    .modal-icon-success { color: #10b981; }
    
    .modal-message {
        margin: 0;
        font-size: 16px;
        line-height: 1.6;
        color: #4b5563;
        text-align: center;
    }
    
    .modal-footer {
        display: flex;
        gap: 12px;
        padding: 20px 24px;
        border-top: 1px solid #e5e7eb;
        justify-content: flex-end;
    }
    
    .modal-cancel-btn,
    .modal-confirm-btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .modal-cancel-btn {
        background: white;
        color: #374151;
        border: 2px solid #e5e7eb;
    }
    
    .modal-cancel-btn:hover {
        background: #f9fafb;
        border-color: #d1d5db;
        transform: translateY(-1px);
    }
    
    .modal-cancel-btn:active {
        transform: translateY(0);
    }
    
    .modal-confirm-btn {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);
    }
    
    .modal-confirm-btn:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.5);
        transform: translateY(-1px);
    }
    
    .modal-confirm-btn:active {
        transform: translateY(0);
    }
    
    .modal-confirm-btn.loading {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    .modal-confirm-btn.loading .spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-right: 8px;
        vertical-align: middle;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .modal-danger .modal-confirm-btn {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.4);
    }
    
    .modal-danger .modal-confirm-btn:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        box-shadow: 0 6px 8px -1px rgba(239, 68, 68, 0.5);
    }
    
    .modal-success .modal-confirm-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4);
    }
    
    .modal-success .modal-confirm-btn:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        box-shadow: 0 6px 8px -1px rgba(16, 185, 129, 0.5);
    }
    
    @media (max-width: 640px) {
        .modal-content {
            width: 95%;
            margin: 10px;
        }
        
        .modal-header,
        .modal-body,
        .modal-footer {
            padding: 16px;
        }
        
        .modal-footer {
            flex-direction: column;
        }
        
        .modal-cancel-btn,
        .modal-confirm-btn {
            width: 100%;
        }
    }
`;

document.head.appendChild(modalStyles);

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { Modal, ModalDialog };
}
