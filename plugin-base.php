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


\WebMoves\PluginBase\Examples\TestPlugin::init_plugin(__FILE__, WM_PLUGIN_BASE_VERSION);


