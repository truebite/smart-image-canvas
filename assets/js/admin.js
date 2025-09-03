/**
 * Auto Featured Image - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initAdminFeatures();
    });

    /**
     * Initialize admin features
     */
    function initAdminFeatures() {
        initColorPickers();
        initLivePreview();
        initFormValidation();
        initTooltips();
        initDependencyControls();
    }

    /**
     * Initialize color pickers
     */
    function initColorPickers() {
        if ($.fn.wpColorPicker) {
            $('.wp-afi-color-picker').wpColorPicker({
                change: function(event, ui) {
                    updateLivePreview();
                },
                clear: function() {
                    updateLivePreview();
                }
            });
        }
    }

    /**
     * Initialize live preview functionality
     */
    function initLivePreview() {
        // Update preview when form fields change
        $('.wp-afi-field').on('change input', debounce(updateLivePreview, 500));
        
        // Refresh preview button
        $('#wp-afi-refresh-preview').on('click', function(e) {
            e.preventDefault();
            updateLivePreview(true);
        });
    }

    /**
     * Update live preview
     */
    function updateLivePreview(force = false) {
        const $container = $('#wp-afi-preview-container');
        const $button = $('#wp-afi-refresh-preview');
        
        if (!$container.length) return;
        
        // Show loading state
        $container.addClass('wp-afi-loading');
        $button.prop('disabled', true);
        
        // Collect form data
        const formData = $('#wp-afi-settings-form').serializeArray();
        
        // Send AJAX request
        $.ajax({
            url: wpAfiAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wp_afi_preview',
                form_data: formData,
                nonce: wpAfiAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $container.html(response.data.html);
                    
                    // Trigger custom event for preview updated
                    $(document).trigger('wp-afi-preview-updated');
                } else {
                    showNotice('error', 'Failed to update preview');
                }
            },
            error: function() {
                showNotice('error', 'Preview update failed');
            },
            complete: function() {
                $container.removeClass('wp-afi-loading');
                $button.prop('disabled', false);
            }
        });
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        $('#wp-afi-settings-form').on('submit', function(e) {
            const isValid = validateForm();
            
            if (!isValid) {
                e.preventDefault();
                showNotice('error', 'Please fix the validation errors before saving.');
            }
        });
    }

    /**
     * Validate form fields
     */
    function validateForm() {
        let isValid = true;
        
        // Clear previous errors
        $('.wp-afi-field-error').removeClass('wp-afi-field-error');
        
        // Validate font size
        const fontSize = parseFloat($('#wp_afi_font_size').val());
        if (isNaN(fontSize) || fontSize < 1 || fontSize > 10) {
            $('#wp_afi_font_size').addClass('wp-afi-field-error');
            isValid = false;
        }
        
        // Validate colors
        $('.wp-afi-color-picker').each(function() {
            const colorValue = $(this).val();
            if (colorValue && !isValidColor(colorValue)) {
                $(this).addClass('wp-afi-field-error');
                isValid = false;
            }
        });
        
        // Validate gradient syntax (basic check)
        const gradient = $('#wp_afi_background_gradient').val().trim();
        if (gradient && !isValidGradient(gradient)) {
            $('#wp_afi_background_gradient').addClass('wp-afi-field-error');
            isValid = false;
        }
        
        return isValid;
    }

    /**
     * Check if color value is valid
     */
    function isValidColor(color) {
        const style = new Option().style;
        style.color = color;
        return style.color !== '';
    }

    /**
     * Check if gradient syntax is valid (basic check)
     */
    function isValidGradient(gradient) {
        return /^(linear-gradient|radial-gradient|conic-gradient)\s*\(/i.test(gradient);
    }

    /**
     * Initialize tooltips and help text
     */
    function initTooltips() {
        // Add help icons with tooltips
        $('.form-table .description').each(function() {
            const $description = $(this);
            const text = $description.text();
            
            if (text.length > 50) {
                $description.addClass('wp-afi-tooltip');
                $description.attr('title', text);
            }
        });
        
        // Initialize tooltip functionality
        if ($.fn.tooltip) {
            $('.wp-afi-tooltip').tooltip({
                position: { my: "left+15 center", at: "right center" }
            });
        }
    }

    /**
     * Initialize dependency controls
     */
    function initDependencyControls() {
        // Show/hide category colors based on enable setting
        toggleCategoryColors();
        
        $('#wp_afi_enable_category_colors').on('change', function() {
            toggleCategoryColors();
        });
        
        // Show/hide gradient field description
        toggleGradientHelp();
        
        $('#wp_afi_background_gradient').on('input', function() {
            toggleGradientHelp();
        });
    }

    /**
     * Toggle category colors visibility
     */
    function toggleCategoryColors() {
        const isEnabled = $('#wp_afi_enable_category_colors').prop('checked');
        const $categoryColors = $('.wp-afi-category-colors').closest('tr');
        
        if (isEnabled) {
            $categoryColors.show();
        } else {
            $categoryColors.hide();
        }
    }

    /**
     * Toggle gradient help text
     */
    function toggleGradientHelp() {
        const gradientValue = $('#wp_afi_background_gradient').val().trim();
        const $helpText = $('#wp_afi_background_gradient').siblings('.description');
        
        if (gradientValue) {
            $helpText.html('Gradient is active and will override the background color.');
        } else {
            $helpText.html('Optional. Override background color with a CSS gradient.');
        }
    }

    /**
     * Show admin notice
     */
    function showNotice(type, message) {
        const $notice = $('<div>')
            .addClass('notice notice-' + type + ' is-dismissible wp-afi-message')
            .html('<p>' + message + '</p>');
        
        $('.wp-afi-settings .wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Debounce function to limit function calls
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Copy to clipboard functionality
     */
    function initCopyToClipboard() {
        $('.wp-afi-copy-button').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const targetSelector = $button.data('target');
            const $target = $(targetSelector);
            
            if ($target.length) {
                const text = $target.val() || $target.text();
                
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function() {
                        showCopySuccess($button);
                    });
                } else {
                    // Fallback for older browsers
                    $target.select();
                    document.execCommand('copy');
                    showCopySuccess($button);
                }
            }
        });
    }

    /**
     * Show copy success feedback
     */
    function showCopySuccess($button) {
        const originalText = $button.text();
        $button.text('Copied!').addClass('wp-afi-copied');
        
        setTimeout(function() {
            $button.text(originalText).removeClass('wp-afi-copied');
        }, 2000);
    }

    /**
     * Handle settings import/export
     */
    function initImportExport() {
        // Export settings
        $('#wp-afi-export-settings').on('click', function(e) {
            e.preventDefault();
            
            const formData = $('#wp-afi-settings-form').serializeArray();
            const settings = {};
            
            formData.forEach(function(item) {
                const name = item.name.replace('wp_afi_settings[', '').replace(']', '');
                settings[name] = item.value;
            });
            
            const blob = new Blob([JSON.stringify(settings, null, 2)], {
                type: 'application/json'
            });
            
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'wp-auto-featured-image-settings.json';
            a.click();
            
            URL.revokeObjectURL(url);
        });
        
        // Import settings
        $('#wp-afi-import-file').on('change', function(e) {
            const file = e.target.files[0];
            
            if (file && file.type === 'application/json') {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    try {
                        const settings = JSON.parse(e.target.result);
                        populateFormWithSettings(settings);
                        showNotice('success', 'Settings imported successfully!');
                    } catch (error) {
                        showNotice('error', 'Invalid settings file.');
                    }
                };
                
                reader.readAsText(file);
            } else {
                showNotice('error', 'Please select a valid JSON file.');
            }
        });
    }

    /**
     * Populate form with imported settings
     */
    function populateFormWithSettings(settings) {
        Object.keys(settings).forEach(function(key) {
            const $field = $('[name="wp_afi_settings[' + key + ']"]');
            
            if ($field.length) {
                if ($field.attr('type') === 'checkbox') {
                    $field.prop('checked', !!settings[key]);
                } else {
                    $field.val(settings[key]);
                }
                
                // Update color pickers
                if ($field.hasClass('wp-afi-color-picker')) {
                    $field.wpColorPicker('color', settings[key]);
                }
            }
        });
        
        // Update preview after importing
        updateLivePreview();
    }

    // Initialize additional features
    initCopyToClipboard();
    initImportExport();

    // Expose functions globally for extensibility
    window.wpAutoFeaturedImageAdmin = {
        updatePreview: updateLivePreview,
        showNotice: showNotice,
        validateForm: validateForm
    };

})(jQuery);
