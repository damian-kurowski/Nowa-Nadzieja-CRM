/**
 * Lazy Loading for Images
 * Improves page load performance by loading images only when they're visible
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check if native lazy loading is supported
    if ('loading' in HTMLImageElement.prototype) {
        // Native lazy loading is supported
        const images = document.querySelectorAll('img[data-src]');
        images.forEach(img => {
            img.src = img.dataset.src;
            img.loading = 'lazy';
            if (img.dataset.srcset) {
                img.srcset = img.dataset.srcset;
            }
        });
    } else {
        // Fallback to Intersection Observer
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    if (img.dataset.srcset) {
                        img.srcset = img.dataset.srcset;
                    }
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px', // Load images 50px before they enter viewport
            threshold: 0.01
        });

        // Observe all images with data-src attribute
        const images = document.querySelectorAll('img[data-src]');
        images.forEach(img => imageObserver.observe(img));
    }

    // Add fade-in effect when images are loaded
    const allImages = document.querySelectorAll('img[data-src]');
    allImages.forEach(img => {
        img.addEventListener('load', function() {
            this.classList.add('loaded');
        });
    });
});

// Export for use in other modules
window.lazyLoadImage = function(img) {
    if (img.dataset.src) {
        img.src = img.dataset.src;
        if (img.dataset.srcset) {
            img.srcset = img.dataset.srcset;
        }
        img.classList.add('loaded');
    }
};