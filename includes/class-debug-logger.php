<?php
/**
 * Debug Logger for Smart Image Canvas
 * Simple logging system for debugging purposes
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SIC_Debug_Logger {
    
    private static $instance = null;
    private $log_option = 'sic_debug_logs';
    private $max_logs = 100; // Keep last 100 log entries
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get singleton instance (alias for backward compatibility)
     */
    public static function instance() {
        return self::get_instance();
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        // Initialize if needed
    }
    
    /**
     * Log a message
     */
    public function log($level, $message, $context = array()) {
        if (!$this->is_debug_enabled()) {
            return;
        }
        
        $logs = get_option($this->log_option, array());
        
        // Create log entry
        $entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'backtrace' => $this->get_simple_backtrace()
        );
        
        // Add to beginning of array
        array_unshift($logs, $entry);
        
        // Keep only the most recent logs
        if (count($logs) > $this->max_logs) {
            $logs = array_slice($logs, 0, $this->max_logs);
        }
        
        update_option($this->log_option, $logs);
    }
    
    /**
     * Log info message
     */
    public function info($message, $context = array()) {
        $this->log('INFO', $message, $context);
    }
    
    /**
     * Log warning message
     */
    public function warning($message, $context = array()) {
        $this->log('WARNING', $message, $context);
    }
    
    /**
     * Log error message
     */
    public function error($message, $context = array()) {
        $this->log('ERROR', $message, $context);
    }
    
    /**
     * Log debug message
     */
    public function debug($message, $context = array()) {
        $this->log('DEBUG', $message, $context);
    }
    
    /**
     * Get all logs
     */
    public function get_logs() {
        return get_option($this->log_option, array());
    }
    
    /**
     * Clear all logs
     */
    public function clear_logs() {
        delete_option($this->log_option);
        $this->info('Debug logs cleared');
    }
    
    /**
     * Check if debug logging is enabled
     */
    public function is_debug_enabled() {
        $settings = get_option('sic_settings', array());
        return isset($settings['debug_enabled']) && $settings['debug_enabled'];
    }
    
    /**
     * Get simple backtrace
     */
    private function get_simple_backtrace() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $simple_trace = array();
        
        foreach ($trace as $frame) {
            if (isset($frame['file']) && isset($frame['line'])) {
                $file = basename($frame['file']);
                $line = $frame['line'];
                $function = isset($frame['function']) ? $frame['function'] : 'unknown';
                $simple_trace[] = "{$file}:{$line} ({$function})";
            }
        }
        
        return $simple_trace;
    }
    
    /**
     * Export logs as text
     */
    public function export_logs() {
        $logs = $this->get_logs();
        $output = "Smart Image Canvas Debug Logs\n";
        $output .= "Generated: " . current_time('Y-m-d H:i:s') . "\n";
        $output .= str_repeat("=", 50) . "\n\n";
        
        foreach ($logs as $log) {
            $output .= "[{$log['timestamp']}] {$log['level']}: {$log['message']}\n";
            
            if (!empty($log['context'])) {
                $output .= "Context: " . print_r($log['context'], true) . "\n";
            }
            
            if (!empty($log['backtrace'])) {
                $output .= "Trace: " . implode(' -> ', $log['backtrace']) . "\n";
            }
            
            $output .= str_repeat("-", 30) . "\n";
        }
        
        return $output;
    }
}
