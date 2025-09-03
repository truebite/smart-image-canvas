/**
 * Auto Featured Image - Customizer Controls JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initCustomizerControls();
    });

    /**
     * Initialize customizer controls
     */
    function initCustomizerControls() {
        setupControlDependencies();
        addControlEnhancements();
        initPreviewFeatures();
    }

    /**
     * Setup control dependencies
     */
    function setupControlDependencies() {
        // Show/hide gradient field based on whether it's being used
        wp.customize('wp_afi_settings[background_gradient]', function(value) {
            value.bind(function(newval) {
                const $bgColorControl = wp.customize.control('wp_afi_background_color').container;
                
                if (newval.trim()) {
                    $bgColorControl.addClass('wp-afi-control-disabled');
                    addControlNote($bgColorControl, 'Background color is overridden by gradient');
                } else {
                    $bgColorControl.removeClass('wp-afi-control-disabled');
                    removeControlNote($bgColorControl);
                }
            });
        });

        // Update font size display
        wp.customize('wp_afi_settings[font_size]', function(value) {
            value.bind(function(newval) {
                const $control = wp.customize.control('wp_afi_font_size').container;
                const $label = $control.find('.customize-control-title');
                $label.text('Font Size (' + newval + 'rem)');
            });
        });
    }

    /**
     * Add control enhancements
     */
    function addControlEnhancements() {
        // Add quick preset buttons for common gradients
        addGradientPresets();
        
        // Add font size quick buttons
        addFontSizePresets();
        
        // Add template style previews
        addTemplateStylePreviews();
        
        // Add responsive preview toggle
        addResponsivePreview();
    }

    /**
     * Add gradient preset buttons
     */
    function addGradientPresets() {
        const $gradientControl = wp.customize.control('wp_afi_background_gradient').container;
        const $gradientField = $gradientControl.find('textarea');
        
        const presets = [
            {
                name: 'Blue Ocean',
                value: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
            },
            {
                name: 'Sunset',
                value: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'
            },
            {
                name: 'Forest',
                value: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
            },
            {
                name: 'Royal',
                value: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
            }
        ];
        
        const $presetContainer = $('<div class="wp-afi-gradient-presets"></div>');
        
        presets.forEach(function(preset) {
            const $button = $('<button type="button" class="button wp-afi-preset-btn">')
                .text(preset.name)
                .css('background', preset.value)
                .on('click', function() {
                    $gradientField.val(preset.value).trigger('change');
                });
                
            $presetContainer.append($button);
        });
        
        $gradientField.after($presetContainer);
    }

    /**
     * Add font size preset buttons
     */
    function addFontSizePresets() {
        const $fontSizeControl = wp.customize.control('wp_afi_font_size').container;
        const $fontSizeField = $fontSizeControl.find('input[type="range"]');
        
        const presets = [
            { name: 'Small', value: '1.8' },
            { name: 'Medium', value: '2.5' },
            { name: 'Large', value: '3.2' },
            { name: 'XLarge', value: '4.0' }
        ];
        
        const $presetContainer = $('<div class="wp-afi-fontsize-presets"></div>');
        
        presets.forEach(function(preset) {
            const $button = $('<button type="button" class="button wp-afi-preset-btn">')
                .text(preset.name)
                .on('click', function() {
                    $fontSizeField.val(preset.value).trigger('change');
                });
                
            $presetContainer.append($button);
        });
        
        $fontSizeField.after($presetContainer);
    }

    /**
     * Add template style previews
     */
    function addTemplateStylePreviews() {
        const $templateControl = wp.customize.control('wp_afi_template_style').container;
        const $select = $templateControl.find('select');
        
        // Add preview thumbnails for each template
        const $previewContainer = $('<div class="wp-afi-template-previews"></div>');
        
        $select.find('option').each(function() {
            const value = $(this).val();
            const label = $(this).text();
            
            if (value) {
                const $preview = $('<div class="wp-afi-template-preview">')
                    .attr('data-template', value)
                    .addClass('wp-afi-template-' + value)
                    .html('<span>' + label + '</span>')
                    .on('click', function() {
                        $select.val(value).trigger('change');
                        updateTemplatePreviewSelection(value);
                    });
                    
                $previewContainer.append($preview);
            }
        });
        
        $select.after($previewContainer);
        
        // Update selection on change
        $select.on('change', function() {
            updateTemplatePreviewSelection($(this).val());
        });
        
        // Initialize selection
        updateTemplatePreviewSelection($select.val());
    }

    /**
     * Update template preview selection
     */
    function updateTemplatePreviewSelection(selectedValue) {
        $('.wp-afi-template-preview').removeClass('selected');
        $('.wp-afi-template-preview[data-template="' + selectedValue + '"]').addClass('selected');
    }

    /**
     * Add responsive preview toggle
     */
    function addResponsivePreview() {
        // Add device preview buttons to the customizer
        const $previewActions = $('.wp-full-overlay-footer .devices');
        
        if ($previewActions.length) {
            const $afiButton = $('<button type="button" class="preview-afi button">')
                .html('<span class="screen-reader-text">Preview Auto Featured Images</span>')
                .attr('title', 'Highlight Auto Featured Images')
                .on('click', function() {
                    toggleImageHighlight();
                    $(this).toggleClass('active');
                });
                
            $previewActions.append($afiButton);
        }
    }

    /**
     * Toggle image highlight in preview
     */
    function toggleImageHighlight() {
        wp.customize.previewer.send('wp-afi-toggle-highlight');
    }

    /**
     * Initialize preview features
     */
    function initPreviewFeatures() {
        // Add real-time preview updates
        setupRealtimePreview();
        
        // Add preview refresh button
        addPreviewRefreshButton();
    }

    /**
     * Setup real-time preview updates
     */
    function setupRealtimePreview() {
        // Throttle preview updates to avoid overwhelming the preview frame
        let updateTimeout;
        
        $('body').on('change input', '.customize-control input, .customize-control select, .customize-control textarea', function() {
            clearTimeout(updateTimeout);
            updateTimeout = setTimeout(function() {
                // Send preview update event
                wp.customize.previewer.send('wp-afi-settings-changed');
            }, 300);
        });
    }

    /**
     * Add preview refresh button
     */
    function addPreviewRefreshButton() {
        const $section = wp.customize.section('wp_afi_general');
        
        if ($section.length) {
            const $refreshButton = $('<button type="button" class="button button-secondary wp-afi-refresh-preview">')
                .text('Refresh Preview')
                .on('click', function() {
                    wp.customize.previewer.send('wp-afi-refresh');
                    $(this).addClass('updating-message').text('Refreshing...');
                    
                    setTimeout(function() {
                        $('.wp-afi-refresh-preview').removeClass('updating-message').text('Refresh Preview');
                    }, 1000);
                });
                
            $section.contentContainer.prepend($refreshButton);
        }
    }

    /**
     * Add control note
     */
    function addControlNote($control, message) {
        if (!$control.find('.wp-afi-control-note').length) {
            const $note = $('<div class="wp-afi-control-note">')
                .html('<em>' + message + '</em>');
            $control.append($note);
        }
    }

    /**
     * Remove control note
     */
    function removeControlNote($control) {
        $control.find('.wp-afi-control-note').remove();
    }

    /**
     * Add custom validation
     */
    function addCustomValidation() {
        // Validate gradient syntax
        wp.customize('wp_afi_settings[background_gradient]', function(value) {
            value.bind(function(newval) {
                const $control = wp.customize.control('wp_afi_background_gradient').container;
                
                if (newval.trim() && !isValidGradient(newval)) {
                    $control.addClass('customize-control-invalid');
                    addControlNote($control, 'Invalid gradient syntax');
                } else {
                    $control.removeClass('customize-control-invalid');
                    removeControlNote($control);
                }
            });
        });
        
        // Validate font size range
        wp.customize('wp_afi_settings[font_size]', function(value) {
            value.bind(function(newval) {
                const $control = wp.customize.control('wp_afi_font_size').container;
                const fontSize = parseFloat(newval);
                
                if (isNaN(fontSize) || fontSize < 1 || fontSize > 10) {
                    $control.addClass('customize-control-invalid');
                } else {
                    $control.removeClass('customize-control-invalid');
                }
            });
        });
    }

    /**
     * Check if gradient is valid
     */
    function isValidGradient(gradient) {
        return /^(linear-gradient|radial-gradient|conic-gradient)\s*\(/i.test(gradient);
    }

    // Initialize validation
    addCustomValidation();

    // Listen for customizer events
    wp.customize.bind('ready', function() {
        console.log('Auto Featured Image customizer controls ready');
        
        // Focus on the AFI panel when accessed via admin menu
        if (wp.customize.settings.url.autofocus && 
            wp.customize.settings.url.autofocus.panel === 'wp_afi_panel') {
            wp.customize.panel('wp_afi_panel').focus();
        }
    });

    // Expose utilities
    window.wpAfiCustomizerControls = {
        updateTemplateSelection: updateTemplatePreviewSelection,
        addNote: addControlNote,
        removeNote: removeControlNote
    };

})(jQuery);
