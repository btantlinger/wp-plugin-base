<?php
/**
 * Plugin Name: Plugin Base
 * Plugin URI: https://webmoves.net
 * Description: Plugin base framework for WordPress development
 * Version: 1.0.0
 * Author: Bob Tantlinger
 * Author URI: https://webmoves.net
 * Text Domain: wm-plugin-base
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.3
 * Network: false
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WM_PLUGIN_BASE_VERSION', '1.0.0');
define('WM_PLUGIN_BASE_FILE', __FILE__);
define('WM_PLUGIN_BASE_PATH', plugin_dir_path(__FILE__));
define('WM_PLUGIN_BASE_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader
require_once WM_PLUGIN_BASE_PATH . 'vendor/autoload.php';

// Initialize the plugin
function wm_plugin_base_init() {
    static $plugin_core = null;
    
    if ($plugin_core === null) {
        $plugin_core = new \WebMoves\PluginBase\PluginCore(WM_PLUGIN_BASE_FILE,  "test-plug", WM_PLUGIN_BASE_VERSION);
        $plugin_core->initialize();
        
        // Make plugin core available globally
        $GLOBALS['wm_plugin_base'] = $plugin_core;
    }
    
    return $plugin_core;
}

// Initialize plugin
wm_plugin_base_init();

// Helper function to get plugin core instance
function wm_plugin_base(): \WebMoves\PluginBase\PluginCore {
    return $GLOBALS['wm_plugin_base'];
}


// Add after the existing initialization
add_action('plugins_loaded', function() {
	// Initialize test plugin
	$test_plugin = new \WebMoves\PluginBase\Examples\TestPlugin(wm_plugin_base());
});
