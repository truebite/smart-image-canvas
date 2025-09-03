/**
 * Auto Featured Image - Frontend JavaScript
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initAutoFeaturedImages();
    });

    /**
     * Initialize auto featured images
     */
    function initAutoFeaturedImages() {
        // Adjust font sizes based on content length
        adjustFontSizes();
        
        // Add lazy loading intersection observer
        if ('IntersectionObserver' in window) {
            initLazyLoading();
        }
        
        // Handle responsive font sizes
        handleResponsiveFonts();
        
        // Add accessibility improvements
        enhanceAccessibility();
    }

    /**
     * Adjust font sizes based on title length
     */
    function adjustFontSizes() {
        $('.wp-afi-generated-image').each(function() {
            const $image = $(this);
            const $title = $image.find('.wp-afi-title');
            
            if ($title.length === 0) return;
            
            const titleText = $title.text();
            const titleLength = titleText.length;
            const originalFontSize = parseFloat($title.css('font-size'));
            
            let scaleFactor = 1;
            
            // Adjust scale based on title length
            if (titleLength > 80) {
                scaleFactor = 0.7;
            } else if (titleLength > 60) {
                scaleFactor = 0.8;
            } else if (titleLength > 40) {
                scaleFactor = 0.9;
            } else if (titleLength < 15) {
                scaleFactor = 1.1;
            }
            
            // Apply the adjusted font size
            const newFontSize = originalFontSize * scaleFactor;
            $title.css('font-size', newFontSize + 'px');
            
            // Add word break for very long words
            if (titleText.includes(' ')) {
                const words = titleText.split(' ');
                const hasLongWord = words.some(word => word.length > 15);
                
                if (hasLongWord) {
                    $title.css('word-break', 'break-word');
                }
            }
        });
    }

    /**
     * Initialize lazy loading with intersection observer
     */
    function initLazyLoading() {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const $image = $(entry.target);
                    
                    // Add loaded class for animations
                    $image.addClass('wp-afi-loaded');
                    
                    // Stop observing this element
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });

        // Observe all generated images
        $('.wp-afi-generated-image').each(function() {
            observer.observe(this);
        });
    }

    /**
     * Handle responsive font sizes
     */
    function handleResponsiveFonts() {
        let resizeTimer;
        
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                adjustFontSizes();
            }, 250);
        });
    }

    /**
     * Enhance accessibility
     */
    function enhanceAccessibility() {
        $('.wp-afi-generated-image').each(function() {
            const $image = $(this);
            const $title = $image.find('.wp-afi-title');
            
            if ($title.length === 0) return;
            
            const titleText = $title.text();
            
            // Ensure proper ARIA attributes
            $image.attr({
                'role': 'img',
                'aria-label': titleText,
                'tabindex': '0'
            });
            
            // Add keyboard navigation
            $image.on('keydown', function(e) {
                if (e.which === 13 || e.which === 32) { // Enter or Space
                    e.preventDefault();
                    
                    // Find associated link and trigger click
                    const $link = $image.closest('a');
                    if ($link.length) {
                        $link[0].click();
                    }
                }
            });
        });
    }

    /**
     * Utility function to check if element is in viewport
     */
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Add hover effects for desktop
     */
    function addHoverEffects() {
        if (window.matchMedia('(hover: hover)').matches) {
            $('.wp-afi-generated-image').hover(
                function() {
                    $(this).addClass('wp-afi-hover');
                },
                function() {
                    $(this).removeClass('wp-afi-hover');
                }
            );
        }
    }

    /**
     * Handle high contrast mode
     */
    function handleHighContrast() {
        if (window.matchMedia('(prefers-contrast: high)').matches) {
            $('body').addClass('wp-afi-high-contrast');
        }
    }

    /**
     * Handle reduced motion preference
     */
    function handleReducedMotion() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            $('body').addClass('wp-afi-reduced-motion');
        }
    }

    // Initialize additional features
    addHoverEffects();
    handleHighContrast();
    handleReducedMotion();

    // Re-initialize when new content is loaded (for AJAX-loaded content)
    $(document).on('wp-afi-content-loaded', function() {
        initAutoFeaturedImages();
    });

    // Expose some functions globally for theme integration
    window.wpAutoFeaturedImage = {
        adjustFontSizes: adjustFontSizes,
        reinitialize: initAutoFeaturedImages
    };

})(jQuery);
