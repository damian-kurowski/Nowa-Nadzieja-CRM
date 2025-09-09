/**
 * Theme Toggle Functionality
 * Handles dark/light theme switching with Turbo compatibility
 */

class ThemeToggle {
    constructor() {
        this.themeToggle = null;
        this.themeIcon = null;
        this.html = document.documentElement;
        this.currentTheme = localStorage.getItem('theme') || 'light';
        
        this.init();
    }
    
    init() {
        // Apply saved theme immediately
        this.applyTheme(this.currentTheme);
        
        // Setup toggle when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupToggle());
        } else {
            this.setupToggle();
        }
        
        // Re-setup after Turbo navigation
        document.addEventListener('turbo:load', () => this.setupToggle());
    }
    
    setupToggle() {
        this.themeToggle = document.getElementById('themeToggle');
        this.themeIcon = document.getElementById('themeIcon');
        
        if (!this.themeToggle || !this.themeIcon) return;
        
        // Remove old event listeners
        this.themeToggle.removeEventListener('click', this.handleToggle);
        
        // Add new event listener
        this.handleToggle = this.handleToggle.bind(this);
        this.themeToggle.addEventListener('click', this.handleToggle);
        
        // Update icon based on current theme
        this.updateIcon();
    }
    
    handleToggle(e) {
        e.preventDefault();
        
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme(this.currentTheme);
        this.updateIcon();
        
        // Save preference
        localStorage.setItem('theme', this.currentTheme);
        
        // Dispatch custom event
        document.dispatchEvent(new CustomEvent('theme:changed', {
            detail: { theme: this.currentTheme }
        }));
    }
    
    applyTheme(theme) {
        if (theme === 'dark') {
            this.html.setAttribute('data-bs-theme', 'dark');
        } else {
            this.html.removeAttribute('data-bs-theme');
        }
    }
    
    updateIcon() {
        if (!this.themeIcon) return;
        
        if (this.currentTheme === 'dark') {
            this.themeIcon.className = 'bi bi-sun';
        } else {
            this.themeIcon.className = 'bi bi-moon';
        }
    }
}

// Initialize theme toggle
const themeToggle = new ThemeToggle();

// Export for global access if needed
window.ThemeToggle = ThemeToggle;