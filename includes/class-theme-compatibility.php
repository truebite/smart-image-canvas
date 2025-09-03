<?php
/**
 * Theme Compatibility Class
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SIC_Theme_Compatibility class
 */
class SIC_Theme_Compatibility {
    
    /**
     * Instance
     *
     * @var SIC_Theme_Compatibility
     */
    private static $instance = null;
    
    /**
     * Current theme
     *
     * @var string
     */
    private $current_theme;
    
    /**
     * Get instance
     *
     * @return SIC_Theme_Compatibility
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
        $this->current_theme = get_template();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_head', array($this, 'add_compatibility_fixes'), 1);
        add_action('init', array($this, 'detect_theme_issues'));
        add_filter('SIC_generated_image_html', array($this, 'modify_html_for_theme'), 10, 4);
        
        // Force re-check for featured images on problematic themes
        if ($this->is_problematic_theme()) {
            add_action('wp', array($this, 'force_featured_image_check'), 99);
        }
        
        // Additional hooks for specific themes
        $this->init_theme_specific_hooks();
    }
    
    /**
     * Detect if current theme has known issues
     *
     * @return bool
     */
    private function is_problematic_theme() {
        $problematic_themes = array(
            'divi',
            'avada',
            'x',
            'pro',
            'betheme',
            'bridge',
            'salient',
            'jupiter',
            'the7',
            'enfold'
        );
        
        return in_array($this->current_theme, $problematic_themes) || 
               $this->is_page_builder_theme();
    }
    
    /**
     * Check if theme uses page builders
     *
     * @return bool
     */
    private function is_page_builder_theme() {
        // Check for common page builder indicators
        return (
            // Elementor
            defined('ELEMENTOR_VERSION') ||
            // Divi
            function_exists('et_setup_theme') ||
            // Beaver Builder
            class_exists('FLBuilder') ||
            // Visual Composer
            defined('WPB_VC_VERSION') ||
            // Fusion Builder (Avada)
            class_exists('FusionBuilder') ||
            // Cornerstone (X/Pro)
            class_exists('Cornerstone_Plugin')
        );
    }
    
    /**
     * Force featured image check on problematic themes
     */
    public function force_featured_image_check() {
        global $post;
        
        if (!$post || has_post_thumbnail($post->ID)) {
            return;
        }
        
        // Force add featured image to global context
        add_filter('post_thumbnail_html', array($this, 'force_generate_image'), 999, 5);
        add_filter('the_post_thumbnail', array($this, 'force_generate_image_simple'), 999, 2);
    }
    
    /**
     * Force generate image with high priority
     *
     * @param string $html
     * @param int $post_id
     * @param int $post_thumbnail_id
     * @param string|array $size
     * @param string|array $attr
     * @return string
     */
    public function force_generate_image($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (!empty($html)) {
            return $html;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return $html;
        }
        
        $generator = SIC_Image_Generator::get_instance();
        return $generator->generate_featured_image_html($post, $size, $attr);
    }
    
    /**
     * Force generate image (simple version)
     *
     * @param string $html
     * @param int|WP_Post $post
     * @return string
     */
    public function force_generate_image_simple($html, $post) {
        if (!empty($html)) {
            return $html;
        }
        
        $post = get_post($post);
        if (!$post) {
            return $html;
        }
        
        $generator = SIC_Image_Generator::get_instance();
        return $generator->generate_featured_image_html($post, 'post-thumbnail', array());
    }
    
    /**
     * Add compatibility fixes
     */
    public function add_compatibility_fixes() {
        $css = $this->get_theme_specific_css();
        $js = $this->get_theme_specific_js();
        
        if (!empty($css)) {
            echo "<style id='wp-afi-theme-fixes'>\n" . $css . "\n</style>\n";
        }
        
        if (!empty($js)) {
            echo "<script id='wp-afi-theme-fixes'>\n" . $js . "\n</script>\n";
        }
    }
    
    /**
     * Get theme-specific CSS fixes
     *
     * @return string
     */
    private function get_theme_specific_css() {
        $css = '';
        
        switch ($this->current_theme) {
            case 'divi':
                $css = '
                .et_pb_post .wp-afi-generated-image,
                .et_pb_blog_grid .wp-afi-generated-image {
                    width: 100% !important;
                    height: auto !important;
                    object-fit: cover;
                }
                .et_pb_image .wp-afi-generated-image {
                    border-radius: inherit;
                }
                ';
                break;
                
            case 'avada':
                $css = '
                .fusion-post-content .wp-afi-generated-image,
                .fusion-blog-layout-grid .wp-afi-generated-image {
                    width: 100% !important;
                    display: block;
                }
                .fusion-image-wrapper .wp-afi-generated-image {
                    border-radius: inherit;
                }
                ';
                break;
                
            case 'x':
            case 'pro':
                $css = '
                .x-entry-featured .wp-afi-generated-image,
                .x-recent-posts .wp-afi-generated-image {
                    width: 100% !important;
                    height: auto !important;
                }
                ';
                break;
                
            case 'betheme':
                $css = '
                .post-photo .wp-afi-generated-image,
                .post-photo-wrapper .wp-afi-generated-image {
                    width: 100% !important;
                    height: auto !important;
                    display: block;
                }
                ';
                break;
                
            case 'bridge':
                $css = '
                .qode-post-image .wp-afi-generated-image,
                .qode-blog-holder .wp-afi-generated-image {
                    width: 100% !important;
                    height: auto !important;
                }
                ';
                break;
                
            case 'salient':
                $css = '
                .post-featured-img .wp-afi-generated-image,
                .nectar-blog .wp-afi-generated-image {
                    width: 100% !important;
                    height: auto !important;
                    object-fit: cover;
                }
                ';
                break;
                
            case 'jupiter':
                $css = '
                .mk-blog-featured-image .wp-afi-generated-image,
                .mk-post-featured-image .wp-afi-generated-image {
                    width: 100% !important;
                    height: auto !important;
                }
                ';
                break;
                
            case 'the7':
                $css = '
                .post-thumbnail-wrap .wp-afi-generated-image,
                .dt-blog-post .wp-afi-generated-image {
                    width: 100% !important;
                    height: auto !important;
                    display: block;
                }
                ';
                break;
                
            case 'enfold':
                $css = '
                .av-masonry-entry .wp-afi-generated-image,
                .slide-image .wp-afi-generated-image {
                    width: 100% !important;
                    height: auto !important;
                    object-fit: cover;
                }
                ';
                break;
        }
        
        // Add general fixes for all themes
        $css .= '
        /* Force display in theme containers */
        .wp-afi-generated-image {
            max-width: 100% !important;
            height: auto !important;
            display: block !important;
        }
        
        /* Fix for themes that hide empty image containers */
        .post-thumbnail:empty,
        .featured-image:empty,
        .entry-image:empty {
            display: block !important;
        }
        
        /* Ensure proper sizing in grid layouts */
        .wp-afi-generated-image {
            box-sizing: border-box !important;
        }
        
        /* Fix for lazy loading issues */
        .wp-afi-generated-image[data-src] {
            display: block !important;
            opacity: 1 !important;
        }
        ';
        
        return $css;
    }
    
    /**
     * Get compatibility CSS (public wrapper for hook manager)
     *
     * @return string
     */
    public function get_compatibility_css() {
        return $this->get_theme_specific_css();
    }
    
    /**
     * Get theme-specific JavaScript fixes
     *
     * @return string
     */
    private function get_theme_specific_js() {
        $js = '';
        
        if ($this->is_page_builder_theme()) {
            $js = '
            (function() {
                // Force check for generated images after page builder loads
                document.addEventListener("DOMContentLoaded", function() {
                    setTimeout(function() {
                        // Trigger featured image check for any empty containers
                        var emptyContainers = document.querySelectorAll(".post-thumbnail:empty, .featured-image:empty, .entry-image:empty");
                        emptyContainers.forEach(function(container) {
                            if (!container.querySelector(".wp-afi-generated-image")) {
                                // Try to trigger WordPress featured image function
                                var event = new CustomEvent("wp-afi-check-featured", {
                                    detail: { container: container }
                                });
                                document.dispatchEvent(event);
                            }
                        });
                    }, 1000);
                });
                
                // Handle AJAX content loading
                document.addEventListener("wp-afi-check-featured", function(e) {
                    var container = e.detail.container;
                    var postId = container.getAttribute("data-post-id") || 
                                container.closest("[data-post-id]")?.getAttribute("data-post-id");
                    
                    if (postId) {
                        // You could make an AJAX call here to generate the image
                        console.log("Checking featured image for post", postId);
                    }
                });
            })();
            ';
        }
        
        return $js;
    }
    
    /**
     * Initialize theme-specific hooks
     */
    private function init_theme_specific_hooks() {
        switch ($this->current_theme) {
            case 'divi':
                add_filter('et_post_gallery_image', array($this, 'divi_post_image_fallback'), 10, 3);
                break;
                
            case 'avada':
                add_filter('fusion_library_image', array($this, 'avada_image_fallback'), 10, 2);
                break;
                
            case 'elementor':
                add_action('elementor/frontend/after_enqueue_styles', array($this, 'elementor_compatibility'));
                break;
        }
    }
    
    /**
     * Divi post image fallback
     *
     * @param string $image
     * @param int $post_id
     * @param array $args
     * @return string
     */
    public function divi_post_image_fallback($image, $post_id, $args) {
        if (empty($image)) {
            $post = get_post($post_id);
            if ($post) {
                $generator = SIC_Image_Generator::get_instance();
                return $generator->generate_featured_image_html($post, 'medium', array());
            }
        }
        return $image;
    }
    
    /**
     * Avada image fallback
     *
     * @param string $image
     * @param array $args
     * @return string
     */
    public function avada_image_fallback($image, $args) {
        if (empty($image) && !empty($args['post_id'])) {
            $post = get_post($args['post_id']);
            if ($post) {
                $generator = SIC_Image_Generator::get_instance();
                return $generator->generate_featured_image_html($post, 'medium', array());
            }
        }
        return $image;
    }
    
    /**
     * Elementor compatibility
     */
    public function elementor_compatibility() {
        // Add Elementor-specific CSS
        echo '<style>
        .elementor-widget-image .wp-afi-generated-image,
        .elementor-widget-theme-post-featured-image .wp-afi-generated-image {
            width: 100% !important;
            height: auto !important;
        }
        </style>';
    }
    
    /**
     * Modify HTML for specific themes
     *
     * @param string $html
     * @param WP_Post $post
     * @param string|array $size
     * @param string|array $attr
     * @return string
     */
    public function modify_html_for_theme($html, $post, $size, $attr) {
        switch ($this->current_theme) {
            case 'divi':
                // Divi expects specific classes
                $html = str_replace('class="wp-afi-generated-image', 'class="et_pb_image wp-afi-generated-image', $html);
                break;
                
            case 'avada':
                // Avada expects fusion classes
                $html = str_replace('class="wp-afi-generated-image', 'class="fusion-image-wrapper wp-afi-generated-image', $html);
                break;
                
            case 'x':
            case 'pro':
                // X/Pro expects specific structure
                $html = '<div class="x-entry-featured">' . $html . '</div>';
                break;
        }
        
        return $html;
    }
    
    /**
     * Detect theme issues and log them
     */
    public function detect_theme_issues() {
        // Check for common issues
        $issues = array();
        
        // Check if theme overrides featured image functions
        if (!has_filter('post_thumbnail_html')) {
            $issues[] = 'Theme may not use standard featured image functions';
        }
        
        // Check for page builder conflicts
        if ($this->is_page_builder_theme()) {
            $issues[] = 'Page builder detected - may need additional compatibility';
        }
        
        // Log issues for debugging (only in debug mode)
        if (WP_DEBUG && !empty($issues)) {
            error_log('WP Auto Featured Image - Theme compatibility issues detected: ' . implode(', ', $issues));
        }
    }
    
    /**
     * Get theme compatibility status
     *
     * @return array
     */
    public function get_compatibility_status() {
        return array(
            'theme' => $this->current_theme,
            'is_problematic' => $this->is_problematic_theme(),
            'is_page_builder' => $this->is_page_builder_theme(),
            'compatibility_level' => $this->get_compatibility_level()
        );
    }
    
    /**
     * Get compatibility level
     *
     * @return string
     */
    private function get_compatibility_level() {
        if (in_array($this->current_theme, array('twentytwentythree', 'twentytwentytwo', 'twentytwentyone'))) {
            return 'excellent';
        }
        
        if (in_array($this->current_theme, array('astra', 'generatepress', 'oceanwp', 'neve', 'kadence'))) {
            return 'good';
        }
        
        if ($this->is_page_builder_theme()) {
            return 'moderate';
        }
        
        return 'basic';
    }
}
