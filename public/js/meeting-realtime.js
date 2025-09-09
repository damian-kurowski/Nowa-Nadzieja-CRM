/**
 * Real-time notifications for meeting management
 */
class MeetingNotifications {
    constructor() {
        this.notificationContainer = null;
        this.checkInterval = null;
        this.lastCheck = Date.now();
        this.csrfTokens = {
            meeting: null,
            ajax: null
        };
        this.initializeNotifications();
    }

    async initializeNotifications() {
        // Create notification container if it doesn't exist
        if (!document.getElementById('meeting-notifications')) {
            this.createNotificationContainer();
        }
        this.notificationContainer = document.getElementById('meeting-notifications');
        
        // Get CSRF tokens first
        await this.refreshCsrfTokens();
        
        // Start checking for updates every 30 seconds
        this.startPeriodicCheck();
        
        // Check immediately
        this.checkForUpdates();
    }

    async refreshCsrfTokens() {
        const meetingId = this.getMeetingId();
        if (!meetingId) return;

        try {
            const response = await fetch(`/zebranie-oddzialu/${meetingId}/api/csrf-tokens`);
            if (response.ok) {
                const data = await response.json();
                this.csrfTokens.meeting = data.meeting_token;
                this.csrfTokens.ajax = data.ajax_token;
            }
        } catch (error) {
            // Silent fail
        }
    }

    getRequestHeaders() {
        return {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': this.csrfTokens.ajax || ''
        };
    }

    createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'meeting-notifications';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1055';
        document.body.appendChild(container);
    }

    startPeriodicCheck() {
        // Clear any existing interval
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
        
        // Check every 30 seconds
        this.checkInterval = setInterval(() => {
            this.checkForUpdates();
        }, 30000);
    }

    async checkForUpdates() {
        const meetingId = this.getMeetingId();
        if (!meetingId) return;

        try {
            const response = await fetch(`/zebranie-oddzialu/${meetingId}/api/status`, {
                method: 'GET',
                headers: this.getRequestHeaders()
            });

            if (response.ok) {
                const data = await response.json();
                this.handleStatusUpdate(data);
            }
        } catch (error) {
            // Silent fail
        }
    }

    handleStatusUpdate(data) {
        // Check for new documents awaiting signature
        if (data.awaiting_documents && data.awaiting_documents.length > 0) {
            this.showDocumentNotification(data.awaiting_documents);
        }

        // Check for meeting status changes
        if (data.status_changed) {
            this.showStatusNotification(data.status, data.message);
        }

        // Update progress indicators
        this.updateProgressIndicators(data.progress);
    }

    showDocumentNotification(documents) {
        const count = documents.length;
        const message = count === 1 
            ? `Nowy dokument oczekuje na Twój podpis: ${documents[0].title}`
            : `${count} dokumentów oczekuje na Twój podpis`;

        this.showNotification(message, 'info', {
            action: {
                text: 'Zobacz dokumenty',
                onClick: () => this.scrollToDocuments()
            }
        });
    }

    showStatusNotification(status, message) {
        const alertType = status === 'completed' ? 'success' : 'info';
        this.showNotification(message, alertType);
    }

    showNotification(message, type = 'info', options = {}) {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${this.getIconForType(type)} me-2"></i>
                    ${message}
                </div>
                ${options.action ? `
                    <button type="button" class="btn btn-outline-light btn-sm me-2" onclick="${options.action.onClick}">
                        ${options.action.text}
                    </button>
                ` : ''}
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        this.notificationContainer.appendChild(toast);
        
        // Initialize Bootstrap toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 10000
        });
        bsToast.show();

        // Remove from DOM after hiding
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    updateProgressIndicators(progress) {
        // Update progress bars and step indicators
        const progressBars = document.querySelectorAll('.meeting-progress-bar');
        progressBars.forEach(bar => {
            const percentage = (progress.completed_steps / progress.total_steps) * 100;
            bar.style.width = `${percentage}%`;
            bar.setAttribute('aria-valuenow', percentage);
        });

        // Update step indicators
        const stepIndicators = document.querySelectorAll('[data-step]');
        stepIndicators.forEach(indicator => {
            const stepNumber = parseInt(indicator.dataset.step);
            if (stepNumber <= progress.completed_steps) {
                indicator.classList.add('completed');
                indicator.classList.remove('active');
            } else if (stepNumber === progress.completed_steps + 1) {
                indicator.classList.add('active');
                indicator.classList.remove('completed');
            } else {
                indicator.classList.remove('active', 'completed');
            }
        });
    }

    scrollToDocuments() {
        const documentsSection = document.getElementById('awaiting-documents');
        if (documentsSection) {
            documentsSection.scrollIntoView({ behavior: 'smooth' });
            documentsSection.classList.add('highlight');
            setTimeout(() => documentsSection.classList.remove('highlight'), 2000);
        }
    }

    getIconForType(type) {
        const icons = {
            'info': 'info-circle',
            'success': 'check-circle',
            'warning': 'exclamation-triangle',
            'danger': 'exclamation-circle'
        };
        return icons[type] || 'info-circle';
    }

    getMeetingId() {
        // Extract meeting ID from URL or data attribute
        const urlMatch = window.location.pathname.match(/\/zebranie-oddzialu\/(\d+)/);
        if (urlMatch) {
            return urlMatch[1];
        }
        
        const meetingElement = document.querySelector('[data-meeting-id]');
        return meetingElement ? meetingElement.dataset.meetingId : null;
    }

    destroy() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
    }
}

// Progress Bar Component
class MeetingProgressBar {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            steps: ['Obserwator', 'Protokolant', 'Prowadzący', 'Stanowiska', 'Zakończenie'],
            currentStep: 0,
            ...options
        };
        this.init();
    }

    init() {
        this.render();
        this.updateProgress();
    }

    render() {
        const stepsHTML = this.options.steps.map((step, index) => `
            <div class="step-indicator" data-step="${index + 1}">
                <div class="step-circle">
                    <span class="step-number">${index + 1}</span>
                    <i class="fas fa-check step-check"></i>
                </div>
                <div class="step-label">${step}</div>
            </div>
        `).join('');

        this.element.innerHTML = `
            <div class="meeting-progress-container">
                <div class="progress mb-3">
                    <div class="progress-bar meeting-progress-bar bg-primary" role="progressbar" 
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
                <div class="steps-container d-flex justify-content-between">
                    ${stepsHTML}
                </div>
            </div>
        `;
    }

    updateProgress() {
        const percentage = (this.options.currentStep / this.options.steps.length) * 100;
        const progressBar = this.element.querySelector('.meeting-progress-bar');
        const stepIndicators = this.element.querySelectorAll('.step-indicator');

        // Update progress bar
        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
            progressBar.setAttribute('aria-valuenow', percentage);
        }

        // Update step indicators
        stepIndicators.forEach((indicator, index) => {
            const stepNumber = index + 1;
            if (stepNumber <= this.options.currentStep) {
                indicator.classList.add('completed');
                indicator.classList.remove('active');
            } else if (stepNumber === this.options.currentStep + 1) {
                indicator.classList.add('active');
                indicator.classList.remove('completed');
            } else {
                indicator.classList.remove('active', 'completed');
            }
        });
    }

    setCurrentStep(step) {
        this.options.currentStep = step;
        this.updateProgress();
    }
}

// Auto-refresh for dynamic content
class MeetingAutoRefresh {
    constructor(selector, interval = 60000) {
        this.elements = document.querySelectorAll(selector);
        this.interval = interval;
        this.refreshTimer = null;
        this.init();
    }

    init() {
        if (this.elements.length === 0) return;
        
        this.startAutoRefresh();
        
        // Pause refresh when user is actively working
        document.addEventListener('click', () => this.resetTimer());
        document.addEventListener('keypress', () => this.resetTimer());
    }

    startAutoRefresh() {
        this.refreshTimer = setInterval(() => {
            this.refreshContent();
        }, this.interval);
    }

    resetTimer() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.startAutoRefresh();
        }
    }

    async refreshContent() {
        const meetingId = this.getMeetingId();
        if (!meetingId) return;

        try {
            const response = await fetch(`/zebranie-oddzialu/${meetingId}/api/refresh`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.updateElements(data);
            }
        } catch (error) {
            // Silent fail
        }
    }

    updateElements(data) {
        this.elements.forEach(element => {
            const section = element.dataset.section;
            if (data[section]) {
                element.innerHTML = data[section];
            }
        });
    }

    getMeetingId() {
        const urlMatch = window.location.pathname.match(/\/zebranie-oddzialu\/(\d+)/);
        return urlMatch ? urlMatch[1] : null;
    }

    destroy() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize real-time notifications
    const meetingNotifications = new MeetingNotifications();
    
    // Initialize progress bars
    const progressElements = document.querySelectorAll('[data-meeting-progress]');
    progressElements.forEach(element => {
        const currentStep = parseInt(element.dataset.currentStep) || 0;
        new MeetingProgressBar(element, { currentStep });
    });
    
    // Initialize auto-refresh for dynamic sections
    const autoRefresh = new MeetingAutoRefresh('[data-auto-refresh]', 45000);
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        meetingNotifications.destroy();
        autoRefresh.destroy();
    });
});