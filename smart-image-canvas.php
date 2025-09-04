<?php
/**
 * Plugin Name: Smart Image Canvas
 * Plugin URI: https://github.com/truebite/smart-image-canvas
 * Description: Automatically generate beautiful CSS-based featured images when no featured image is set. Features live preview, customizable styles, and responsive design.
 * Version: 1.0.8
 * Author: Your Name
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-image-canvas
 * Domain Path: /languages
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SIC_VERSION', '1.0.8');
define('SIC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SIC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Smart_Image_Canvas {
    
    /**
     * Plugin instance
     *
     * @var Smart_Image_Canvas
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return Smart_Image_Canvas
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
        $this->load_dependencies();
        $this->init_hooks();
        
        // Initialize plugin updater early after dependencies are loaded
        new SIC_Plugin_Updater(__FILE__, 'smart-image-canvas', SIC_VERSION);
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load new modular architecture
        require_once SIC_PLUGIN_DIR . 'includes/class-cache-manager.php';
        require_once SIC_PLUGIN_DIR . 'includes/class-template-manager.php';
        require_once SIC_PLUGIN_DIR . 'includes/class-hook-manager.php';
        require_once SIC_PLUGIN_DIR . 'includes/class-image-generator.php';
        
        // Keep existing classes
        require_once SIC_PLUGIN_DIR . 'includes/class-admin-settings.php';
        require_once SIC_PLUGIN_DIR . 'includes/class-frontend-display.php';
        require_once SIC_PLUGIN_DIR . 'includes/class-customizer.php';
        require_once SIC_PLUGIN_DIR . 'includes/class-theme-compatibility.php';
        require_once SIC_PLUGIN_DIR . 'includes/class-debug.php';
        require_once SIC_PLUGIN_DIR . 'includes/class-plugin-updater.php';
        require_once SIC_PLUGIN_DIR . 'includes/class-debug-logger.php';
        
        // Load performance monitor only in debug mode
        if (WP_DEBUG) {
            require_once SIC_PLUGIN_DIR . 'includes/class-performance-monitor.php';
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Cache cleanup hooks
        add_action('create_category', array($this, 'clear_category_cache'));
        add_action('edit_category', array($this, 'clear_category_cache'));
        add_action('delete_category', array($this, 'clear_category_cache'));
        
        // Plugin activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize components
        SIC_Image_Generator::get_instance();
        SIC_Admin_Settings::get_instance();
        SIC_Frontend_Display::get_instance();
        SIC_Customizer::get_instance();
        SIC_Theme_Compatibility::get_instance();
        
        // Load text domain for translations
        load_plugin_textdomain('smart-image-canvas', false, dirname(SIC_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'wp-afi-frontend',
            SIC_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            SIC_VERSION
        );
        
        wp_enqueue_script(
            'wp-afi-frontend',
            SIC_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            SIC_VERSION,
            true
        );
        
        // Localize script with settings
                $settings = get_option('sic_settings', array());
        wp_localize_script('wp-afi-frontend', 'wpAfi', array(
            'settings' => $settings,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('SIC_nonce')
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on specific admin pages
        if (!in_array($hook, array('settings_page_smart-image-canvas', 'post.php', 'post-new.php'))) {
            return;
        }
        
        wp_enqueue_style(
            'wp-afi-admin',
            SIC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SIC_VERSION
        );
        
        wp_enqueue_script(
            'wp-afi-admin',
            SIC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            SIC_VERSION,
            true
        );
        
        wp_enqueue_style('wp-color-picker');
        
        wp_localize_script('wp-afi-admin', 'wpAfiAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('sic_nonce')
        ));
    }
    
    /**
     * Get plugin settings
     *
     * @return array
     */
    public static function get_settings() {
        $defaults = array(
            'enabled' => true,
            'auto_activate' => true,
            'post_types' => array('post'),
            'background_color' => '#2563eb',
            'background_gradient' => '',
            'text_color' => '#ffffff',
            'font_family' => 'Inter, system-ui, sans-serif',
            'font_size' => '2.5',
            'font_weight' => '600',
            'text_align' => 'center',
            'text_position' => 'center',
            'aspect_ratio' => '16:9',
            'template_style' => 'modern',
            'enable_category_colors' => false,
            'category_colors' => array(),
            'custom_css' => '',
            'github_token' => '',
            'debug_enabled' => false
        );
        
        $settings = get_option('sic_settings', $defaults);
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Clear category cache when categories are modified
     */
    public function clear_category_cache() {
        delete_transient('sic_categories_list');
        
        // Also clear any HTML cache that might include category data
        $image_generator = SIC_Image_Generator::get_instance();
        if (method_exists($image_generator, 'clear_all_cache')) {
            $image_generator->clear_all_cache();
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options using the same defaults as get_settings()
        $defaults = array(
            'enabled' => true,
            'auto_activate' => true,
            'post_types' => array('post'),
            'background_color' => '#2563eb',
            'background_gradient' => '',
            'text_color' => '#ffffff',
            'font_family' => 'Inter, system-ui, sans-serif',
            'font_size' => '2.5',
            'font_weight' => '600',
            'text_align' => 'center',
            'text_position' => 'center',
            'aspect_ratio' => '16:9',
            'template_style' => 'modern',
            'enable_category_colors' => false,
            'category_colors' => array(),
            'custom_css' => '',
            'github_token' => '',
            'debug_enabled' => false
        );
        
        add_option('sic_settings', $defaults);
        
        // Clear any existing cache
        $this->clear_category_cache();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear all plugin cache
        $this->clear_category_cache();
        
        // Clear CSS cache
        $frontend = SIC_Frontend_Display::get_instance();
        if (method_exists($frontend, 'clear_css_cache')) {
            $frontend->clear_css_cache();
        }
    }
}

/**
 * Initialize the plugin
 */
function smart_image_canvas() {
    return Smart_Image_Canvas::get_instance();
}

// Start the plugin
smart_image_canvas();

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, function() {
    // Remove old plugin reference if it exists
    $active_plugins = get_option('active_plugins', array());
    $old_plugin = 'wp-auto-featured-image/wp-auto-featured-image.php';
    
    if (in_array($old_plugin, $active_plugins)) {
        $active_plugins = array_diff($active_plugins, array($old_plugin));
        update_option('active_plugins', $active_plugins);
    }
    
    // Clear any cached data
    delete_transient('plugin_slugs');
    wp_cache_delete('plugins', 'plugins');
});
