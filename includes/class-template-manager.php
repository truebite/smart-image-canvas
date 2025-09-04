<?php
/**
 * Template Manager Class
 * Handles template styles, sizing, and HTML generation for featured images
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SIC_Template_Manager class
 * Responsible for generating HTML templates and managing visual styles
 */
class SIC_Template_Manager {
    
    /**
     * Instance
     *
     * @var SIC_Template_Manager
     */
    private static $instance = null;
    
    /**
     * Get instance
     *
     * @return SIC_Template_Manager
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
        // Template manager doesn't need hooks, it's a pure service class
    }
    
    /**
     * Generate featured image HTML
     *
     * @param WP_Post $post Post object
     * @param string|array $size Image size
     * @param string|array $attr Image attributes
     * @param array $settings Plugin settings
     * @return string Generated HTML
     */
    public function generate_html(WP_Post $post, $size, $attr, array $settings) {
        $title = $this->get_post_title($post);
        $size_info = $this->get_size_info($size, $settings);
        $category_data = $this->get_category_data($post, $settings);
        $attributes = $this->prepare_attributes($attr, $title);
        
        $css_classes = $this->build_css_classes($settings, $category_data['class'], $attributes['class']);
        $inline_styles = $this->build_inline_styles($settings, $size_info, $category_data['style'], $attributes['style']);
        
        $image_id = $this->generate_image_id($post, $title, $settings);
        
        $html = $this->build_html_structure($image_id, $css_classes, $inline_styles, $title);
        
        return apply_filters('SIC_generated_image_html', $html, $post, $size, $attr);
    }
    
    /**
     * Get sanitized post title
     *
     * @param WP_Post $post Post object
     * @return string Post title
     */
    private function get_post_title(WP_Post $post) {
        $title = get_the_title($post);
        return !empty($title) ? $title : __('Untitled', 'smart-image-canvas');
    }
    
    /**
     * Get category-related data for styling
     *
     * @param WP_Post $post Post object
     * @param array $settings Plugin settings
     * @return array Category class and style
     */
    private function get_category_data(WP_Post $post, array $settings) {
        $category_class = '';
        $category_style = '';
        
        if (!empty($settings['enable_category_colors'])) {
            $categories = wp_get_post_categories($post->ID, array('number' => 1, 'fields' => 'all'));
            
            if (!empty($categories)) {
                $primary_category = $categories[0];
                $category_class = 'wp-afi-category-' . $primary_category->slug;
                
                if (isset($settings['category_colors'][$primary_category->term_id])) {
                    $category_color = $settings['category_colors'][$primary_category->term_id];
                    $category_style = "background-color: {$category_color};";
                }
            }
        }
        
        return array(
            'class' => $category_class,
            'style' => $category_style
        );
    }
    
    /**
     * Prepare HTML attributes
     *
     * @param string|array $attr Raw attributes
     * @param string $title Post title for alt text
     * @return array Prepared attributes
     */
    private function prepare_attributes($attr, $title) {
        return wp_parse_args($attr, array(
            'class' => '',
            'style' => '',
            'alt' => $title
        ));
    }
    
    /**
     * Build CSS classes array
     *
     * @param array $settings Plugin settings
     * @param string $category_class Category-specific class
     * @param string $additional_class Additional classes from attributes
     * @return array CSS classes
     */
    private function build_css_classes(array $settings, $category_class, $additional_class) {
        $css_classes = array(
            'wp-afi-generated-image',
            'wp-afi-template-' . $settings['template_style'],
            'wp-afi-aspect-' . str_replace(':', '-', $settings['aspect_ratio']),
            'wp-afi-align-' . $settings['text_align'],
            'wp-afi-position-' . $settings['text_position']
        );
        
        if (!empty($category_class)) {
            $css_classes[] = $category_class;
        }
        
        if (!empty($additional_class)) {
            $css_classes[] = $additional_class;
        }
        
        return $css_classes;
    }
    
    /**
     * Build inline styles array
     *
     * @param array $settings Plugin settings
     * @param array $size_info Size information
     * @param string $category_style Category-specific styles
     * @param string $additional_style Additional styles from attributes
     * @return array Inline styles
     */
    private function build_inline_styles(array $settings, array $size_info, $category_style, $additional_style) {
        $inline_styles = array();
        
        // Background
        if (!empty($settings['background_gradient'])) {
            $inline_styles[] = "background: {$settings['background_gradient']}";
        } else {
            $inline_styles[] = "background-color: {$settings['background_color']}";
        }
        
        // Typography
        $inline_styles[] = "color: {$settings['text_color']}";
        $inline_styles[] = "font-family: {$settings['font_family']}";
        $inline_styles[] = "font-weight: {$settings['font_weight']}";
        
        // Responsive font size
        $font_size = $this->calculate_responsive_font_size($settings['font_size'], $size_info);
        $inline_styles[] = "font-size: {$font_size}";
        
        // Dimensions
        if (!empty($size_info['width'])) {
            $inline_styles[] = "width: {$size_info['width']}px";
        }
        if (!empty($size_info['height'])) {
            $inline_styles[] = "height: {$size_info['height']}px";
        }
        
        // Category override
        if (!empty($category_style)) {
            $inline_styles[] = $category_style;
        }
        
        // Additional styles
        if (!empty($additional_style)) {
            $inline_styles[] = $additional_style;
        }
        
        return $inline_styles;
    }
    
    /**
     * Generate unique image ID
     *
     * @param WP_Post $post Post object
     * @param string $title Post title
     * @param array $settings Plugin settings
     * @return string Unique image ID
     */
    private function generate_image_id(WP_Post $post, $title, array $settings) {
        return 'wp-afi-' . $post->ID . '-' . md5($title . serialize($settings));
    }
    
    /**
     * Build the final HTML structure
     *
     * @param string $image_id Unique image ID
     * @param array $css_classes CSS classes
     * @param array $inline_styles Inline styles
     * @param string $title Post title
     * @return string HTML structure
     */
    private function build_html_structure($image_id, array $css_classes, array $inline_styles, $title) {
        return sprintf(
            '<div id="%s" class="%s" style="%s" role="img" aria-label="%s">
                <div class="wp-afi-content">
                    <div class="wp-afi-title">%s</div>
                </div>
            </div>',
            esc_attr($image_id),
            esc_attr(implode(' ', $css_classes)),
            esc_attr(implode('; ', $inline_styles)),
            esc_attr($title),
            esc_html($title)
        );
    }
    
    /**
     * Get size information for given size parameter
     *
     * @param string|array $size Image size
     * @param array $settings Plugin settings
     * @return array Size information with width and height
     */
    public function get_size_info($size, array $settings = array()) {
        // If size is already an array with width/height, use it
        if (is_array($size)) {
            return array(
                'width' => $size[0],
                'height' => $size[1]
            );
        }
        
        // Get base dimensions from WordPress size
        global $_wp_additional_image_sizes;
        $base_width = 300; // Default width
        $base_height = 200; // Default height
        
        // Handle built-in WordPress sizes
        if (in_array($size, array('thumbnail', 'medium', 'medium_large', 'large'))) {
            $base_width = get_option($size . '_size_w', $base_width);
            $base_height = get_option($size . '_size_h', $base_height);
        }
        // Handle additional theme/plugin sizes
        elseif (isset($_wp_additional_image_sizes[$size])) {
            $base_width = $_wp_additional_image_sizes[$size]['width'];
            $base_height = $_wp_additional_image_sizes[$size]['height'];
        }
        
        // Apply aspect ratio from settings if available
        if (!empty($settings['aspect_ratio'])) {
            $aspect_ratio = $settings['aspect_ratio'];
            
            // Handle custom aspect ratio
            if ($aspect_ratio === 'custom' && !empty($settings['custom_aspect_ratio'])) {
                $aspect_ratio = $settings['custom_aspect_ratio'];
            }
            
            $ratio_parts = explode(':', $aspect_ratio);
            
            if (count($ratio_parts) === 2) {
                $ratio_width = floatval($ratio_parts[0]);
                $ratio_height = floatval($ratio_parts[1]);
                
                if ($ratio_width > 0 && $ratio_height > 0) {
                    // Calculate height based on width and aspect ratio
                    $calculated_height = intval($base_width * ($ratio_height / $ratio_width));
                    return array(
                        'width' => $base_width,
                        'height' => $calculated_height
                    );
                }
            }
        }
        
        // Fallback to original dimensions
        return array(
            'width' => $base_width,
            'height' => $base_height
        );
    }
    
    /**
     * Calculate responsive font size based on container dimensions
     *
     * @param string $base_size Base font size
     * @param array $size_info Container size information
     * @return string Calculated font size with unit
     */
    private function calculate_responsive_font_size($base_size, array $size_info) {
        $base_font_size = floatval($base_size);
        
        if (!empty($size_info['width'])) {
            $width = intval($size_info['width']);
            
            // Scale font size based on container width
            if ($width < 300) {
                $multiplier = 0.6;
            } elseif ($width < 600) {
                $multiplier = 0.8;
            } elseif ($width > 1200) {
                $multiplier = 1.4;
            } else {
                $multiplier = 1.0;
            }
            
            $calculated_size = $base_font_size * $multiplier;
        } else {
            $calculated_size = $base_font_size;
        }
        
        return $calculated_size . 'rem';
    }
    
    /**
     * Get available template styles
     *
     * @return array Template styles
     */
    public static function get_template_styles() {
        return apply_filters('SIC_template_styles', array(
            'modern' => __('Modern', 'smart-image-canvas'),
            'classic' => __('Classic', 'smart-image-canvas'),
            'minimal' => __('Minimal', 'smart-image-canvas'),
            'bold' => __('Bold', 'smart-image-canvas'),
            'elegant' => __('Elegant', 'smart-image-canvas'),
            'creative' => __('Creative', 'smart-image-canvas')
        ));
    }
    
    /**
     * Get available aspect ratios
     *
     * @return array Aspect ratios
     */
    public static function get_aspect_ratios() {
        return apply_filters('SIC_aspect_ratios', array(
            '16:9' => '16:9 (Widescreen)',
            '4:3' => '4:3 (Standard)',
            '3:2' => '3:2 (Classic)',
            '1:1' => '1:1 (Square)',
            '3:4' => '3:4 (Portrait)',
            '9:16' => '9:16 (Vertical)',
            '21:9' => '21:9 (Ultra-wide)',
            '5:4' => '5:4 (Traditional)',
            '8:5' => '8:5 (Golden Ratio)',
            '2:1' => '2:1 (Panoramic)',
            'custom' => 'Custom (Enter your own ratio)'
        ));
    }
    
    /**
     * Get available font families
     *
     * @return array Font families
     */
    public static function get_font_families() {
        return apply_filters('SIC_font_families', array(
            'Inter, system-ui, sans-serif' => 'Inter',
            'system-ui, sans-serif' => 'System UI',
            '"Helvetica Neue", Helvetica, Arial, sans-serif' => 'Helvetica',
            'Georgia, "Times New Roman", serif' => 'Georgia',
            '"Times New Roman", Times, serif' => 'Times New Roman',
            'Monaco, Consolas, "Courier New", monospace' => 'Monaco',
            '"Roboto", sans-serif' => 'Roboto',
            '"Open Sans", sans-serif' => 'Open Sans',
            '"Lato", sans-serif' => 'Lato',
            '"Montserrat", sans-serif' => 'Montserrat',
            '"Poppins", sans-serif' => 'Poppins'
        ));
    }
}
