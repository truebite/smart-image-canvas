<?php
/**
 * Uninstall script for Smart Image Canvas plugin
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data on uninstall
 */
function sic_uninstall_cleanup() {
    // Remove plugin options
    delete_option('sic_settings');
    
    // Remove any transients
    delete_transient('sic_cache');
    
    // Clean up upload directory
    $upload_dir = wp_upload_dir();
    $sic_dir = $upload_dir['basedir'] . '/smart-image-canvas';
    
    if (file_exists($sic_dir)) {
        sic_delete_directory($sic_dir);
    }
    
    // Remove any custom post meta (if we stored any)
    global $wpdb;
    $wpdb->delete($wpdb->postmeta, array('meta_key' => '_sic_disabled'));
    
    // Clear any cached data
    wp_cache_flush();
}

/**
 * Recursively delete directory
 *
 * @param string $dir Directory path
 * @return bool Success
 */
function sic_delete_directory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!sic_delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

// Run cleanup
sic_uninstall_cleanup();
