<?php
/**
 * Hook Manager Class
 * Manages WordPress hooks and filters for featured image generation
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SIC_Hook_Manager class
 * Responsible for managing WordPress hooks and theme compatibility
 */
class SIC_Hook_Manager {
    
    /**
     * Instance
     *
     * @var SIC_Hook_Manager
     */
    private static $instance = null;
    
    /**
     * Settings
     *
     * @var array
     */
    private $settings;
    
    /**
     * Image generator reference
     *
     * @var SIC_Image_Generator
     */
    private $image_generator;
    
    /**
     * Get instance
     *
     * @return SIC_Hook_Manager
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
    }
    
    /**
     * Set image generator reference
     *
     * @param SIC_Image_Generator $image_generator Image generator instance
     */
    public function set_image_generator(SIC_Image_Generator $image_generator) {
        $this->image_generator = $image_generator;
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Core featured image hooks
        add_filter('post_thumbnail_html', array($this, 'maybe_generate_featured_image'), 10, 5);
        add_filter('get_post_metadata', array($this, 'override_thumbnail_id'), 10, 4);
        add_filter('has_post_thumbnail', array($this, 'override_has_post_thumbnail'), 10, 2);
        
        // Additional theme compatibility hooks
        add_filter('wp_get_attachment_image', array($this, 'filter_attachment_image'), 10, 5);
        add_filter('the_post_thumbnail', array($this, 'filter_the_post_thumbnail'), 10, 2);
        add_filter('get_the_post_thumbnail', array($this, 'filter_get_the_post_thumbnail'), 10, 5);
        
        // Content injection for better compatibility
        add_filter('the_content', array($this, 'maybe_inject_featured_image'), 5);
        add_action('wp_head', array($this, 'add_theme_compatibility_css'), 5);
        
        // Single post/page specific handling
        add_action('template_redirect', array($this, 'handle_single_post_display'));
        
        // Third-party plugin compatibility
        $this->init_third_party_compatibility();
        
        // Testing and debugging
        add_shortcode('SIC_test', array($this, 'test_shortcode'));
    }
    
    /**
     * Initialize third-party plugin compatibility hooks
     */
    private function init_third_party_compatibility() {
        // Elementor compatibility
        add_filter('elementor/frontend/widget/before_render_content', array($this, 'elementor_compatibility'));
        
        // Gutenberg block compatibility
        add_filter('render_block', array($this, 'gutenberg_block_compatibility'), 10, 2);
        
        // WooCommerce compatibility
        add_filter('woocommerce_placeholder_img_src', array($this, 'woocommerce_compatibility'));
    }
    
    /**
     * Maybe generate featured image HTML
     *
     * @param string $html Existing HTML
     * @param int $post_id Post ID
     * @param int $post_thumbnail_id Thumbnail ID
     * @param string|array $size Image size
     * @param string|array $attr Image attributes
     * @return string Featured image HTML
     */
    public function maybe_generate_featured_image($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (WP_DEBUG) {
            error_log("WP AFI Debug: maybe_generate_featured_image called for post {$post_id}");
        }
        
        // If we already have valid HTML and thumbnail, return it
        if ($this->has_valid_existing_thumbnail($html, $post_thumbnail_id)) {
            return $html;
        }
        
        $post = get_post($post_id);
        if (!$this->should_generate_for_post($post)) {
            return $html;
        }
        
        if (WP_DEBUG) {
            error_log("WP AFI Debug: Generating featured image for post {$post_id}");
        }
        
        return $this->image_generator->generate_featured_image_html($post, $size, $attr);
    }
    
    /**
     * Check if there's a valid existing thumbnail
     *
     * @param string $html Existing HTML
     * @param int $post_thumbnail_id Thumbnail ID
     * @return bool True if valid thumbnail exists
     */
    private function has_valid_existing_thumbnail($html, $post_thumbnail_id) {
        if (empty($html) || empty($post_thumbnail_id)) {
            return false;
        }
        
        $attachment = get_post($post_thumbnail_id);
        return $attachment && 
               $attachment->post_type === 'attachment' && 
               strpos($attachment->post_mime_type, 'image/') === 0;
    }
    
    /**
     * Check if we should generate image for this post
     *
     * @param WP_Post|null $post Post object
     * @return bool True if should generate
     */
    private function should_generate_for_post($post) {
        if (!$post) {
            return false;
        }
        
        if (!$this->settings['enabled'] || !$this->settings['auto_activate']) {
            return false;
        }
        
        return $this->image_generator->should_generate_for_post_type($post->post_type);
    }
    
    /**
     * Override thumbnail ID to indicate we have a generated image
     *
     * @param mixed $value Metadata value
     * @param int $object_id Object ID
     * @param string $meta_key Meta key
     * @param bool $single Single value flag
     * @return mixed Metadata value
     */
    public function override_thumbnail_id($value, $object_id, $meta_key, $single) {
        if ($meta_key !== '_thumbnail_id' || !empty($value)) {
            return $value;
        }
        
        if (!$this->settings['enabled'] || !$this->settings['auto_activate']) {
            return $value;
        }
        
        $post = get_post($object_id);
        if (!$post || !$this->image_generator->should_generate_for_post_type($post->post_type)) {
            return $value;
        }
        
        // Return a fake ID to indicate we have a featured image
        return 'wp-afi-generated';
    }
    
    /**
     * Override has_post_thumbnail for theme compatibility
     *
     * @param bool $has_thumbnail Current thumbnail status
     * @param int|WP_Post $post Post object or ID
     * @return bool Whether post has thumbnail
     */
    public function override_has_post_thumbnail($has_thumbnail, $post = null) {
        if ($has_thumbnail || !$this->settings['enabled'] || !$this->settings['auto_activate']) {
            return $has_thumbnail;
        }
        
        $post = get_post($post);
        if (!$post || !$this->image_generator->should_generate_for_post_type($post->post_type)) {
            return $has_thumbnail;
        }
        
        return $this->image_generator->should_generate_image($post);
    }
    
    /**
     * Filter wp_get_attachment_image for additional compatibility
     *
     * @param string $html HTML content
     * @param int $attachment_id Attachment ID
     * @param string|array $size Image size
     * @param bool $icon Icon flag
     * @param array $attr Image attributes
     * @return string HTML content
     */
    public function filter_attachment_image($html, $attachment_id, $size, $icon, $attr) {
        if (empty($html) && $attachment_id === 'wp-afi-generated') {
            global $post;
            if ($post && $this->image_generator->should_generate_for_post_type($post->post_type)) {
                return $this->image_generator->generate_featured_image_html($post, $size, $attr);
            }
        }
        
        return $html;
    }
    
    /**
     * Filter the_post_thumbnail for additional compatibility
     *
     * @param string $html HTML content
     * @param int|WP_Post $post Post object or ID
     * @return string HTML content
     */
    public function filter_the_post_thumbnail($html, $post = null) {
        if (!empty($html)) {
            return $html;
        }
        
        $post = get_post($post);
        if (!$this->should_generate_for_post($post) || has_post_thumbnail($post->ID)) {
            return $html;
        }
        
        return $this->image_generator->generate_featured_image_html($post, 'post-thumbnail', array());
    }
    
    /**
     * Filter get_the_post_thumbnail for additional compatibility
     *
     * @param string $html HTML content
     * @param int|WP_Post $post Post object or ID
     * @param string|array $size Image size
     * @param string|array $attr Image attributes
     * @param bool $skip_lazy Skip lazy loading flag
     * @return string HTML content
     */
    public function filter_get_the_post_thumbnail($html, $post, $size, $attr, $skip_lazy = false) {
        if (!empty($html)) {
            return $html;
        }
        
        $post = get_post($post);
        if (!$this->should_generate_for_post($post)) {
            return $html;
        }
        
        return $this->image_generator->generate_featured_image_html($post, $size, $attr);
    }
    
    /**
     * Add theme compatibility CSS
     */
    public function add_theme_compatibility_css() {
        if (!$this->settings['enabled']) {
            return;
        }
        
        $theme_compat = SIC_Theme_Compatibility::get_instance();
        $compatibility_css = $theme_compat->get_compatibility_css();
        
        if (!empty($compatibility_css)) {
            echo "<style id='wp-afi-theme-compatibility'>\n" . $compatibility_css . "\n</style>\n";
        }
    }
    
    /**
     * Handle single post display with aggressive hooks
     */
    public function handle_single_post_display() {
        if (!is_single() && !is_page()) {
            return;
        }
        
        global $post;
        if (!$post || has_post_thumbnail($post->ID) || !$this->should_generate_for_post($post)) {
            return;
        }
        
        add_filter('post_thumbnail_html', array($this, 'force_featured_image_on_single'), 999, 5);
        add_action('wp_footer', array($this, 'inject_featured_image_js'));
    }
    
    /**
     * Force featured image on single posts/pages
     *
     * @param string $html HTML content
     * @param int $post_id Post ID
     * @param int $post_thumbnail_id Thumbnail ID
     * @param string|array $size Image size
     * @param string|array $attr Image attributes
     * @return string HTML content
     */
    public function force_featured_image_on_single($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (!empty($html)) {
            return $html;
        }
        
        $post = get_post($post_id);
        if (!$post || !$this->image_generator->should_generate_image($post)) {
            return $html;
        }
        
        return $this->image_generator->generate_featured_image_html($post, $size, $attr);
    }
    
    /**
     * Maybe inject featured image into content
     *
     * @param string $content Post content
     * @return string Modified content
     */
    public function maybe_inject_featured_image($content) {
        if (!is_single() && !is_page()) {
            return $content;
        }
        
        global $post;
        if (!$this->should_generate_for_post($post) || !$this->image_generator->should_generate_image($post)) {
            return $content;
        }
        
        if (WP_DEBUG) {
            error_log("WP AFI Debug: Injecting featured image into content for post {$post->ID}");
        }
        
        $featured_image = $this->image_generator->generate_featured_image_html(
            $post, 
            'large', 
            array('class' => 'wp-afi-injected-image')
        );
        
        if (!empty($featured_image)) {
            $featured_container = sprintf(
                '<div class="wp-afi-featured-container wp-afi-content-injected" style="margin-bottom: 2rem; text-align: center;">%s</div>',
                $featured_image
            );
            $content = $featured_container . $content;
        }
        
        return $content;
    }
    
    /**
     * Inject JavaScript to handle missing featured images
     */
    public function inject_featured_image_js() {
        global $post;
        if (!$post || has_post_thumbnail($post->ID) || !$this->image_generator->should_generate_image($post)) {
            return;
        }
        
        $featured_image = $this->image_generator->generate_featured_image_html(
            $post, 
            'large', 
            array('class' => 'wp-afi-js-injected')
        );
        
        $this->output_javascript_injection($featured_image);
    }
    
    /**
     * Output JavaScript for image injection
     *
     * @param string $featured_image Featured image HTML
     */
    private function output_javascript_injection($featured_image) {
        $featured_image_json = json_encode($featured_image);
        ?>
        <script>
        (function() {
            var selectors = [
                '.post-thumbnail', '.featured-image', '.entry-image', '.post-image',
                '.wp-post-image', '.attachment-post-thumbnail', '.post-featured-image',
                '.entry-featured-image', '.single-featured-image'
            ];
            
            var featuredImage = <?php echo $featured_image_json; ?>;
            var imageInserted = false;
            
            selectors.forEach(function(selector) {
                if (imageInserted) return;
                var container = document.querySelector(selector);
                if (container && container.innerHTML.trim() === '') {
                    container.innerHTML = featuredImage;
                    imageInserted = true;
                }
            });
            
            if (!imageInserted) {
                var contentSelectors = ['.entry-content', '.post-content', '.content', 'article .content', '.single-content'];
                contentSelectors.forEach(function(selector) {
                    if (imageInserted) return;
                    var content = document.querySelector(selector);
                    if (content) {
                        var imageDiv = document.createElement('div');
                        imageDiv.className = 'wp-afi-featured-container wp-afi-js-inserted';
                        imageDiv.innerHTML = featuredImage;
                        content.insertBefore(imageDiv, content.firstChild);
                        imageInserted = true;
                    }
                });
            }
        })();
        </script>
        <style>
        .wp-afi-featured-container { margin-bottom: 2rem; }
        .wp-afi-js-inserted .wp-afi-generated-image { width: 100%; height: auto; display: block; }
        </style>
        <?php
    }
    
    /**
     * Third-party plugin compatibility methods
     */
    
    /**
     * Elementor compatibility
     *
     * @param \Elementor\Widget_Base $widget Widget instance
     */
    public function elementor_compatibility($widget) {
        if (in_array($widget->get_name(), array('image', 'posts'))) {
            add_filter('elementor/image_size/get_attachment_image', array($this, 'elementor_image_filter'), 10, 4);
        }
    }
    
    /**
     * Elementor image filter
     *
     * @param string $html HTML content
     * @param array $settings Widget settings
     * @param string $image_size_key Image size key
     * @param string $image_key Image key
     * @return string HTML content
     */
    public function elementor_image_filter($html, $settings, $image_size_key, $image_key) {
        if (empty($html) && !empty($settings['post_id'])) {
            $post = get_post($settings['post_id']);
            if ($post && !has_post_thumbnail($post->ID)) {
                return $this->image_generator->generate_featured_image_html($post, 'medium', array());
            }
        }
        return $html;
    }
    
    /**
     * Gutenberg block compatibility
     *
     * @param string $block_content Block content
     * @param array $block Block data
     * @return string Modified block content
     */
    public function gutenberg_block_compatibility($block_content, $block) {
        if ($block['blockName'] === 'core/post-featured-image' && strpos($block_content, '<img') === false) {
            $post_id = $block['attrs']['postId'] ?? get_the_ID();
            $post = get_post($post_id);
            
            if ($post && !has_post_thumbnail($post->ID)) {
                $size = $block['attrs']['sizeSlug'] ?? 'post-thumbnail';
                $generated_image = $this->image_generator->generate_featured_image_html($post, $size, array());
                $block_content = '<div class="wp-block-post-featured-image">' . $generated_image . '</div>';
            }
        }
        
        return $block_content;
    }
    
    /**
     * WooCommerce compatibility
     *
     * @param string $placeholder_img_src Placeholder image source
     * @return string Image source
     */
    public function woocommerce_compatibility($placeholder_img_src) {
        // WooCommerce has its own placeholder system, we generally don't interfere
        return $placeholder_img_src;
    }
    
    /**
     * Test shortcode for debugging
     *
     * @param array $atts Shortcode attributes
     * @return string Debug output
     */
    public function test_shortcode($atts) {
        $atts = shortcode_atts(array('post_id' => get_the_ID()), $atts);
        
        $post = get_post(intval($atts['post_id']));
        if (!$post) {
            return '<div style="padding: 1rem; background: #f0f0f0; border: 1px solid #ccc;">❌ Post not found</div>';
        }
        
        return $this->image_generator->generate_debug_output($post);
    }
}
