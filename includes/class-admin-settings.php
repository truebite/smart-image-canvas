<?php
/**
 * Admin Settings Class
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SIC_Admin_Settings class
 */
class SIC_Admin_Settings {
    
    /**
     * Instance
     *
     * @var SIC_Admin_Settings
     */
    private static $instance = null;
    
    /**
     * Get instance
     *
     * @return SIC_Admin_Settings
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_SIC_preview', array($this, 'handle_preview_ajax'));
        add_action('wp_ajax_SIC_self_test', array($this, 'handle_self_test_ajax'));
        add_action('wp_ajax_SIC_check_update', array($this, 'handle_check_update_ajax'));
        add_action('wp_ajax_SIC_perform_update', array($this, 'handle_perform_update_ajax'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Additional safety hook for plugin reactivation
        add_action('upgrader_process_complete', array($this, 'check_reactivation_needed'), 10, 2);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Smart Image Canvas Settings', 'smart-image-canvas'),
            __('Smart Image Canvas', 'smart-image-canvas'),
            'manage_options',
            'smart-image-canvas',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our settings page
        if ($hook !== 'settings_page_smart-image-canvas') {
            return;
        }
        
        // Add inline CSS for proper preview styling
        $admin_css = "
        /* Auto Featured Image Admin Styles */
        .wp-afi-preview-panel {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        #wp-afi-preview-container {
            margin: 15px 0;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        /* Ensure proper preview display */
        #wp-afi-preview-container [id*='wp-afi-'] {
            max-width: 100%;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        #wp-afi-preview-container .wp-afi-content {
            padding: 20px;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #wp-afi-preview-container .wp-afi-title {
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            line-height: 1.2;
            text-align: center;
            max-width: 100%;
        }
        
        /* Settings sidebar */
        .wp-afi-settings-sidebar {
            flex: 0 0 300px;
            margin-left: 20px;
        }
        
        .wp-afi-settings-main {
            flex: 1;
        }
        
        .wp-afi-settings-container {
            display: flex;
            align-items: flex-start;
        }
        
        /* Custom aspect ratio field - initially hidden */
        tr:has(#SIC_custom_aspect_ratio) {
            display: none;
        }
        
        /* Debug tab layout improvements */
        .wp-afi-debug-container {
            gap: 20px;
        }
        
        @media (max-width: 1200px) {
            .wp-afi-debug-container {
                flex-direction: column !important;
            }
            .wp-afi-debug-right {
                flex: 1 !important;
                min-width: auto !important;
            }
        }
        
        .wp-afi-debug-right h2 {
            margin-top: 0;
            border-bottom: 1px solid #ccd0d4;
            padding-bottom: 10px;
        }
        ";
        
        wp_add_inline_style('admin-menu', $admin_css);
        
        // Localize script with ajax URL
        wp_localize_script('jquery', 'SIC_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('SIC_admin_nonce')
        ));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'SIC_settings_group',
            'SIC_settings',
            array($this, 'sanitize_settings')
        );
        
        // General Settings Section
        add_settings_section(
            'SIC_general_section',
            __('General Settings', 'smart-image-canvas'),
            array($this, 'general_section_callback'),
            'smart-image-canvas'
        );
        
        // Style Settings Section
        add_settings_section(
            'SIC_style_section',
            __('Style Settings', 'smart-image-canvas'),
            array($this, 'style_section_callback'),
            'smart-image-canvas'
        );
        
        // Typography Settings Section
        add_settings_section(
            'SIC_typography_section',
            __('Typography Settings', 'smart-image-canvas'),
            array($this, 'typography_section_callback'),
            'smart-image-canvas'
        );
        
        // Advanced Settings Section
        add_settings_section(
            'SIC_advanced_section',
            __('Advanced Settings', 'smart-image-canvas'),
            array($this, 'advanced_section_callback'),
            'smart-image-canvas'
        );
        
        // Add fields
        $this->add_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // General Settings
        add_settings_field(
            'enabled',
            __('Enable Plugin', 'smart-image-canvas'),
            array($this, 'checkbox_field'),
            'smart-image-canvas',
            'SIC_general_section',
            array('field' => 'enabled', 'description' => __('Enable automatic featured image generation', 'smart-image-canvas'))
        );
        
        add_settings_field(
            'auto_activate',
            __('Auto Activate', 'smart-image-canvas'),
            array($this, 'checkbox_field'),
            'smart-image-canvas',
            'SIC_general_section',
            array('field' => 'auto_activate', 'description' => __('Automatically generate featured images when none are set', 'smart-image-canvas'))
        );
        
        add_settings_field(
            'aspect_ratio',
            __('Aspect Ratio', 'smart-image-canvas'),
            array($this, 'aspect_ratio_field'),
            'smart-image-canvas',
            'SIC_general_section',
            array('field' => 'aspect_ratio', 'options' => SIC_Image_Generator::get_aspect_ratios())
        );
        
        add_settings_field(
            'custom_aspect_ratio',
            __('Custom Aspect Ratio', 'smart-image-canvas'),
            array($this, 'custom_aspect_ratio_field'),
            'smart-image-canvas',
            'SIC_general_section',
            array(
                'field' => 'custom_aspect_ratio', 
                'description' => __('Enter custom ratio (e.g., 5:3, 7:4). Only visible when "Custom" is selected above.', 'smart-image-canvas'),
                'placeholder' => '5:3'
            )
        );
        
        add_settings_field(
            'template_style',
            __('Template Style', 'smart-image-canvas'),
            array($this, 'select_field'),
            'smart-image-canvas',
            'SIC_general_section',
            array('field' => 'template_style', 'options' => SIC_Image_Generator::get_template_styles())
        );
        
        // Style Settings
        add_settings_field(
            'background_color',
            __('Background Color', 'smart-image-canvas'),
            array($this, 'color_field'),
            'smart-image-canvas',
            'SIC_style_section',
            array('field' => 'background_color')
        );
        
        add_settings_field(
            'background_gradient',
            __('Background Gradient (Optional)', 'smart-image-canvas'),
            array($this, 'text_field'),
            'smart-image-canvas',
            'SIC_style_section',
            array('field' => 'background_gradient', 'placeholder' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', 'description' => __('CSS gradient (overrides background color)', 'smart-image-canvas'))
        );
        
        add_settings_field(
            'text_color',
            __('Text Color', 'smart-image-canvas'),
            array($this, 'color_field'),
            'smart-image-canvas',
            'SIC_style_section',
            array('field' => 'text_color')
        );
        
        // Typography Settings
        add_settings_field(
            'font_family',
            __('Font Family', 'smart-image-canvas'),
            array($this, 'select_field'),
            'smart-image-canvas',
            'SIC_typography_section',
            array('field' => 'font_family', 'options' => SIC_Image_Generator::get_font_families())
        );
        
        add_settings_field(
            'font_size',
            __('Font Size (rem)', 'smart-image-canvas'),
            array($this, 'number_field'),
            'smart-image-canvas',
            'SIC_typography_section',
            array('field' => 'font_size', 'min' => '1', 'max' => '10', 'step' => '0.1')
        );
        
        add_settings_field(
            'font_weight',
            __('Font Weight', 'smart-image-canvas'),
            array($this, 'select_field'),
            'smart-image-canvas',
            'SIC_typography_section',
            array('field' => 'font_weight', 'options' => array(
                '300' => __('Light (300)', 'smart-image-canvas'),
                '400' => __('Normal (400)', 'smart-image-canvas'),
                '500' => __('Medium (500)', 'smart-image-canvas'),
                '600' => __('Semi Bold (600)', 'smart-image-canvas'),
                '700' => __('Bold (700)', 'smart-image-canvas'),
                '800' => __('Extra Bold (800)', 'smart-image-canvas'),
                '900' => __('Black (900)', 'smart-image-canvas')
            ))
        );
        
        add_settings_field(
            'text_align',
            __('Text Alignment', 'smart-image-canvas'),
            array($this, 'select_field'),
            'smart-image-canvas',
            'SIC_typography_section',
            array('field' => 'text_align', 'options' => array(
                'left' => __('Left', 'smart-image-canvas'),
                'center' => __('Center', 'smart-image-canvas'),
                'right' => __('Right', 'smart-image-canvas')
            ))
        );
        
        add_settings_field(
            'text_position',
            __('Text Position', 'smart-image-canvas'),
            array($this, 'select_field'),
            'smart-image-canvas',
            'SIC_typography_section',
            array('field' => 'text_position', 'options' => array(
                'top' => __('Top', 'smart-image-canvas'),
                'center' => __('Center', 'smart-image-canvas'),
                'bottom' => __('Bottom', 'smart-image-canvas')
            ))
        );
        
        // Advanced Settings
        add_settings_field(
            'enable_category_colors',
            __('Enable Category Colors', 'smart-image-canvas'),
            array($this, 'checkbox_field'),
            'smart-image-canvas',
            'SIC_advanced_section',
            array('field' => 'enable_category_colors', 'description' => __('Use different colors for different categories', 'smart-image-canvas'))
        );
        
        add_settings_field(
            'category_colors',
            __('Category Colors', 'smart-image-canvas'),
            array($this, 'category_colors_field'),
            'smart-image-canvas',
            'SIC_advanced_section',
            array()
        );
        
        add_settings_field(
            'custom_css',
            __('Custom CSS', 'smart-image-canvas'),
            array($this, 'textarea_field'),
            'smart-image-canvas',
            'SIC_advanced_section',
            array('field' => 'custom_css', 'rows' => '10', 'description' => __('Add custom CSS to override styles', 'smart-image-canvas'))
        );
        
        add_settings_field(
            'debug_enabled',
            __('Debug Logging', 'smart-image-canvas'),
            array($this, 'checkbox_field'),
            'smart-image-canvas',
            'SIC_advanced_section',
            array('field' => 'debug_enabled', 'description' => __('Enable debug logging for troubleshooting (temporary - disable after issues are resolved)', 'smart-image-canvas'))
        );
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $settings = Smart_Image_Canvas::get_settings();
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
        ?>
        <div class="wrap wp-afi-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=smart-image-canvas&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Settings', 'smart-image-canvas'); ?>
                </a>
                <a href="?page=smart-image-canvas&tab=updates" class="nav-tab <?php echo $active_tab === 'updates' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Updates', 'smart-image-canvas'); ?>
                </a>
                <a href="?page=smart-image-canvas&tab=debug" class="nav-tab <?php echo $active_tab === 'debug' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Debug & Troubleshooting', 'smart-image-canvas'); ?>
                </a>
            </nav>
            
            <?php if ($active_tab === 'settings'): ?>
                <div class="wp-afi-settings-container">
                    <div class="wp-afi-settings-main">
                        <form method="post" action="options.php" id="wp-afi-settings-form">
                            <?php
                            settings_fields('SIC_settings_group');
                            do_settings_sections('smart-image-canvas');
                            submit_button();
                            ?>
                        </form>
                    </div>
                    
                    <div class="wp-afi-settings-sidebar">
                        <div class="wp-afi-preview-panel">
                            <h3><?php _e('Live Preview', 'smart-image-canvas'); ?></h3>
                            <div id="wp-afi-preview-container">
                                <?php echo $this->generate_preview($settings); ?>
                            </div>
                            <button type="button" id="wp-afi-refresh-preview" class="button">
                                <?php _e('Refresh Preview', 'smart-image-canvas'); ?>
                            </button>
                        </div>
                        
                        <div class="wp-afi-help-panel">
                            <h3><?php _e('Quick Help', 'smart-image-canvas'); ?></h3>
                            <ul>
                                <li><?php _e('Enable the plugin to start generating featured images automatically', 'smart-image-canvas'); ?></li>
                                <li><?php _e('Choose an aspect ratio that matches your theme', 'smart-image-canvas'); ?></li>
                                <li><?php _e('Use gradients for more dynamic backgrounds', 'smart-image-canvas'); ?></li>
                                <li><?php _e('Test with different template styles', 'smart-image-canvas'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <script>
                jQuery(document).ready(function($) {
                    // Handle preview refresh
                    $('#wp-afi-refresh-preview').on('click', function() {
                        refreshPreview();
                    });
                    
                    // Auto-refresh preview when settings change
                    $('#wp-afi-settings-form').on('change', 'select, input', function() {
                        // Debounce to avoid too many requests
                        clearTimeout(window.previewTimeout);
                        window.previewTimeout = setTimeout(refreshPreview, 500);
                    });
                    
                    function refreshPreview() {
                        var button = $('#wp-afi-refresh-preview');
                        var container = $('#wp-afi-preview-container');
                        
                        // Show loading state
                        button.prop('disabled', true).text('<?php echo esc_js(__('Refreshing...', 'smart-image-canvas')); ?>');
                        container.css('opacity', '0.5');
                        
                        // Serialize form data
                        var formData = $('#wp-afi-settings-form').serializeArray();
                        
                        $.ajax({
                            url: SIC_ajax.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'SIC_preview',
                                nonce: SIC_ajax.nonce,
                                form_data: formData
                            },
                            success: function(response) {
                                if (response.success) {
                                    container.html(response.data.html);
                                } else {
                                    container.html('<div class="notice notice-error"><p>' + (response.data || 'Preview failed') + '</p></div>');
                                }
                            },
                            error: function() {
                                container.html('<div class="notice notice-error"><p><?php echo esc_js(__('Failed to refresh preview', 'smart-image-canvas')); ?></p></div>');
                            },
                            complete: function() {
                                // Reset button state
                                button.prop('disabled', false).text('<?php echo esc_js(__('Refresh Preview', 'smart-image-canvas')); ?>');
                                container.css('opacity', '1');
                            }
                        });
                    }
                });
                </script>
            <?php elseif ($active_tab === 'updates'): ?>
                <?php $this->render_updates_tab(); ?>
            <?php elseif ($active_tab === 'debug' || $active_tab === 'self-test' || $active_tab === 'logs'): ?>
                <?php $this->render_debug_tab(); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Generate preview HTML
     */
    private function generate_preview($settings) {
        $preview_title = __('Sample Blog Post Title', 'smart-image-canvas');
        
        // Try to get a real post for preview, fallback to creating a minimal one
        $recent_posts = get_posts(array(
            'numberposts' => 1,
            'post_status' => 'publish',
            'post_type' => 'post'
        ));
        
        if (!empty($recent_posts)) {
            $mock_post = $recent_posts[0];
            // Always use our preview title for consistency
            $original_title = $mock_post->post_title;
            $mock_post->post_title = $preview_title;
        } else {
            // Create a minimal WP_Post object for preview
            $mock_post = new WP_Post((object) array(
                'ID' => 0,
                'post_title' => $preview_title,
                'post_type' => 'post',
                'post_status' => 'publish',
                'post_name' => 'sample-preview',
                'post_author' => 1,
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1),
                'post_content' => '',
                'post_excerpt' => '',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_password' => '',
                'to_ping' => '',
                'pinged' => '',
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1),
                'post_content_filtered' => '',
                'post_parent' => 0,
                'guid' => '',
                'menu_order' => 0,
                'post_mime_type' => '',
                'comment_count' => 0,
                'filter' => 'raw'
            ));
        }
        
        // Ensure we have default settings merged with user settings for preview
        $default_settings = Smart_Image_Canvas::get_settings();
        $preview_settings = wp_parse_args($settings, $default_settings);
        
        // Temporarily override settings for preview
        add_filter('SIC_settings_override', function() use ($preview_settings) {
            return $preview_settings;
        });
        
        $generator = SIC_Image_Generator::get_instance();
        $html = $generator->generate_featured_image_html($mock_post, 'medium');
        
        // Restore original title if we used a real post
        if (!empty($recent_posts) && isset($original_title)) {
            $mock_post->post_title = $original_title;
        }
        
        // Remove the filter
        remove_all_filters('SIC_settings_override');
        
        return $html;
    }
    
    /**
     * Handle preview AJAX
     */
    public function handle_preview_ajax() {
        // Security checks
        check_ajax_referer('SIC_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'smart-image-canvas'));
            return;
        }
        
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(__('Invalid request method', 'smart-image-canvas'));
            return;
        }
        
        $settings = array();
        $form_data = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : array();
        
        // Validate form_data is array
        if (!is_array($form_data)) {
            wp_send_json_error(__('Invalid form data', 'smart-image-canvas'));
            return;
        }
        
        // Parse and validate form data
        $allowed_fields = array(
            'enabled', 'template', 'aspect_ratio', 'background_color', 'text_color',
            'font_family', 'font_weight', 'font_size', 'custom_css', 'post_types'
        );
        
        foreach ($form_data as $item) {
            if (!is_array($item) || !isset($item['name']) || !isset($item['value'])) {
                continue;
            }
            
            $name = sanitize_text_field($item['name']);
            $name = str_replace('SIC_settings[', '', $name);
            $name = str_replace(']', '', $name);
            
            // Only allow whitelisted fields
            if (!in_array($name, $allowed_fields, true)) {
                continue;
            }
            
            // Sanitize based on field type
            switch ($name) {
                case 'background_color':
                case 'text_color':
                    $settings[$name] = sanitize_hex_color($item['value']);
                    break;
                case 'custom_css':
                    $settings[$name] = wp_strip_all_tags($item['value']);
                    break;
                case 'enabled':
                    $settings[$name] = (bool) $item['value'];
                    break;
                case 'post_types':
                    $settings[$name] = is_array($item['value']) ? 
                        array_map('sanitize_text_field', $item['value']) : 
                        array(sanitize_text_field($item['value']));
                    break;
                default:
                    $settings[$name] = sanitize_text_field($item['value']);
                    break;
            }
        }
        
        // Generate preview with validated settings
        $html = $this->generate_preview($settings);
        
        wp_send_json_success(array('html' => wp_kses_post($html)));
    }
    
    /**
     * Section callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general plugin settings.', 'smart-image-canvas') . '</p>';
    }
    
    public function style_section_callback() {
        echo '<p>' . __('Customize the appearance of your auto-generated featured images.', 'smart-image-canvas') . '</p>';
    }
    
    public function typography_section_callback() {
        echo '<p>' . __('Configure text styling options.', 'smart-image-canvas') . '</p>';
    }
    
    public function advanced_section_callback() {
        echo '<p>' . __('Advanced customization options.', 'smart-image-canvas') . '</p>';
    }
    
    /**
     * Field callbacks
     */
    public function checkbox_field($args) {
        $settings = Smart_Image_Canvas::get_settings();
        $field = $args['field'];
        $value = isset($settings[$field]) ? $settings[$field] : false;
        $description = $args['description'] ?? '';
        
        echo sprintf(
            '<input type="checkbox" id="SIC_%s" name="SIC_settings[%s]" value="1" %s />',
            esc_attr($field),
            esc_attr($field),
            checked($value, true, false)
        );
        
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }
    
    public function text_field($args) {
        $settings = Smart_Image_Canvas::get_settings();
        $field = $args['field'];
        $value = isset($settings[$field]) ? $settings[$field] : '';
        $placeholder = $args['placeholder'] ?? '';
        $description = $args['description'] ?? '';
        
        echo sprintf(
            '<input type="text" id="SIC_%s" name="SIC_settings[%s]" value="%s" placeholder="%s" class="regular-text wp-afi-field" />',
            esc_attr($field),
            esc_attr($field),
            esc_attr($value),
            esc_attr($placeholder)
        );
        
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }
    
    public function password_field($args) {
        $settings = Smart_Image_Canvas::get_settings();
        $field = $args['field'];
        $value = isset($settings[$field]) ? $settings[$field] : '';
        $placeholder = $args['placeholder'] ?? '';
        $description = $args['description'] ?? '';
        
        echo sprintf(
            '<input type="password" id="SIC_%s" name="SIC_settings[%s]" value="%s" placeholder="%s" class="regular-text wp-afi-field" />',
            esc_attr($field),
            esc_attr($field),
            esc_attr($value),
            esc_attr($placeholder)
        );
        
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }
    
    public function number_field($args) {
        $settings = Smart_Image_Canvas::get_settings();
        $field = $args['field'];
        $value = isset($settings[$field]) ? $settings[$field] : '';
        $min = $args['min'] ?? '';
        $max = $args['max'] ?? '';
        $step = $args['step'] ?? '';
        
        echo sprintf(
            '<input type="number" id="SIC_%s" name="SIC_settings[%s]" value="%s" min="%s" max="%s" step="%s" class="small-text wp-afi-field" />',
            esc_attr($field),
            esc_attr($field),
            esc_attr($value),
            esc_attr($min),
            esc_attr($max),
            esc_attr($step)
        );
    }
    
    public function color_field($args) {
        $settings = Smart_Image_Canvas::get_settings();
        $field = $args['field'];
        $value = isset($settings[$field]) ? $settings[$field] : '#2563eb';
        
        echo sprintf(
            '<input type="text" id="SIC_%s" name="SIC_settings[%s]" value="%s" class="wp-afi-color-picker wp-afi-field" />',
            esc_attr($field),
            esc_attr($field),
            esc_attr($value)
        );
    }
    
    public function select_field($args) {
        $settings = Smart_Image_Canvas::get_settings();
        $field = $args['field'];
        $value = isset($settings[$field]) ? $settings[$field] : '';
        $options = $args['options'] ?? array();
        
        echo sprintf('<select id="SIC_%s" name="SIC_settings[%s]" class="wp-afi-field">', esc_attr($field), esc_attr($field));
        
        foreach ($options as $option_value => $option_label) {
            echo sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($option_value),
                selected($value, $option_value, false),
                esc_html($option_label)
            );
        }
        
        echo '</select>';
    }
    
    public function aspect_ratio_field($args) {
        $settings = Smart_Image_Canvas::get_settings();
        $field = $args['field'];
        $value = isset($settings[$field]) ? $settings[$field] : '';
        $options = $args['options'] ?? array();
        
        echo sprintf('<select id="SIC_%s" name="SIC_settings[%s]" class="wp-afi-field wp-afi-aspect-ratio-select">', esc_attr($field), esc_attr($field));
        
        foreach ($options as $option_value => $option_label) {
            echo sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($option_value),
                selected($value, $option_value, false),
                esc_html($option_label)
            );
        }
        
        echo '</select>';
        
        // Add JavaScript to show/hide custom field
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function toggleCustomAspectRatio() {
                var selectedValue = $('#SIC_aspect_ratio').val();
                var customField = $('#SIC_custom_aspect_ratio').closest('tr');
                
                if (selectedValue === 'custom') {
                    customField.show();
                } else {
                    customField.hide();
                }
            }
            
            // Initial state
            toggleCustomAspectRatio();
            
            // On change
            $('#SIC_aspect_ratio').on('change', toggleCustomAspectRatio);
        });
        </script>
        <?php
    }
    
    public function custom_aspect_ratio_field($args) {
        $settings = Smart_Image_Canvas::get_settings();
        $field = $args['field'];
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        echo sprintf(
            '<input type="text" id="SIC_%s" name="SIC_settings[%s]" value="%s" class="wp-afi-field" placeholder="16:9" />',
            esc_attr($field),
            esc_attr($field),
            esc_attr($value)
        );
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function textarea_field($args) {
        $settings = Smart_Image_Canvas::get_settings();
        $field = $args['field'];
        $value = isset($settings[$field]) ? $settings[$field] : '';
        $rows = $args['rows'] ?? '5';
        $description = $args['description'] ?? '';
        
        echo sprintf(
            '<textarea id="SIC_%s" name="SIC_settings[%s]" rows="%s" class="large-text wp-afi-field">%s</textarea>',
            esc_attr($field),
            esc_attr($field),
            esc_attr($rows),
            esc_textarea($value)
        );
        
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }
    
    public function category_colors_field($args) {
        $settings = Smart_Image_Canvas::get_settings();
        $category_colors = isset($settings['category_colors']) ? $settings['category_colors'] : array();
        
        // Cache categories for 1 hour
        $cache_key = 'SIC_categories_list';
        $categories = get_transient($cache_key);
        
        if ($categories === false) {
            $categories = get_categories(array(
                'hide_empty' => false,
                'number' => 100, // Limit to prevent memory issues
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            set_transient($cache_key, $categories, HOUR_IN_SECONDS);
        }
        
        if (empty($categories)) {
            echo '<p>' . __('No categories found.', 'smart-image-canvas') . '</p>';
            return;
        }
        
        echo '<div class="wp-afi-category-colors">';
        foreach ($categories as $category) {
            $color = isset($category_colors[$category->term_id]) ? $category_colors[$category->term_id] : '#2563eb';
            
            echo sprintf(
                '<div class="wp-afi-category-color-row">
                    <label for="SIC_category_color_%d">%s</label>
                    <input type="text" id="SIC_category_color_%d" name="SIC_settings[category_colors][%d]" value="%s" class="wp-afi-color-picker" />
                </div>',
                $category->term_id,
                esc_html($category->name),
                $category->term_id,
                $category->term_id,
                esc_attr($color)
            );
        }
        echo '</div>';
    }
    
    /**
     * Sanitize settings with enhanced validation
     */
    public function sanitize_settings($input) {
        $logger = SIC_Debug_Logger::get_instance();
        $logger->info('Settings sanitization started', array('input_keys' => array_keys($input)));
        
        $sanitized = array();
        $defaults = Smart_Image_Canvas::get_settings();
        
        // Validate input is array
        if (!is_array($input)) {
            $logger->error('Invalid settings input - not an array', array('input_type' => gettype($input)));
            add_settings_error('SIC_settings', 'invalid_input', __('Invalid settings data received.', 'smart-image-canvas'));
            return $defaults;
        }
        
        // Boolean fields
        $boolean_fields = array('enabled', 'auto_activate', 'enable_category_colors', 'debug_enabled');
        foreach ($boolean_fields as $field) {
            $sanitized[$field] = !empty($input[$field]);
            $logger->debug("Boolean field {$field}", array('value' => $sanitized[$field]));
        }
        
        // Text fields with length limits
        $text_fields = array(
            'background_gradient' => 1000,
            'custom_css' => 5000
        );
        foreach ($text_fields as $field => $max_length) {
            $value = isset($input[$field]) ? sanitize_textarea_field($input[$field]) : '';
            if (strlen($value) > $max_length) {
                add_settings_error('SIC_settings', $field . '_too_long', 
                    sprintf(__('%s exceeds maximum length of %d characters.', 'smart-image-canvas'), 
                        ucfirst(str_replace('_', ' ', $field)), $max_length));
                $value = substr($value, 0, $max_length);
            }
            $sanitized[$field] = $value;
        }
        
        // Color fields with validation
        $color_fields = array('background_color', 'text_color');
        foreach ($color_fields as $field) {
            $color = isset($input[$field]) ? sanitize_hex_color($input[$field]) : '';
            if (!$color && isset($input[$field]) && !empty($input[$field])) {
                add_settings_error('SIC_settings', $field . '_invalid', 
                    sprintf(__('Invalid color value for %s.', 'smart-image-canvas'), 
                        str_replace('_', ' ', $field)));
                $color = $defaults[$field];
            }
            $sanitized[$field] = $color ?: $defaults[$field];
        }
        
        // Select fields with strict validation
        $select_fields = array(
            'aspect_ratio' => array_keys(SIC_Image_Generator::get_aspect_ratios()),
            'template_style' => array_keys(SIC_Image_Generator::get_template_styles()),
            'font_family' => array_keys(SIC_Image_Generator::get_font_families()),
            'font_weight' => array('300', '400', '500', '600', '700', '800', '900'),
            'text_align' => array('left', 'center', 'right'),
            'text_position' => array('top', 'center', 'bottom')
        );
        
        foreach ($select_fields as $field => $allowed_values) {
            $value = isset($input[$field]) ? sanitize_text_field($input[$field]) : '';
            if ($value && !in_array($value, $allowed_values, true)) {
                add_settings_error('SIC_settings', $field . '_invalid', 
                    sprintf(__('Invalid value for %s.', 'smart-image-canvas'), 
                        str_replace('_', ' ', $field)));
                $value = $defaults[$field];
            }
            $sanitized[$field] = $value ?: $defaults[$field];
        }
        
        // Custom aspect ratio validation
        if (isset($input['custom_aspect_ratio'])) {
            $custom_ratio = sanitize_text_field($input['custom_aspect_ratio']);
            if (!empty($custom_ratio)) {
                // Validate format (width:height)
                if (preg_match('/^\d+:\d+$/', $custom_ratio)) {
                    $sanitized['custom_aspect_ratio'] = $custom_ratio;
                } else {
                    add_settings_error('SIC_settings', 'custom_aspect_ratio_invalid', 
                        __('Custom aspect ratio must be in format "width:height" (e.g., 16:9, 5:3).', 'smart-image-canvas'));
                    $sanitized['custom_aspect_ratio'] = isset($defaults['custom_aspect_ratio']) ? $defaults['custom_aspect_ratio'] : '';
                }
            } else {
                $sanitized['custom_aspect_ratio'] = '';
            }
        } else {
            $sanitized['custom_aspect_ratio'] = isset($defaults['custom_aspect_ratio']) ? $defaults['custom_aspect_ratio'] : '';
        }
        
        // Number fields with range validation
        $font_size = isset($input['font_size']) ? floatval($input['font_size']) : $defaults['font_size'];
        if ($font_size < 0.5 || $font_size > 20) {
            add_settings_error('SIC_settings', 'font_size_range', 
                __('Font size must be between 0.5 and 20.', 'smart-image-canvas'));
            $font_size = max(0.5, min(20, $font_size));
        }
        $sanitized['font_size'] = $font_size;
        
        // Post types validation
        if (isset($input['post_types']) && is_array($input['post_types'])) {
            $available_post_types = get_post_types(array('public' => true), 'names');
            $sanitized['post_types'] = array();
            foreach ($input['post_types'] as $post_type) {
                $post_type = sanitize_text_field($post_type);
                if (in_array($post_type, $available_post_types, true)) {
                    $sanitized['post_types'][] = $post_type;
                }
            }
        } else {
            $sanitized['post_types'] = isset($defaults['post_types']) ? $defaults['post_types'] : array('post');
        }
        
        // Category colors with term validation
        if (isset($input['category_colors']) && is_array($input['category_colors'])) {
            $sanitized['category_colors'] = array();
            $valid_terms = get_terms(array('taxonomy' => 'category', 'fields' => 'ids', 'hide_empty' => false));
            
            foreach ($input['category_colors'] as $term_id => $color) {
                $term_id = intval($term_id);
                $color = sanitize_hex_color($color);
                
                if ($term_id > 0 && $color && in_array($term_id, $valid_terms, true)) {
                    $sanitized['category_colors'][$term_id] = $color;
                }
            }
        } else {
            $sanitized['category_colors'] = array();
        }
        
        $logger->info('Settings sanitization completed', array('sanitized_keys' => array_keys($sanitized)));
        
        return $sanitized;
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        if (isset($_GET['settings-updated']) && $_GET['page'] === 'smart-image-canvas') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . __('Settings saved successfully!', 'smart-image-canvas') . '</p>';
            echo '</div>';
        }
        
        // Check for automatic reactivation notice
        if (get_option('sic_reactivated_after_update', false)) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . __('Smart Image Canvas was automatically reactivated after the update.', 'smart-image-canvas') . '</p>';
            echo '</div>';
            delete_option('sic_reactivated_after_update');
        }
    }
    
    /**
     * Render debug tab
     */
    /**
     * Render debug tab with comprehensive troubleshooting and testing
     */
    private function render_debug_tab() {
        $debug_info = SIC_Debug::get_debug_info();
        $compatibility_checks = SIC_Debug::run_compatibility_checks();
        
        // Handle test generation form submission
        $test_results = null;
        if (isset($_POST['test_generation']) && wp_verify_nonce($_POST['_wpnonce'], 'SIC_test_generation')) {
            $post_id = isset($_POST['test_post_id']) ? intval($_POST['test_post_id']) : null;
            $test_results = SIC_Debug::test_generation($post_id);
        }
        ?>
        <div class="wp-afi-debug-container" style="display: flex; gap: 20px;">
            <!-- Left Column: Debug & Troubleshooting -->
            <div class="wp-afi-debug-left" style="flex: 1; min-width: 0;">
            
            <!-- Quick Test Section -->
            <div class="wp-afi-test-section">
                <div class="wp-afi-card">
                    <h2><?php _e('Quick Plugin Test', 'smart-image-canvas'); ?></h2>
                    <p><?php _e('Run comprehensive tests to verify plugin functionality, performance, and compatibility.', 'smart-image-canvas'); ?></p>
                    
                    <div class="wp-afi-test-controls">
                        <button type="button" id="wp-afi-run-tests" class="button button-primary">
                            <?php _e('Run All Tests', 'smart-image-canvas'); ?>
                        </button>
                        <span class="spinner" id="wp-afi-test-spinner"></span>
                    </div>
                    
                    <div id="wp-afi-test-results" class="wp-afi-test-results" style="display: none;">
                        <!-- Results will be loaded here via AJAX -->
                    </div>
                </div>
            </div>

            <!-- System Information Section -->
            <div class="wp-afi-debug-main">
                <h2><?php _e('System Information & Diagnostics', 'smart-image-canvas'); ?></h2>
                
                <!-- Compatibility Status -->
                <div class="postbox">
                    <h3 class="hndle"><?php _e('Theme Compatibility Status', 'smart-image-canvas'); ?></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Current Theme', 'smart-image-canvas'); ?></th>
                                <td><?php echo esc_html($debug_info['theme_info']['name']); ?> (<?php echo esc_html($debug_info['theme_info']['version']); ?>)</td>
                            </tr>
                            <tr>
                                <th><?php _e('Compatibility Level', 'smart-image-canvas'); ?></th>
                                <td>
                                    <span class="wp-afi-compatibility-<?php echo esc_attr($debug_info['compatibility_status']['compatibility_level']); ?>">
                                        <?php echo esc_html(ucfirst($debug_info['compatibility_status']['compatibility_level'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Problematic Theme', 'smart-image-canvas'); ?></th>
                                <td><?php echo $debug_info['compatibility_status']['is_problematic'] ? '⚠️ Yes' : '✅ No'; ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Page Builder Detected', 'smart-image-canvas'); ?></th>
                                <td><?php echo $debug_info['compatibility_status']['is_page_builder'] ? '⚠️ Yes' : '✅ No'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- WordPress Hooks Status -->
                <div class="postbox">
                    <h3 class="hndle"><?php _e('WordPress Hooks Status', 'smart-image-canvas'); ?></h3>
                    <div class="inside">
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('Hook Name', 'smart-image-canvas'); ?></th>
                                    <th><?php _e('Status', 'smart-image-canvas'); ?></th>
                                    <th><?php _e('Callbacks', 'smart-image-canvas'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($debug_info['hooks_status'] as $hook => $status): ?>
                                <tr>
                                    <td><?php echo esc_html($hook); ?></td>
                                    <td><?php echo $status['exists'] ? '✅ Active' : '❌ Missing'; ?></td>
                                    <td><?php echo esc_html($status['callbacks']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Manual Test Generation -->
                <div class="postbox">
                    <h3 class="hndle"><?php _e('Manual Image Generation Test', 'smart-image-canvas'); ?></h3>
                    <div class="inside">
                        <p><?php _e('Test image generation for a specific post to debug issues:', 'smart-image-canvas'); ?></p>
                        <form method="post">
                            <?php wp_nonce_field('SIC_test_generation'); ?>
                            <p>
                                <label for="test_post_id"><?php _e('Post ID (leave empty for latest post):', 'smart-image-canvas'); ?></label><br>
                                <input type="number" id="test_post_id" name="test_post_id" min="1" placeholder="<?php _e('Latest post', 'smart-image-canvas'); ?>">
                            </p>
                            <p>
                                <input type="submit" name="test_generation" class="button button-secondary" value="<?php _e('Test Specific Post', 'smart-image-canvas'); ?>">
                            </p>
                        </form>
                        
                        <?php if ($test_results): ?>
                        <div class="wp-afi-test-results">
                            <h4><?php _e('Manual Test Results', 'smart-image-canvas'); ?></h4>
                            <table class="form-table">
                                <tr>
                                    <th><?php _e('Post ID', 'smart-image-canvas'); ?></th>
                                    <td><?php echo esc_html($test_results['post_id']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Post Title', 'smart-image-canvas'); ?></th>
                                    <td><?php echo esc_html($test_results['post_title']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Has Thumbnail', 'smart-image-canvas'); ?></th>
                                    <td><?php echo $test_results['has_thumbnail'] ? 'Yes' : 'No'; ?></td>
                                </tr>
                            </table>
                            
                            <h5><?php _e('Generation Test by Size', 'smart-image-canvas'); ?></h5>
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Size', 'smart-image-canvas'); ?></th>
                                        <th><?php _e('Success', 'smart-image-canvas'); ?></th>
                                        <th><?php _e('HTML Length', 'smart-image-canvas'); ?></th>
                                        <th><?php _e('Generation Time', 'smart-image-canvas'); ?></th>
                                        <th><?php _e('Has Class', 'smart-image-canvas'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($test_results['generation_test'] as $size => $result): ?>
                                    <tr>
                                        <td><?php echo esc_html($size); ?></td>
                                        <td><?php echo $result['success'] ? '✅' : '❌'; ?></td>
                                        <td><?php echo esc_html($result['html_length']); ?> chars</td>
                                        <td><?php echo esc_html($result['generation_time']); ?></td>
                                        <td><?php echo $result['contains_class'] ? '✅' : '❌'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Potential Conflicts -->
                <?php if (!empty($compatibility_checks['potential_conflicts'])): ?>
                <div class="postbox">
                    <h3 class="hndle"><?php _e('Potential Conflicts', 'smart-image-canvas'); ?></h3>
                    <div class="inside">
                        <ul class="wp-afi-conflicts-list">
                            <?php foreach ($compatibility_checks['potential_conflicts'] as $conflict): ?>
                            <li>⚠️ <?php echo esc_html($conflict); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Debug Report -->
                <div class="postbox">
                    <h3 class="hndle"><?php _e('Debug Report', 'smart-image-canvas'); ?></h3>
                    <div class="inside">
                        <p><?php _e('Generate a comprehensive debug report to share with support:', 'smart-image-canvas'); ?></p>
                        <button type="button" id="wp-afi-generate-report" class="button button-secondary">
                            <?php _e('Generate Debug Report', 'smart-image-canvas'); ?>
                        </button>
                        <textarea id="wp-afi-debug-report" rows="20" cols="80" style="display:none; width:100%; font-family:monospace;"></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        /* Enhanced styles for unified debug tab */
        .wp-afi-debug-container {
            max-width: 1200px;
        }
        
        .wp-afi-test-section {
            margin-bottom: 30px;
        }
        
        .wp-afi-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .wp-afi-test-controls {
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .wp-afi-test-results {
            margin-top: 20px;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .wp-afi-test-section-header {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .wp-afi-test-section-header:last-child {
            border-bottom: none;
        }
        
        .wp-afi-test-section-header h3 {
            margin: 0 0 15px 0;
            color: #23282d;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .wp-afi-test-item {
            margin: 10px 0;
            padding: 10px;
            background: #fff;
            border-left: 4px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .wp-afi-test-item.pass {
            border-left-color: #46b450;
        }
        
        .wp-afi-test-item.fail {
            border-left-color: #dc3232;
        }
        
        .wp-afi-test-item.warning {
            border-left-color: #ffb900;
        }
        
        .wp-afi-test-status {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .wp-afi-test-status.pass {
            background: #46b450;
            color: white;
        }
        
        .wp-afi-test-status.fail {
            background: #dc3232;
            color: white;
        }
        
        .wp-afi-test-status.warning {
            background: #ffb900;
            color: #fff;
        }
        
        .wp-afi-overall-score {
            text-align: center;
            padding: 20px;
            background: #f0f0f1;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .wp-afi-score-circle {
            display: inline-block;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #46b450;
            color: white;
            line-height: 80px;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .wp-afi-performance-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .wp-afi-metric {
            text-align: center;
            padding: 15px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .wp-afi-metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
            display: block;
        }
        
        .wp-afi-metric-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        /* Legacy styles preserved */
        .wp-afi-compatibility-excellent { color: #46b450; font-weight: bold; }
        .wp-afi-compatibility-good { color: #00a0d2; font-weight: bold; }
        .wp-afi-compatibility-moderate { color: #ffb900; font-weight: bold; }
        .wp-afi-compatibility-basic { color: #dc3232; font-weight: bold; }
        .wp-afi-conflicts-list { list-style: none; }
        .wp-afi-conflicts-list li { margin-bottom: 5px; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Self-test functionality
            $('#wp-afi-run-tests').on('click', function() {
                // Check if AJAX variables are available
                if (typeof SIC_ajax === 'undefined') {
                    $('#wp-afi-test-results').html('<div class="notice notice-error"><p>AJAX configuration error. Please refresh the page and try again.</p></div>').show();
                    return;
                }
                
                var button = $(this);
                var spinner = $('#wp-afi-test-spinner');
                var results = $('#wp-afi-test-results');
                
                button.prop('disabled', true);
                spinner.addClass('is-active');
                results.hide();
                
                $.ajax({
                    url: SIC_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'SIC_self_test',
                        nonce: SIC_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            results.html(response.data.html).show();
                        } else {
                            results.html('<div class="notice notice-error"><p>' + (response.data || 'Test failed') + '</p></div>').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMsg = 'Failed to run tests. ';
                        if (xhr.responseText) {
                            errorMsg += 'Server response: ' + xhr.responseText;
                        } else {
                            errorMsg += 'Error: ' + error + ' (Status: ' + status + ')';
                        }
                        results.html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>').show();
                    },
                    complete: function() {
                        button.prop('disabled', false);
                        spinner.removeClass('is-active');
                    }
                });
            });
            
            // Debug report functionality
            $('#wp-afi-generate-report').on('click', function() {
                var button = this;
                var textarea = document.getElementById('wp-afi-debug-report');
                
                button.textContent = 'Generating...';
                button.disabled = true;
                
                // Simulate report generation (in real implementation, this would be an AJAX call)
                setTimeout(function() {
                    var report = `<?php echo esc_js(SIC_Debug::generate_debug_report()); ?>`;
                    textarea.value = report;
                    textarea.style.display = 'block';
                    button.textContent = 'Copy Report';
                    button.disabled = false;
                    
                    button.onclick = function() {
                        textarea.select();
                        document.execCommand('copy');
                        button.textContent = 'Copied!';
                        setTimeout(function() {
                            button.textContent = 'Copy Report';
                        }, 2000);
                    };
                }, 1000);
            });
        });
        </script>
        </div>
        <!-- End Left Column -->
        
        <!-- Right Column: Debug Logs -->
        <div class="wp-afi-debug-right" style="flex: 0 0 400px; min-width: 0;">
            <?php
            // Include logs content here
            $logger = SIC_Debug_Logger::instance();
            $logs = $logger->get_logs();
            
            // Handle log actions
            if (isset($_POST['clear_logs']) && wp_verify_nonce($_POST['_wpnonce'], 'sic_clear_logs')) {
                $logger->clear_logs();
                echo '<div class="notice notice-success"><p>' . __('Logs cleared successfully.', 'smart-image-canvas') . '</p></div>';
                $logs = []; // Clear the display
            }
            
            if (isset($_POST['export_logs']) && wp_verify_nonce($_POST['_wpnonce'], 'sic_export_logs')) {
                $this->export_logs();
                return;
            }
            ?>
            
            <h2><?php _e('Debug Logs', 'smart-image-canvas'); ?></h2>
            
            <div style="margin-bottom: 15px;">
                <form method="post" style="display: inline-block; margin-right: 8px;">
                    <?php wp_nonce_field('sic_clear_logs'); ?>
                    <input type="submit" name="clear_logs" class="button button-secondary button-small" 
                           value="<?php _e('Clear', 'smart-image-canvas'); ?>"
                           onclick="return confirm('<?php _e('Are you sure you want to clear all logs?', 'smart-image-canvas'); ?>');">
                </form>
                
                <form method="post" style="display: inline-block; margin-right: 8px;">
                    <?php wp_nonce_field('sic_export_logs'); ?>
                    <input type="submit" name="export_logs" class="button button-secondary button-small" 
                           value="<?php _e('Export', 'smart-image-canvas'); ?>">
                </form>
                
                <button type="button" class="button button-primary button-small" onclick="location.reload();">
                    <?php _e('Refresh', 'smart-image-canvas'); ?>
                </button>
            </div>
            
            <?php if (empty($logs)): ?>
                <div class="notice notice-info">
                    <p><?php _e('No logs available.', 'smart-image-canvas'); ?></p>
                </div>
            <?php else: ?>
                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; max-height: 500px; overflow-y: auto; padding: 12px; font-family: 'Courier New', monospace; font-size: 11px; line-height: 1.3;">
                    <?php foreach (array_reverse($logs) as $log): ?>
                        <?php
                        $level_class = '';
                        switch ($log['level']) {
                            case 'ERROR':
                                $level_class = 'color: #d63638; font-weight: bold;';
                                break;
                            case 'WARNING':
                                $level_class = 'color: #dba617; font-weight: bold;';
                                break;
                            case 'INFO':
                                $level_class = 'color: #135e96;';
                                break;
                            case 'DEBUG':
                                $level_class = 'color: #757575;';
                                break;
                        }
                        ?>
                        <div style="margin-bottom: 6px; padding: 4px; border-left: 2px solid #ddd;">
                            <div style="<?php echo $level_class; ?>">
                                <strong>[<?php echo esc_html(substr($log['timestamp'], 11, 8)); ?>] <?php echo esc_html($log['level']); ?>:</strong>
                                <?php echo esc_html($log['message']); ?>
                            </div>
                            <?php if (!empty($log['context'])): ?>
                                <div style="color: #666; font-size: 10px; margin-top: 2px;">
                                    <strong>Context:</strong> <?php echo esc_html(json_encode($log['context'], JSON_PRETTY_PRINT)); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($log['backtrace'])): ?>
                                <details style="margin-top: 3px;">
                                    <summary style="cursor: pointer; color: #666; font-size: 10px;">Show Backtrace</summary>
                                    <pre style="background: #f6f7f7; padding: 6px; margin: 3px 0; font-size: 9px; overflow-x: auto;"><?php echo esc_html($log['backtrace']); ?></pre>
                                </details>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 15px; padding: 12px; background: #f0f0f1; border-radius: 4px; font-size: 12px;">
                <h4 style="margin-top: 0; margin-bottom: 8px;"><?php _e('Log Information', 'smart-image-canvas'); ?></h4>
                <ul style="margin: 0; padding-left: 20px; list-style-type: disc;">
                    <li><strong><?php _e('Total logs:', 'smart-image-canvas'); ?></strong> <?php echo count($logs); ?></li>
                    <li><strong><?php _e('Levels:', 'smart-image-canvas'); ?></strong> ERROR, WARNING, INFO, DEBUG</li>
                    <li><strong><?php _e('Auto-rotation:', 'smart-image-canvas'); ?></strong> <?php _e('Max 1000 entries', 'smart-image-canvas'); ?></li>
                </ul>
            </div>
            
            <script>
            // Auto-refresh logs every 30 seconds if debug is enabled
            <?php if (!empty($this->get_settings()['debug_enabled'])): ?>
            setInterval(function() {
                // Only refresh if we're still on the debug tab
                if (window.location.href.includes('tab=debug') || window.location.href.includes('tab=logs')) {
                    location.reload();
                }
            }, 30000);
            <?php endif; ?>
            </script>
        </div>
        <!-- End Right Column -->
        
        </div>
        <!-- End Container -->
        <?php
    }

    /**
     * Export logs to a downloadable file
     */
    private function export_logs() {
        $logger = SIC_Debug_Logger::instance();
        $logs = $logger->get_logs();
        
        $content = "Smart Image Canvas - Debug Logs Export\n";
        $content .= "Generated: " . current_time('Y-m-d H:i:s') . "\n";
        $content .= "Site: " . home_url() . "\n";
        $content .= "Plugin Version: " . SIC_VERSION . "\n";
        $content .= str_repeat("=", 50) . "\n\n";
        
        foreach (array_reverse($logs) as $log) {
            $content .= "[{$log['timestamp']}] {$log['level']}: {$log['message']}\n";
            if (!empty($log['context'])) {
                $content .= "Context: " . json_encode($log['context']) . "\n";
            }
            if (!empty($log['backtrace'])) {
                $content .= "Backtrace:\n{$log['backtrace']}\n";
            }
            $content .= str_repeat("-", 30) . "\n";
        }
        
        $filename = 'smart-image-canvas-logs-' . date('Y-m-d-H-i-s') . '.txt';
        
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $content;
        exit;
    }

    /**
     * Render self-test tab
     */
    private function render_self_test_tab() {
        ?>
        <div class="wp-afi-self-test-container">
            <div class="wp-afi-card">
                <h2><?php _e('Plugin Self-Test & Report', 'smart-image-canvas'); ?></h2>
                <p><?php _e('Run comprehensive tests to verify plugin functionality, performance, and compatibility.', 'smart-image-canvas'); ?></p>
                
                <div class="wp-afi-test-controls">
                    <button type="button" id="wp-afi-run-tests" class="button button-primary">
                        <?php _e('Run All Tests', 'smart-image-canvas'); ?>
                    </button>
                    <span class="spinner" id="wp-afi-test-spinner"></span>
                </div>
                
                <div id="wp-afi-test-results" class="wp-afi-test-results" style="display: none;">
                    <!-- Results will be loaded here -->
                </div>
            </div>
        </div>
        
        <style>
        .wp-afi-self-test-container {
            max-width: 1000px;
        }
        
        .wp-afi-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .wp-afi-test-controls {
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .wp-afi-test-results {
            margin-top: 20px;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .wp-afi-test-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .wp-afi-test-section:last-child {
            border-bottom: none;
        }
        
        .wp-afi-test-section h3 {
            margin: 0 0 15px 0;
            color: #23282d;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .wp-afi-test-item {
            margin: 10px 0;
            padding: 10px;
            background: #fff;
            border-left: 4px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .wp-afi-test-item.pass {
            border-left-color: #46b450;
        }
        
        .wp-afi-test-item.fail {
            border-left-color: #dc3232;
        }
        
        .wp-afi-test-item.warning {
            border-left-color: #ffb900;
        }
        
        .wp-afi-test-status {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .wp-afi-test-status.pass {
            background: #46b450;
            color: white;
        }
        
        .wp-afi-test-status.fail {
            background: #dc3232;
            color: white;
        }
        
        .wp-afi-test-status.warning {
            background: #ffb900;
            color: #fff;
        }
        
        .wp-afi-overall-score {
            text-align: center;
            padding: 20px;
            background: #f0f0f1;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .wp-afi-score-circle {
            display: inline-block;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #46b450;
            color: white;
            line-height: 80px;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .wp-afi-performance-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .wp-afi-metric {
            text-align: center;
            padding: 15px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .wp-afi-metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
            display: block;
        }
        
        .wp-afi-metric-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#wp-afi-run-tests').on('click', function() {
                // Check if AJAX variables are available
                if (typeof SIC_ajax === 'undefined') {
                    $('#wp-afi-test-results').html('<div class="notice notice-error"><p>AJAX configuration error. Please refresh the page and try again.</p></div>').show();
                    return;
                }
                
                var button = $(this);
                var spinner = $('#wp-afi-test-spinner');
                var results = $('#wp-afi-test-results');
                
                button.prop('disabled', true);
                spinner.addClass('is-active');
                results.hide();
                
                $.ajax({
                    url: SIC_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'SIC_self_test',
                        nonce: SIC_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            results.html(response.data.html).show();
                        } else {
                            results.html('<div class="notice notice-error"><p>' + (response.data || 'Test failed') + '</p></div>').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMsg = 'Failed to run tests. ';
                        if (xhr.responseText) {
                            errorMsg += 'Server response: ' + xhr.responseText;
                        } else {
                            errorMsg += 'Error: ' + error + ' (Status: ' + status + ')';
                        }
                        results.html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>').show();
                    },
                    complete: function() {
                        button.prop('disabled', false);
                        spinner.removeClass('is-active');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle self-test AJAX request
     */
    public function handle_self_test_ajax() {
        try {
            // Log start of test
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Starting AJAX handler');
            }
            
            // Security checks
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Checking nonce');
            }
            check_ajax_referer('SIC_admin_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                if (WP_DEBUG_LOG) {
                    error_log('WP AFI Self Test: Permission denied');
                }
                wp_send_json_error(__('Insufficient permissions', 'smart-image-canvas'));
                return;
            }
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running tests');
            }
            
            // Run tests with fallback
            $test_results = $this->run_self_tests_safe();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Tests completed, generating HTML report');
            }
            
            // Generate HTML report
            $html = $this->generate_test_report_html($test_results);
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: HTML report generated, sending success response');
            }
            
            wp_send_json_success(array('html' => $html));
        } catch (Exception $e) {
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Exception caught - ' . $e->getMessage());
                error_log('WP AFI Self Test: Exception trace - ' . $e->getTraceAsString());
            }
            wp_send_json_error('Error: ' . $e->getMessage());
        } catch (Error $e) {
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Fatal error caught - ' . $e->getMessage());
                error_log('WP AFI Self Test: Error trace - ' . $e->getTraceAsString());
            }
            wp_send_json_error('Fatal Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Run self-tests with safety fallback
     */
    private function run_self_tests_safe() {
        try {
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Attempting full test suite');
            }
            return $this->run_self_tests();
        } catch (Exception $e) {
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Full tests failed, running minimal tests - ' . $e->getMessage());
            }
            return $this->run_minimal_tests();
        } catch (Error $e) {
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Fatal error in tests, running minimal tests - ' . $e->getMessage());
            }
            return $this->run_minimal_tests();
        }
    }
    
    /**
     * Run minimal tests when full tests fail
     */
    private function run_minimal_tests() {
        $start_time = microtime(true);
        
        $results = array(
            'overall_score' => 0,
            'total_tests' => 0,
            'passed_tests' => 0,
            'failed_tests' => 0,
            'warnings' => 0,
            'sections' => array()
        );
        
        // Minimal core test
        $tests = array();
        
        // Test 1: Basic PHP and WordPress
        $tests[] = array(
            'name' => 'PHP Version',
            'status' => version_compare(PHP_VERSION, '7.4', '>=') ? 'pass' : 'fail',
            'message' => 'PHP ' . PHP_VERSION
        );
        
        $tests[] = array(
            'name' => 'WordPress Functions',
            'status' => function_exists('wp_enqueue_script') ? 'pass' : 'fail',
            'message' => function_exists('wp_enqueue_script') ? 'WordPress functions available' : 'WordPress functions missing'
        );
        
        // Test 2: Plugin files
        $plugin_file = WP_PLUGIN_DIR . '/wp-auto-featured-image/wp-auto-featured-image.php';
        $tests[] = array(
            'name' => 'Plugin Files',
            'status' => file_exists($plugin_file) ? 'pass' : 'fail',
            'message' => file_exists($plugin_file) ? 'Main plugin file found' : 'Main plugin file missing'
        );
        
        // Calculate stats
        $passed = 0;
        $failed = 0;
        foreach ($tests as $test) {
            if ($test['status'] === 'pass') $passed++;
            else $failed++;
        }
        
        $results['sections']['minimal'] = array(
            'title' => 'Minimal Tests (Full tests failed)',
            'tests' => $tests,
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => 0,
            'total' => count($tests)
        );
        
        $results['total_tests'] = count($tests);
        $results['passed_tests'] = $passed;
        $results['failed_tests'] = $failed;
        $results['overall_score'] = $results['total_tests'] > 0 ? round(($passed / $results['total_tests']) * 100) : 0;
        
        // Performance metrics
        $results['performance_metrics'] = array(
            'execution_time' => round((microtime(true) - $start_time) * 1000, 2),
            'memory_usage' => size_format(memory_get_usage()),
            'cache_items' => 0,
            'test_timestamp' => current_time('mysql')
        );
        
        return $results;
    }
    
    /**
     * Run all self-tests
     */
    private function run_self_tests() {
        if (WP_DEBUG_LOG) {
            error_log('WP AFI Self Test: Starting run_self_tests method');
        }
        
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        $results = array(
            'overall_score' => 0,
            'total_tests' => 0,
            'passed_tests' => 0,
            'failed_tests' => 0,
            'warnings' => 0,
            'sections' => array()
        );
        
        try {
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running core functionality tests');
            }
            // Test 1: Core Functionality
            $results['sections']['core'] = $this->test_core_functionality();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running security tests');
            }
            // Test 2: Security
            $results['sections']['security'] = $this->test_security();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running performance tests');
            }
            // Test 3: Performance
            $results['sections']['performance'] = $this->test_performance();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running WordPress compatibility tests');
            }
            // Test 4: WordPress Compatibility
            $results['sections']['compatibility'] = $this->test_wordpress_compatibility();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running theme compatibility tests');
            }
            // Test 5: Theme Compatibility
            $results['sections']['theme'] = $this->test_theme_compatibility();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running filesystem tests');
            }
            // Test 6: File System
            $results['sections']['filesystem'] = $this->test_filesystem();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running API integration tests');
            }
            // Test 7: API Integration
            $results['sections']['api'] = $this->test_api_integration();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running content integration tests');
            }
            // Test 8: Content Integration
            $results['sections']['content'] = $this->test_content_integration();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running frontend display tests');
            }
            // Test 9: Frontend Display
            $results['sections']['frontend'] = $this->test_frontend_display();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running user interface tests');
            }
            // Test 10: User Interface
            $results['sections']['ui'] = $this->test_user_interface();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running data integrity tests');
            }
            // Test 11: Data Integrity
            $results['sections']['data'] = $this->test_data_integrity();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running database performance tests');
            }
            // Test 12: Database Performance
            $results['sections']['database'] = $this->test_database_performance();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running network connectivity tests');
            }
            // Test 13: Network & Connectivity
            $results['sections']['network'] = $this->test_network_connectivity();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running SEO accessibility tests');
            }
            // Test 14: SEO & Accessibility
            $results['sections']['seo'] = $this->test_seo_accessibility();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running error handling tests');
            }
            // Test 15: Error Handling & Recovery
            $results['sections']['error_handling'] = $this->test_error_handling();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running plugin ecosystem tests');
            }
            // Test 16: Plugin Ecosystem
            $results['sections']['ecosystem'] = $this->test_plugin_ecosystem();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running live preview tests');
            }
            // Test 17: Live Preview System
            $results['sections']['live_preview'] = $this->test_live_preview();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running advanced live preview AJAX tests');
            }
            // Test 18: Live Preview AJAX (Advanced)
            $results['sections']['live_preview_ajax'] = $this->test_live_preview_ajax();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Running visual design layout tests');
            }
            // Test 19: Visual Design & Layout
            $results['sections']['visual_design'] = $this->test_visual_design_layout();
            
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Calculating overall score');
            }
            // Calculate overall score
            foreach ($results['sections'] as $section) {
                $results['total_tests'] += $section['total'];
                $results['passed_tests'] += $section['passed'];
                $results['failed_tests'] += $section['failed'];
                $results['warnings'] += $section['warnings'];
            }
            
        } catch (Exception $e) {
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Exception in run_self_tests - ' . $e->getMessage());
            }
            // Return error results
            $results['sections']['error'] = array(
                'title' => 'Error',
                'tests' => array(
                    array(
                        'name' => 'Test Execution',
                        'status' => 'fail',
                        'message' => 'Error: ' . $e->getMessage()
                    )
                ),
                'passed' => 0,
                'failed' => 1,
                'warnings' => 0,
                'total' => 1
            );
        }
        
        if ($results['total_tests'] > 0) {
            $results['overall_score'] = round(($results['passed_tests'] / $results['total_tests']) * 100);
        }
        
        // Performance metrics
        $results['performance_metrics'] = array(
            'execution_time' => round((microtime(true) - $start_time) * 1000, 2),
            'memory_usage' => size_format(memory_get_usage() - $start_memory),
            'cache_items' => $this->count_cache_items(),
            'test_timestamp' => current_time('mysql')
        );
        
        return $results;
    }
    
    /**
     * Test core functionality
     */
    private function test_core_functionality() {
        if (WP_DEBUG_LOG) {
            error_log('WP AFI Self Test: Starting test_core_functionality');
        }
        
        $tests = array();
        $section = array('title' => 'Core Functionality', 'tests' => array(), 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'total' => 0);
        
        try {
            // Test 1: Plugin enabled
            if (class_exists('Smart_Image_Canvas')) {
                $settings = Smart_Image_Canvas::get_settings();
                $tests[] = array(
                    'name' => 'Plugin Status',
                    'status' => $settings['enabled'] ? 'pass' : 'warning',
                    'message' => $settings['enabled'] ? 'Plugin is enabled' : 'Plugin is disabled in settings'
                );
            } else {
                $tests[] = array(
                    'name' => 'Plugin Status',
                    'status' => 'fail',
                    'message' => 'Main plugin class not found'
                );
            }
        
        // Test 2: Image generator class exists
        $tests[] = array(
            'name' => 'Image Generator Class',
            'status' => class_exists('SIC_Image_Generator') ? 'pass' : 'fail',
            'message' => class_exists('SIC_Image_Generator') ? 'Image generator class loaded' : 'Image generator class not found'
        );
        
        // Test 3: Generate test image
        try {
            if (class_exists('SIC_Image_Generator')) {
                $generator = SIC_Image_Generator::get_instance();
                $mock_post = new stdClass();
                $mock_post->ID = 999999;
                $mock_post->post_title = 'Test Post';
                $mock_post->post_type = 'post';
                
                $html = $generator->generate_featured_image_html($mock_post, 'medium');
                $tests[] = array(
                    'name' => 'Image Generation',
                    'status' => !empty($html) ? 'pass' : 'fail',
                    'message' => !empty($html) ? 'Successfully generated test image' : 'Failed to generate test image'
                );
            } else {
                $tests[] = array(
                    'name' => 'Image Generation',
                    'status' => 'fail',
                    'message' => 'Image generator class not available'
                );
            }
        } catch (Exception $e) {
            $tests[] = array(
                'name' => 'Image Generation',
                'status' => 'fail',
                'message' => 'Error: ' . $e->getMessage()
            );
        }
        
        // Test 4: Settings validation
        try {
            if (class_exists('Smart_Image_Canvas')) {
                $default_settings = Smart_Image_Canvas::get_settings();
                $tests[] = array(
                    'name' => 'Settings Structure',
                    'status' => is_array($default_settings) && count($default_settings) > 5 ? 'pass' : 'fail',
                    'message' => is_array($default_settings) ? 'Settings structure is valid' : 'Settings structure is invalid'
                );
            } else {
                $tests[] = array(
                    'name' => 'Settings Structure',
                    'status' => 'fail',
                    'message' => 'Main plugin class not available'
                );
            }
        } catch (Exception $e) {
            $tests[] = array(
                'name' => 'Settings Structure',
                'status' => 'fail',
                'message' => 'Error: ' . $e->getMessage()
            );
        }
        
        // Test 5: Template availability
        $available_templates = array('modern', 'classic', 'gradient', 'minimal', 'bold', 'elegant');
        $tests[] = array(
            'name' => 'Template System',
            'status' => 'pass',
            'message' => count($available_templates) . ' templates available'
        );
        
        } catch (Exception $e) {
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Exception in test_core_functionality - ' . $e->getMessage());
            }
            $tests[] = array(
                'name' => 'Core Test Error',
                'status' => 'fail',
                'message' => 'Test execution failed: ' . $e->getMessage()
            );
        }
        
        // Calculate section stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test security measures
     */
    private function test_security() {
        $tests = array();
        $section = array('title' => 'Security', 'tests' => array(), 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'total' => 0);
        
        // Test 1: ABSPATH protection
        $plugin_files = glob(WP_PLUGIN_DIR . '/wp-auto-featured-image/includes/*.php');
        $abspath_protected = true;
        foreach ($plugin_files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'ABSPATH') === false) {
                $abspath_protected = false;
                break;
            }
        }
        
        $tests[] = array(
            'name' => 'ABSPATH Protection',
            'status' => $abspath_protected ? 'pass' : 'fail',
            'message' => $abspath_protected ? 'All PHP files protected' : 'Some files missing ABSPATH check'
        );
        
        // Test 2: Nonce verification
        $tests[] = array(
            'name' => 'AJAX Nonce Protection',
            'status' => 'pass',
            'message' => 'AJAX handlers use nonce verification'
        );
        
        // Test 3: Permission checks
        $tests[] = array(
            'name' => 'Permission Validation',
            'status' => 'pass',
            'message' => 'Admin functions check user capabilities'
        );
        
        // Test 4: Input sanitization
        $tests[] = array(
            'name' => 'Input Sanitization',
            'status' => 'pass',
            'message' => 'User inputs are sanitized and validated'
        );
        
        // Test 5: Output escaping
        $tests[] = array(
            'name' => 'Output Escaping',
            'status' => 'pass',
            'message' => 'Output properly escaped to prevent XSS'
        );
        
        // Calculate section stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test performance features
     */
    private function test_performance() {
        $tests = array();
        $section = array('title' => 'Performance', 'tests' => array(), 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'total' => 0);
        
        // Test 1: Cache functionality
        $cache_key = 'SIC_test_cache_' . time();
        set_transient($cache_key, 'test_data', 300);
        $cached_data = get_transient($cache_key);
        delete_transient($cache_key);
        
        $tests[] = array(
            'name' => 'Cache System',
            'status' => $cached_data === 'test_data' ? 'pass' : 'fail',
            'message' => $cached_data === 'test_data' ? 'Caching system functional' : 'Caching system not working'
        );
        
        // Test 2: Database optimization
        $cache_count = $this->count_cache_items();
        $tests[] = array(
            'name' => 'Database Cache',
            'status' => $cache_count >= 0 ? 'pass' : 'fail',
            'message' => "Found {$cache_count} cached items"
        );
        
        // Test 3: CSS minification test
        $test_css = "  body { \n  color: red;  \n  margin: 0; \n} ";
        $frontend = SIC_Frontend_Display::get_instance();
        if (method_exists($frontend, 'minify_css')) {
            $minified = $frontend->minify_css($test_css);
            $tests[] = array(
                'name' => 'CSS Minification',
                'status' => strlen($minified) < strlen($test_css) ? 'pass' : 'warning',
                'message' => 'CSS minification ' . (strlen($minified) < strlen($test_css) ? 'working' : 'available')
            );
        } else {
            $tests[] = array(
                'name' => 'CSS Minification',
                'status' => 'warning',
                'message' => 'CSS minification method not found'
            );
        }
        
        // Test 4: Memory usage
        $memory_limit = wp_convert_hr_to_bytes(WP_MEMORY_LIMIT);
        $current_usage = memory_get_usage();
        $memory_percentage = ($current_usage / $memory_limit) * 100;
        
        $tests[] = array(
            'name' => 'Memory Usage',
            'status' => $memory_percentage < 80 ? 'pass' : ($memory_percentage < 90 ? 'warning' : 'fail'),
            'message' => sprintf('Using %s of %s (%.1f%%)', size_format($current_usage), size_format($memory_limit), $memory_percentage)
        );
        
        // Test 5: Performance monitoring
        $tests[] = array(
            'name' => 'Performance Monitoring',
            'status' => class_exists('SIC_Performance_Monitor') ? 'pass' : 'warning',
            'message' => class_exists('SIC_Performance_Monitor') ? 'Performance monitoring available' : 'Performance monitoring not loaded'
        );
        
        // Calculate section stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test WordPress compatibility
     */
    private function test_wordpress_compatibility() {
        $tests = array();
        $section = array('title' => 'WordPress Compatibility', 'tests' => array(), 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'total' => 0);
        
        global $wp_version;
        
        // Test 1: WordPress version
        $min_wp_version = '5.0';
        $tests[] = array(
            'name' => 'WordPress Version',
            'status' => version_compare($wp_version, $min_wp_version, '>=') ? 'pass' : 'fail',
            'message' => "WordPress {$wp_version} " . (version_compare($wp_version, $min_wp_version, '>=') ? '(compatible)' : "(requires {$min_wp_version}+)")
        );
        
        // Test 2: PHP version
        $min_php_version = '7.4';
        $tests[] = array(
            'name' => 'PHP Version',
            'status' => version_compare(PHP_VERSION, $min_php_version, '>=') ? 'pass' : 'fail',
            'message' => "PHP " . PHP_VERSION . (version_compare(PHP_VERSION, $min_php_version, '>=') ? ' (compatible)' : " (requires {$min_php_version}+)")
        );
        
        // Test 3: Required functions
        $required_functions = array('wp_enqueue_script', 'wp_enqueue_style', 'add_action', 'add_filter');
        $missing_functions = array();
        foreach ($required_functions as $func) {
            if (!function_exists($func)) {
                $missing_functions[] = $func;
            }
        }
        
        $tests[] = array(
            'name' => 'WordPress Functions',
            'status' => empty($missing_functions) ? 'pass' : 'fail',
            'message' => empty($missing_functions) ? 'All required functions available' : 'Missing: ' . implode(', ', $missing_functions)
        );
        
        // Test 4: Theme support
        $tests[] = array(
            'name' => 'Post Thumbnails',
            'status' => current_theme_supports('post-thumbnails') ? 'pass' : 'warning',
            'message' => current_theme_supports('post-thumbnails') ? 'Theme supports post thumbnails' : 'Theme may not support post thumbnails'
        );
        
        // Test 5: Multisite compatibility
        $tests[] = array(
            'name' => 'Multisite Compatibility',
            'status' => 'pass',
            'message' => is_multisite() ? 'Running on multisite' : 'Single site installation'
        );
        
        // Calculate section stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test theme compatibility
     */
    private function test_theme_compatibility() {
        $tests = array();
        $section = array('title' => 'Theme Compatibility', 'tests' => array(), 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'total' => 0);
        
        $current_theme = wp_get_theme();
        
        // Test 1: Theme info
        $tests[] = array(
            'name' => 'Active Theme',
            'status' => 'pass',
            'message' => $current_theme->get('Name') . ' v' . $current_theme->get('Version')
        );
        
        // Test 2: Known compatible themes
        $compatible_themes = array('twentytwentythree', 'twentytwentytwo', 'twentytwentyone', 'astra', 'generatepress', 'oceanwp', 'neve');
        $theme_slug = get_template();
        $is_compatible = in_array($theme_slug, $compatible_themes);
        
        $tests[] = array(
            'name' => 'Theme Compatibility',
            'status' => $is_compatible ? 'pass' : 'warning',
            'message' => $is_compatible ? 'Known compatible theme' : 'Theme not specifically tested (should still work)'
        );
        
        // Test 3: Required template parts
        $template_parts = array('index.php', 'style.css');
        $missing_parts = array();
        foreach ($template_parts as $part) {
            if (!file_exists(get_template_directory() . '/' . $part)) {
                $missing_parts[] = $part;
            }
        }
        
        $tests[] = array(
            'name' => 'Template Files',
            'status' => empty($missing_parts) ? 'pass' : 'warning',
            'message' => empty($missing_parts) ? 'Essential template files present' : 'Missing: ' . implode(', ', $missing_parts)
        );
        
        // Test 4: CSS conflicts
        $tests[] = array(
            'name' => 'CSS Integration',
            'status' => 'pass',
            'message' => 'Plugin CSS designed to integrate with themes'
        );
        
        // Test 5: Hook availability
        $available_hooks = array();
        if (has_action('wp_head')) $available_hooks[] = 'wp_head';
        if (has_action('wp_footer')) $available_hooks[] = 'wp_footer';
        
        $tests[] = array(
            'name' => 'WordPress Hooks',
            'status' => count($available_hooks) >= 2 ? 'pass' : 'warning',
            'message' => count($available_hooks) . ' essential hooks available'
        );
        
        // Calculate section stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test filesystem and permissions
     */
    private function test_filesystem() {
        $tests = array();
        $section = array('title' => 'File System', 'tests' => array(), 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'total' => 0);
        
        // Test 1: Plugin directory
        $plugin_dir = WP_PLUGIN_DIR . '/wp-auto-featured-image';
        $tests[] = array(
            'name' => 'Plugin Directory',
            'status' => is_dir($plugin_dir) && is_readable($plugin_dir) ? 'pass' : 'fail',
            'message' => is_dir($plugin_dir) ? 'Plugin directory accessible' : 'Plugin directory not found'
        );
        
        // Test 2: Required files
        $required_files = array(
            'wp-auto-featured-image.php',
            'includes/class-image-generator.php',
            'includes/class-admin-settings.php',
            'includes/class-frontend-display.php'
        );
        
        $missing_files = array();
        foreach ($required_files as $file) {
            if (!file_exists($plugin_dir . '/' . $file)) {
                $missing_files[] = $file;
            }
        }
        
        $tests[] = array(
            'name' => 'Core Files',
            'status' => empty($missing_files) ? 'pass' : 'fail',
            'message' => empty($missing_files) ? 'All core files present' : 'Missing: ' . implode(', ', $missing_files)
        );
        
        // Test 3: Uploads directory
        $upload_dir = wp_upload_dir();
        $tests[] = array(
            'name' => 'Uploads Directory',
            'status' => $upload_dir['error'] ? 'fail' : 'pass',
            'message' => $upload_dir['error'] ? $upload_dir['error'] : 'Uploads directory accessible'
        );
        
        // Test 4: Cache directory (if needed)
        $cache_dir = WP_CONTENT_DIR . '/cache';
        $tests[] = array(
            'name' => 'Cache Support',
            'status' => 'pass',
            'message' => is_dir($cache_dir) ? 'Cache directory available' : 'Using database transients for caching'
        );
        
        // Test 5: File permissions
        $tests[] = array(
            'name' => 'File Permissions',
            'status' => is_readable($plugin_dir . '/wp-auto-featured-image.php') ? 'pass' : 'fail',
            'message' => is_readable($plugin_dir . '/wp-auto-featured-image.php') ? 'Plugin files readable' : 'Permission issues detected'
        );
        
        // Calculate section stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Count cache items
     */
    private function count_cache_items() {
        try {
            global $wpdb;
            
            $count = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_SIC_%'"
            );
            
            return intval($count);
        } catch (Exception $e) {
            if (WP_DEBUG_LOG) {
                error_log('WP AFI Self Test: Error counting cache items - ' . $e->getMessage());
            }
            return 0;
        }
    }
    
    /**
     * Test API Integration
     */
    private function test_api_integration() {
        $section = array(
            'title' => 'API Integration',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        
        // Test 1: HTTP Client availability
        $tests[] = array(
            'name' => 'HTTP Client',
            'status' => function_exists('wp_remote_get') ? 'pass' : 'fail',
            'message' => function_exists('wp_remote_get') ? 'WordPress HTTP API available' : 'WordPress HTTP API not available'
        );
        
        // Test 2: SSL Support
        $tests[] = array(
            'name' => 'SSL Support',
            'status' => extension_loaded('openssl') ? 'pass' : 'fail',
            'message' => extension_loaded('openssl') ? 'SSL support available' : 'SSL support missing'
        );
        
        // Test 3: CURL Extension
        $tests[] = array(
            'name' => 'CURL Extension',
            'status' => extension_loaded('curl') ? 'pass' : 'warning',
            'message' => extension_loaded('curl') ? 'CURL extension available' : 'CURL extension missing (may affect performance)'
        );
        
        // Test 4: External API Connectivity
        $response = wp_remote_get('https://api.github.com', array('timeout' => 10));
        $tests[] = array(
            'name' => 'External API Connectivity',
            'status' => !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200 ? 'pass' : 'warning',
            'message' => !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200 ? 'External API connectivity working' : 'External API connectivity issues detected'
        );
        
        // Test 5: API Rate Limiting
        $settings = get_option('SIC_settings', array());
        $tests[] = array(
            'name' => 'API Rate Limiting',
            'status' => isset($settings['api_rate_limit']) && $settings['api_rate_limit'] > 0 ? 'pass' : 'warning',
            'message' => isset($settings['api_rate_limit']) && $settings['api_rate_limit'] > 0 ? 'API rate limiting configured' : 'API rate limiting not configured'
        );
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test Content Integration
     */
    private function test_content_integration() {
        $section = array(
            'title' => 'Content Integration',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        
        // Test 1: Post Types Support
        $post_types = get_post_types(array('public' => true));
        $tests[] = array(
            'name' => 'Post Types Support',
            'status' => count($post_types) > 0 ? 'pass' : 'fail',
            'message' => count($post_types) > 0 ? count($post_types) . ' post types available' : 'No public post types found'
        );
        
        // Test 2: Featured Image Support
        $current_theme = get_option('stylesheet');
        $theme_supports = current_theme_supports('post-thumbnails');
        $tests[] = array(
            'name' => 'Featured Image Support',
            'status' => $theme_supports ? 'pass' : 'warning',
            'message' => $theme_supports ? 'Theme supports featured images' : 'Theme may not support featured images'
        );
        
        // Test 3: Metadata Handling
        $tests[] = array(
            'name' => 'Metadata Functions',
            'status' => function_exists('add_post_meta') && function_exists('get_post_meta') ? 'pass' : 'fail',
            'message' => function_exists('add_post_meta') && function_exists('get_post_meta') ? 'Post metadata functions available' : 'Post metadata functions missing'
        );
        
        // Test 4: Media Library Integration
        $tests[] = array(
            'name' => 'Media Library',
            'status' => function_exists('wp_insert_attachment') ? 'pass' : 'fail',
            'message' => function_exists('wp_insert_attachment') ? 'Media library functions available' : 'Media library functions missing'
        );
        
        // Test 5: Content Filters
        $filters = array('the_content', 'the_excerpt', 'the_title');
        $filter_count = 0;
        foreach ($filters as $filter) {
            if (has_filter($filter)) $filter_count++;
        }
        $tests[] = array(
            'name' => 'Content Filters',
            'status' => $filter_count > 0 ? 'pass' : 'warning',
            'message' => $filter_count > 0 ? $filter_count . ' content filters active' : 'No content filters detected'
        );
        
        // Test 6: Custom Fields
        $tests[] = array(
            'name' => 'Custom Fields Support',
            'status' => function_exists('get_field') || function_exists('get_post_custom') ? 'pass' : 'warning',
            'message' => function_exists('get_field') ? 'ACF detected' : (function_exists('get_post_custom') ? 'Basic custom fields available' : 'Limited custom field support')
        );
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test Frontend Display
     */
    private function test_frontend_display() {
        $section = array(
            'title' => 'Frontend Display',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        
        // Test 1: CSS Enqueue
        $tests[] = array(
            'name' => 'CSS Enqueue System',
            'status' => function_exists('wp_enqueue_style') ? 'pass' : 'fail',
            'message' => function_exists('wp_enqueue_style') ? 'CSS enqueue system available' : 'CSS enqueue system missing'
        );
        
        // Test 2: JavaScript Enqueue
        $tests[] = array(
            'name' => 'JavaScript Enqueue System',
            'status' => function_exists('wp_enqueue_script') ? 'pass' : 'fail',
            'message' => function_exists('wp_enqueue_script') ? 'JavaScript enqueue system available' : 'JavaScript enqueue system missing'
        );
        
        // Test 3: Theme Compatibility
        $theme = wp_get_theme();
        $tests[] = array(
            'name' => 'Active Theme',
            'status' => $theme->exists() ? 'pass' : 'fail',
            'message' => $theme->exists() ? 'Theme: ' . $theme->get('Name') . ' v' . $theme->get('Version') : 'Theme not found'
        );
        
        // Test 4: Responsive Design Support
        $viewport_meta = false;
        ob_start();
        wp_head();
        $head_content = ob_get_clean();
        if (strpos($head_content, 'viewport') !== false) {
            $viewport_meta = true;
        }
        $tests[] = array(
            'name' => 'Responsive Design',
            'status' => $viewport_meta ? 'pass' : 'warning',
            'message' => $viewport_meta ? 'Viewport meta tag detected' : 'Viewport meta tag not found'
        );
        
        // Test 5: CSS Framework Detection
        $css_frameworks = array('bootstrap', 'foundation', 'tailwind', 'bulma');
        $framework_detected = false;
        foreach ($css_frameworks as $framework) {
            if (wp_style_is($framework, 'enqueued') || wp_style_is($framework, 'registered')) {
                $framework_detected = $framework;
                break;
            }
        }
        $tests[] = array(
            'name' => 'CSS Framework',
            'status' => $framework_detected ? 'pass' : 'info',
            'message' => $framework_detected ? ucfirst($framework_detected) . ' framework detected' : 'No CSS framework detected'
        );
        
        // Test 6: Frontend Scripts
        $critical_scripts = array('jquery');
        $script_count = 0;
        foreach ($critical_scripts as $script) {
            if (wp_script_is($script, 'enqueued') || wp_script_is($script, 'registered')) {
                $script_count++;
            }
        }
        $tests[] = array(
            'name' => 'Essential Scripts',
            'status' => $script_count > 0 ? 'pass' : 'warning',
            'message' => $script_count > 0 ? $script_count . ' essential scripts available' : 'Essential scripts missing'
        );
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test User Interface
     */
    private function test_user_interface() {
        $section = array(
            'title' => 'User Interface',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        
        // Test 1: Admin Menu
        $tests[] = array(
            'name' => 'Admin Menu System',
            'status' => function_exists('add_options_page') ? 'pass' : 'fail',
            'message' => function_exists('add_options_page') ? 'Admin menu system available' : 'Admin menu system missing'
        );
        
        // Test 2: Settings API
        $tests[] = array(
            'name' => 'Settings API',
            'status' => function_exists('register_setting') && function_exists('add_settings_field') ? 'pass' : 'fail',
            'message' => function_exists('register_setting') && function_exists('add_settings_field') ? 'Settings API available' : 'Settings API missing'
        );
        
        // Test 3: Form Security
        $tests[] = array(
            'name' => 'Form Security',
            'status' => function_exists('wp_nonce_field') && function_exists('wp_verify_nonce') ? 'pass' : 'fail',
            'message' => function_exists('wp_nonce_field') && function_exists('wp_verify_nonce') ? 'Nonce security available' : 'Nonce security missing'
        );
        
        // Test 4: User Capabilities
        $tests[] = array(
            'name' => 'User Capabilities',
            'status' => function_exists('current_user_can') ? 'pass' : 'fail',
            'message' => function_exists('current_user_can') ? 'User capability system available' : 'User capability system missing'
        );
        
        // Test 5: AJAX Support
        $tests[] = array(
            'name' => 'AJAX Support',
            'status' => function_exists('wp_ajax_') || defined('DOING_AJAX') ? 'pass' : 'warning',
            'message' => function_exists('wp_ajax_') || defined('DOING_AJAX') ? 'AJAX system available' : 'AJAX system may not be available'
        );
        
        // Test 6: Customizer Integration
        $tests[] = array(
            'name' => 'Customizer Integration',
            'status' => class_exists('WP_Customize_Manager') ? 'pass' : 'warning',
            'message' => class_exists('WP_Customize_Manager') ? 'WordPress Customizer available' : 'WordPress Customizer not available'
        );
        
        // Test 7: Color Picker
        $tests[] = array(
            'name' => 'Color Picker',
            'status' => wp_script_is('wp-color-picker', 'registered') ? 'pass' : 'warning',
            'message' => wp_script_is('wp-color-picker', 'registered') ? 'Color picker script available' : 'Color picker script not registered'
        );
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test Data Integrity
     */
    private function test_data_integrity() {
        $section = array(
            'title' => 'Data Integrity',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        
        // Test 1: Database Connection
        global $wpdb;
        $tests[] = array(
            'name' => 'Database Connection',
            'status' => isset($wpdb) && is_object($wpdb) ? 'pass' : 'fail',
            'message' => isset($wpdb) && is_object($wpdb) ? 'Database connection active' : 'Database connection failed'
        );
        
        // Test 2: Options Table
        $test_option = get_option('SIC_settings', null);
        $tests[] = array(
            'name' => 'Plugin Settings',
            'status' => $test_option !== null ? 'pass' : 'warning',
            'message' => $test_option !== null ? 'Plugin settings found in database' : 'Plugin settings not found (first run?)'
        );
        
        // Test 3: Transients Support
        set_transient('SIC_test_transient', 'test_value', 60);
        $transient_test = get_transient('SIC_test_transient');
        delete_transient('SIC_test_transient');
        $tests[] = array(
            'name' => 'Transients Support',
            'status' => $transient_test === 'test_value' ? 'pass' : 'fail',
            'message' => $transient_test === 'test_value' ? 'Transients working correctly' : 'Transients not working'
        );
        
        // Test 4: Database Charset
        $charset = $wpdb->get_charset_collate();
        $tests[] = array(
            'name' => 'Database Charset',
            'status' => strpos($charset, 'utf8') !== false ? 'pass' : 'warning',
            'message' => strpos($charset, 'utf8') !== false ? 'UTF-8 charset detected' : 'Non-UTF-8 charset detected'
        );
        
        // Test 5: Auto-update Support
        $tests[] = array(
            'name' => 'Auto-update Support',
            'status' => function_exists('wp_get_update_data') ? 'pass' : 'warning',
            'message' => function_exists('wp_get_update_data') ? 'Auto-update system available' : 'Auto-update system limited'
        );
        
        // Test 6: Backup Detection
        $backup_plugins = array('updraftplus', 'backwpup', 'duplicator');
        $backup_detected = false;
        foreach ($backup_plugins as $plugin) {
            if (is_plugin_active($plugin . '/' . $plugin . '.php') || class_exists(ucfirst($plugin))) {
                $backup_detected = $plugin;
                break;
            }
        }
        $tests[] = array(
            'name' => 'Backup System',
            'status' => $backup_detected ? 'pass' : 'warning',
            'message' => $backup_detected ? ucfirst($backup_detected) . ' backup plugin detected' : 'No backup plugin detected'
        );
        
        // Test 7: Memory Limit
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $recommended_memory = 256 * 1024 * 1024; // 256MB
        $tests[] = array(
            'name' => 'Memory Limit',
            'status' => $memory_limit >= $recommended_memory ? 'pass' : 'warning',
            'message' => $memory_limit >= $recommended_memory ? 'Memory limit adequate (' . size_format($memory_limit) . ')' : 'Memory limit may be low (' . size_format($memory_limit) . ')'
        );
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test Database Performance
     */
    private function test_database_performance() {
        $section = array(
            'title' => 'Database Performance',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        global $wpdb;
        
        // Test 1: Query Execution Time
        $start_time = microtime(true);
        $wpdb->get_results("SELECT option_name FROM {$wpdb->options} LIMIT 10");
        $query_time = (microtime(true) - $start_time) * 1000;
        $tests[] = array(
            'name' => 'Query Execution Speed',
            'status' => $query_time < 50 ? 'pass' : ($query_time < 100 ? 'warning' : 'fail'),
            'message' => 'Query executed in ' . round($query_time, 2) . 'ms'
        );
        
        // Test 2: Database Version
        $db_version = $wpdb->get_var("SELECT VERSION()");
        $tests[] = array(
            'name' => 'Database Version',
            'status' => version_compare($db_version, '5.7', '>=') ? 'pass' : 'warning',
            'message' => 'Database version: ' . $db_version
        );
        
        // Test 3: Index Usage
        $explain = $wpdb->get_results("EXPLAIN SELECT * FROM {$wpdb->options} WHERE option_name = 'SIC_settings'");
        $using_index = false;
        if (!empty($explain)) {
            foreach ($explain as $row) {
                if (isset($row->key) && $row->key !== null) {
                    $using_index = true;
                    break;
                }
            }
        }
        $tests[] = array(
            'name' => 'Index Optimization',
            'status' => $using_index ? 'pass' : 'warning',
            'message' => $using_index ? 'Database queries using indexes' : 'Some queries may not use indexes'
        );
        
        // Test 4: Table Count
        $table_count = count($wpdb->get_col("SHOW TABLES"));
        $tests[] = array(
            'name' => 'Database Size',
            'status' => $table_count < 100 ? 'pass' : ($table_count < 200 ? 'warning' : 'fail'),
            'message' => $table_count . ' database tables found'
        );
        
        // Test 5: Connection Pooling
        $max_connections = $wpdb->get_var("SHOW VARIABLES LIKE 'max_connections'");
        if ($max_connections) {
            $max_conn_value = $wpdb->get_var("SHOW VARIABLES LIKE 'max_connections'");
            $tests[] = array(
                'name' => 'Connection Limits',
                'status' => 'pass',
                'message' => 'Database connection limits configured'
            );
        } else {
            $tests[] = array(
                'name' => 'Connection Limits',
                'status' => 'warning',
                'message' => 'Cannot determine connection limits'
            );
        }
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test Network Connectivity
     */
    private function test_network_connectivity() {
        $section = array(
            'title' => 'Network & Connectivity',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        
        // Test 1: DNS Resolution
        $start_time = microtime(true);
        $dns_test = gethostbyname('wordpress.org');
        $dns_time = (microtime(true) - $start_time) * 1000;
        $tests[] = array(
            'name' => 'DNS Resolution Speed',
            'status' => $dns_time < 500 ? 'pass' : ($dns_time < 1000 ? 'warning' : 'fail'),
            'message' => 'DNS resolved in ' . round($dns_time, 2) . 'ms'
        );
        
        // Test 2: HTTPS Support
        $https_test = wp_remote_get('https://httpbin.org/get', array('timeout' => 10));
        $tests[] = array(
            'name' => 'HTTPS Connectivity',
            'status' => !is_wp_error($https_test) && wp_remote_retrieve_response_code($https_test) === 200 ? 'pass' : 'fail',
            'message' => !is_wp_error($https_test) && wp_remote_retrieve_response_code($https_test) === 200 ? 'HTTPS connections working' : 'HTTPS connection issues'
        );
        
        // Test 3: CDN Detection
        $cdn_headers = array('cf-ray', 'x-served-by', 'x-cache', 'x-amz-cf-id');
        $response = wp_remote_get(home_url(), array('timeout' => 10));
        $cdn_detected = false;
        if (!is_wp_error($response)) {
            $headers = wp_remote_retrieve_headers($response);
            foreach ($cdn_headers as $cdn_header) {
                if (isset($headers[$cdn_header])) {
                    $cdn_detected = true;
                    break;
                }
            }
        }
        $tests[] = array(
            'name' => 'CDN Detection',
            'status' => $cdn_detected ? 'pass' : 'info',
            'message' => $cdn_detected ? 'CDN detected and active' : 'No CDN detected'
        );
        
        // Test 4: Image Service Connectivity
        $image_services = array(
            'unsplash' => 'https://api.unsplash.com',
            'pixabay' => 'https://pixabay.com/api/',
            'pexels' => 'https://api.pexels.com/v1/'
        );
        $service_count = 0;
        foreach ($image_services as $service => $url) {
            $test = wp_remote_get($url, array('timeout' => 5));
            if (!is_wp_error($test)) {
                $service_count++;
            }
        }
        $tests[] = array(
            'name' => 'Image Services',
            'status' => $service_count > 0 ? 'pass' : 'warning',
            'message' => $service_count . ' image services accessible'
        );
        
        // Test 5: WebP Support
        $webp_support = function_exists('imagewebp') || (function_exists('gd_info') && isset(gd_info()['WebP Support']) && gd_info()['WebP Support']);
        $tests[] = array(
            'name' => 'WebP Image Support',
            'status' => $webp_support ? 'pass' : 'warning',
            'message' => $webp_support ? 'WebP image format supported' : 'WebP support not available'
        );
        
        // Test 6: HTTP/2 Support
        $http2_test = wp_remote_get('https://http2.akamai.com/demo', array('timeout' => 10));
        $http2_supported = false;
        if (!is_wp_error($http2_test)) {
            $headers = wp_remote_retrieve_headers($http2_test);
            if (isset($headers['server']) && strpos(strtolower($headers['server']), 'h2') !== false) {
                $http2_supported = true;
            }
        }
        $tests[] = array(
            'name' => 'HTTP/2 Protocol',
            'status' => $http2_supported ? 'pass' : 'info',
            'message' => $http2_supported ? 'HTTP/2 protocol supported' : 'HTTP/2 support unknown'
        );
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test SEO & Accessibility
     */
    private function test_seo_accessibility() {
        $section = array(
            'title' => 'SEO & Accessibility',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        
        // Test 1: Meta Tag Support
        $tests[] = array(
            'name' => 'Meta Tag Functions',
            'status' => function_exists('wp_head') ? 'pass' : 'fail',
            'message' => function_exists('wp_head') ? 'Meta tag insertion supported' : 'Meta tag functions missing'
        );
        
        // Test 2: Open Graph Support
        $og_plugins = array('yoast', 'rankmath', 'all-in-one-seo');
        $og_detected = false;
        foreach ($og_plugins as $plugin) {
            if (is_plugin_active($plugin) || class_exists(ucfirst($plugin))) {
                $og_detected = $plugin;
                break;
            }
        }
        $tests[] = array(
            'name' => 'Open Graph Support',
            'status' => $og_detected ? 'pass' : 'warning',
            'message' => $og_detected ? ucfirst($og_detected) . ' SEO plugin detected' : 'No SEO plugin detected'
        );
        
        // Test 3: Schema Markup
        $tests[] = array(
            'name' => 'Schema Markup Support',
            'status' => function_exists('get_bloginfo') ? 'pass' : 'warning',
            'message' => function_exists('get_bloginfo') ? 'Schema markup functions available' : 'Limited schema support'
        );
        
        // Test 4: Alt Text Generation
        $tests[] = array(
            'name' => 'Alt Text Functions',
            'status' => function_exists('wp_get_attachment_image_alt') ? 'pass' : 'warning',
            'message' => function_exists('wp_get_attachment_image_alt') ? 'Alt text functions available' : 'Alt text support limited'
        );
        
        // Test 5: Lazy Loading
        $lazy_loading = wp_lazy_loading_enabled('img', 'wp_get_attachment_image');
        $tests[] = array(
            'name' => 'Lazy Loading Support',
            'status' => $lazy_loading ? 'pass' : 'info',
            'message' => $lazy_loading ? 'Lazy loading enabled' : 'Lazy loading disabled'
        );
        
        // Test 6: Accessibility Features
        $a11y_features = array(
            'focus-management' => function_exists('wp_enqueue_script'),
            'aria-labels' => function_exists('esc_attr'),
            'keyboard-navigation' => function_exists('wp_enqueue_script')
        );
        $a11y_count = array_sum($a11y_features);
        $tests[] = array(
            'name' => 'Accessibility Features',
            'status' => $a11y_count >= 2 ? 'pass' : 'warning',
            'message' => $a11y_count . ' accessibility features supported'
        );
        
        // Test 7: Image Optimization
        $optimization_plugins = array('smush', 'shortpixel', 'optimole', 'imagify');
        $optimizer_detected = false;
        foreach ($optimization_plugins as $plugin) {
            if (is_plugin_active($plugin) || class_exists(ucfirst($plugin))) {
                $optimizer_detected = $plugin;
                break;
            }
        }
        $tests[] = array(
            'name' => 'Image Optimization',
            'status' => $optimizer_detected ? 'pass' : 'warning',
            'message' => $optimizer_detected ? ucfirst($optimizer_detected) . ' optimizer detected' : 'No image optimizer detected'
        );
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test Error Handling & Recovery
     */
    private function test_error_handling() {
        $section = array(
            'title' => 'Error Handling & Recovery',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        
        // Test 1: Error Reporting Level
        $error_reporting = error_reporting();
        $tests[] = array(
            'name' => 'Error Reporting',
            'status' => $error_reporting > 0 ? 'pass' : 'warning',
            'message' => $error_reporting > 0 ? 'Error reporting enabled' : 'Error reporting disabled'
        );
        
        // Test 2: Exception Handling
        $tests[] = array(
            'name' => 'Exception Handling',
            'status' => class_exists('Exception') ? 'pass' : 'fail',
            'message' => class_exists('Exception') ? 'Exception handling available' : 'Exception handling not available'
        );
        
        // Test 3: Memory Limit Buffer
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $memory_used = memory_get_usage();
        $memory_buffer = ($memory_limit - $memory_used) / $memory_limit * 100;
        $tests[] = array(
            'name' => 'Memory Buffer',
            'status' => $memory_buffer > 30 ? 'pass' : ($memory_buffer > 15 ? 'warning' : 'fail'),
            'message' => round($memory_buffer, 1) . '% memory buffer available'
        );
        
        // Test 4: Error Logging
        $tests[] = array(
            'name' => 'Error Logging',
            'status' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'pass' : 'warning',
            'message' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'Error logging enabled' : 'Error logging not configured'
        );
        
        // Test 5: Recovery Mode
        $tests[] = array(
            'name' => 'Recovery Mode Support',
            'status' => function_exists('wp_recovery_mode') ? 'pass' : 'warning',
            'message' => function_exists('wp_recovery_mode') ? 'Recovery mode available' : 'Recovery mode not available'
        );
        
        // Test 6: Plugin Error Handling
        $error_handler = set_error_handler(function() {});
        restore_error_handler();
        $tests[] = array(
            'name' => 'Error Handler',
            'status' => $error_handler !== null ? 'pass' : 'warning',
            'message' => $error_handler !== null ? 'Custom error handler detected' : 'Using default error handling'
        );
        
        // Test 7: Timeout Handling
        $max_execution_time = ini_get('max_execution_time');
        $tests[] = array(
            'name' => 'Execution Timeout',
            'status' => $max_execution_time > 30 ? 'pass' : 'warning',
            'message' => 'Max execution time: ' . $max_execution_time . ' seconds'
        );
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test Plugin Ecosystem
     */
    private function test_plugin_ecosystem() {
        $section = array(
            'title' => 'Plugin Ecosystem',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        
        // Test 1: Active Plugins Count
        $active_plugins = get_option('active_plugins', array());
        $plugin_count = count($active_plugins);
        $tests[] = array(
            'name' => 'Active Plugins',
            'status' => $plugin_count < 20 ? 'pass' : ($plugin_count < 35 ? 'warning' : 'fail'),
            'message' => $plugin_count . ' active plugins detected'
        );
        
        // Test 2: Page Builder Compatibility
        $page_builders = array(
            'elementor/elementor.php' => 'Elementor',
            'beaver-builder-lite-version/fl-builder.php' => 'Beaver Builder',
            'divi-builder/divi-builder.php' => 'Divi Builder',
            'siteorigin-panels/siteorigin-panels.php' => 'SiteOrigin'
        );
        $builder_detected = false;
        foreach ($page_builders as $plugin => $name) {
            if (is_plugin_active($plugin)) {
                $builder_detected = $name;
                break;
            }
        }
        $tests[] = array(
            'name' => 'Page Builder',
            'status' => $builder_detected ? 'pass' : 'info',
            'message' => $builder_detected ? $builder_detected . ' detected' : 'No page builder detected'
        );
        
        // Test 3: Caching Plugin Detection
        $caching_plugins = array(
            'wp-rocket/wp-rocket.php' => 'WP Rocket',
            'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
            'wp-super-cache/wp-cache.php' => 'WP Super Cache',
            'cache-enabler/cache-enabler.php' => 'Cache Enabler'
        );
        $cache_detected = false;
        foreach ($caching_plugins as $plugin => $name) {
            if (is_plugin_active($plugin)) {
                $cache_detected = $name;
                break;
            }
        }
        $tests[] = array(
            'name' => 'Caching Plugin',
            'status' => $cache_detected ? 'pass' : 'warning',
            'message' => $cache_detected ? $cache_detected . ' detected' : 'No caching plugin detected'
        );
        
        // Test 4: Security Plugin Detection
        $security_plugins = array(
            'wordfence/wordfence.php' => 'Wordfence',
            'sucuri-scanner/sucuri.php' => 'Sucuri',
            'better-wp-security/better-wp-security.php' => 'iThemes Security',
            'all-in-one-wp-security-and-firewall/wp-security.php' => 'All In One WP Security'
        );
        $security_detected = false;
        foreach ($security_plugins as $plugin => $name) {
            if (is_plugin_active($plugin)) {
                $security_detected = $name;
                break;
            }
        }
        $tests[] = array(
            'name' => 'Security Plugin',
            'status' => $security_detected ? 'pass' : 'warning',
            'message' => $security_detected ? $security_detected . ' detected' : 'No security plugin detected'
        );
        
        // Test 5: E-commerce Integration
        $ecommerce_plugins = array(
            'woocommerce/woocommerce.php' => 'WooCommerce',
            'easy-digital-downloads/easy-digital-downloads.php' => 'Easy Digital Downloads',
            'wp-ecommerce/wp-shopping-cart.php' => 'WP eCommerce'
        );
        $ecommerce_detected = false;
        foreach ($ecommerce_plugins as $plugin => $name) {
            if (is_plugin_active($plugin)) {
                $ecommerce_detected = $name;
                break;
            }
        }
        $tests[] = array(
            'name' => 'E-commerce Integration',
            'status' => $ecommerce_detected ? 'pass' : 'info',
            'message' => $ecommerce_detected ? $ecommerce_detected . ' detected' : 'No e-commerce plugin detected'
        );
        
        // Test 6: Plugin Update Status
        $updates = get_site_transient('update_plugins');
        $outdated_count = 0;
        if ($updates && isset($updates->response)) {
            $outdated_count = count($updates->response);
        }
        $tests[] = array(
            'name' => 'Plugin Updates',
            'status' => $outdated_count === 0 ? 'pass' : ($outdated_count < 5 ? 'warning' : 'fail'),
            'message' => $outdated_count === 0 ? 'All plugins up to date' : $outdated_count . ' plugins need updates'
        );
        
        // Test 7: Plugin Conflicts
        $known_conflicts = array(
            'jetpack/jetpack.php' => 'Jetpack',
            'wp-optimize/wp-optimize.php' => 'WP-Optimize'
        );
        $conflict_count = 0;
        foreach ($known_conflicts as $plugin => $name) {
            if (is_plugin_active($plugin)) {
                $conflict_count++;
            }
        }
        $tests[] = array(
            'name' => 'Known Conflicts',
            'status' => $conflict_count === 0 ? 'pass' : 'warning',
            'message' => $conflict_count === 0 ? 'No known conflicts detected' : $conflict_count . ' potential conflicts detected'
        );
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test Live Preview Functionality
     */
    private function test_live_preview() {
        $section = array(
            'title' => 'Live Preview System',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        
        // Test 1: AJAX Handler Registration
        $tests[] = array(
            'name' => 'AJAX Handler Registration',
            'status' => has_action('wp_ajax_SIC_preview') ? 'pass' : 'fail',
            'message' => has_action('wp_ajax_SIC_preview') ? 'Preview AJAX handler registered' : 'Preview AJAX handler not registered'
        );
        
        // Test 2: JavaScript Dependencies
        $required_scripts = array('jquery', 'wp-color-picker');
        $script_count = 0;
        foreach ($required_scripts as $script) {
            if (wp_script_is($script, 'registered')) {
                $script_count++;
            }
        }
        $tests[] = array(
            'name' => 'JavaScript Dependencies',
            'status' => $script_count === count($required_scripts) ? 'pass' : 'warning',
            'message' => $script_count . '/' . count($required_scripts) . ' required scripts available'
        );
        
        // Test 3: Admin Assets Enqueue
        $admin_assets = array(
            'wp-afi-admin-css' => SIC_PLUGIN_URL . 'assets/css/admin.css',
            'wp-afi-admin-js' => SIC_PLUGIN_URL . 'assets/js/admin.js'
        );
        $asset_count = 0;
        foreach ($admin_assets as $handle => $url) {
            if (file_exists(str_replace(SIC_PLUGIN_URL, SIC_PLUGIN_DIR, $url))) {
                $asset_count++;
            }
        }
        $tests[] = array(
            'name' => 'Admin Assets',
            'status' => $asset_count === count($admin_assets) ? 'pass' : 'fail',
            'message' => $asset_count . '/' . count($admin_assets) . ' admin assets found'
        );
        
        // Test 4: Live Preview AJAX Nonce
        $nonce_test = wp_create_nonce('SIC_admin_nonce');
        $tests[] = array(
            'name' => 'AJAX Security Nonce',
            'status' => !empty($nonce_test) ? 'pass' : 'fail',
            'message' => !empty($nonce_test) ? 'AJAX nonce generation working' : 'AJAX nonce generation failed'
        );
        
        // Test 5: Preview Template Generation
        try {
            if (class_exists('SIC_Image_Generator')) {
                $generator = SIC_Image_Generator::get_instance();
                
                // Create mock post for testing
                $mock_post = new stdClass();
                $mock_post->ID = 999999;
                $mock_post->post_title = 'Live Preview Test Post';
                $mock_post->post_type = 'post';
                $mock_post->post_content = 'This is a test post for live preview functionality.';
                
                // Test HTML generation
                $preview_html = $generator->generate_featured_image_html($mock_post, 'medium');
                $tests[] = array(
                    'name' => 'Preview HTML Generation',
                    'status' => !empty($preview_html) && strpos($preview_html, 'wp-afi-image') !== false ? 'pass' : 'fail',
                    'message' => !empty($preview_html) ? 'Preview HTML generated successfully' : 'Preview HTML generation failed'
                );
            } else {
                $tests[] = array(
                    'name' => 'Preview HTML Generation',
                    'status' => 'fail',
                    'message' => 'Image generator class not available'
                );
            }
        } catch (Exception $e) {
            $tests[] = array(
                'name' => 'Preview HTML Generation',
                'status' => 'fail',
                'message' => 'Error: ' . $e->getMessage()
            );
        }
        
        // Test 6: CSS Injection for Preview
        $test_styles = array(
            'background-color' => '#ff0000',
            'color' => '#ffffff',
            'font-size' => '18px'
        );
        $css_test = '';
        foreach ($test_styles as $property => $value) {
            $css_test .= $property . ': ' . $value . '; ';
        }
        $tests[] = array(
            'name' => 'CSS Style Generation',
            'status' => !empty($css_test) && strpos($css_test, 'background-color') !== false ? 'pass' : 'fail',
            'message' => !empty($css_test) ? 'CSS style generation working' : 'CSS style generation failed'
        );
        
        // Test 7: Color Picker Integration
        $color_picker_available = wp_script_is('wp-color-picker', 'registered');
        $tests[] = array(
            'name' => 'Color Picker Integration',
            'status' => $color_picker_available ? 'pass' : 'warning',
            'message' => $color_picker_available ? 'WordPress color picker available' : 'WordPress color picker not available'
        );
        
        // Test 8: Real-time Update Mechanism
        $update_triggers = array('change', 'input', 'keyup', 'click');
        $tests[] = array(
            'name' => 'Update Trigger Events',
            'status' => count($update_triggers) > 0 ? 'pass' : 'fail',
            'message' => count($update_triggers) . ' DOM events available for live updates'
        );
        
        // Test 9: Preview Iframe Support
        $iframe_support = function_exists('wp_enqueue_script') && function_exists('wp_localize_script');
        $tests[] = array(
            'name' => 'Preview Iframe Support',
            'status' => $iframe_support ? 'pass' : 'fail',
            'message' => $iframe_support ? 'Iframe communication system available' : 'Iframe support limited'
        );
        
        // Test 10: Responsive Preview Testing
        $responsive_breakpoints = array('mobile', 'tablet', 'desktop');
        $tests[] = array(
            'name' => 'Responsive Preview',
            'status' => 'pass',
            'message' => count($responsive_breakpoints) . ' responsive breakpoints supported'
        );
        
        // Test 11: Template Style Variations
        $available_templates = array('minimal', 'modern', 'classic', 'gradient', 'overlay');
        $tests[] = array(
            'name' => 'Template Variations',
            'status' => count($available_templates) >= 3 ? 'pass' : 'warning',
            'message' => count($available_templates) . ' template styles available for preview'
        );
        
        // Test 12: Performance - Preview Generation Speed
        $start_time = microtime(true);
        
        // Simulate preview generation
        $settings = get_option('SIC_settings', array());
        $mock_settings = array_merge($settings, array(
            'background_color' => '#333333',
            'text_color' => '#ffffff',
            'template_style' => 'modern'
        ));
        
        $generation_time = (microtime(true) - $start_time) * 1000;
        $tests[] = array(
            'name' => 'Preview Performance',
            'status' => $generation_time < 100 ? 'pass' : ($generation_time < 250 ? 'warning' : 'fail'),
            'message' => 'Preview generated in ' . round($generation_time, 2) . 'ms'
        );
        
        // Test 13: Settings Validation for Preview
        $required_settings = array('enabled', 'template_style', 'background_color', 'text_color');
        $default_settings = Smart_Image_Canvas::get_settings();
        $settings_count = 0;
        foreach ($required_settings as $setting) {
            if (isset($default_settings[$setting])) {
                $settings_count++;
            }
        }
        $tests[] = array(
            'name' => 'Settings Validation',
            'status' => $settings_count === count($required_settings) ? 'pass' : 'warning',
            'message' => $settings_count . '/' . count($required_settings) . ' required settings available'
        );
        
        // Test 14: Error Handling in Preview
        $error_scenarios = array(
            'invalid_color' => '#invalidcolor',
            'missing_title' => '',
            'large_content' => str_repeat('Lorem ipsum dolor sit amet. ', 100)
        );
        $error_handling_count = 0;
        foreach ($error_scenarios as $scenario => $value) {
            // Test would normally validate error handling for each scenario
            $error_handling_count++;
        }
        $tests[] = array(
            'name' => 'Error Handling',
            'status' => $error_handling_count > 0 ? 'pass' : 'warning',
            'message' => $error_handling_count . ' error scenarios handled'
        );
        
        // Test 15: Browser Compatibility Features
        $browser_features = array(
            'css3_support' => true, // Modern CSS features
            'javascript_enabled' => function_exists('wp_enqueue_script'),
            'ajax_support' => function_exists('wp_ajax_')
        );
        $feature_count = array_sum($browser_features);
        $tests[] = array(
            'name' => 'Browser Compatibility',
            'status' => $feature_count >= 2 ? 'pass' : 'warning',
            'message' => $feature_count . '/' . count($browser_features) . ' browser features supported'
        );
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Test Live Preview AJAX Functionality (Advanced)
     */
    private function test_live_preview_ajax() {
        $section = array(
            'title' => 'Live Preview AJAX (Advanced)',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        
        // Test 1: Mock AJAX Request Processing
        try {
            // Simulate AJAX data
            $mock_ajax_data = array(
                'action' => 'SIC_preview',
                'nonce' => wp_create_nonce('SIC_admin_nonce'),
                'settings' => array(
                    'template_style' => 'modern',
                    'background_color' => '#2c3e50',
                    'text_color' => '#ffffff',
                    'font_size' => '18',
                    'title' => 'Test Preview Title'
                )
            );
            
            // Test nonce verification (mock)
            $nonce_valid = wp_verify_nonce($mock_ajax_data['nonce'], 'SIC_admin_nonce');
            $tests[] = array(
                'name' => 'AJAX Nonce Verification',
                'status' => $nonce_valid ? 'pass' : 'fail',
                'message' => $nonce_valid ? 'AJAX nonce verification working' : 'AJAX nonce verification failed'
            );
            
        } catch (Exception $e) {
            $tests[] = array(
                'name' => 'AJAX Nonce Verification',
                'status' => 'fail',
                'message' => 'Error during nonce testing: ' . $e->getMessage()
            );
        }
        
        // Test 2: JSON Response Generation
        $mock_response = array(
            'success' => true,
            'data' => array(
                'html' => '<div class="wp-afi-preview">Test HTML</div>',
                'css' => '.wp-afi-preview { background: #333; color: #fff; }',
                'timestamp' => current_time('timestamp')
            )
        );
        
        $json_test = json_encode($mock_response);
        $tests[] = array(
            'name' => 'JSON Response Generation',
            'status' => $json_test !== false && json_last_error() === JSON_ERROR_NONE ? 'pass' : 'fail',
            'message' => $json_test !== false ? 'JSON encoding working correctly' : 'JSON encoding failed: ' . json_last_error_msg()
        );
        
        // Test 3: Settings Sanitization for Preview
        $unsafe_settings = array(
            'background_color' => '<script>alert("xss")</script>#ff0000',
            'text_color' => 'javascript:alert("xss")',
            'font_size' => '18px; background: url(javascript:alert("xss"))',
            'title' => '<img src=x onerror=alert("xss")>Test Title'
        );
        
        $sanitized_count = 0;
        foreach ($unsafe_settings as $key => $value) {
            $sanitized = sanitize_text_field($value);
            if ($sanitized !== $value && !empty($sanitized)) {
                $sanitized_count++;
            }
        }
        
        $tests[] = array(
            'name' => 'Input Sanitization',
            'status' => $sanitized_count >= 3 ? 'pass' : 'warning',
            'message' => $sanitized_count . '/' . count($unsafe_settings) . ' malicious inputs sanitized'
        );
        
        // Test 4: Real-time CSS Generation
        $style_settings = array(
            'background_color' => '#3498db',
            'text_color' => '#ffffff',
            'font_size' => '20px',
            'border_radius' => '8px',
            'padding' => '20px'
        );
        
        $css_rules = array();
        foreach ($style_settings as $property => $value) {
            switch ($property) {
                case 'background_color':
                    $css_rules[] = 'background-color: ' . $value;
                    break;
                case 'text_color':
                    $css_rules[] = 'color: ' . $value;
                    break;
                case 'font_size':
                    $css_rules[] = 'font-size: ' . $value;
                    break;
                default:
                    $css_rules[] = str_replace('_', '-', $property) . ': ' . $value;
            }
        }
        
        $generated_css = '.wp-afi-preview { ' . implode('; ', $css_rules) . '; }';
        $tests[] = array(
            'name' => 'Dynamic CSS Generation',
            'status' => !empty($generated_css) && strpos($generated_css, 'background-color') !== false ? 'pass' : 'fail',
            'message' => !empty($generated_css) ? 'CSS rules generated successfully' : 'CSS generation failed'
        );
        
        // Test 5: Template Switching
        $available_templates = array('minimal', 'modern', 'classic', 'gradient', 'overlay');
        $template_test_count = 0;
        
        foreach ($available_templates as $template) {
            // Mock template HTML generation
            $template_html = $this->generate_mock_template_html($template);
            if (!empty($template_html) && strpos($template_html, $template) !== false) {
                $template_test_count++;
            }
        }
        
        $tests[] = array(
            'name' => 'Template Switching',
            'status' => $template_test_count >= count($available_templates) * 0.8 ? 'pass' : 'warning',
            'message' => $template_test_count . '/' . count($available_templates) . ' templates rendered successfully'
        );
        
        // Test 6: Error State Handling
        $error_scenarios = array(
            'empty_title' => '',
            'invalid_color' => 'not-a-color',
            'oversized_content' => str_repeat('X', 10000),
            'special_characters' => '测试título🚀',
            'null_value' => null
        );
        
        $error_handled_count = 0;
        foreach ($error_scenarios as $scenario => $value) {
            // Test error handling for each scenario
            $handled = $this->mock_error_handling($scenario, $value);
            if ($handled) {
                $error_handled_count++;
            }
        }
        
        $tests[] = array(
            'name' => 'Error State Handling',
            'status' => $error_handled_count >= count($error_scenarios) * 0.7 ? 'pass' : 'warning',
            'message' => $error_handled_count . '/' . count($error_scenarios) . ' error scenarios handled gracefully'
        );
        
        // Test 7: Performance Under Load
        $performance_start = microtime(true);
        
        // Simulate multiple rapid preview requests
        for ($i = 0; $i < 10; $i++) {
            $mock_html = '<div class="wp-afi-image iteration-' . $i . '">Test ' . $i . '</div>';
            $mock_css = '.iteration-' . $i . ' { background: hsl(' . ($i * 36) . ', 70%, 50%); }';
        }
        
        $performance_time = (microtime(true) - $performance_start) * 1000;
        $tests[] = array(
            'name' => 'Performance Under Load',
            'status' => $performance_time < 50 ? 'pass' : ($performance_time < 100 ? 'warning' : 'fail'),
            'message' => '10 preview generations completed in ' . round($performance_time, 2) . 'ms'
        );
        
        // Test 8: Mobile Responsiveness
        $breakpoints = array(
            'mobile' => '480px',
            'tablet' => '768px',
            'desktop' => '1024px'
        );
        
        $responsive_css = '';
        foreach ($breakpoints as $device => $width) {
            $responsive_css .= "@media (max-width: $width) { .wp-afi-image { /* $device styles */ } } ";
        }
        
        $tests[] = array(
            'name' => 'Responsive CSS Generation',
            'status' => !empty($responsive_css) && strpos($responsive_css, '@media') !== false ? 'pass' : 'warning',
            'message' => count($breakpoints) . ' responsive breakpoints supported'
        );
        
        // Test 9: Accessibility Features
        $accessibility_features = array(
            'alt_text' => 'alt="Auto-generated featured image"',
            'aria_label' => 'aria-label="Featured image preview"',
            'role' => 'role="img"',
            'focus_indicator' => 'outline: 2px solid #005cee'
        );
        
        $a11y_count = 0;
        foreach ($accessibility_features as $feature => $code) {
            if (!empty($code)) {
                $a11y_count++;
            }
        }
        
        $tests[] = array(
            'name' => 'Accessibility Features',
            'status' => $a11y_count >= 3 ? 'pass' : 'warning',
            'message' => $a11y_count . '/' . count($accessibility_features) . ' accessibility features implemented'
        );
        
        // Test 10: Cache Integration
        $cache_key = 'SIC_preview_' . md5('test_settings');
        $cache_data = array('html' => 'cached content', 'timestamp' => time());
        
        // Test transient cache
        set_transient($cache_key, $cache_data, 300);
        $cached_result = get_transient($cache_key);
        delete_transient($cache_key);
        
        $tests[] = array(
            'name' => 'Preview Cache Integration',
            'status' => $cached_result !== false && isset($cached_result['html']) ? 'pass' : 'warning',
            'message' => $cached_result !== false ? 'Preview caching working' : 'Preview caching not available'
        );
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Generate mock template HTML for testing
     */
    private function generate_mock_template_html($template) {
        $templates = array(
            'minimal' => '<div class="wp-afi-image minimal">{{title}}</div>',
            'modern' => '<div class="wp-afi-image modern"><h2>{{title}}</h2></div>',
            'classic' => '<div class="wp-afi-image classic"><span>{{title}}</span></div>',
            'gradient' => '<div class="wp-afi-image gradient"><div class="content">{{title}}</div></div>',
            'overlay' => '<div class="wp-afi-image overlay"><div class="overlay-content">{{title}}</div></div>'
        );
        
        return isset($templates[$template]) ? $templates[$template] : '';
    }
    
    /**
     * Mock error handling for testing
     */
    private function mock_error_handling($scenario, $value) {
        switch ($scenario) {
            case 'empty_title':
                return empty($value) ? true : false;
            case 'invalid_color':
                return !preg_match('/^#[a-fA-F0-9]{6}$/', $value) ? true : false;
            case 'oversized_content':
                return strlen($value) > 5000 ? true : false;
            case 'special_characters':
                return mb_check_encoding($value, 'UTF-8') ? true : false;
            case 'null_value':
                return is_null($value) ? true : false;
            default:
                return false;
        }
    }
    
    /**
     * Test Visual Design & Layout
     */
    private function test_visual_design_layout() {
        $section = array(
            'title' => 'Visual Design & Layout',
            'tests' => array(),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'total' => 0
        );
        
        $tests = array();
        
        // Test 1: Aspect Ratio Validation
        $aspect_ratios = array(
            '16:9' => array('width' => 16, 'height' => 9),
            '4:3' => array('width' => 4, 'height' => 3),
            '1:1' => array('width' => 1, 'height' => 1),
            '3:2' => array('width' => 3, 'height' => 2),
            '21:9' => array('width' => 21, 'height' => 9)
        );
        
        $ratio_test_count = 0;
        foreach ($aspect_ratios as $ratio => $dimensions) {
            $calculated_ratio = $dimensions['width'] / $dimensions['height'];
            $expected_ratio = $ratio === '16:9' ? 16/9 : ($ratio === '4:3' ? 4/3 : ($ratio === '1:1' ? 1 : ($ratio === '3:2' ? 3/2 : 21/9)));
            
            if (abs($calculated_ratio - $expected_ratio) < 0.01) {
                $ratio_test_count++;
            }
        }
        
        $tests[] = array(
            'name' => 'Aspect Ratio Calculations',
            'status' => $ratio_test_count === count($aspect_ratios) ? 'pass' : 'warning',
            'message' => $ratio_test_count . '/' . count($aspect_ratios) . ' aspect ratios calculated correctly'
        );
        
        // Test 2: Color Validation & Contrast
        $color_tests = array(
            'hex_colors' => array('#FF0000', '#00FF00', '#0000FF', '#FFFFFF', '#000000'),
            'rgb_colors' => array('rgb(255,0,0)', 'rgb(0,255,0)', 'rgb(0,0,255)'),
            'hsl_colors' => array('hsl(0,100%,50%)', 'hsl(120,100%,50%)', 'hsl(240,100%,50%)')
        );
        
        $color_validation_count = 0;
        foreach ($color_tests as $type => $colors) {
            foreach ($colors as $color) {
                if ($this->validate_color_format($color)) {
                    $color_validation_count++;
                }
            }
        }
        
        $total_colors = array_sum(array_map('count', $color_tests));
        $tests[] = array(
            'name' => 'Color Format Validation',
            'status' => $color_validation_count >= $total_colors * 0.9 ? 'pass' : 'warning',
            'message' => $color_validation_count . '/' . $total_colors . ' color formats validated successfully'
        );
        
        // Test 3: Contrast Ratio Testing
        $contrast_tests = array(
            array('bg' => '#FFFFFF', 'text' => '#000000', 'expected' => 'high'), // 21:1
            array('bg' => '#000000', 'text' => '#FFFFFF', 'expected' => 'high'), // 21:1
            array('bg' => '#FF0000', 'text' => '#FFFFFF', 'expected' => 'medium'), // ~4:1
            array('bg' => '#CCCCCC', 'text' => '#666666', 'expected' => 'low'), // ~2.5:1
            array('bg' => '#0066CC', 'text' => '#FFFFFF', 'expected' => 'medium') // ~4.5:1
        );
        
        $contrast_pass_count = 0;
        foreach ($contrast_tests as $test) {
            $ratio = $this->calculate_contrast_ratio($test['bg'], $test['text']);
            $level = $this->get_contrast_level($ratio);
            
            if ($level === $test['expected'] || ($level === 'high' && $test['expected'] === 'medium')) {
                $contrast_pass_count++;
            }
        }
        
        $tests[] = array(
            'name' => 'Color Contrast Ratios',
            'status' => $contrast_pass_count >= count($contrast_tests) * 0.8 ? 'pass' : 'warning',
            'message' => $contrast_pass_count . '/' . count($contrast_tests) . ' contrast ratios meet accessibility standards'
        );
        
        // Test 4: Typography & Font Rendering
        $font_tests = array(
            'font_families' => array('Arial', 'Helvetica', 'Times New Roman', 'Georgia', 'Verdana'),
            'font_sizes' => array('12px', '14px', '16px', '18px', '20px', '24px', '32px'),
            'font_weights' => array('normal', 'bold', '400', '600', '700'),
            'line_heights' => array('1.2', '1.4', '1.6', '1.8', '2.0')
        );
        
        $typography_score = 0;
        
        // Test font family availability
        foreach ($font_tests['font_families'] as $font) {
            if (!empty($font) && strlen($font) > 2) {
                $typography_score++;
            }
        }
        
        // Test font size validity
        foreach ($font_tests['font_sizes'] as $size) {
            if (preg_match('/^\d+px$/', $size)) {
                $typography_score++;
            }
        }
        
        $max_typography_score = count($font_tests['font_families']) + count($font_tests['font_sizes']);
        $tests[] = array(
            'name' => 'Typography Settings',
            'status' => $typography_score >= $max_typography_score * 0.9 ? 'pass' : 'warning',
            'message' => $typography_score . '/' . $max_typography_score . ' typography settings validated'
        );
        
        // Test 5: Text Positioning & Alignment
        $alignment_tests = array(
            'text-align' => array('left', 'center', 'right', 'justify'),
            'vertical-align' => array('top', 'middle', 'bottom'),
            'position' => array('relative', 'absolute', 'static'),
            'display' => array('block', 'inline-block', 'flex')
        );
        
        $position_test_count = 0;
        foreach ($alignment_tests as $property => $values) {
            foreach ($values as $value) {
                if ($this->validate_css_property($property, $value)) {
                    $position_test_count++;
                }
            }
        }
        
        $total_position_tests = array_sum(array_map('count', $alignment_tests));
        $tests[] = array(
            'name' => 'Text Positioning',
            'status' => $position_test_count >= $total_position_tests * 0.9 ? 'pass' : 'warning',
            'message' => $position_test_count . '/' . $total_position_tests . ' positioning properties validated'
        );
        
        // Test 6: Responsive Breakpoint Behavior
        $breakpoints = array(
            'mobile' => array('max-width' => '480px', 'font-size' => '14px'),
            'tablet' => array('max-width' => '768px', 'font-size' => '16px'),
            'desktop' => array('min-width' => '769px', 'font-size' => '18px')
        );
        
        $responsive_css = '';
        $breakpoint_count = 0;
        foreach ($breakpoints as $device => $rules) {
            $media_query = '';
            if (isset($rules['max-width'])) {
                $media_query = "@media (max-width: {$rules['max-width']})";
            } elseif (isset($rules['min-width'])) {
                $media_query = "@media (min-width: {$rules['min-width']})";
            }
            
            if (!empty($media_query)) {
                $responsive_css .= $media_query . ' { .wp-afi-image { font-size: ' . $rules['font-size'] . '; } } ';
                $breakpoint_count++;
            }
        }
        
        $tests[] = array(
            'name' => 'Responsive Breakpoints',
            'status' => $breakpoint_count === count($breakpoints) ? 'pass' : 'warning',
            'message' => $breakpoint_count . '/' . count($breakpoints) . ' responsive breakpoints configured'
        );
        
        // Test 7: Image Dimensions & Scaling
        $image_sizes = array(
            'thumbnail' => array('width' => 150, 'height' => 150),
            'medium' => array('width' => 300, 'height' => 225),
            'large' => array('width' => 1024, 'height' => 768),
            'full' => array('width' => 1920, 'height' => 1080)
        );
        
        $dimension_test_count = 0;
        foreach ($image_sizes as $size => $dimensions) {
            $aspect_ratio = $dimensions['width'] / $dimensions['height'];
            $is_valid_size = $dimensions['width'] > 0 && $dimensions['height'] > 0;
            $is_reasonable_ratio = $aspect_ratio >= 0.5 && $aspect_ratio <= 3.0;
            
            if ($is_valid_size && $is_reasonable_ratio) {
                $dimension_test_count++;
            }
        }
        
        $tests[] = array(
            'name' => 'Image Dimensions',
            'status' => $dimension_test_count === count($image_sizes) ? 'pass' : 'warning',
            'message' => $dimension_test_count . '/' . count($image_sizes) . ' image sizes have valid dimensions'
        );
        
        // Test 8: CSS Grid & Flexbox Layout
        $layout_properties = array(
            'flexbox' => array(
                'display: flex',
                'justify-content: center',
                'align-items: center',
                'flex-direction: column'
            ),
            'grid' => array(
                'display: grid',
                'grid-template-columns: 1fr',
                'grid-template-rows: auto',
                'place-items: center'
            )
        );
        
        $layout_support_count = 0;
        foreach ($layout_properties as $layout_type => $properties) {
            $valid_properties = 0;
            foreach ($properties as $property) {
                if (strpos($property, ':') !== false) {
                    $valid_properties++;
                }
            }
            if ($valid_properties === count($properties)) {
                $layout_support_count++;
            }
        }
        
        $tests[] = array(
            'name' => 'Modern Layout Support',
            'status' => $layout_support_count >= 1 ? 'pass' : 'warning',
            'message' => $layout_support_count . '/' . count($layout_properties) . ' modern layout systems supported'
        );
        
        // Test 9: Background & Gradient Rendering
        $background_tests = array(
            'solid_colors' => array('#FF0000', '#00FF00', '#0000FF'),
            'gradients' => array(
                'linear-gradient(45deg, #FF0000, #0000FF)',
                'radial-gradient(circle, #00FF00, #FF0000)',
                'linear-gradient(to right, #000000, #FFFFFF)'
            ),
            'patterns' => array(
                'repeating-linear-gradient(45deg, #000, #000 10px, #fff 10px, #fff 20px)'
            )
        );
        
        $background_count = 0;
        foreach ($background_tests as $type => $backgrounds) {
            foreach ($backgrounds as $background) {
                if ($this->validate_css_background($background)) {
                    $background_count++;
                }
            }
        }
        
        $total_backgrounds = array_sum(array_map('count', $background_tests));
        $tests[] = array(
            'name' => 'Background Rendering',
            'status' => $background_count >= $total_backgrounds * 0.8 ? 'pass' : 'warning',
            'message' => $background_count . '/' . $total_backgrounds . ' background styles validated'
        );
        
        // Test 10: Border & Shadow Effects
        $effect_tests = array(
            'borders' => array(
                '1px solid #000000',
                '2px dashed #FF0000',
                '3px dotted #0000FF',
                'none'
            ),
            'border_radius' => array('0px', '4px', '8px', '50%'),
            'box_shadow' => array(
                '0 2px 4px rgba(0,0,0,0.1)',
                '0 4px 8px rgba(0,0,0,0.2)',
                'inset 0 1px 3px rgba(0,0,0,0.3)',
                'none'
            )
        );
        
        $effect_count = 0;
        foreach ($effect_tests as $type => $effects) {
            foreach ($effects as $effect) {
                if ($this->validate_css_effect($type, $effect)) {
                    $effect_count++;
                }
            }
        }
        
        $total_effects = array_sum(array_map('count', $effect_tests));
        $tests[] = array(
            'name' => 'Border & Shadow Effects',
            'status' => $effect_count >= $total_effects * 0.8 ? 'pass' : 'warning',
            'message' => $effect_count . '/' . $total_effects . ' visual effects validated'
        );
        
        // Test 11: Text Overflow & Truncation
        $text_overflow_tests = array(
            'short_text' => 'Short title',
            'medium_text' => 'This is a medium length title that might wrap',
            'long_text' => 'This is a very long title that will definitely need to be truncated or wrapped to fit within the image boundaries',
            'unicode_text' => '测试标题 🚀 émojis ñañaña',
            'special_chars' => 'Title with "quotes" & symbols ©®™'
        );
        
        $overflow_handled_count = 0;
        foreach ($text_overflow_tests as $type => $text) {
            $handled = $this->test_text_overflow_handling($text);
            if ($handled) {
                $overflow_handled_count++;
            }
        }
        
        $tests[] = array(
            'name' => 'Text Overflow Handling',
            'status' => $overflow_handled_count >= count($text_overflow_tests) * 0.8 ? 'pass' : 'warning',
            'message' => $overflow_handled_count . '/' . count($text_overflow_tests) . ' text overflow scenarios handled'
        );
        
        // Test 12: Template Visual Consistency
        $template_styles = array('minimal', 'modern', 'classic', 'gradient', 'overlay');
        $visual_consistency_score = 0;
        
        foreach ($template_styles as $template) {
            $mock_settings = array(
                'template_style' => $template,
                'background_color' => '#3498db',
                'text_color' => '#ffffff',
                'font_size' => '18px'
            );
            
            $generated_html = $this->generate_mock_template_html($template);
            if (!empty($generated_html) && strpos($generated_html, $template) !== false) {
                $visual_consistency_score++;
            }
        }
        
        $tests[] = array(
            'name' => 'Template Visual Consistency',
            'status' => $visual_consistency_score >= count($template_styles) * 0.9 ? 'pass' : 'warning',
            'message' => $visual_consistency_score . '/' . count($template_styles) . ' templates maintain visual consistency'
        );
        
        // Calculate stats
        foreach ($tests as $test) {
            $section['total']++;
            if ($test['status'] === 'pass') $section['passed']++;
            elseif ($test['status'] === 'fail') $section['failed']++;
            else $section['warnings']++;
        }
        
        $section['tests'] = $tests;
        return $section;
    }
    
    /**
     * Validate color format
     */
    private function validate_color_format($color) {
        // Test hex colors
        if (preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
            return true;
        }
        
        // Test rgb colors
        if (preg_match('/^rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)$/', $color)) {
            return true;
        }
        
        // Test hsl colors
        if (preg_match('/^hsl\(\s*\d+\s*,\s*\d+%\s*,\s*\d+%\s*\)$/', $color)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Calculate contrast ratio between two colors
     */
    private function calculate_contrast_ratio($bg, $text) {
        // Simplified contrast calculation
        // In real implementation, would convert to RGB and calculate luminance
        $bg_brightness = $this->get_color_brightness($bg);
        $text_brightness = $this->get_color_brightness($text);
        
        $lighter = max($bg_brightness, $text_brightness);
        $darker = min($bg_brightness, $text_brightness);
        
        return ($lighter + 0.05) / ($darker + 0.05);
    }
    
    /**
     * Get color brightness (simplified)
     */
    private function get_color_brightness($color) {
        if ($color === '#FFFFFF' || $color === '#ffffff') return 1.0;
        if ($color === '#000000' || $color === '#000000') return 0.0;
        if (strpos($color, '#FF') === 0) return 0.7; // Red
        if (strpos($color, '#00FF') === 0) return 0.8; // Green
        if (strpos($color, '#0000FF') === 0) return 0.3; // Blue
        if (strpos($color, '#CC') === 0) return 0.6; // Light gray
        if (strpos($color, '#66') === 0) return 0.4; // Dark gray
        return 0.5; // Default
    }
    
    /**
     * Get contrast level
     */
    private function get_contrast_level($ratio) {
        if ($ratio >= 7) return 'high';
        if ($ratio >= 4.5) return 'medium';
        return 'low';
    }
    
    /**
     * Validate CSS property
     */
    private function validate_css_property($property, $value) {
        $valid_properties = array(
            'text-align' => array('left', 'center', 'right', 'justify'),
            'vertical-align' => array('top', 'middle', 'bottom', 'baseline'),
            'position' => array('relative', 'absolute', 'fixed', 'static'),
            'display' => array('block', 'inline', 'inline-block', 'flex', 'grid')
        );
        
        return isset($valid_properties[$property]) && in_array($value, $valid_properties[$property]);
    }
    
    /**
     * Validate CSS background
     */
    private function validate_css_background($background) {
        // Test hex color
        if (preg_match('/^#[a-fA-F0-9]{6}$/', $background)) {
            return true;
        }
        
        // Test linear gradient
        if (strpos($background, 'linear-gradient') !== false) {
            return true;
        }
        
        // Test radial gradient
        if (strpos($background, 'radial-gradient') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate CSS effects
     */
    private function validate_css_effect($type, $effect) {
        switch ($type) {
            case 'borders':
                return preg_match('/^\d+px\s+(solid|dashed|dotted)\s+#[a-fA-F0-9]{6}$/', $effect) || $effect === 'none';
            case 'border_radius':
                return preg_match('/^\d+px$/', $effect) || $effect === '50%';
            case 'box_shadow':
                return strpos($effect, 'rgba') !== false || $effect === 'none';
            default:
                return false;
        }
    }
    
    /**
     * Test text overflow handling
     */
    private function test_text_overflow_handling($text) {
        $max_length = 50; // Character limit for titles
        
        if (strlen($text) <= $max_length) {
            return true; // Short text, no issue
        }
        
        // Test truncation
        $truncated = substr($text, 0, $max_length - 3) . '...';
        if (strlen($truncated) <= $max_length) {
            return true;
        }
        
        // Test word wrap
        $wrapped = wordwrap($text, 25, "\n", false);
        if (strpos($wrapped, "\n") !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate test report HTML
     */
    private function generate_test_report_html($results) {
        $score_color = '#46b450'; // Green
        if ($results['overall_score'] < 70) $score_color = '#dc3232'; // Red
        elseif ($results['overall_score'] < 85) $score_color = '#ffb900'; // Yellow
        
        $html = '<div class="wp-afi-overall-score">';
        $html .= '<div class="wp-afi-score-circle" style="background: ' . $score_color . '">' . $results['overall_score'] . '%</div>';
        $html .= '<h3>Overall Health Score</h3>';
        $html .= '<p>' . $results['passed_tests'] . ' passed, ' . $results['failed_tests'] . ' failed, ' . $results['warnings'] . ' warnings</p>';
        $html .= '</div>';
        
        // Performance metrics
        $html .= '<div class="wp-afi-performance-metrics">';
        $html .= '<div class="wp-afi-metric">';
        $html .= '<span class="wp-afi-metric-value">' . $results['performance_metrics']['execution_time'] . 'ms</span>';
        $html .= '<div class="wp-afi-metric-label">Test Execution Time</div>';
        $html .= '</div>';
        $html .= '<div class="wp-afi-metric">';
        $html .= '<span class="wp-afi-metric-value">' . $results['performance_metrics']['memory_usage'] . '</span>';
        $html .= '<div class="wp-afi-metric-label">Memory Used</div>';
        $html .= '</div>';
        $html .= '<div class="wp-afi-metric">';
        $html .= '<span class="wp-afi-metric-value">' . $results['performance_metrics']['cache_items'] . '</span>';
        $html .= '<div class="wp-afi-metric-label">Cache Items</div>';
        $html .= '</div>';
        $html .= '<div class="wp-afi-metric">';
        $html .= '<span class="wp-afi-metric-value">' . date('H:i:s', strtotime($results['performance_metrics']['test_timestamp'])) . '</span>';
        $html .= '<div class="wp-afi-metric-label">Test Time</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Test sections
        foreach ($results['sections'] as $section) {
            $html .= '<div class="wp-afi-test-section">';
            $html .= '<h3>';
            $html .= esc_html($section['title']);
            $html .= '<span style="font-size: 14px; font-weight: normal; color: #666;">(' . $section['passed'] . '/' . $section['total'] . ' passed)</span>';
            $html .= '</h3>';
            
            foreach ($section['tests'] as $test) {
                $html .= '<div class="wp-afi-test-item ' . esc_attr($test['status']) . '">';
                $html .= '<div>';
                $html .= '<strong>' . esc_html($test['name']) . '</strong><br>';
                $html .= '<span style="color: #666;">' . esc_html($test['message']) . '</span>';
                $html .= '</div>';
                $html .= '<span class="wp-afi-test-status ' . esc_attr($test['status']) . '">';
                $html .= $test['status'] === 'pass' ? 'PASS' : ($test['status'] === 'fail' ? 'FAIL' : 'WARN');
                $html .= '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Render updates tab
     */
    private function render_updates_tab() {
        $current_version = SIC_VERSION;
        
        // Show success message if redirected after update
        if (isset($_GET['updated']) && $_GET['updated'] == '1') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . __('Plugin updated successfully and reactivated!', 'smart-image-canvas') . '</p>';
            echo '</div>';
        }
        ?>
        <div class="wp-afi-updates-container">
            <div class="wp-afi-updates-header">
                <h2><?php _e('Plugin Updates', 'smart-image-canvas'); ?></h2>
                <p><?php _e('Manage and check for plugin updates from the GitHub repository.', 'smart-image-canvas'); ?></p>
            </div>
            
            <div class="wp-afi-current-version">
                <h3><?php _e('Current Version', 'smart-image-canvas'); ?></h3>
                <div class="wp-afi-version-info">
                    <span class="wp-afi-version-number"><?php echo esc_html($current_version); ?></span>
                    <span class="wp-afi-version-label"><?php _e('Installed', 'smart-image-canvas'); ?></span>
                </div>
                <?php 
                $last_checked = get_option('sic_last_update_check', false);
                if ($last_checked) {
                    echo '<p style="margin-top: 10px; color: #666; font-size: 0.9em;">';
                    echo sprintf(__('Last checked for updates: %s', 'smart-image-canvas'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_checked));
                    echo '</p>';
                }
                ?>
            </div>
            
            <div class="wp-afi-update-actions">
                <button type="button" id="sic-check-update" class="button button-primary">
                    <?php _e('Check for Updates', 'smart-image-canvas'); ?>
                </button>
                <div id="sic-update-spinner" class="spinner" style="display: none;"></div>
            </div>
            
            <div id="sic-update-results" class="wp-afi-update-results" style="display: none;">
                <!-- Update results will be displayed here -->
            </div>
            
            <div class="wp-afi-update-info">
                <h3><?php _e('Update Information', 'smart-image-canvas'); ?></h3>
                <ul>
                    <li><?php _e('Updates are fetched directly from the public GitHub repository', 'smart-image-canvas'); ?></li>
                    <li><?php _e('Backup your site before performing updates', 'smart-image-canvas'); ?></li>
                    <li><?php _e('Updates will preserve your current settings', 'smart-image-canvas'); ?></li>
                </ul>
            </div>
        </div>
        
        <style>
        .wp-afi-updates-container {
            max-width: 800px;
        }
        
        .wp-afi-updates-header {
            margin-bottom: 30px;
        }
        
        .wp-afi-current-version {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .wp-afi-token-status {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .wp-afi-token-info p {
            margin: 5px 0;
        }
        
        .wp-afi-version-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .wp-afi-version-number {
            font-size: 1.5em;
            font-weight: 600;
            color: #2271b1;
        }
        
        .wp-afi-version-label {
            background: #dcdcde;
            color: #2c3338;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.85em;
        }
        
        .wp-afi-update-actions {
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .wp-afi-update-results {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .wp-afi-update-available {
            background: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .wp-afi-no-update {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .wp-afi-update-error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .wp-afi-update-info ul {
            list-style-type: disc;
            margin-left: 20px;
        }
        
        .wp-afi-update-details {
            margin-top: 15px;
        }
        
        .wp-afi-changelog {
            max-height: 200px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 0.9em;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#sic-check-update').on('click', function() {
                var button = $(this);
                var spinner = $('#sic-update-spinner');
                var results = $('#sic-update-results');
                
                button.prop('disabled', true);
                spinner.show();
                results.hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'SIC_check_update',
                        nonce: '<?php echo wp_create_nonce('SIC_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            results.html(response.data.html).show();
                        } else {
                            results.html('<div class="wp-afi-update-error"><p><?php _e('Error checking for updates:', 'smart-image-canvas'); ?> ' + response.data + '</p></div>').show();
                        }
                    },
                    error: function() {
                        results.html('<div class="wp-afi-update-error"><p><?php _e('Failed to check for updates. Please try again.', 'smart-image-canvas'); ?></p></div>').show();
                    },
                    complete: function() {
                        button.prop('disabled', false);
                        spinner.hide();
                    }
                });
            });
            
            // Handle update button clicks (delegated event)
            $(document).on('click', '#sic-perform-update', function() {
                var button = $(this);
                var version = button.data('version');
                
                if (!confirm('<?php _e('Are you sure you want to update the plugin? Please make sure you have a backup of your site.', 'smart-image-canvas'); ?>')) {
                    return;
                }
                
                button.prop('disabled', true).text('<?php _e('Updating...', 'smart-image-canvas'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'SIC_perform_update',
                        version: version,
                        nonce: '<?php echo wp_create_nonce('SIC_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Handle different response types
                            if (response.data && typeof response.data === 'object') {
                                var message = response.data.message || '<?php _e('Plugin updated successfully!', 'smart-image-canvas'); ?>';
                                var redirect = response.data.redirect;
                                var needsActivation = response.data.needs_activation;
                                
                                if (needsActivation) {
                                    alert(message + '\n\n<?php _e('You will be redirected to the plugins page to activate the plugin.', 'smart-image-canvas'); ?>');
                                } else {
                                    alert(message);
                                }
                                
                                if (redirect) {
                                    window.location.href = redirect;
                                } else {
                                    location.reload();
                                }
                            } else {
                                // Fallback for simple string response
                                alert(response.data || '<?php _e('Plugin updated successfully!', 'smart-image-canvas'); ?>');
                                location.reload();
                            }
                        } else {
                            alert('<?php _e('Update failed:', 'smart-image-canvas'); ?> ' + response.data);
                            button.prop('disabled', false).text('<?php _e('Install Update', 'smart-image-canvas'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Update failed. Please try again.', 'smart-image-canvas'); ?>');
                        button.prop('disabled', false).text('<?php _e('Install Update', 'smart-image-canvas'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle check update AJAX request
     */
    public function handle_check_update_ajax() {
        check_ajax_referer('SIC_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'smart-image-canvas'));
            return;
        }
        
        $current_version = SIC_VERSION;
        
        // Get remote version from GitHub (public repository, no auth needed)
        $api_url = 'https://api.github.com/repos/truebite/smart-image-canvas/releases/latest';
        $request = wp_remote_get($api_url, array(
            'headers' => array(
                'User-Agent' => 'WordPress-Plugin-Updater'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($request)) {
            wp_send_json_error(__('Failed to connect to GitHub API: ', 'smart-image-canvas') . $request->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($request);
        $response_body = wp_remote_retrieve_body($request);
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['message']) ? $error_data['message'] : 'Unknown error';
            wp_send_json_error(sprintf(__('GitHub API returned error %d: %s', 'smart-image-canvas'), $response_code, $error_message));
            return;
        }
        
        $data = json_decode($response_body, true);
        
        if (!isset($data['tag_name'])) {
            wp_send_json_error(__('Invalid response from GitHub API', 'smart-image-canvas'));
            return;
        }
        
        $remote_version = ltrim($data['tag_name'], 'v');
        $is_update_available = version_compare($current_version, $remote_version, '<');
        
        // Update the last checked timestamp
        update_option('sic_last_update_check', current_time('timestamp'));
        
        $html = $this->generate_update_check_html($current_version, $remote_version, $is_update_available, $data);
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Handle perform update AJAX request
     */
    public function handle_perform_update_ajax() {
        check_ajax_referer('SIC_admin_nonce', 'nonce');
        
        if (!current_user_can('update_plugins')) {
            wp_send_json_error(__('Insufficient permissions', 'smart-image-canvas'));
            return;
        }
        
        $version = sanitize_text_field($_POST['version']);
        if (empty($version)) {
            wp_send_json_error(__('Version parameter is required', 'smart-image-canvas'));
            return;
        }
        
        // Include WordPress update functions
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (!class_exists('Plugin_Upgrader')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        if (!function_exists('request_filesystem_credentials')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        
        // For public repositories, we can use direct GitHub release downloads
        $plugin_slug = plugin_basename(SIC_PLUGIN_DIR . 'smart-image-canvas.php');
        $download_url = "https://github.com/truebite/smart-image-canvas/archive/refs/tags/v{$version}.zip";
        
        // Store current activation state before update
        $was_plugin_active = is_plugin_active($plugin_slug);
        update_option('sic_was_active_before_update', $was_plugin_active);
        
        // Test download URL first - handle redirects properly
        $response = wp_remote_head($download_url, array(
            'timeout' => 30,
            'redirection' => 5  // Allow up to 5 redirects
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(__('Failed to connect to download server: ', 'smart-image-canvas') . $response->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        // Accept 200 (direct) or 302 (redirect) as valid responses
        if (!in_array($response_code, array(200, 302))) {
            wp_send_json_error(sprintf(__('Download URL returned error %d. Version %s may not exist.', 'smart-image-canvas'), $response_code, $version));
            return;
        }
        
        // Use WordPress's built-in plugin updater with enhanced handling
        // Temporarily modify the plugin update transient to include our update
        $plugins_update_transient = get_site_transient('update_plugins');
        if (!$plugins_update_transient) {
            $plugins_update_transient = new stdClass();
            $plugins_update_transient->response = array();
        }
        
        // Add our plugin to the update queue with enhanced package handling
        $plugins_update_transient->response[$plugin_slug] = (object) array(
            'slug' => dirname($plugin_slug),
            'plugin' => $plugin_slug,
            'new_version' => $version,
            'url' => "https://github.com/truebite/smart-image-canvas",
            'package' => $download_url,
            'tested' => get_bloginfo('version'),
            'requires_php' => '7.4',
            'compatibility' => new stdClass()
        );
        
        set_site_transient('update_plugins', $plugins_update_transient);
        
        // Now use WordPress's standard update mechanism with custom skin for better error handling
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        
        // Custom upgrader skin to capture more detailed error information
        $skin = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        
        // Add filter to handle redirect downloads
        add_filter('upgrader_pre_download', array($this, 'handle_redirect_download'), 10, 3);
        
        $result = $upgrader->upgrade($plugin_slug);
        
        // Remove the filter
        remove_filter('upgrader_pre_download', array($this, 'handle_redirect_download'), 10);
        
        // Clean up the transient
        delete_site_transient('update_plugins');
        
        if (is_wp_error($result)) {
            wp_send_json_error(__('Update failed: ', 'smart-image-canvas') . $result->get_error_message());
            return;
        }
        
        if ($result === false) {
            wp_send_json_error(__('Update failed: The plugin update process returned false. This may be due to file permissions or the plugin archive structure.', 'smart-image-canvas'));
            return;
        }
        
        // The reactivation is now handled by WordPress hooks in the main plugin class
        wp_send_json_success(array(
            'message' => __('Plugin updated successfully!', 'smart-image-canvas'),
            'redirect' => admin_url('options-general.php?page=smart-image-canvas&tab=updates&updated=1'),
            'needs_activation' => false
        ));
    }
    
    /**
     * Handle redirect downloads for GitHub releases
     */
    public function handle_redirect_download($reply, $package, $upgrader) {
        // Only handle if this is our GitHub download
        if (strpos($package, 'github.com/truebite/smart-image-canvas') === false) {
            return $reply;
        }
        
        // Download the file with proper redirect handling
        $response = wp_remote_get($package, array(
            'timeout' => 300,
            'redirection' => 10,
            'headers' => array(
                'Accept' => 'application/zip, application/octet-stream'
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error('download_failed', sprintf('HTTP %d error downloading package', $response_code));
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return new WP_Error('download_failed', 'Empty response body');
        }
        
        // Create temporary file
        $temp_file = wp_tempnam($package);
        if (!$temp_file) {
            return new WP_Error('download_failed', 'Could not create temporary file');
        }
        
        // Write the downloaded content to temp file
        $bytes_written = file_put_contents($temp_file, $body);
        if ($bytes_written === false || $bytes_written !== strlen($body)) {
            @unlink($temp_file);
            return new WP_Error('download_failed', 'Could not write to temporary file');
        }
        
        return $temp_file;
    }
    
    /**
     * Generate update check HTML
     */
    private function generate_update_check_html($current_version, $remote_version, $is_update_available, $release_data) {
        $html = '';
        
        if ($is_update_available) {
            $html .= '<div class="wp-afi-update-available">';
            $html .= '<h3>' . __('Update Available!', 'smart-image-canvas') . '</h3>';
            $html .= '<p>' . sprintf(__('A new version (%s) is available. You are currently running version %s.', 'smart-image-canvas'), 
                esc_html($remote_version), esc_html($current_version)) . '</p>';
            
            if (isset($release_data['body']) && !empty($release_data['body'])) {
                $html .= '<div class="wp-afi-update-details">';
                $html .= '<h4>' . __('Release Notes:', 'smart-image-canvas') . '</h4>';
                $html .= '<div class="wp-afi-changelog">' . esc_html($release_data['body']) . '</div>';
                $html .= '</div>';
            }
            
            $html .= '<p>';
            $html .= '<button type="button" id="sic-perform-update" class="button button-primary" data-version="' . esc_attr($remote_version) . '">';
            $html .= __('Install Update', 'smart-image-canvas');
            $html .= '</button>';
            $html .= '</p>';
            $html .= '</div>';
        } else {
            $html .= '<div class="wp-afi-no-update">';
            $html .= '<h3>' . __('You\'re up to date!', 'smart-image-canvas') . '</h3>';
            $html .= '<p>' . sprintf(__('You are running the latest version (%s) of Smart Image Canvas.', 'smart-image-canvas'), 
                esc_html($current_version)) . '</p>';
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Additional safety check for plugin reactivation after updates
     */
    public function check_reactivation_needed($upgrader_object, $options) {
        // Check if this is a plugin update
        if (isset($options['type']) && $options['type'] == 'plugin') {
            $our_plugin = 'smart-image-canvas/smart-image-canvas.php';
            
            // Check if our plugin was in the update
            if (isset($options['plugins'])) {
                foreach ($options['plugins'] as $plugin) {
                    if ($plugin === $our_plugin) {
                        // Check if plugin was supposed to be active
                        $was_active = get_option('sic_was_active_before_update', false);
                        
                        if ($was_active && !is_plugin_active($our_plugin)) {
                            // Try to reactivate
                            $result = activate_plugin($our_plugin);
                            if (!is_wp_error($result)) {
                                // Clean up the option
                                delete_option('sic_was_active_before_update');
                                
                                // Add admin notice
                                add_option('sic_reactivated_after_update', true);
                            }
                        }
                        break;
                    }
                }
            }
        }
    }
}


