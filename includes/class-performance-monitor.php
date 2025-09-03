<?php
/**
 * Performance Monitor for WP Auto Featured Image
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SIC_Performance_Monitor class
 */
class SIC_Performance_Monitor {
    
    /**
     * Instance
     *
     * @var SIC_Performance_Monitor
     */
    private static $instance = null;
    
    /**
     * Performance data
     *
     * @var array
     */
    private $performance_data = array();
    
    /**
     * Get instance
     *
     * @return SIC_Performance_Monitor
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
        if (WP_DEBUG) {
            $this->init_monitoring();
        }
    }
    
    /**
     * Initialize performance monitoring
     */
    private function init_monitoring() {
        add_action('wp_head', array($this, 'start_timing'), 1);
        add_action('wp_footer', array($this, 'end_timing'), 999);
        add_action('admin_footer', array($this, 'show_admin_stats'));
    }
    
    /**
     * Start timing
     */
    public function start_timing() {
        $this->performance_data['start_time'] = microtime(true);
        $this->performance_data['start_memory'] = memory_get_usage();
    }
    
    /**
     * End timing and log results
     */
    public function end_timing() {
        if (!isset($this->performance_data['start_time'])) {
            return;
        }
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $execution_time = $end_time - $this->performance_data['start_time'];
        $memory_usage = $end_memory - $this->performance_data['start_memory'];
        
        $this->performance_data['execution_time'] = $execution_time;
        $this->performance_data['memory_usage'] = $memory_usage;
        $this->performance_data['cache_hits'] = $this->get_cache_stats();
        
        // Log to debug.log if WP_DEBUG_LOG is enabled
        if (WP_DEBUG_LOG) {
            error_log(sprintf(
                'WP AFI Performance: Time: %fs, Memory: %s, Cache Hits: %d',
                $execution_time,
                size_format($memory_usage),
                $this->performance_data['cache_hits']
            ));
        }
    }
    
    /**
     * Get cache statistics
     */
    private function get_cache_stats() {
        global $wpdb;
        
        $cache_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_SIC_%'"
        );
        
        return intval($cache_count);
    }
    
    /**
     * Show admin statistics
     */
    public function show_admin_stats() {
        if (!current_user_can('manage_options') || !isset($this->performance_data['execution_time'])) {
            return;
        }
        
        echo sprintf(
            '<div style="position:fixed;bottom:10px;right:10px;background:#fff;border:1px solid #ccc;padding:10px;font-size:12px;z-index:9999;">
                <strong>WP AFI Performance:</strong><br>
                Time: %fs<br>
                Memory: %s<br>
                Cache: %d items
            </div>',
            $this->performance_data['execution_time'],
            size_format($this->performance_data['memory_usage']),
            $this->performance_data['cache_hits']
        );
    }
}

// Initialize if debug mode is enabled
if (WP_DEBUG) {
    SIC_Performance_Monitor::get_instance();
}
