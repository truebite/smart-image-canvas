<?php
/**
 * Plugin Updater for Smart Image Canvas
 * Handles automatic updates from private GitHub repository
 *
 * @package Smart_Image_Canvas
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SIC_Plugin_Updater {
    
    /**
     * GitHub repository information
     */
    private $username = 'truebite';
    private $repository = 'smart-image-canvas';
    private $github_token = ''; // Will be set via settings
    
    /**
     * Debug logger instance
     */
    private $logger;
    
    /**
     * Plugin information
     */
    private $plugin_slug;
    private $plugin_file;
    private $version;
    private $plugin_path;
    
    /**
     * Initialize the updater
     */
    public function __construct($plugin_file, $plugin_slug, $version) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
        $this->plugin_path = plugin_basename($plugin_file);
        
        // Initialize debug logger
        $this->logger = SIC_Debug_Logger::instance();
        
        // Get GitHub token from settings
        $settings = get_option('sic_settings', array());
        $this->github_token = isset($settings['github_token']) ? $settings['github_token'] : '';
        
        // Log updater initialization
        $this->logger->info('Plugin updater initialized', [
            'plugin_slug' => $this->plugin_slug,
            'version' => $this->version,
            'has_token' => !empty($this->github_token),
            'token_length' => strlen($this->github_token)
        ]);
        
        if (!empty($this->github_token)) {
            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
            add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
            add_filter('upgrader_pre_download', array($this, 'download_package'), 10, 3);
            
            $this->logger->info('Auto-update hooks registered successfully');
        } else {
            $this->logger->warning('GitHub token not found - automatic updates disabled');
        }
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get remote version
        $remote_version = $this->get_remote_version();
        
        if ($remote_version && version_compare($this->version, $remote_version, '<')) {
            $plugin_data = array(
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_path,
                'new_version' => $remote_version,
                'url' => "https://github.com/{$this->username}/{$this->repository}",
                'package' => $this->get_download_url($remote_version)
            );
            
            $transient->response[$this->plugin_path] = (object) $plugin_data;
        }
        
        return $transient;
    }
    
    /**
     * Get remote version from GitHub releases
     */
    private function get_remote_version() {
        $this->logger->info('Checking for remote version from GitHub releases');
        
        $request = wp_remote_get($this->get_api_url('releases/latest'), array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->github_token,
                'User-Agent' => 'WordPress-Plugin-Updater',
                'X-GitHub-Api-Version' => '2022-11-28'
            )
        ));
        
        if (is_wp_error($request)) {
            $this->logger->error('GitHub API request failed', [
                'error' => $request->get_error_message(),
                'url' => $this->get_api_url('releases/latest')
            ]);
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($request);
        $this->logger->info('GitHub API response received', [
            'response_code' => $response_code,
            'url' => $this->get_api_url('releases/latest')
        ]);
        
        if ($response_code === 200) {
            $body = wp_remote_retrieve_body($request);
            $data = json_decode($body, true);
            
            if (isset($data['tag_name'])) {
                $remote_version = ltrim($data['tag_name'], 'v');
                $this->logger->info('Remote version found', [
                    'remote_version' => $remote_version,
                    'current_version' => $this->version,
                    'update_available' => version_compare($this->version, $remote_version, '<')
                ]);
                return $remote_version;
            } else {
                $this->logger->warning('No tag_name found in GitHub response', ['response_data' => $data]);
            }
        } else {
            $this->logger->error('GitHub API returned error response', [
                'response_code' => $response_code,
                'response_body' => wp_remote_retrieve_body($request)
            ]);
        }
        
        return false;
    }
    
    /**
     * Get plugin information for update details
     */
    public function plugin_info($false, $action, $response) {
        if ($action !== 'plugin_information' || $response->slug !== $this->plugin_slug) {
            return $false;
        }
        
        $request = wp_remote_get($this->get_api_url('releases/latest'), array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->github_token,
                'User-Agent' => 'WordPress-Plugin-Updater',
                'X-GitHub-Api-Version' => '2022-11-28'
            )
        ));
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $data = json_decode($body, true);
            
            $response->name = 'Smart Image Canvas';
            $response->slug = $this->plugin_slug;
            $response->version = isset($data['tag_name']) ? ltrim($data['tag_name'], 'v') : $this->version;
            $response->author = 'Your Name';
            $response->homepage = "https://github.com/{$this->username}/{$this->repository}";
            $response->download_link = $this->get_download_url($response->version);
            $response->sections = array(
                'description' => 'Automatically generate beautiful CSS-based featured images when no featured image is set.',
                'changelog' => isset($data['body']) ? $data['body'] : 'Bug fixes and improvements.'
            );
            
            return $response;
        }
        
        return $false;
    }
    
    /**
     * Download the plugin package
     */
    public function download_package($reply, $package, $upgrader) {
        if (strpos($package, 'github.com') !== false && strpos($package, $this->repository) !== false) {
            $args = array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->github_token,
                    'Accept' => 'application/vnd.github.v3+json',
                    'X-GitHub-Api-Version' => '2022-11-28'
                )
            );
            
            $request = wp_remote_get($package, $args);
            
            if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
                $temp_file = download_url($package, 300, false, $args);
                return $temp_file;
            }
        }
        
        return $reply;
    }
    
    /**
     * Get GitHub API URL
     */
    private function get_api_url($endpoint) {
        return "https://api.github.com/repos/{$this->username}/{$this->repository}/{$endpoint}";
    }
    
    /**
     * Get download URL for a specific version
     */
    private function get_download_url($version) {
        return "https://api.github.com/repos/{$this->username}/{$this->repository}/zipball/v{$version}";
    }
}
