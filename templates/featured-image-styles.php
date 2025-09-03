<?php
/**
 * Featured Image Style Templates
 *
 * @package WP_Auto_Featured_Image
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get template-specific CSS rules
 *
 * @param string $template Template name
 * @return string CSS rules
 */
function wp_afi_get_template_css($template) {
    $css = '';
    
    switch ($template) {
        case 'modern':
            $css = '
            .wp-afi-template-modern {
                background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
                border-radius: 12px;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
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
                border-radius: inherit;
            }
            
            .wp-afi-template-modern .wp-afi-title {
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                letter-spacing: -0.02em;
            }';
            break;
            
        case 'classic':
            $css = '
            .wp-afi-template-classic {
                border: 3px solid rgba(255,255,255,0.3);
                border-radius: 8px;
                position: relative;
            }
            
            .wp-afi-template-classic::after {
                content: "";
                position: absolute;
                top: 15px;
                left: 15px;
                right: 15px;
                bottom: 15px;
                border: 1px solid rgba(255,255,255,0.2);
                border-radius: 4px;
                pointer-events: none;
                z-index: 1;
            }
            
            .wp-afi-template-classic .wp-afi-content {
                padding: 3rem;
            }
            
            .wp-afi-template-classic .wp-afi-title {
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
                font-style: italic;
                position: relative;
                z-index: 2;
            }';
            break;
            
        case 'minimal':
            $css = '
            .wp-afi-template-minimal {
                border-radius: 0;
                box-shadow: none;
                background: none !important;
                border: 1px solid currentColor;
            }
            
            .wp-afi-template-minimal:hover {
                transform: none;
                box-shadow: none;
            }
            
            .wp-afi-template-minimal .wp-afi-content {
                padding: 4rem 2rem;
                background: rgba(0,0,0,0.8);
                margin: 1rem;
            }
            
            .wp-afi-template-minimal .wp-afi-title {
                font-weight: 300;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                font-size: 0.9em;
            }';
            break;
            
        case 'bold':
            $css = '
            .wp-afi-template-bold {
                transform: skew(-2deg);
                border-radius: 16px;
                position: relative;
                overflow: hidden;
            }
            
            .wp-afi-template-bold::before {
                content: "";
                position: absolute;
                top: -5px;
                left: -5px;
                right: -5px;
                bottom: -5px;
                background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent, rgba(255,255,255,0.1));
                z-index: 1;
                animation: wp-afi-shimmer 3s infinite;
            }
            
            .wp-afi-template-bold .wp-afi-content {
                transform: skew(2deg);
                padding: 2rem;
                position: relative;
                z-index: 2;
            }
            
            .wp-afi-template-bold .wp-afi-title {
                text-transform: uppercase;
                letter-spacing: 0.1em;
                text-shadow: 3px 3px 0px rgba(0,0,0,0.2);
                font-weight: 800;
            }
            
            @keyframes wp-afi-shimmer {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(100%); }
            }';
            break;
            
        case 'elegant':
            $css = '
            .wp-afi-template-elegant {
                border-radius: 24px;
                position: relative;
                background: radial-gradient(circle at center, rgba(255,255,255,0.1) 0%, transparent 70%);
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
            
            .wp-afi-template-elegant .wp-afi-content {
                position: relative;
                z-index: 2;
            }
            
            .wp-afi-template-elegant .wp-afi-title {
                font-family: Georgia, serif;
                font-style: italic;
                text-shadow: 0 1px 3px rgba(0,0,0,0.2);
                letter-spacing: 0.02em;
            }';
            break;
            
        case 'creative':
            $css = '
            .wp-afi-template-creative {
                border-radius: 50px 8px 50px 8px;
                background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.2) 0%, transparent 50%);
                position: relative;
                overflow: hidden;
            }
            
            .wp-afi-template-creative::before {
                content: "";
                position: absolute;
                top: 10%;
                left: 10%;
                width: 20px;
                height: 20px;
                background: rgba(255,255,255,0.3);
                border-radius: 50%;
                z-index: 1;
            }
            
            .wp-afi-template-creative::after {
                content: "";
                position: absolute;
                bottom: 20%;
                right: 15%;
                width: 15px;
                height: 15px;
                background: rgba(255,255,255,0.2);
                border-radius: 50%;
                z-index: 1;
            }
            
            .wp-afi-template-creative .wp-afi-content {
                position: relative;
                z-index: 2;
            }
            
            .wp-afi-template-creative .wp-afi-title {
                transform: rotate(-2deg);
                text-shadow: 2px 2px 0px rgba(0,0,0,0.1), 4px 4px 0px rgba(0,0,0,0.05);
                display: inline-block;
            }';
            break;
    }
    
    return $css;
}

/**
 * Get available template configurations
 *
 * @return array Template configurations
 */
function wp_afi_get_template_configs() {
    return array(
        'modern' => array(
            'name' => __('Modern', 'wp-auto-featured-image'),
            'description' => __('Clean, contemporary design with subtle gradients', 'wp-auto-featured-image'),
            'features' => array('gradient-overlay', 'blur-effect', 'subtle-shadow'),
            'best_for' => array('tech', 'business', 'portfolio')
        ),
        'classic' => array(
            'name' => __('Classic', 'wp-auto-featured-image'),
            'description' => __('Traditional design with elegant borders', 'wp-auto-featured-image'),
            'features' => array('double-border', 'italic-text', 'formal-styling'),
            'best_for' => array('literature', 'formal', 'traditional')
        ),
        'minimal' => array(
            'name' => __('Minimal', 'wp-auto-featured-image'),
            'description' => __('Simple, clean design with minimal styling', 'wp-auto-featured-image'),
            'features' => array('clean-lines', 'typography-focus', 'simple-border'),
            'best_for' => array('design', 'photography', 'art')
        ),
        'bold' => array(
            'name' => __('Bold', 'wp-auto-featured-image'),
            'description' => __('Eye-catching design with dynamic effects', 'wp-auto-featured-image'),
            'features' => array('skewed-design', 'uppercase-text', 'animation'),
            'best_for' => array('sports', 'news', 'entertainment')
        ),
        'elegant' => array(
            'name' => __('Elegant', 'wp-auto-featured-image'),
            'description' => __('Sophisticated design with serif typography', 'wp-auto-featured-image'),
            'features' => array('serif-font', 'rounded-corners', 'subtle-glow'),
            'best_for' => array('luxury', 'fashion', 'lifestyle')
        ),
        'creative' => array(
            'name' => __('Creative', 'wp-auto-featured-image'),
            'description' => __('Artistic design with unique shapes and effects', 'wp-auto-featured-image'),
            'features' => array('unique-borders', 'decorative-elements', 'rotation'),
            'best_for' => array('creative', 'artistic', 'personal')
        )
    );
}

/**
 * Get template preview HTML
 *
 * @param string $template Template name
 * @param string $title Sample title
 * @return string HTML preview
 */
function wp_afi_get_template_preview($template, $title = 'Sample Title') {
    $config = wp_afi_get_template_configs()[$template] ?? array();
    $name = $config['name'] ?? ucfirst($template);
    
    return sprintf(
        '<div class="wp-afi-generated-image wp-afi-template-%s wp-afi-aspect-16-9 wp-afi-align-center wp-afi-position-center" style="background-color: #2563eb; width: 200px;">
            <div class="wp-afi-content">
                <div class="wp-afi-title" style="color: #ffffff; font-family: Inter, sans-serif; font-weight: 600; font-size: 1rem;">%s</div>
            </div>
        </div>',
        esc_attr($template),
        esc_html($title)
    );
}

/**
 * Render template selector
 *
 * @param string $current_template Currently selected template
 * @return string HTML for template selector
 */
function wp_afi_render_template_selector($current_template = 'modern') {
    $templates = wp_afi_get_template_configs();
    $output = '<div class="wp-afi-template-selector">';
    
    foreach ($templates as $template_key => $template_config) {
        $is_selected = ($template_key === $current_template) ? 'selected' : '';
        
        $output .= sprintf(
            '<div class="wp-afi-template-option %s" data-template="%s">
                <div class="wp-afi-template-preview-wrapper">
                    %s
                </div>
                <div class="wp-afi-template-info">
                    <h4>%s</h4>
                    <p>%s</p>
                    <div class="wp-afi-template-features">
                        %s
                    </div>
                </div>
            </div>',
            $is_selected,
            esc_attr($template_key),
            wp_afi_get_template_preview($template_key, $template_config['name']),
            esc_html($template_config['name']),
            esc_html($template_config['description']),
            implode(', ', array_map('esc_html', $template_config['features']))
        );
    }
    
    $output .= '</div>';
    
    return $output;
}
