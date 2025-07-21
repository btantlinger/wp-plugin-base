# WordPress Plugin Base

>
> **⚠️Please Note: This library is currently in early development and is NOT suitable for production use.**
>
> **It is not advised to deploy plugins built with this library to production WordPress sites until a stable version is released.**



A modern WordPress plugin base that provides a solid foundation for creating well-structured, maintainable WordPress plugins. This library uses a service-based architecture with dependency injection, organized hook management, and clean separation of concerns.

## Overview

The WordPress Plugin Base is designed to help developers create WordPress plugins with:

- **Clean Architecture**: Separation of concerns and modular design
- **Modern PHP Practices**: Type safety, dependency injection, and PSR standards
- **Developer Experience**: Streamlined workflows and reduced boilerplate code
- **Testability**: Easy unit testing with PHPUnit

## Requirements

- PHP 8.3 or higher
- WordPress 6.0 or higher
- Composer

## Installation

1. Clone the repository to your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone <repository-url> plugin-base
   ```

2. Install dependencies using Composer:
   ```bash
   cd plugin-base
   composer install
   ```

3. Activate the plugin in the WordPress admin panel.

## Architecture

The Plugin Base follows a service-based architecture with several key components:

### 1. AbstractPlugin

Base class for plugins, providing core functionality:
- Singleton pattern with `init_plugin` and `get_instance` methods
- Manages plugin core and database
- Child classes override `initialize()` and `get_services()`

### 2. PluginCore

Manages the plugin's core functionality:
- Loads configuration and dependencies
- Provides service container functionality
- Handles plugin lifecycle events

### 3. Settings

Manages plugin settings:
- `AbstractSettingBuilder`: Base class for settings builders
- `SettingsProvider`: Interface for providing settings configurations
- `FieldValidators`: Provides validation for settings fields

### 4. Components

Modular functionality via traits:
- `HasAction`: WordPress action hooks
- `HasAdminMenu`: Admin menu functionality
- `HasAjax`: AJAX functionality
- `HasAssets`: Asset management
- `HasCli`: WP-CLI commands
- `HasFilter`: WordPress filter hooks
- `HasMetaBox`: Meta box functionality
- `HasPostType`: Custom post type functionality
- `HasRestApi`: REST API functionality
- `HasSchedule`: Scheduled tasks
- `HasShortcode`: Shortcode functionality
- `HasWidget`: Widget functionality

### 5. Component Registration

The Plugin Base uses a component-based architecture where functionality is encapsulated in classes that implement the `ComponentInterface`. This interface defines three key methods:

#### ComponentInterface Methods

- **`register()`**: This method is called when a component is being registered. It's where the component should set up its WordPress hooks, filters, and other initialization code. This is the main entry point for a component's functionality.

- **`can_register()`**: This method acts as a conditional gate that determines whether a component should be registered. It allows components to check conditions (like user roles, plugin settings, or environment variables) before registering. If this method returns `false`, the component will not be registered, even if it's added to the component manager.

- **`get_priority()`**: This method determines the order in which components are registered. Components with lower priority values are registered first. This is useful when certain components depend on others being registered first.

#### Component Registration Process

1. Components are added to the `ComponentManager` via the `register_component()` method, either:
   - Manually through `PluginCoreInterface::register_component()` method (e.g., `$plugin->get_core()->register_component(new MyComponent())`)
   - Automatically when a service implementing `ComponentInterface` is added to the container with `PluginCore::set()`

2. When `initialize_components()` is called (during WordPress's `plugins_loaded` hook), the `ComponentManager`:
   - Sorts all registered components by priority
   - For each component, checks if it can be registered using `can_register()`
   - If `can_register()` returns true, calls the component's `register()` method

#### The ComponentRegistration Trait

The Plugin Base provides a `ComponentRegistration` trait that simplifies component implementation:

1. **Automatic Registration**: The trait provides a final implementation of the `register()` method required by `ComponentInterface`, so you don't need to implement it yourself.

2. **Trait Discovery**: It automatically discovers all capability traits used by the component (like `HasAction`, `HasFilter`, etc.) and calls their registration methods.

3. **Registration Hooks**: It provides hooks for customizing the registration process:
   - `before_register()`: Called before trait registration, useful for setup tasks
   - `after_register()`: Called after trait registration, useful for adding additional hooks

4. **One-time Registration**: It ensures traits are only initialized once with an internal flag.

When using capability traits like `HasAction` or `HasFilter`, you must also use the `ComponentRegistration` trait. Each capability trait requires you to implement specific methods:

| Trait | Required Methods |
|-------|-----------------|
| `HasAction` | `get_action_hook()`, `execute_action()` |
| `HasFilter` | `get_filter_hook()`, `execute_filter()` |
| `HasShortcode` | `get_shortcode_tag()`, `render_shortcode()` |
| `HasAdminMenu` | `render_admin_page()`, `get_page_title()`, `get_menu_title()`, `get_menu_slug()` |

This approach allows you to create components by combining traits for different WordPress functionalities without writing boilerplate code.

#### Example Component

```php
use WebMoves\PluginBase\Contracts\Components\Component;

class MyComponent implements Component
{
    public function register(): void
    {
        // Register hooks, filters, or other initialization code
        add_action('init', [$this, 'initialize']);
        add_filter('the_content', [$this, 'modify_content']);
    }
    
    public function can_register(): bool
    {
        // Only register this component in the admin area
        return is_admin();
    }
    
    public function get_priority(): int
    {
        // Standard priority
        return 10;
    }
   
    public function modify_content(string $content): string
    {
        // Modify the content
        return $content . '<p>Modified by MyComponent</p>';
    }
}
```

#### Example Component Using Traits

Here's a more creative example that uses multiple traits to create a component for displaying customizable notices in WordPress. This demonstrates how to build reusable components:

```php
use WebMoves\PluginBase\Contracts\Components\Component;
use WebMoves\PluginBase\Concerns\Components\ComponentRegistration;
use WebMoves\PluginBase\Concerns\Components\HasAction;
use WebMoves\PluginBase\Concerns\Components\HasShortcode;
use WebMoves\PluginBase\Concerns\Components\HasFilter;

class NoticeMessage implements Component {

    // Use the ComponentRegistration trait to implement the register() method automatically
    use ComponentRegistration;
    
    // Use capability traits for different WordPress functionalities
    use HasAction;
    use HasShortcode;
    use HasFilter;
    
    public function __construct(
        private string $notice_type = 'info', 
        private string $notice_message = '', 
        private bool $is_dismissible = true,
        private string $admin_notice_hook = 'admin_notices',
        private array $shortcode_defaults = ['type' => 'info', 'dismissible' => 'true']
    ) {
        // Constructor property promotion automatically assigns parameters to properties
    }
    
    /**
     * Define the action hook to use
     * Required by HasAction trait
     */
    protected function get_action_hook(): string {
        return $this->admin_notice_hook;
    }
    
    /**
     * Define the filter hook to use
     * Required by HasFilter trait
     */
    protected function get_filter_hook(): string {
        return 'the_content';
    }
    
    /**
     * Define the shortcode tag to use
     * Required by HasShortcode trait
     */
    protected function get_shortcode_tag(): string {
        return 'notice';
    }
    
    /**
     * Hook that runs after trait registration is complete
     * This is provided by the ComponentRegistration trait
     */
    protected function after_register(): void {
        // Register additional hooks that aren't covered by the traits
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Implement the action functionality - display admin notice
     * Required by HasAction trait
     */
    public function execute_action(...$args): void {
        $dismissible_class = $this->is_dismissible ? ' is-dismissible' : '';
        echo '<div class="notice notice-' . esc_attr($this->notice_type) . $dismissible_class . '">';
        echo '<p>' . wp_kses_post($this->notice_message) . '</p>';
        echo '</div>';
    }
    
    /**
     * Implement the filter functionality - add notice to content
     * Required by HasFilter trait
     */
    public function execute_filter(...$args): mixed {
        $content = $args[0] ?? '';
        
        // Only add the notice to the main content, not excerpts or other filtered content
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $notice_html = '<div class="content-notice content-notice-' . esc_attr($this->notice_type) . '">';
        $notice_html .= '<p>' . wp_kses_post($this->notice_message) . '</p>';
        $notice_html .= '</div>';
        
        // Add notice at the beginning of the content
        return $notice_html . $content;
    }
    
    /**
     * Implement the shortcode rendering
     * Required by HasShortcode trait
     */
    protected function render_shortcode($atts, $content = null): string {
        // Parse attributes with defaults
        $attributes = shortcode_atts($this->shortcode_defaults, $atts);
        
        // Get type and dismissible from attributes
        $type = $attributes['type'];
        $dismissible = filter_var($attributes['dismissible'], FILTER_VALIDATE_BOOLEAN);
        
        // Build the shortcode output
        $output = '<div class="shortcode-notice shortcode-notice-' . esc_attr($type);
        $output .= $dismissible ? ' is-dismissible' : '';
        $output .= '">';
        
        if ($content) {
            $output .= '<p>' . do_shortcode($content) . '</p>';
        } else {
            $output .= '<p>' . wp_kses_post($this->notice_message) . '</p>';
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Custom method for enqueueing scripts and styles
     * Not required by any trait, but called from after_register()
     */
    public function enqueue_scripts(): void {
        // Enqueue styles for our notices
        wp_enqueue_style(
            'notice-message-style',
            plugin_dir_url(__FILE__) . 'assets/css/notice-message.css',
            [],
            '1.0.0'
        );
        
        // If notices are dismissible, enqueue the WordPress dismissible notices script
        if ($this->is_dismissible) {
            wp_enqueue_script('common');
        }
    }
    
    /**
     * Define the component's priority
     * Required by Component
     */
    public function get_priority(): int { 
        return 10; 
    }
    
    /**
     * Determine if this component should be registered
     * Required by Component
     */
    public function can_register(): bool {
        // This component can be registered in any context
        return true;
    }
}
```

To use this component, you would define it in your plugin. The key benefit is that you can create multiple instances with different configurations:

```php
// In your plugin's get_services() method
public function get_services(): array
{
    return [
        // Info notice for all users
        'info.notice' => new NoticeMessage(
            'info',                                          // Notice type
            'Welcome to our plugin! Check out the new features.', // Notice message
            true,                                            // Is dismissible
            'admin_notices'                                  // Admin hook
        ),
        
        // Warning notice only for admin users
        'warning.notice' => new NoticeMessage(
            'warning',                                       // Different notice type
            'Your license will expire in 7 days. Please renew.', // Different message
            false,                                           // Not dismissible
            'admin_notices',                                 // Same admin hook
            ['type' => 'warning', 'dismissible' => 'false']  // Different shortcode defaults
        ),
        
        // Error notice for network admin
        'error.notice' => new NoticeMessage(
            'error',                                         // Different notice type
            'Critical update required!',                     // Different message
            true,                                            // Dismissible
            'network_admin_notices'                          // Different admin hook
        )
    ];
}
```

> **Note:** Components returned as services in the `get_services()` method are automatically registered with the plugin core. There's no need to call `register_component()` explicitly for these components.

This approach demonstrates the reusability of components:
1. Each instance displays a different type of notice (info, warning, error)
2. Each instance shows a different message
3. Some instances are dismissible, others are not
4. Different instances can hook into different admin hooks
5. All instances share the same registration logic through the traits

The power of this design is that you can easily create multiple notice types with different configurations without modifying the component's code. This promotes reusability and makes your components more flexible and maintainable.


## Configuration

The plugin uses a configuration directory structure for various settings:

### Configuration Files

Configuration files are stored in the `/config` directory:

- **Logging Configuration**: 
  - `/config/monolog.php` or `/config/logging.php` - Configure logging settings
  - The LoggerFactory looks for these files in multiple locations (plugin dir, current working dir, etc.)

- **Core Dependencies**:
  - `/config/core-dependencies.php` - Define core dependencies
  - Additional configuration files are loaded in a specific order by PluginCore

- **Bundles**:
  - `/config/bundles/` - Directory for bundle configurations


## Creating a New Plugin

To create a new plugin using this Plugin Base:

1. Create a new class that extends `AbstractPlugin`
2. Implement the `initialize()` method to set up your plugin
3. Implement the `get_services()` method to register your services
4. Call `YourPlugin::init_plugin(__FILE__, '1.0.0')` in your main plugin file

Example:

```php
<?php
/**
 * Plugin Name: Your Plugin
 * Plugin URI: https://example.com
 * Description: Your plugin description
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: your-plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load Composer autoloader
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Initialize the plugin
YourPlugin::init_plugin(__FILE__, '1.0.0');
```

Your plugin class:

```php
<?php

namespace YourNamespace;

use WebMoves\PluginBase\AbstractPlugin;
use WebMoves\PluginBase\Settings\MenuAdminPage;
use WebMoves\PluginBase\Settings\SettingsPage;
use WebMoves\PluginBase\Settings\DefaultSettingsBuilder;

class YourPlugin extends AbstractPlugin
{
    public function initialize(): void
    {
        // Initialize your plugin
        $logger = $this->core->get_logger('app');
        $logger->info('Plugin initialized');
    }
    
    public function get_services(): array
    {
        $plugin_slug = 'your-plugin';
        
        // Create a settings builder with providers
        $builder = new DefaultSettingsBuilder(
            $this->get_core(),
            'your_plugin_settings',
            'your-plugin-settings',
            [
                new YourSettingsProvider('your-scope')
            ]
        );
        
        return [
            // Main plugin page
            MenuAdminPage::class => new MenuAdminPage($plugin_slug, 'Your Plugin', 'Your Plugin'),
            
            // Settings page as submenu
            SettingsPage::class => new SettingsPage(
                $builder,
                'Your Plugin Settings',
                'Settings',
                $plugin_slug
            ),
            
            // Other services
            YourService::class => new YourService(),
        ];
    }
}
```

## Features


### Dependency Injection

The Plugin Base uses PHP-DI for dependency injection. Services should be registered with the container in the `get_services()` method of your plugin class:

```php
public function get_services(): array
{
    return [
        MyService::class => new MyService(),
        'my-service' => function() {
            return new MyService();
        },
    ];
}
```

### Settings Management

Type-safe settings with automatic prefixing and validation:

```php
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;
use WebMoves\PluginBase\Settings\AbstractSettingsProvider;

class ApiSettingsProvider extends AbstractSettingsProvider
{
    public function get_settings_configuration(): array
    {
        return [
            'section' => [
                'id' => 'api_settings',
                'title' => __('API Settings', 'my-plugin'),
                'description' => __('Configure API connection settings.', 'my-plugin'),
            ],
            'fields' => [
                'api_key' => [
                    'label' => __('API Key', 'my-plugin'),
                    'type' => 'text',
                    'description' => __('Enter your API key.', 'my-plugin'),
                    'default' => '',
                    'required' => true,
                ],
                // More fields...
            ]
        ];
    }
}
```

### Admin Pages

The Plugin Base provides a flexible system for creating admin pages in the WordPress dashboard:

#### Types of Admin Pages

1. **Menu Admin Pages**: Top-level menu items in the WordPress admin
2. **Settings Pages**: Pages that display and manage plugin settings
3. **Custom Admin Pages**: Custom pages with specialized functionality

#### Creating a Menu Admin Page

To create a top-level menu page:

```php
use WebMoves\PluginBase\Settings\MenuAdminPage;

// In your plugin's get_services() method
public function get_services(): array
{
    $plugin_slug = 'my-plugin';
    return [
        MenuAdminPage::class => new MenuAdminPage(
            $plugin_slug,           // Menu slug
            'My Plugin',            // Page title
            'My Plugin'             // Menu title
        ),
    ];
}
```

This creates a top-level menu item in the WordPress admin. You can customize the menu by passing additional parameters to the constructor:

```php
new MenuAdminPage(
    'my-plugin',                    // Menu slug
    'My Plugin',                    // Page title
    'My Plugin',                    // Menu title
    null,                           // Parent slug (null for top-level)
    'dashicons-admin-tools',        // Menu icon
    30                              // Menu position
);
```

#### Creating a Settings Page

To create a settings page that displays and manages plugin settings:

```php
use WebMoves\PluginBase\Settings\SettingsPage;
use WebMoves\PluginBase\Settings\DefaultSettingsBuilder;

// In your plugin's get_services() method
public function get_services(): array
{
    $plugin_slug = 'my-plugin';
    
    // Create a settings builder with providers
    $builder = new DefaultSettingsBuilder(
        $this->get_core(),
        'my_plugin_settings',      // Option name
        'my-plugin-settings',      // Page slug
        [
            new MySettingsProvider('my-scope'),
            new AnotherSettingsProvider('another-scope')
        ]
    );
    
    return [
        // Main plugin page
        MenuAdminPage::class => new MenuAdminPage($plugin_slug, 'My Plugin', 'My Plugin'),
        
        // Settings page as submenu
        SettingsPage::class => new SettingsPage(
            $builder,                  // Settings builder
            'My Plugin Settings',      // Page title
            'Settings',                // Menu title
            $plugin_slug               // Parent slug (for submenu)
        ),
    ];
}
```

#### Creating a Custom Admin Page

To create a custom admin page with specialized functionality:

```php
use WebMoves\PluginBase\Settings\AbstractAdminPage;

class MyCustomPage extends AbstractAdminPage
{
    protected function render_admin_page(): void
    {
        // Render your custom page content
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->get_page_title()) . '</h1>';
        echo '<p>This is my custom admin page.</p>';
        
        // Add your custom content, forms, tables, etc.
        
        echo '</div>';
    }
}

// In your plugin's get_services() method
public function get_services(): array
{
    $plugin_slug = 'my-plugin';
    return [
        // Main plugin page
        MenuAdminPage::class => new MenuAdminPage($plugin_slug, 'My Plugin', 'My Plugin'),
        
        // Custom page as submenu
        MyCustomPage::class => new MyCustomPage(
            'my-custom-page',      // Page slug
            'My Custom Page',      // Page title
            'Custom Page',         // Menu title
            $plugin_slug           // Parent slug (for submenu)
        ),
    ];
}
```

#### Admin Page Registration Process

Admin pages implement the `ComponentInterface` and use the `HasAdminMenu` trait, which:

1. Registers the page with WordPress during the `admin_menu` action
2. Handles the rendering process with pre-render setup
3. Manages page hooks and capabilities

The `can_register()` method ensures pages are only registered in the admin area and when the user has the required capability.

### Database Management

Automated table creation and migrations:

```php
protected function get_database_tables(): array {
    return [
        'my_plugin_items' => "
            CREATE TABLE {table_name} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id)
            ) {charset_collate};
        ",
    ];
}

protected function get_database_upgrade_callbacks(): array {
    return [
        function($old_version, $new_version) {
            // Perform upgrade from old_version to new_version
            if (version_compare($old_version, '1.1.0', '<')) {
                // Add new column to table
                global $wpdb;
                $table_name = $wpdb->prefix . 'my_plugin_items';
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN status varchar(50) DEFAULT 'active'");
            }
        }
    ];
}
```


## License

This project is licensed under the GPL v2 or later.