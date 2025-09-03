<?php
/**
 * Refactored Image Generator Class
 * Core orchestrator for featured image generation
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SIC_Image_Generator class
 * Main coordinator for featured image generation, using specialized service classes
 */
class SIC_Image_Generator {
    
    /**
     * Instance
     *
     * @var SIC_Image_Generator
     */
    private static $instance = null;
    
    /**
     * Plugin settings
     *
     * @var array
     */
    private $settings;
    
    /**
     * Cache manager
     *
     * @var SIC_Cache_Manager
     */
    private $cache_manager;
    
    /**
     * Template manager
     *
     * @var SIC_Template_Manager
     */
    private $template_manager;
    
    /**
     * Hook manager
     *
     * @var SIC_Hook_Manager
     */
    private $hook_manager;
    
    /**
     * Get instance
     *
     * @return SIC_Image_Generator
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
        $this->settings = Smart_Image_Canvas::get_settings();
        $this->init_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Initialize service dependencies
     */
    private function init_dependencies() {
        $this->cache_manager = SIC_Cache_Manager::get_instance();
        $this->template_manager = SIC_Template_Manager::get_instance();
        $this->hook_manager = SIC_Hook_Manager::get_instance();
        
        // Provide reference to hook manager so it can call back to this class
        $this->hook_manager->set_image_generator($this);
    }
    
    /**
     * Initialize hooks (only for settings updates)
     */
    private function init_hooks() {
        add_action('update_option_sic_settings', array($this, 'on_settings_updated'));
    }
    
    /**
     * Handle settings update
     */
    public function on_settings_updated() {
        $this->settings = Smart_Image_Canvas::get_settings();
        $this->cache_manager->clear_all_cache();
    }
    
    /**
     * Generate featured image HTML with caching
     *
     * @param WP_Post $post Post object
     * @param string|array $size Image size
     * @param string|array $attr Image attributes
     * @return string Generated HTML
     */
    public function generate_featured_image_html(WP_Post $post, $size = 'post-thumbnail', $attr = '') {
        // Generate cache key
        $cache_key = $this->cache_manager->generate_cache_key($post->ID, $size, $attr, $this->settings);
        
        // Try to get from cache first
        $cached_html = $this->cache_manager->get_cached_html($cache_key);
        if ($cached_html !== false) {
            return $cached_html;
        }
        
        // Generate HTML using template manager
        $html = $this->template_manager->generate_html($post, $size, $attr, $this->settings);
        
        // Store in cache
        $this->cache_manager->store_html($cache_key, $html);
        
        return $html;
    }
    
    /**
     * Check if we should generate featured image for this post type
     *
     * @param string $post_type Post type
     * @return bool True if should generate
     */
    public function should_generate_for_post_type($post_type) {
        $allowed_post_types = !empty($this->settings['post_types']) ? $this->settings['post_types'] : array('post');
        $supported_post_types = apply_filters('SIC_supported_post_types', $allowed_post_types);
        
        if (WP_DEBUG) {
            error_log("WP AFI Debug: Checking post type '{$post_type}' against allowed types: " . implode(', ', $supported_post_types));
        }
        
        return in_array($post_type, $supported_post_types);
    }
    
    /**
     * Check if post should have generated image
     *
     * @param WP_Post $post Post object
     * @return bool True if should generate
     */
    public function should_generate_image(WP_Post $post) {
        // Plugin enabled?
        if (!$this->settings['enabled']) {
            return false;
        }
        
        // Check if there's a real featured image (not just meta data)
        if ($this->has_real_featured_image($post)) {
            return false;
        }
        
        // Check post types
        if (!$this->should_generate_for_post_type($post->post_type)) {
            return false;
        }
        
        // Check if post has title
        $title = trim($post->post_title);
        return !empty($title);
    }
    
    /**
     * Check if post has a real featured image
     *
     * @param WP_Post $post Post object
     * @return bool True if has real featured image
     */
    private function has_real_featured_image(WP_Post $post) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        if (!$thumbnail_id) {
            return false;
        }
        
        $attachment = get_post($thumbnail_id);
        if (!$attachment) {
            // Clean up invalid thumbnail meta
            delete_post_meta($post->ID, '_thumbnail_id');
            return false;
        }
        
        // Verify it's a real image attachment
        return $attachment->post_type === 'attachment' && 
               strpos($attachment->post_mime_type, 'image/') === 0;
    }
    
    /**
     * Force generate featured image (for debugging/testing)
     *
     * @param WP_Post $post Post object
     * @param string|array $size Image size
     * @param array $attr Image attributes
     * @return string Generated HTML
     */
    public function force_generate_featured_image(WP_Post $post, $size = 'post-thumbnail', $attr = array()) {
        // Temporarily disable any theme interference
        $this->disable_conflicting_filters();
        
        // Generate the image
        $html = $this->template_manager->generate_html($post, $size, $attr, $this->settings);
        
        // Restore filters
        $this->restore_conflicting_filters();
        
        return $html;
    }
    
    /**
     * Disable conflicting filters temporarily
     */
    private function disable_conflicting_filters() {
        $this->backup_filters = array();
        
        $conflicting_filters = array(
            'post_thumbnail_html',
            'wp_get_attachment_image',
            'the_post_thumbnail'
        );
        
        foreach ($conflicting_filters as $filter) {
            if (has_filter($filter)) {
                $this->backup_filters[$filter] = $GLOBALS['wp_filter'][$filter];
                unset($GLOBALS['wp_filter'][$filter]);
            }
        }
    }
    
    /**
     * Restore conflicting filters
     */
    private function restore_conflicting_filters() {
        if (!isset($this->backup_filters)) {
            return;
        }
        
        foreach ($this->backup_filters as $filter => $callbacks) {
            $GLOBALS['wp_filter'][$filter] = $callbacks;
        }
        
        unset($this->backup_filters);
    }
    
    /**
     * Generate debug output for testing
     *
     * @param WP_Post $post Post object
     * @return string Debug HTML
     */
    public function generate_debug_output(WP_Post $post) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $thumbnail_url = get_the_post_thumbnail_url($post->ID);
        $meta_value = get_post_meta($post->ID, '_thumbnail_id', true);
        
        $output = '<div style="padding: 1rem; background: #f9f9f9; border: 1px solid #ddd; margin: 1rem 0;">';
        $output .= '<h4>🔧 WP Auto Featured Image Debug</h4>';
        $output .= '<p><strong>Post ID:</strong> ' . $post->ID . '</p>';
        $output .= '<p><strong>Post Title:</strong> ' . $post->post_title . '</p>';
        $output .= '<p><strong>Post Type:</strong> ' . $post->post_type . '</p>';
        $output .= '<p><strong>Has Thumbnail:</strong> ' . (has_post_thumbnail($post->ID) ? '✅ Yes' : '❌ No') . '</p>';
        $output .= '<p><strong>Thumbnail ID:</strong> ' . ($thumbnail_id ? $thumbnail_id : 'None') . '</p>';
        $output .= '<p><strong>Thumbnail URL:</strong> ' . ($thumbnail_url ? $thumbnail_url : 'None') . '</p>';
        $output .= '<p><strong>Meta Value:</strong> ' . ($meta_value ? $meta_value : 'None') . '</p>';
        $output .= '<p><strong>Plugin Enabled:</strong> ' . ($this->settings['enabled'] ? '✅ Yes' : '❌ No') . '</p>';
        $output .= '<p><strong>Auto Activate:</strong> ' . ($this->settings['auto_activate'] ? '✅ Yes' : '❌ No') . '</p>';
        $output .= '<p><strong>Should Generate:</strong> ' . ($this->should_generate_image($post) ? '✅ Yes' : '❌ No') . '</p>';
        $output .= '<p><strong>Post Type Allowed:</strong> ' . ($this->should_generate_for_post_type($post->post_type) ? '✅ Yes' : '❌ No') . '</p>';
        
        $allowed_types = !empty($this->settings['post_types']) ? $this->settings['post_types'] : array('post');
        $output .= '<p><strong>Allowed Post Types:</strong> ' . implode(', ', $allowed_types) . '</p>';
        
        // Check if thumbnail is a real attachment
        if ($thumbnail_id) {
            $attachment = get_post($thumbnail_id);
            if ($attachment) {
                $output .= '<p><strong>Attachment Type:</strong> ' . $attachment->post_type . '</p>';
                $output .= '<p><strong>MIME Type:</strong> ' . $attachment->post_mime_type . '</p>';
            } else {
                $output .= '<p><strong>⚠️ Thumbnail ID exists but attachment not found!</strong></p>';
            }
        }
        
        // Show cache stats
        $cache_stats = $this->cache_manager->get_cache_stats();
        $output .= '<p><strong>Cached Items:</strong> ' . $cache_stats['cached_items'] . '</p>';
        
        // Force generate for testing
        $output .= '<h5>🧪 Force Generated Image:</h5>';
        $generated = $this->force_generate_featured_image($post, 'medium', array());
        $output .= '<div style="border: 1px solid #ccc; padding: 1rem; background: white;">' . $generated . '</div>';
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Get cache manager instance
     *
     * @return SIC_Cache_Manager
     */
    public function get_cache_manager() {
        return $this->cache_manager;
    }
    
    /**
     * Get template manager instance
     *
     * @return SIC_Template_Manager
     */
    public function get_template_manager() {
        return $this->template_manager;
    }
    
    /**
     * Clear cache for specific post (delegated to cache manager)
     *
     * @param int $post_id Post ID
     */
    public function clear_post_cache($post_id) {
        $this->cache_manager->clear_post_cache($post_id);
    }
    
    /**
     * Clear all cache (delegated to cache manager)
     */
    public function clear_all_cache() {
        $this->cache_manager->clear_all_cache();
    }
    
    /**
     * Get available template styles (delegated to template manager)
     * 
     * @return array Template styles
     */
    public static function get_template_styles() {
        return SIC_Template_Manager::get_template_styles();
    }
    
    /**
     * Get available aspect ratios (delegated to template manager)
     * 
     * @return array Aspect ratios
     */
    public static function get_aspect_ratios() {
        return SIC_Template_Manager::get_aspect_ratios();
    }
    
    /**
     * Get available font families (delegated to template manager)
     * 
     * @return array Font families
     */
    public static function get_font_families() {
        return SIC_Template_Manager::get_font_families();
    }
}
