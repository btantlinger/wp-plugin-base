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
 * RequiresPlugins: acf
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load Composer autoloader
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

\WebMoves\PluginBase\Examples\TestPluginBase::init_plugin(__FILE__);


