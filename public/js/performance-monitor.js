/**
 * Simple performance monitoring for client-side operations
 */
class PerformanceMonitor {
    constructor() {
        this.metrics = new Map();
        this.enableLogging = document.documentElement.dataset.env === 'dev';
        this.init();
    }

    init() {
        // Monitor page load performance
        if (window.performance && window.performance.timing) {
            this.trackPageLoad();
        }

        // Monitor long tasks (if supported)
        this.observeLongTasks();

        // Monitor memory usage periodically (if supported)
        this.monitorMemory();
    }

    trackPageLoad() {
        window.addEventListener('load', () => {
            const timing = window.performance.timing;
            const pageLoadTime = timing.loadEventEnd - timing.navigationStart;
            const domReadyTime = timing.domContentLoadedEventEnd - timing.navigationStart;
            const firstPaintTime = this.getFirstPaintTime();

            this.recordMetric('page_load_time', pageLoadTime);
            this.recordMetric('dom_ready_time', domReadyTime);
            
            if (firstPaintTime) {
                this.recordMetric('first_paint_time', firstPaintTime);
            }

        });
    }

    getFirstPaintTime() {
        if (window.performance && window.performance.getEntriesByType) {
            const paintEntries = window.performance.getEntriesByType('paint');
            const firstPaint = paintEntries.find(entry => entry.name === 'first-contentful-paint');
            return firstPaint ? Math.round(firstPaint.startTime) : null;
        }
        return null;
    }

    observeLongTasks() {
        if ('PerformanceObserver' in window) {
            try {
                const observer = new PerformanceObserver((list) => {
                    list.getEntries().forEach((entry) => {
                        if (entry.duration > 50) { // Tasks longer than 50ms
                            this.recordMetric('long_task', entry.duration);
                            
                        }
                    });
                });
                observer.observe({ entryTypes: ['longtask'] });
            } catch (e) {
                // PerformanceObserver not fully supported
            }
        }
    }

    monitorMemory() {
        if (window.performance && window.performance.memory) {
            setInterval(() => {
                const memory = window.performance.memory;
                const usedMB = Math.round(memory.usedJSHeapSize / 1048576);
                const limitMB = Math.round(memory.jsHeapSizeLimit / 1048576);
                
                this.recordMetric('memory_usage_mb', usedMB);
                
                // Warn if memory usage is high
                const memoryUsagePercent = (usedMB / limitMB) * 100;
                if (memoryUsagePercent > 80) {
                }
            }, 60000); // Check every minute
        }
    }

    recordMetric(name, value) {
        if (!this.metrics.has(name)) {
            this.metrics.set(name, []);
        }
        
        const metrics = this.metrics.get(name);
        metrics.push({
            value,
            timestamp: Date.now()
        });
        
        // Keep only last 100 metrics per type
        if (metrics.length > 100) {
            metrics.shift();
        }
    }

    async reportSlowPerformance(type, value) {
        // Only report in production and not too frequently
        if (this.enableLogging || this.isRecentlyReported(type)) {
            return;
        }

        try {
            await fetch('/api/performance-report', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    type,
                    value,
                    url: window.location.pathname,
                    userAgent: navigator.userAgent,
                    timestamp: Date.now()
                })
            });
            
            // Mark as reported
            this.markAsReported(type);
        } catch (error) {
            // Silently fail - performance reporting shouldn't break the app
        }
    }

    isRecentlyReported(type) {
        const key = `perf_reported_${type}`;
        const lastReported = sessionStorage.getItem(key);
        return lastReported && (Date.now() - parseInt(lastReported)) < 300000; // 5 minutes
    }

    markAsReported(type) {
        const key = `perf_reported_${type}`;
        sessionStorage.setItem(key, Date.now().toString());
    }

    // Public method to track custom metrics
    trackCustomMetric(name, startTime) {
        const duration = Date.now() - startTime;
        this.recordMetric(name, duration);
        
        
        return duration;
    }

    // Get performance summary
    getPerformanceSummary() {
        const summary = {};
        
        this.metrics.forEach((values, name) => {
            if (values.length > 0) {
                const recent = values.slice(-10); // Last 10 measurements
                const avg = recent.reduce((sum, m) => sum + m.value, 0) / recent.length;
                const max = Math.max(...recent.map(m => m.value));
                const min = Math.min(...recent.map(m => m.value));
                
                summary[name] = {
                    average: Math.round(avg),
                    max: Math.round(max),
                    min: Math.round(min),
                    count: values.length
                };
            }
        });
        
        return summary;
    }
}

// Initialize performance monitoring
const performanceMonitor = new PerformanceMonitor();

// Export for global use
window.performanceMonitor = performanceMonitor;