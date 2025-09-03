<?php
/**
 * Frontend Display Class
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SIC_Frontend_Display class
 */
class SIC_Frontend_Display {
    
    /**
     * Instance
     *
     * @var SIC_Frontend_Display
     */
    private static $instance = null;
    
    /**
     * Settings
     *
     * @var array
     */
    private $settings;
    
    /**
     * Get instance
     *
     * @return SIC_Frontend_Display
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
        $this->init_hooks();
        
        // Add cache cleanup hooks
        add_action('update_option_sic_settings', array($this, 'clear_css_cache'));
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_head', array($this, 'output_dynamic_css'));
        add_action('wp_footer', array($this, 'output_dynamic_js'));
    }
    
    /**
     * Output dynamic CSS in head with caching
     */
    public function output_dynamic_css() {
        if (!$this->settings['enabled']) {
            return;
        }
        
        $css = $this->get_cached_css();
        
        if (!empty($css)) {
            echo "\n<style id='wp-afi-dynamic-css'>\n" . $css . "\n</style>\n";
        }
    }
    
    /**
     * Get CSS with caching
     *
     * @return string
     */
    private function get_cached_css() {
        $cache_key = 'SIC_dynamic_css_' . md5(serialize($this->settings));
        $cached_css = get_transient($cache_key);
        
        if ($cached_css !== false && !WP_DEBUG) {
            return $cached_css;
        }
        
        $css = $this->generate_dynamic_css();
        
        // Minify CSS in production
        if (!WP_DEBUG) {
            $css = $this->minify_css($css);
        }
        
        // Cache for 6 hours
        set_transient($cache_key, $css, 6 * HOUR_IN_SECONDS);
        
        return $css;
    }
    
    /**
     * Clear CSS cache
     */
    public function clear_css_cache() {
        global $wpdb;
        
        $like = $wpdb->esc_like('SIC_dynamic_css_') . '%';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            "_transient_{$like}"
        ));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            "_transient_timeout_{$like}"
        ));
    }
    
    /**
     * Minify CSS
     *
     * @param string $css
     * @return string
     */
    private function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
        
        // Remove extra spaces
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/;\s*}/', '}', $css);
        $css = preg_replace('/\s*;\s*/', ';', $css);
        $css = preg_replace('/\s*:\s*/', ':', $css);
        $css = preg_replace('/\s*,\s*/', ',', $css);
        
        return trim($css);
    }
    
    /**
     * Generate dynamic CSS
     *
     * @return string
     */
    private function generate_dynamic_css() {
        $css_parts = array();
        
        // Base styles for generated images
        $css_parts[] = $this->get_base_css();
        
        // Template-specific styles
        $css_parts[] = $this->get_template_css();
        
        // Aspect ratio styles
        $css_parts[] = $this->get_aspect_ratio_css();
        
        // Responsive styles
        $css_parts[] = $this->get_responsive_css();
        
        // Category-specific styles
        if ($this->settings['enable_category_colors']) {
            $css_parts[] = $this->get_category_css();
        }
        
        // Custom CSS
        if (!empty($this->settings['custom_css'])) {
            $css_parts[] = $this->settings['custom_css'];
        }
        
        return implode("\n\n", array_filter($css_parts));
    }
    
    /**
     * Get base CSS
     *
     * @return string
     */
    private function get_base_css() {
        return '
/* Auto Featured Image - Base Styles */
.wp-afi-generated-image {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.wp-afi-generated-image:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.wp-afi-content {
    position: relative;
    z-index: 2;
    padding: 2rem;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.wp-afi-title {
    font-family: ' . esc_attr($this->settings['font_family']) . ';
    font-weight: ' . esc_attr($this->settings['font_weight']) . ';
    color: ' . esc_attr($this->settings['text_color']) . ';
    line-height: 1.2;
    letter-spacing: -0.025em;
    word-wrap: break-word;
    overflow-wrap: break-word;
    hyphens: auto;
    text-rendering: optimizeLegibility;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Text alignment */
.wp-afi-align-left .wp-afi-content {
    justify-content: flex-start;
    text-align: left;
}

.wp-afi-align-center .wp-afi-content {
    justify-content: center;
    text-align: center;
}

.wp-afi-align-right .wp-afi-content {
    justify-content: flex-end;
    text-align: right;
}

/* Text position */
.wp-afi-position-top .wp-afi-content {
    align-items: flex-start;
    padding-top: 3rem;
}

.wp-afi-position-center .wp-afi-content {
    align-items: center;
}

.wp-afi-position-bottom .wp-afi-content {
    align-items: flex-end;
    padding-bottom: 3rem;
}';
    }
    
    /**
     * Get template CSS
     *
     * @return string
     */
    private function get_template_css() {
        $template = $this->settings['template_style'];
        
        switch ($template) {
            case 'modern':
                return '
/* Modern Template */
.wp-afi-template-modern {
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
}

.wp-afi-template-modern::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(0,0,0,0.1) 100%);
    pointer-events: none;
    z-index: 1;
}

.wp-afi-template-modern .wp-afi-title {
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}';
                
            case 'classic':
                return '
/* Classic Template */
.wp-afi-template-classic {
    border: 3px solid rgba(255,255,255,0.2);
}

.wp-afi-template-classic .wp-afi-content {
    padding: 3rem;
}

.wp-afi-template-classic .wp-afi-title {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    font-style: italic;
}';
                
            case 'minimal':
                return '
/* Minimal Template */
.wp-afi-template-minimal {
    border-radius: 0;
    box-shadow: none;
}

.wp-afi-template-minimal:hover {
    transform: none;
    box-shadow: none;
}

.wp-afi-template-minimal .wp-afi-content {
    padding: 4rem 2rem;
}

.wp-afi-template-minimal .wp-afi-title {
    font-weight: 300;
    letter-spacing: 0.05em;
}';
                
            case 'bold':
                return '
/* Bold Template */
.wp-afi-template-bold {
    transform: skew(-2deg);
    border-radius: 16px;
}

.wp-afi-template-bold .wp-afi-content {
    transform: skew(2deg);
    padding: 2rem;
}

.wp-afi-template-bold .wp-afi-title {
    text-transform: uppercase;
    letter-spacing: 0.1em;
    text-shadow: 3px 3px 0px rgba(0,0,0,0.2);
}';
                
            case 'elegant':
                return '
/* Elegant Template */
.wp-afi-template-elegant {
    border-radius: 24px;
    position: relative;
}

.wp-afi-template-elegant::after {
    content: "";
    position: absolute;
    top: 20px;
    left: 20px;
    right: 20px;
    bottom: 20px;
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 16px;
    pointer-events: none;
    z-index: 1;
}

.wp-afi-template-elegant .wp-afi-title {
    font-family: Georgia, serif;
    font-style: italic;
    text-shadow: 0 1px 3px rgba(0,0,0,0.2);
}';
                
            case 'creative':
                return '
/* Creative Template */
.wp-afi-template-creative {
    border-radius: 50px 8px 50px 8px;
    background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.2) 0%, transparent 50%);
}

.wp-afi-template-creative .wp-afi-title {
    transform: rotate(-2deg);
    text-shadow: 2px 2px 0px rgba(0,0,0,0.1), 4px 4px 0px rgba(0,0,0,0.05);
}';
                
            default:
                return '';
        }
    }
    
    /**
     * Get aspect ratio CSS
     *
     * @return string
     */
    private function get_aspect_ratio_css() {
        $ratios = array(
            '16:9' => '56.25%',  // 9/16 * 100
            '4:3' => '75%',      // 3/4 * 100
            '1:1' => '100%',     // 1/1 * 100
            '3:2' => '66.67%',   // 2/3 * 100
            '21:9' => '42.86%',  // 9/21 * 100
            '9:16' => '177.78%'  // 16/9 * 100
        );
        
        $css = '/* Aspect Ratio Styles */';
        
        foreach ($ratios as $ratio => $percentage) {
            $class = str_replace(':', '-', $ratio);
            $css .= "\n.wp-afi-aspect-{$class} {\n";
            $css .= "    aspect-ratio: {$ratio};\n";
            $css .= "}\n";
            
            // Fallback for browsers that don't support aspect-ratio
            $css .= "@supports not (aspect-ratio: {$ratio}) {\n";
            $css .= "    .wp-afi-aspect-{$class} {\n";
            $css .= "        position: relative;\n";
            $css .= "        width: 100%;\n";
            $css .= "        height: 0;\n";
            $css .= "        padding-bottom: {$percentage};\n";
            $css .= "    }\n";
            $css .= "    .wp-afi-aspect-{$class} .wp-afi-content {\n";
            $css .= "        position: absolute;\n";
            $css .= "        top: 0;\n";
            $css .= "        left: 0;\n";
            $css .= "        width: 100%;\n";
            $css .= "        height: 100%;\n";
            $css .= "    }\n";
            $css .= "}\n";
        }
        
        return $css;
    }
    
    /**
     * Get responsive CSS
     *
     * @return string
     */
    private function get_responsive_css() {
        return '
/* Responsive Styles */
@media (max-width: 768px) {
    .wp-afi-generated-image {
        border-radius: 6px;
    }
    
    .wp-afi-content {
        padding: 1.5rem;
    }
    
    .wp-afi-title {
        font-size: 1.5rem !important;
        line-height: 1.3;
    }
    
    .wp-afi-position-top .wp-afi-content {
        padding-top: 2rem;
    }
    
    .wp-afi-position-bottom .wp-afi-content {
        padding-bottom: 2rem;
    }
}

@media (max-width: 480px) {
    .wp-afi-content {
        padding: 1rem;
    }
    
    .wp-afi-title {
        font-size: 1.25rem !important;
        line-height: 1.4;
    }
    
    .wp-afi-template-bold .wp-afi-title {
        letter-spacing: 0.05em;
    }
}

@media (min-width: 1200px) {
    .wp-afi-title {
        font-size: ' . (floatval($this->settings['font_size']) * 1.2) . 'rem !important;
    }
}';
    }
    
    /**
     * Get category CSS
     *
     * @return string
     */
    private function get_category_css() {
        if (empty($this->settings['category_colors'])) {
            return '';
        }
        
        $css = '/* Category-specific Styles */';
        
        foreach ($this->settings['category_colors'] as $term_id => $color) {
            $category = get_term($term_id);
            if ($category && !is_wp_error($category)) {
                $css .= "\n.wp-afi-category-{$category->slug} {\n";
                $css .= "    background-color: {$color} !important;\n";
                $css .= "}\n";
            }
        }
        
        return $css;
    }
    
    /**
     * Output dynamic JavaScript
     */
    public function output_dynamic_js() {
        if (!$this->settings['enabled']) {
            return;
        }
        
        ?>
        <script id="wp-afi-dynamic-js">
        (function() {
            // Adjust font size based on title length
            function adjustFontSize() {
                const images = document.querySelectorAll('.wp-afi-generated-image');
                
                images.forEach(function(image) {
                    const title = image.querySelector('.wp-afi-title');
                    if (!title) return;
                    
                    const titleLength = title.textContent.length;
                    const baseSize = parseFloat(getComputedStyle(title).fontSize);
                    let adjustedSize = baseSize;
                    
                    // Adjust font size based on title length
                    if (titleLength > 60) {
                        adjustedSize = baseSize * 0.8;
                    } else if (titleLength > 40) {
                        adjustedSize = baseSize * 0.9;
                    } else if (titleLength < 20) {
                        adjustedSize = baseSize * 1.1;
                    }
                    
                    title.style.fontSize = adjustedSize + 'px';
                });
            }
            
            // Run on DOM ready and resize
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', adjustFontSize);
            } else {
                adjustFontSize();
            }
            
            window.addEventListener('resize', adjustFontSize);
            
            // Add intersection observer for animations
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }
                    });
                }, {
                    threshold: 0.1
                });
                
                document.querySelectorAll('.wp-afi-generated-image').forEach(function(image) {
                    image.style.opacity = '0';
                    image.style.transform = 'translateY(20px)';
                    image.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
                    observer.observe(image);
                });
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Get image dimensions for specific size
     *
     * @param string $size
     * @return array
     */
    public function get_image_dimensions($size) {
        global $_wp_additional_image_sizes;
        
        if (is_array($size)) {
            return array(
                'width' => $size[0],
                'height' => $size[1]
            );
        }
        
        if (in_array($size, array('thumbnail', 'medium', 'medium_large', 'large'))) {
            return array(
                'width' => get_option($size . '_size_w'),
                'height' => get_option($size . '_size_h')
            );
        }
        
        if (isset($_wp_additional_image_sizes[$size])) {
            return array(
                'width' => $_wp_additional_image_sizes[$size]['width'],
                'height' => $_wp_additional_image_sizes[$size]['height']
            );
        }
        
        return array(
            'width' => 300,
            'height' => 200
        );
    }
}
