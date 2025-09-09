/**
 * Global error handling and user-friendly error messages
 */
class ErrorHandler {
    constructor() {
        this.setupGlobalErrorHandling();
        this.setupUnhandledRejectionHandler();
        this.enableLogging = document.documentElement.dataset.env === 'dev';
    }

    setupGlobalErrorHandling() {
        window.addEventListener('error', (event) => {
            const error = {
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                stack: event.error?.stack
            };

            this.handleError(error, 'javascript_error');
        });
    }

    setupUnhandledRejectionHandler() {
        window.addEventListener('unhandledrejection', (event) => {
            const error = {
                message: event.reason?.message || 'Unhandled Promise Rejection',
                stack: event.reason?.stack,
                promise: event.reason
            };

            this.handleError(error, 'promise_rejection');
        });
    }

    handleError(error, type = 'unknown') {
        if (this.enableLogging) {
            console.error(`🚨 ${type}:`, error);
        }

        // Show user-friendly message for critical errors
        if (this.isCriticalError(error, type)) {
            this.showUserErrorMessage(error, type);
        }

    }

    isCriticalError(error, type) {
        // Define what constitutes a critical error that should be shown to users
        const criticalPatterns = [
            /network/i,
            /fetch/i,
            /cors/i,
            /unauthorized/i,
            /forbidden/i,
            /server/i
        ];

        const errorText = error.message || error.stack || '';
        return criticalPatterns.some(pattern => pattern.test(errorText)) || 
               type === 'promise_rejection';
    }

    showUserErrorMessage(error, type) {
        const message = this.getUserFriendlyMessage(error, type);
        
        // Check if we have a notification system available
        if (window.MeetingNotifications) {
            // Use existing notification system
            const notifications = new window.MeetingNotifications();
            notifications.showNotification(message, 'danger');
        } else {
            // Fallback to simple alert or custom toast
            this.showSimpleToast(message, 'error');
        }
    }

    getUserFriendlyMessage(error, type) {
        const messages = {
            'network_error': 'Wystąpił problem z połączeniem internetowym. Sprawdź połączenie i spróbuj ponownie.',
            'server_error': 'Wystąpił problem z serwerem. Spróbuj odświeżyć stronę.',
            'permission_error': 'Nie masz uprawnień do wykonania tej operacji.',
            'validation_error': 'Dane formularza zawierają błędy. Sprawdź wprowadzone informacje.',
            'javascript_error': 'Wystąpił nieoczekiwany błąd aplikacji.',
            'promise_rejection': 'Wystąpił problem podczas przetwarzania żądania.'
        };

        // Try to categorize the error
        const errorText = (error.message || '').toLowerCase();
        
        if (errorText.includes('network') || errorText.includes('fetch')) {
            return messages.network_error;
        } else if (errorText.includes('forbidden') || errorText.includes('unauthorized')) {
            return messages.permission_error;
        } else if (errorText.includes('validation') || errorText.includes('invalid')) {
            return messages.validation_error;
        } else if (errorText.includes('server') || errorText.includes('500')) {
            return messages.server_error;
        }

        return messages[type] || messages.javascript_error;
    }

    showSimpleToast(message, type = 'error') {
        // Create a simple toast notification
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type} position-fixed`;
        toast.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border-radius: 8px;
        `;
        
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div class="flex-grow-1">${message}</div>
                <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;

        document.body.appendChild(toast);

        // Auto-remove after 8 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 8000);
    }

    async reportError(error, type) {
        // Rate limiting - don't report same error too frequently
        const errorKey = this.getErrorKey(error);
        if (this.isRecentlyReported(errorKey)) {
            return;
        }

        try {
            await fetch('/api/error-report', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    type,
                    message: error.message,
                    stack: error.stack,
                    filename: error.filename,
                    lineno: error.lineno,
                    colno: error.colno,
                    url: window.location.href,
                    userAgent: navigator.userAgent,
                    timestamp: Date.now()
                })
            });

            this.markAsReported(errorKey);
        } catch (reportError) {
            // Silently fail - error reporting shouldn't break the app
            if (this.enableLogging) {
                console.warn('Failed to report error:', reportError);
            }
        }
    }

    getErrorKey(error) {
        // Create a simple hash of the error for deduplication
        const errorStr = `${error.message || ''}_${error.filename || ''}_${error.lineno || ''}`;
        return btoa(errorStr).slice(0, 16);
    }

    isRecentlyReported(errorKey) {
        const key = `error_reported_${errorKey}`;
        const lastReported = sessionStorage.getItem(key);
        return lastReported && (Date.now() - parseInt(lastReported)) < 600000; // 10 minutes
    }

    markAsReported(errorKey) {
        const key = `error_reported_${errorKey}`;
        sessionStorage.setItem(key, Date.now().toString());
    }

    // Method to manually report errors
    reportCustomError(message, context = {}) {
        const error = {
            message,
            context,
            custom: true
        };
        
        this.handleError(error, 'custom_error');
    }
}

// Initialize error handler
const errorHandler = new ErrorHandler();

// Export for global use
window.errorHandler = errorHandler;