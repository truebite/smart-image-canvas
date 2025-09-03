<?php
/**
 * Customizer Integration Class
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SIC_Customizer class
 */
class SIC_Customizer {
    
    /**
     * Instance
     *
     * @var SIC_Customizer
     */
    private static $instance = null;
    
    /**
     * Get instance
     *
     * @return SIC_Customizer
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
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('customize_register', array($this, 'register_customizer_settings'));
        add_action('customize_preview_init', array($this, 'enqueue_preview_scripts'));
        add_action('customize_controls_enqueue_scripts', array($this, 'enqueue_control_scripts'));
    }
    
    /**
     * Register customizer settings
     *
     * @param WP_Customize_Manager $wp_customize
     */
    public function register_customizer_settings($wp_customize) {
        // Add Auto Featured Image Panel
        $wp_customize->add_panel('SIC_panel', array(
            'title' => __('Auto Featured Image', 'smart-image-canvas'),
            'description' => __('Customize the appearance of auto-generated featured images', 'smart-image-canvas'),
            'priority' => 160
        ));
        
        // General Section
        $wp_customize->add_section('SIC_general', array(
            'title' => __('General Settings', 'smart-image-canvas'),
            'panel' => 'SIC_panel',
            'priority' => 10
        ));
        
        // Style Section
        $wp_customize->add_section('SIC_style', array(
            'title' => __('Style Settings', 'smart-image-canvas'),
            'panel' => 'SIC_panel',
            'priority' => 20
        ));
        
        // Typography Section
        $wp_customize->add_section('SIC_typography', array(
            'title' => __('Typography', 'smart-image-canvas'),
            'panel' => 'SIC_panel',
            'priority' => 30
        ));
        
        $this->add_general_controls($wp_customize);
        $this->add_style_controls($wp_customize);
        $this->add_typography_controls($wp_customize);
    }
    
    /**
     * Add general controls
     *
     * @param WP_Customize_Manager $wp_customize
     */
    private function add_general_controls($wp_customize) {
        // Enable Plugin
        $wp_customize->add_setting('SIC_settings[enabled]', array(
            'default' => true,
            'type' => 'option',
            'capability' => 'edit_theme_options',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'transport' => 'postMessage'
        ));
        
        $wp_customize->add_control('SIC_enabled', array(
            'label' => __('Enable Auto Featured Images', 'smart-image-canvas'),
            'description' => __('Generate featured images automatically when none are set', 'smart-image-canvas'),
            'section' => 'SIC_general',
            'settings' => 'SIC_settings[enabled]',
            'type' => 'checkbox'
        ));
        
        // Template Style
        $wp_customize->add_setting('SIC_settings[template_style]', array(
            'default' => 'modern',
            'type' => 'option',
            'capability' => 'edit_theme_options',
            'sanitize_callback' => array($this, 'sanitize_select'),
            'transport' => 'postMessage'
        ));
        
        $wp_customize->add_control('SIC_template_style', array(
            'label' => __('Template Style', 'smart-image-canvas'),
            'section' => 'SIC_general',
            'settings' => 'SIC_settings[template_style]',
            'type' => 'select',
            'choices' => SIC_Image_Generator::get_template_styles()
        ));
        
        // Aspect Ratio
        $wp_customize->add_setting('SIC_settings[aspect_ratio]', array(
            'default' => '16:9',
            'type' => 'option',
            'capability' => 'edit_theme_options',
            'sanitize_callback' => array($this, 'sanitize_select'),
            'transport' => 'postMessage'
        ));
        
        $wp_customize->add_control('SIC_aspect_ratio', array(
            'label' => __('Aspect Ratio', 'smart-image-canvas'),
            'section' => 'SIC_general',
            'settings' => 'SIC_settings[aspect_ratio]',
            'type' => 'select',
            'choices' => SIC_Image_Generator::get_aspect_ratios()
        ));
    }
    
    /**
     * Add style controls
     *
     * @param WP_Customize_Manager $wp_customize
     */
    private function add_style_controls($wp_customize) {
        // Background Color
        $wp_customize->add_setting('SIC_settings[background_color]', array(
            'default' => '#2563eb',
            'type' => 'option',
            'capability' => 'edit_theme_options',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage'
        ));
        
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'SIC_background_color', array(
            'label' => __('Background Color', 'smart-image-canvas'),
            'section' => 'SIC_style',
            'settings' => 'SIC_settings[background_color]'
        )));
        
        // Text Color
        $wp_customize->add_setting('SIC_settings[text_color]', array(
            'default' => '#ffffff',
            'type' => 'option',
            'capability' => 'edit_theme_options',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage'
        ));
        
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'SIC_text_color', array(
            'label' => __('Text Color', 'smart-image-canvas'),
            'section' => 'SIC_style',
            'settings' => 'SIC_settings[text_color]'
        )));
        
        // Background Gradient
        $wp_customize->add_setting('SIC_settings[background_gradient]', array(
            'default' => '',
            'type' => 'option',
            'capability' => 'edit_theme_options',
            'sanitize_callback' => 'sanitize_textarea_field',
            'transport' => 'postMessage'
        ));
        
        $wp_customize->add_control('SIC_background_gradient', array(
            'label' => __('Background Gradient (CSS)', 'smart-image-canvas'),
            'description' => __('Optional. Override background color with a CSS gradient.', 'smart-image-canvas'),
            'section' => 'SIC_style',
            'settings' => 'SIC_settings[background_gradient]',
            'type' => 'textarea'
        ));
    }
    
    /**
     * Add typography controls
     *
     * @param WP_Customize_Manager $wp_customize
     */
    private function add_typography_controls($wp_customize) {
        // Font Family
        $wp_customize->add_setting('SIC_settings[font_family]', array(
            'default' => 'Inter, system-ui, sans-serif',
            'type' => 'option',
            'capability' => 'edit_theme_options',
            'sanitize_callback' => array($this, 'sanitize_select'),
            'transport' => 'postMessage'
        ));
        
        $wp_customize->add_control('SIC_font_family', array(
            'label' => __('Font Family', 'smart-image-canvas'),
            'section' => 'SIC_typography',
            'settings' => 'SIC_settings[font_family]',
            'type' => 'select',
            'choices' => SIC_Image_Generator::get_font_families()
        ));
        
        // Font Size
        $wp_customize->add_setting('SIC_settings[font_size]', array(
            'default' => '2.5',
            'type' => 'option',
            'capability' => 'edit_theme_options',
            'sanitize_callback' => array($this, 'sanitize_number'),
            'transport' => 'postMessage'
        ));
        
        $wp_customize->add_control('SIC_font_size', array(
            'label' => __('Font Size (rem)', 'smart-image-canvas'),
            'section' => 'SIC_typography',
            'settings' => 'SIC_settings[font_size]',
            'type' => 'range',
            'input_attrs' => array(
                'min' => 1,
                'max' => 10,
                'step' => 0.1
            )
        ));
        
        // Font Weight
        $wp_customize->add_setting('SIC_settings[font_weight]', array(
            'default' => '600',
            'type' => 'option',
            'capability' => 'edit_theme_options',
            'sanitize_callback' => array($this, 'sanitize_select'),
            'transport' => 'postMessage'
        ));
        
        $wp_customize->add_control('SIC_font_weight', array(
            'label' => __('Font Weight', 'smart-image-canvas'),
            'section' => 'SIC_typography',
            'settings' => 'SIC_settings[font_weight]',
            'type' => 'select',
            'choices' => array(
                '300' => __('Light (300)', 'smart-image-canvas'),
                '400' => __('Normal (400)', 'smart-image-canvas'),
                '500' => __('Medium (500)', 'smart-image-canvas'),
                '600' => __('Semi Bold (600)', 'smart-image-canvas'),
                '700' => __('Bold (700)', 'smart-image-canvas'),
                '800' => __('Extra Bold (800)', 'smart-image-canvas'),
                '900' => __('Black (900)', 'smart-image-canvas')
            )
        ));
        
        // Text Alignment
        $wp_customize->add_setting('SIC_settings[text_align]', array(
            'default' => 'center',
            'type' => 'option',
            'capability' => 'edit_theme_options',
            'sanitize_callback' => array($this, 'sanitize_select'),
            'transport' => 'postMessage'
        ));
        
        $wp_customize->add_control('SIC_text_align', array(
            'label' => __('Text Alignment', 'smart-image-canvas'),
            'section' => 'SIC_typography',
            'settings' => 'SIC_settings[text_align]',
            'type' => 'radio',
            'choices' => array(
                'left' => __('Left', 'smart-image-canvas'),
                'center' => __('Center', 'smart-image-canvas'),
                'right' => __('Right', 'smart-image-canvas')
            )
        ));
        
        // Text Position
        $wp_customize->add_setting('SIC_settings[text_position]', array(
            'default' => 'center',
            'type' => 'option',
            'capability' => 'edit_theme_options',
            'sanitize_callback' => array($this, 'sanitize_select'),
            'transport' => 'postMessage'
        ));
        
        $wp_customize->add_control('SIC_text_position', array(
            'label' => __('Text Position', 'smart-image-canvas'),
            'section' => 'SIC_typography',
            'settings' => 'SIC_settings[text_position]',
            'type' => 'radio',
            'choices' => array(
                'top' => __('Top', 'smart-image-canvas'),
                'center' => __('Center', 'smart-image-canvas'),
                'bottom' => __('Bottom', 'smart-image-canvas')
            )
        ));
    }
    
    /**
     * Enqueue preview scripts
     */
    public function enqueue_preview_scripts() {
        wp_enqueue_script(
            'wp-afi-customizer-preview',
            SIC_PLUGIN_URL . 'assets/js/customizer-preview.js',
            array('jquery', 'customize-preview'),
            SIC_VERSION,
            true
        );
    }
    
    /**
     * Enqueue control scripts
     */
    public function enqueue_control_scripts() {
        wp_enqueue_script(
            'wp-afi-customizer-controls',
            SIC_PLUGIN_URL . 'assets/js/customizer-controls.js',
            array('jquery', 'customize-controls'),
            SIC_VERSION,
            true
        );
        
        wp_enqueue_style(
            'wp-afi-customizer-controls',
            SIC_PLUGIN_URL . 'assets/css/customizer-controls.css',
            array(),
            SIC_VERSION
        );
    }
    
    /**
     * Sanitize checkbox
     *
     * @param bool $checked
     * @return bool
     */
    public function sanitize_checkbox($checked) {
        return (bool) $checked;
    }
    
    /**
     * Sanitize select
     *
     * @param string $input
     * @param WP_Customize_Setting $setting
     * @return string
     */
    public function sanitize_select($input, $setting = null) {
        // Get list of choices from the control associated with the setting
        $choices = array();
        $control = null;
        
        if ($setting) {
            $control = $setting->manager->get_control($setting->id);
            if ($control && isset($control->choices)) {
                $choices = $control->choices;
            }
        }
        
        // If valid choice, return it; otherwise, return default
        return (array_key_exists($input, $choices)) ? $input : $setting->default;
    }
    
    /**
     * Sanitize number
     *
     * @param int|float $number
     * @param WP_Customize_Setting $setting
     * @return int|float
     */
    public function sanitize_number($number, $setting = null) {
        $number = floatval($number);
        
        // Get input attributes from the control
        $min = 1;
        $max = 10;
        $step = 0.1;
        
        if ($setting) {
            $control = $setting->manager->get_control($setting->id);
            if ($control && isset($control->input_attrs)) {
                $min = isset($control->input_attrs['min']) ? $control->input_attrs['min'] : $min;
                $max = isset($control->input_attrs['max']) ? $control->input_attrs['max'] : $max;
                $step = isset($control->input_attrs['step']) ? $control->input_attrs['step'] : $step;
            }
        }
        
        return max($min, min($max, $number));
    }
}
