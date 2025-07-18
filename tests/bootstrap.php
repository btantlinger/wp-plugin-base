<?php
/**
 * PHPUnit bootstrap file for WordPress Plugin Base Framework
 *
 * @package WebMoves\PluginBase\Tests
 */

// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Set up WordPress function mocks
if (!function_exists('plugin_dir_path')) {
    /**
     * Mock for plugin_dir_path WordPress function
     *
     * @param string $file The path to the plugin file
     * @return string The path to the plugin directory
     */
    function plugin_dir_path($file)
    {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    /**
     * Mock for plugin_dir_url WordPress function
     *
     * @param string $file The path to the plugin file
     * @return string The URL to the plugin directory
     */
    function plugin_dir_url($file)
    {
        return 'https://example.com/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('wp_die')) {
    /**
     * Mock for wp_die WordPress function
     *
     * @param string $message The message to display
     * @param string $title The title of the error page
     * @param array $args Additional arguments
     */
    function wp_die($message = '', $title = '', $args = [])
    {
        throw new \Exception($message);
    }
}

if (!function_exists('__')) {
    /**
     * Mock for __ WordPress translation function
     *
     * @param string $text Text to translate
     * @param string $domain Text domain
     * @return string Translated text
     */
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('_e')) {
    /**
     * Mock for _e WordPress translation function
     *
     * @param string $text Text to translate and echo
     * @param string $domain Text domain
     */
    function _e($text, $domain = 'default')
    {
        echo $text;
    }
}

if (!function_exists('esc_html__')) {
    /**
     * Mock for esc_html__ WordPress function
     *
     * @param string $text Text to translate and escape
     * @param string $domain Text domain
     * @return string Translated and escaped text
     */
    function esc_html__($text, $domain = 'default')
    {
        return htmlspecialchars($text);
    }
}

if (!function_exists('esc_html_e')) {
    /**
     * Mock for esc_html_e WordPress function
     *
     * @param string $text Text to translate, escape, and echo
     * @param string $domain Text domain
     */
    function esc_html_e($text, $domain = 'default')
    {
        echo htmlspecialchars($text);
    }
}

if (!function_exists('esc_attr__')) {
    /**
     * Mock for esc_attr__ WordPress function
     *
     * @param string $text Text to translate and escape
     * @param string $domain Text domain
     * @return string Translated and escaped text
     */
    function esc_attr__($text, $domain = 'default')
    {
        return htmlspecialchars($text);
    }
}

if (!function_exists('esc_attr_e')) {
    /**
     * Mock for esc_attr_e WordPress function
     *
     * @param string $text Text to translate, escape, and echo
     * @param string $domain Text domain
     */
    function esc_attr_e($text, $domain = 'default')
    {
        echo htmlspecialchars($text);
    }
}

if (!function_exists('esc_html')) {
    /**
     * Mock for esc_html WordPress function
     *
     * @param string $text Text to escape
     * @return string Escaped text
     */
    function esc_html($text)
    {
        return htmlspecialchars($text);
    }
}

if (!function_exists('esc_attr')) {
    /**
     * Mock for esc_attr WordPress function
     *
     * @param string $text Text to escape
     * @return string Escaped text
     */
    function esc_attr($text)
    {
        return htmlspecialchars($text);
    }
}

if (!function_exists('esc_url')) {
    /**
     * Mock for esc_url WordPress function
     *
     * @param string $url URL to escape
     * @return string Escaped URL
     */
    function esc_url($url)
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('add_action')) {
    /**
     * Mock for add_action WordPress function
     *
     * @param string $hook The name of the WordPress action
     * @param callable $callback The callback function to be run when the action is called
     * @param int $priority The order in which the function is executed
     * @param int $accepted_args The number of arguments the function accepts
     */
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        // No-op for testing
    }
}

if (!function_exists('add_filter')) {
    /**
     * Mock for add_filter WordPress function
     *
     * @param string $hook The name of the WordPress filter
     * @param callable $callback The callback function to be run when the filter is called
     * @param int $priority The order in which the function is executed
     * @param int $accepted_args The number of arguments the function accepts
     */
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        // No-op for testing
    }
}

if (!function_exists('apply_filters')) {
    /**
     * Mock for apply_filters WordPress function
     *
     * @param string $hook The name of the filter hook
     * @param mixed $value The value to filter
     * @return mixed The filtered value
     */
    function apply_filters($hook, $value)
    {
        $args = func_get_args();
        array_shift($args); // Remove $hook
        return $args[0]; // Return the value unchanged
    }
}

if (!function_exists('do_action')) {
    /**
     * Mock for do_action WordPress function
     *
     * @param string $hook The name of the action to be executed
     */
    function do_action($hook)
    {
        // No-op for testing
    }
}

if (!function_exists('register_activation_hook')) {
    /**
     * Mock for register_activation_hook WordPress function
     *
     * @param string $file The path to the plugin file
     * @param callable $callback The function to be called when the plugin is activated
     */
    function register_activation_hook($file, $callback)
    {
        // No-op for testing
    }
}

if (!function_exists('register_deactivation_hook')) {
    /**
     * Mock for register_deactivation_hook WordPress function
     *
     * @param string $file The path to the plugin file
     * @param callable $callback The function to be called when the plugin is deactivated
     */
    function register_deactivation_hook($file, $callback)
    {
        // No-op for testing
    }
}

if (!function_exists('plugins_url')) {
    /**
     * Mock for plugins_url WordPress function
     *
     * @param string $path Path relative to the plugins directory
     * @param string $plugin The plugin file that you want to be relative to
     * @return string The URL to the plugins directory
     */
    function plugins_url($path = '', $plugin = '')
    {
        return 'https://example.com/wp-content/plugins/' . $path;
    }
}

if (!function_exists('sanitize_title')) {
    /**
     * Mock for sanitize_title WordPress function
     *
     * @param string $title The title to be sanitized
     * @return string The sanitized title
     */
    function sanitize_title($title)
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
    }
}

// Define additional WordPress constants if needed
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

// Set up any additional test environment configuration here