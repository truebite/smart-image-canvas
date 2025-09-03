<?php
/**
 * Debug and Troubleshooting Utilities
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SIC_Debug class
 */
class SIC_Debug {
    
    /**
     * Get debug information
     *
     * @return array
     */
    public static function get_debug_info() {
        $theme_compat = SIC_Theme_Compatibility::get_instance();
        $current_theme = wp_get_theme();
        
        $debug_info = array(
            'plugin_version' => SIC_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'theme_info' => array(
                'name' => $current_theme->get('Name'),
                'version' => $current_theme->get('Version'),
                'template' => get_template(),
                'stylesheet' => get_stylesheet(),
                'is_child_theme' => is_child_theme()
            ),
            'compatibility_status' => $theme_compat->get_compatibility_status(),
            'active_plugins' => self::get_relevant_plugins(),
            'hooks_status' => self::check_hooks_status(),
            'settings' => get_option('SIC_settings', array()),
            'server_info' => array(
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            )
        );
        
        return $debug_info;
    }
    
    /**
     * Get relevant active plugins
     *
     * @return array
     */
    private static function get_relevant_plugins() {
        $active_plugins = get_option('active_plugins', array());
        $relevant_plugins = array();
        
        $relevant_keywords = array(
            'elementor', 'divi', 'beaver', 'visual-composer', 'wpbakery',
            'fusion', 'cornerstone', 'gutenberg', 'featured', 'image',
            'thumbnail', 'gallery', 'media', 'seo', 'yoast', 'rankmath'
        );
        
        foreach ($active_plugins as $plugin) {
            foreach ($relevant_keywords as $keyword) {
                if (strpos(strtolower($plugin), $keyword) !== false) {
                    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                    $relevant_plugins[] = array(
                        'file' => $plugin,
                        'name' => $plugin_data['Name'],
                        'version' => $plugin_data['Version']
                    );
                    break;
                }
            }
        }
        
        return $relevant_plugins;
    }
    
    /**
     * Check hooks status
     *
     * @return array
     */
    private static function check_hooks_status() {
        global $wp_filter;
        
        $important_hooks = array(
            'post_thumbnail_html',
            'get_post_metadata',
            'has_post_thumbnail',
            'wp_get_attachment_image',
            'the_post_thumbnail'
        );
        
        $hooks_status = array();
        
        foreach ($important_hooks as $hook) {
            $hooks_status[$hook] = array(
                'exists' => isset($wp_filter[$hook]),
                'callbacks' => isset($wp_filter[$hook]) ? count($wp_filter[$hook]->callbacks) : 0
            );
            
            if (isset($wp_filter[$hook])) {
                $hooks_status[$hook]['priorities'] = array_keys($wp_filter[$hook]->callbacks);
            }
        }
        
        return $hooks_status;
    }
    
    /**
     * Test featured image generation
     *
     * @param int $post_id
     * @return array
     */
    public static function test_generation($post_id = null) {
        if (!$post_id) {
            // Get the latest post
            $posts = get_posts(array('numberposts' => 1));
            if (empty($posts)) {
                return array('error' => 'No posts found to test');
            }
            $post_id = $posts[0]->ID;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return array('error' => 'Post not found');
        }
        
        $generator = SIC_Image_Generator::get_instance();
        
        $test_results = array(
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'has_thumbnail' => has_post_thumbnail($post_id),
            'generation_test' => array()
        );
        
        // Test different sizes
        $sizes = array('thumbnail', 'medium', 'large', 'full');
        
        foreach ($sizes as $size) {
            $start_time = microtime(true);
            $html = $generator->generate_featured_image_html($post, $size, array());
            $end_time = microtime(true);
            
            $test_results['generation_test'][$size] = array(
                'success' => !empty($html),
                'html_length' => strlen($html),
                'generation_time' => round(($end_time - $start_time) * 1000, 2) . 'ms',
                'contains_class' => strpos($html, 'wp-afi-generated-image') !== false
            );
        }
        
        return $test_results;
    }
    
    /**
     * Run compatibility checks
     *
     * @return array
     */
    public static function run_compatibility_checks() {
        $checks = array();
        
        // Check 1: WordPress functions availability
        $wp_functions = array(
            'has_post_thumbnail',
            'get_the_post_thumbnail',
            'wp_get_attachment_image',
            'add_theme_support'
        );
        
        $checks['wordpress_functions'] = array();
        foreach ($wp_functions as $function) {
            $checks['wordpress_functions'][$function] = function_exists($function);
        }
        
        // Check 2: Theme support
        $theme_supports = array(
            'post-thumbnails',
            'html5',
            'custom-logo',
            'customize-selective-refresh-widgets'
        );
        
        $checks['theme_support'] = array();
        foreach ($theme_supports as $feature) {
            $checks['theme_support'][$feature] = current_theme_supports($feature);
        }
        
        // Check 3: Image sizes
        $checks['image_sizes'] = wp_get_additional_image_sizes();
        $checks['intermediate_image_sizes'] = get_intermediate_image_sizes();
        
        // Check 4: Plugin conflicts
        $checks['potential_conflicts'] = self::check_potential_conflicts();
        
        // Check 5: CSS/JS loading
        $checks['assets_loaded'] = self::check_assets_loaded();
        
        return $checks;
    }
    
    /**
     * Check for potential plugin conflicts
     *
     * @return array
     */
    private static function check_potential_conflicts() {
        $conflicts = array();
        
        // Check for SEO plugins that might modify images
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            $conflicts[] = 'Yoast SEO - May modify image output';
        }
        
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            $conflicts[] = 'Rank Math SEO - May modify image output';
        }
        
        // Check for caching plugins
        if (is_plugin_active('wp-rocket/wp-rocket.php')) {
            $conflicts[] = 'WP Rocket - May cache generated images';
        }
        
        if (is_plugin_active('w3-total-cache/w3-total-cache.php')) {
            $conflicts[] = 'W3 Total Cache - May cache generated images';
        }
        
        // Check for image optimization plugins
        if (is_plugin_active('wp-smushit/wp-smush.php')) {
            $conflicts[] = 'Smush - May try to optimize CSS images';
        }
        
        if (is_plugin_active('shortpixel-image-optimiser/wp-shortpixel.php')) {
            $conflicts[] = 'ShortPixel - May try to optimize images';
        }
        
        return $conflicts;
    }
    
    /**
     * Check if assets are loaded
     *
     * @return array
     */
    private static function check_assets_loaded() {
        global $wp_styles, $wp_scripts;
        
        $assets = array(
            'css' => array(
                'wp-afi-frontend' => isset($wp_styles->registered['wp-afi-frontend']),
                'wp-afi-admin' => isset($wp_styles->registered['wp-afi-admin'])
            ),
            'js' => array(
                'wp-afi-frontend' => isset($wp_scripts->registered['wp-afi-frontend']),
                'wp-afi-admin' => isset($wp_scripts->registered['wp-afi-admin']),
                'wp-afi-customizer' => isset($wp_scripts->registered['wp-afi-customizer'])
            )
        );
        
        return $assets;
    }
    
    /**
     * Generate debug report
     *
     * @return string
     */
    public static function generate_debug_report() {
        $debug_info = self::get_debug_info();
        $compatibility_checks = self::run_compatibility_checks();
        
        $report = "WP Auto Featured Image - Debug Report\n";
        $report .= "Generated: " . current_time('mysql') . "\n";
        $report .= str_repeat('=', 50) . "\n\n";
        
        // Plugin Info
        $report .= "PLUGIN INFORMATION:\n";
        $report .= "Plugin Version: " . $debug_info['plugin_version'] . "\n";
        $report .= "WordPress Version: " . $debug_info['wordpress_version'] . "\n";
        $report .= "PHP Version: " . $debug_info['php_version'] . "\n\n";
        
        // Theme Info
        $report .= "THEME INFORMATION:\n";
        $report .= "Theme Name: " . $debug_info['theme_info']['name'] . "\n";
        $report .= "Theme Version: " . $debug_info['theme_info']['version'] . "\n";
        $report .= "Template: " . $debug_info['theme_info']['template'] . "\n";
        $report .= "Is Child Theme: " . ($debug_info['theme_info']['is_child_theme'] ? 'Yes' : 'No') . "\n";
        $report .= "Compatibility Level: " . $debug_info['compatibility_status']['compatibility_level'] . "\n";
        $report .= "Is Problematic: " . ($debug_info['compatibility_status']['is_problematic'] ? 'Yes' : 'No') . "\n";
        $report .= "Uses Page Builder: " . ($debug_info['compatibility_status']['is_page_builder'] ? 'Yes' : 'No') . "\n\n";
        
        // Plugin Settings
        $report .= "PLUGIN SETTINGS:\n";
        foreach ($debug_info['settings'] as $key => $value) {
            $report .= ucfirst(str_replace('_', ' ', $key)) . ": " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
        $report .= "\n";
        
        // Relevant Plugins
        $report .= "RELEVANT PLUGINS:\n";
        if (empty($debug_info['active_plugins'])) {
            $report .= "No relevant plugins detected.\n";
        } else {
            foreach ($debug_info['active_plugins'] as $plugin) {
                $report .= "- " . $plugin['name'] . " (" . $plugin['version'] . ")\n";
            }
        }
        $report .= "\n";
        
        // Hooks Status
        $report .= "WORDPRESS HOOKS STATUS:\n";
        foreach ($debug_info['hooks_status'] as $hook => $status) {
            $report .= "- " . $hook . ": " . ($status['exists'] ? 'Exists' : 'Missing') . " (" . $status['callbacks'] . " callbacks)\n";
        }
        $report .= "\n";
        
        // Potential Conflicts
        $report .= "POTENTIAL CONFLICTS:\n";
        if (empty($compatibility_checks['potential_conflicts'])) {
            $report .= "No potential conflicts detected.\n";
        } else {
            foreach ($compatibility_checks['potential_conflicts'] as $conflict) {
                $report .= "- " . $conflict . "\n";
            }
        }
        
        return $report;
    }
}
