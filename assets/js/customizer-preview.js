/**
 * Auto Featured Image - Customizer Preview JavaScript
 */

(function($) {
    'use strict';

    wp.customize = wp.customize || {};

    $(document).ready(function() {
        initCustomizerPreview();
    });

    /**
     * Initialize customizer preview functionality
     */
    function initCustomizerPreview() {
        // Listen for setting changes and update preview
        bindSettingChanges();
        
        // Add preview utilities
        addPreviewUtilities();
    }

    /**
     * Bind setting changes to preview updates
     */
    function bindSettingChanges() {
        // General settings
        wp.customize('wp_afi_settings[enabled]', function(value) {
            value.bind(function(newval) {
                toggleGeneratedImages(newval);
            });
        });

        wp.customize('wp_afi_settings[template_style]', function(value) {
            value.bind(function(newval) {
                updateTemplateStyle(newval);
            });
        });

        wp.customize('wp_afi_settings[aspect_ratio]', function(value) {
            value.bind(function(newval) {
                updateAspectRatio(newval);
            });
        });

        // Style settings
        wp.customize('wp_afi_settings[background_color]', function(value) {
            value.bind(function(newval) {
                updateBackgroundColor(newval);
            });
        });

        wp.customize('wp_afi_settings[text_color]', function(value) {
            value.bind(function(newval) {
                updateTextColor(newval);
            });
        });

        wp.customize('wp_afi_settings[background_gradient]', function(value) {
            value.bind(function(newval) {
                updateBackgroundGradient(newval);
            });
        });

        // Typography settings
        wp.customize('wp_afi_settings[font_family]', function(value) {
            value.bind(function(newval) {
                updateFontFamily(newval);
            });
        });

        wp.customize('wp_afi_settings[font_size]', function(value) {
            value.bind(function(newval) {
                updateFontSize(newval);
            });
        });

        wp.customize('wp_afi_settings[font_weight]', function(value) {
            value.bind(function(newval) {
                updateFontWeight(newval);
            });
        });

        wp.customize('wp_afi_settings[text_align]', function(value) {
            value.bind(function(newval) {
                updateTextAlign(newval);
            });
        });

        wp.customize('wp_afi_settings[text_position]', function(value) {
            value.bind(function(newval) {
                updateTextPosition(newval);
            });
        });
    }

    /**
     * Toggle visibility of generated images
     */
    function toggleGeneratedImages(enabled) {
        const $images = $('.wp-afi-generated-image');
        
        if (enabled) {
            $images.show().addClass('wp-afi-enabled');
        } else {
            $images.hide().removeClass('wp-afi-enabled');
        }
    }

    /**
     * Update template style
     */
    function updateTemplateStyle(newStyle) {
        const $images = $('.wp-afi-generated-image');
        
        // Remove all template classes
        $images.removeClass(function(index, className) {
            return (className.match(/(^|\s)wp-afi-template-\S+/g) || []).join(' ');
        });
        
        // Add new template class
        $images.addClass('wp-afi-template-' + newStyle);
        
        // Trigger style recalculation
        triggerStyleUpdate();
    }

    /**
     * Update aspect ratio
     */
    function updateAspectRatio(newRatio) {
        const $images = $('.wp-afi-generated-image');
        
        // Remove all aspect ratio classes
        $images.removeClass(function(index, className) {
            return (className.match(/(^|\s)wp-afi-aspect-\S+/g) || []).join(' ');
        });
        
        // Add new aspect ratio class
        const aspectClass = 'wp-afi-aspect-' + newRatio.replace(':', '-');
        $images.addClass(aspectClass);
        
        triggerStyleUpdate();
    }

    /**
     * Update background color
     */
    function updateBackgroundColor(newColor) {
        const $images = $('.wp-afi-generated-image');
        
        $images.each(function() {
            const $image = $(this);
            const currentStyle = $image.attr('style') || '';
            
            // Only update if no gradient is set
            const hasGradient = currentStyle.includes('background:') && 
                               currentStyle.includes('gradient');
            
            if (!hasGradient) {
                updateImageStyle($image, 'background-color', newColor);
            }
        });
    }

    /**
     * Update text color
     */
    function updateTextColor(newColor) {
        const $titles = $('.wp-afi-title');
        updateElementStyle($titles, 'color', newColor);
    }

    /**
     * Update background gradient
     */
    function updateBackgroundGradient(newGradient) {
        const $images = $('.wp-afi-generated-image');
        
        $images.each(function() {
            const $image = $(this);
            
            if (newGradient.trim()) {
                updateImageStyle($image, 'background', newGradient);
            } else {
                // Fallback to background color
                const bgColor = wp.customize('wp_afi_settings[background_color]')();
                updateImageStyle($image, 'background-color', bgColor);
                removeImageStyle($image, 'background');
            }
        });
    }

    /**
     * Update font family
     */
    function updateFontFamily(newFamily) {
        const $titles = $('.wp-afi-title');
        updateElementStyle($titles, 'font-family', newFamily);
    }

    /**
     * Update font size
     */
    function updateFontSize(newSize) {
        const $titles = $('.wp-afi-title');
        updateElementStyle($titles, 'font-size', newSize + 'rem');
    }

    /**
     * Update font weight
     */
    function updateFontWeight(newWeight) {
        const $titles = $('.wp-afi-title');
        updateElementStyle($titles, 'font-weight', newWeight);
    }

    /**
     * Update text alignment
     */
    function updateTextAlign(newAlign) {
        const $images = $('.wp-afi-generated-image');
        
        // Remove all alignment classes
        $images.removeClass('wp-afi-align-left wp-afi-align-center wp-afi-align-right');
        
        // Add new alignment class
        $images.addClass('wp-afi-align-' + newAlign);
    }

    /**
     * Update text position
     */
    function updateTextPosition(newPosition) {
        const $images = $('.wp-afi-generated-image');
        
        // Remove all position classes
        $images.removeClass('wp-afi-position-top wp-afi-position-center wp-afi-position-bottom');
        
        // Add new position class
        $images.addClass('wp-afi-position-' + newPosition);
    }

    /**
     * Update element style
     */
    function updateElementStyle($elements, property, value) {
        $elements.each(function() {
            $(this).css(property, value);
        });
    }

    /**
     * Update image container style
     */
    function updateImageStyle($image, property, value) {
        const currentStyle = $image.attr('style') || '';
        const styleObj = parseStyle(currentStyle);
        
        styleObj[property] = value;
        
        const newStyle = buildStyleString(styleObj);
        $image.attr('style', newStyle);
    }

    /**
     * Remove image container style
     */
    function removeImageStyle($image, property) {
        const currentStyle = $image.attr('style') || '';
        const styleObj = parseStyle(currentStyle);
        
        delete styleObj[property];
        
        const newStyle = buildStyleString(styleObj);
        $image.attr('style', newStyle);
    }

    /**
     * Parse style string into object
     */
    function parseStyle(styleString) {
        const styleObj = {};
        
        if (styleString) {
            const styles = styleString.split(';');
            styles.forEach(function(style) {
                const parts = style.split(':');
                if (parts.length === 2) {
                    const property = parts[0].trim();
                    const value = parts[1].trim();
                    if (property && value) {
                        styleObj[property] = value;
                    }
                }
            });
        }
        
        return styleObj;
    }

    /**
     * Build style string from object
     */
    function buildStyleString(styleObj) {
        const styles = [];
        
        Object.keys(styleObj).forEach(function(property) {
            if (styleObj[property]) {
                styles.push(property + ': ' + styleObj[property]);
            }
        });
        
        return styles.join('; ');
    }

    /**
     * Trigger style update
     */
    function triggerStyleUpdate() {
        // Force repaint
        const $images = $('.wp-afi-generated-image');
        $images.each(function() {
            const $image = $(this);
            $image.hide().show();
        });
        
        // Trigger custom event
        $(document).trigger('wp-afi-styles-updated');
    }

    /**
     * Add preview utilities
     */
    function addPreviewUtilities() {
        // Add preview indicator
        if ($('.wp-afi-generated-image').length > 0) {
            addPreviewIndicator();
        }
        
        // Highlight images on hover in customizer
        addCustomizerHighlight();
    }

    /**
     * Add preview indicator
     */
    function addPreviewIndicator() {
        const $indicator = $('<div class="wp-afi-preview-indicator">Auto Featured Image Preview</div>');
        
        $indicator.css({
            'position': 'fixed',
            'top': '10px',
            'right': '10px',
            'background': '#0073aa',
            'color': '#fff',
            'padding': '8px 12px',
            'border-radius': '4px',
            'font-size': '12px',
            'z-index': '999999',
            'box-shadow': '0 2px 5px rgba(0,0,0,0.2)'
        });
        
        $('body').append($indicator);
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            $indicator.fadeOut();
        }, 3000);
    }

    /**
     * Add customizer highlight functionality
     */
    function addCustomizerHighlight() {
        $('.wp-afi-generated-image').on('mouseenter', function() {
            $(this).css('outline', '2px solid #0073aa');
        }).on('mouseleave', function() {
            $(this).css('outline', '');
        });
    }

    /**
     * Refresh all generated images
     */
    function refreshGeneratedImages() {
        // This would typically regenerate images with current settings
        // For now, we'll just trigger a style update
        triggerStyleUpdate();
        
        // Re-run any frontend JavaScript
        if (window.wpAutoFeaturedImage && window.wpAutoFeaturedImage.reinitialize) {
            window.wpAutoFeaturedImage.reinitialize();
        }
    }

    // Expose utilities for other scripts
    window.wpAfiCustomizerPreview = {
        refresh: refreshGeneratedImages,
        updateStyle: updateElementStyle,
        triggerUpdate: triggerStyleUpdate
    };

    // Listen for customizer ready event
    wp.customize.bind('ready', function() {
        // Customizer is ready, perform any additional setup
        console.log('Auto Featured Image customizer preview ready');
    });

})(jQuery);
