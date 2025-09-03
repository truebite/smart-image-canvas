<?php
/**
 * Cache Manager Class
 * Handles all caching operations for generated featured images
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SIC_Cache_Manager class
 * Responsible for caching generated featured images and managing cache lifecycle
 */
class SIC_Cache_Manager {
    
    /**
     * Cache prefix for all plugin transients
     */
    const CACHE_PREFIX = 'SIC_';
    
    /**
     * Default cache duration (1 hour)
     */
    const DEFAULT_CACHE_DURATION = HOUR_IN_SECONDS;
    
    /**
     * Instance
     *
     * @var SIC_Cache_Manager
     */
    private static $instance = null;
    
    /**
     * Get instance
     *
     * @return SIC_Cache_Manager
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
     * Initialize WordPress hooks for cache invalidation
     */
    private function init_hooks() {
        add_action('save_post', array($this, 'clear_post_cache'));
        add_action('post_updated', array($this, 'clear_post_cache'));
        add_action('update_option_SIC_settings', array($this, 'clear_all_cache'));
        add_action('create_category', array($this, 'clear_all_cache'));
        add_action('edit_category', array($this, 'clear_all_cache'));
        add_action('delete_category', array($this, 'clear_all_cache'));
    }
    
    /**
     * Generate cache key for post/size/attr combination
     *
     * @param int $post_id Post ID
     * @param string|array $size Image size
     * @param string|array $attr Image attributes
     * @param array $settings Plugin settings
     * @return string Cache key
     */
    public function generate_cache_key($post_id, $size, $attr, $settings) {
        $settings_hash = md5(serialize($settings));
        $size_key = is_array($size) ? md5(serialize($size)) : $size;
        $attr_key = is_array($attr) ? md5(serialize($attr)) : md5($attr);
        
        return self::CACHE_PREFIX . "html_{$post_id}_{$size_key}_{$attr_key}_{$settings_hash}";
    }
    
    /**
     * Get cached HTML for a post
     *
     * @param string $cache_key Cache key
     * @return string|false Cached HTML or false if not found
     */
    public function get_cached_html($cache_key) {
        // Skip cache in debug mode for easier development
        if (WP_DEBUG) {
            return false;
        }
        
        return get_transient($cache_key);
    }
    
    /**
     * Store HTML in cache
     *
     * @param string $cache_key Cache key
     * @param string $html HTML to cache
     * @param int $duration Cache duration in seconds
     * @return bool Success status
     */
    public function store_html($cache_key, $html, $duration = null) {
        if ($duration === null) {
            $duration = self::DEFAULT_CACHE_DURATION;
        }
        
        return set_transient($cache_key, $html, $duration);
    }
    
    /**
     * Clear cache for specific post
     *
     * @param int $post_id Post ID
     */
    public function clear_post_cache($post_id) {
        global $wpdb;
        
        $like_pattern = $wpdb->esc_like(self::CACHE_PREFIX . "html_{$post_id}_") . '%';
        
        // Delete transients and their timeout entries
        $this->delete_transients_by_pattern($like_pattern);
    }
    
    /**
     * Clear all plugin cache
     */
    public function clear_all_cache() {
        global $wpdb;
        
        $like_pattern = $wpdb->esc_like(self::CACHE_PREFIX) . '%';
        
        // Delete all plugin transients
        $this->delete_transients_by_pattern($like_pattern);
    }
    
    /**
     * Delete transients by pattern
     *
     * @param string $like_pattern SQL LIKE pattern
     */
    private function delete_transients_by_pattern($like_pattern) {
        global $wpdb;
        
        // Delete transient values
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            "_transient_{$like_pattern}"
        ));
        
        // Delete transient timeouts
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            "_transient_timeout_{$like_pattern}"
        ));
    }
    
    /**
     * Get cache statistics
     *
     * @return array Cache stats
     */
    public function get_cache_stats() {
        global $wpdb;
        
        $transient_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
            "_transient_" . $wpdb->esc_like(self::CACHE_PREFIX) . '%'
        ));
        
        $timeout_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
            "_transient_timeout_" . $wpdb->esc_like(self::CACHE_PREFIX) . '%'
        ));
        
        return array(
            'cached_items' => intval($transient_count),
            'timeout_entries' => intval($timeout_count),
            'cache_prefix' => self::CACHE_PREFIX
        );
    }
}
