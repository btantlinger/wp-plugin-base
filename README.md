# Web Moves Plugin Base

>
> **⚠️Please Note: This library is currently in early development and is NOT suitable for production use.**
>
> **It is not advised to deploy plugins built with this library to production WordPress sites until a stable version is released.**



A modern WordPress plugin base that provides a solid foundation for creating well-structured, maintainable WordPress plugins. This library uses a service-based architecture with dependency injection, organized hook management, and clean separation of concerns.

## Overview

Web Moves Plugin Base is designed to help developers create WordPress plugins with:

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

### 1. PluginBase

Base class for plugins, providing core functionality:
- Singleton pattern with `init_plugin` and `get_instance` methods
- Manages plugin core
- Can be extended by subclasses that override `initialize()` and `get_services()`

### 2. PluginCore

Manages the plugin's core functionality:
- Loads configuration
- Provides service container functionality
- Handles plugin lifecycle events
- Manages components
- Provides access to plugin metadata

### 3. Plugin Metadata

The Plugin Base has a PluginMetadata interface and DefaultPluginMetadata implementation for managing plugin metadata:
- Separates plugin metadata from the plugin core
- Provides methods for accessing plugin information:
  - Name, version, text domain
  - Description, author, URIs
  - Required WordPress and PHP versions
  - Required plugins
  - Plugin file paths and slugs
- Makes it easier to access plugin information throughout the codebase
- Available for dependency injection

### 4. Configuration System

The Plugin Base has a robust configuration system:
- Loads configuration from the plugin.config.php file
- Supports dot notation for accessing configuration values
- Provides methods for getting, setting, and checking configuration values
- Has specific methods for retrieving common configuration sections:
  - Required plugins
  - Services
  - Components
  - Logging configuration

Example configuration file structure:
```
/config
  /plugin.config.php       # Main configuration file
```

Example configuration usage:
```php
// Get a configuration value
$apiKey = $this->core->get_config()->get('api.key', 'default_key');

// Check if a configuration value exists
if ($this->core->get_config()->has('feature.enabled')) {
    // Do something
}

// Set a configuration value at runtime
$this->core->get_config()->set('cache.ttl', 3600);
```

### 5. Settings

Manages plugin settings:
- `SettingsProvider`: Interface for providing settings configurations
- `SettingsManager`: Interface for managing settings operations
- `AbstractSettingsProvider`: Base class for settings providers

### 6. Components

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

### 7. Lifecycle Management

The Plugin Base has a Lifecycle enum that defines different plugin lifecycle events:

1. Plugin management events:
   - INSTALL: When the plugin is first installed
   - ACTIVATE: When the plugin is activated
   - DEACTIVATE: When the plugin is deactivated
   - UNINSTALL: When the plugin is uninstalled/deleted

2. Runtime lifecycle events:
   - BOOTSTRAP: Very early, before WordPress fully loads (plugins_loaded hook)
   - INIT: Standard init hook
   - ADMIN_INIT: Admin-specific initialization
   - READY: After everything is loaded (wp_loaded hook)

Components are registered for specific lifecycle events by implementing the `register_on()` method.

### 8. Component Registration

The Plugin Base uses a component-based architecture where functionality is encapsulated in classes that implement the `Component` interface. This interface defines four key methods:

#### Component Interface Methods

- **`register()`**: This method is called when a component is being registered. It's where the component should set up its WordPress hooks, filters, and other initialization code. This is the main entry point for a component's functionality.

- **`can_register()`**: This method acts as a conditional gate that determines whether a component should be registered. It allows components to check conditions (like user roles, plugin settings, or environment variables) before registering. If this method returns `false`, the component will not be registered, even if it's added to the component manager.

- **`get_priority()`**: This method determines the order in which components are registered. Components with lower priority values are registered first. This is useful when certain components depend on others being registered first.

- **`register_on()`**: This method determines which lifecycle event the component should be registered on. It returns a Lifecycle enum value.

#### Component Registration Process

1. Components are added to the `ComponentManager` via the `add()` method, either:
   - Manually through `PluginCoreInterface::get_component_manager()->add()` method
   - Automatically when a service implementing `Component` is added to the container with `PluginCore::set()`
   - Through configuration in the plugin.config.php file

2. When a lifecycle event occurs, the `ComponentManager`:
   - Gets all components for that specific lifecycle
   - Sorts them by priority
   - For each component, checks if it can be registered using `can_register()`
   - If `can_register()` returns true, calls the component's `register()` method

#### The ComponentRegistration Trait

The Plugin Base includes a `ComponentRegistration` trait that simplifies component implementation:

1. **Automatic Registration**: The trait provides a final implementation of the `register()` method required by `Component`, so you don't need to implement it yourself.

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
use WebMoves\PluginBase\Enums\Lifecycle;

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
    
    public function register_on(): Lifecycle
    {
        // Register on the INIT lifecycle
        return Lifecycle::INIT;
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
use WebMoves\PluginBase\Enums\Lifecycle;

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
     * Define which lifecycle event this component should register on
     * Required by Component interface
     */
    public function register_on(): Lifecycle
    {
        return Lifecycle::ADMIN_INIT;
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

> **Note:** Components returned as services in the `get_services()` method are automatically registered with the plugin core. There's no need to call `add()` explicitly for these components.

This approach demonstrates the reusability of components:
1. Each instance displays a different type of notice (info, warning, error)
2. Each instance shows a different message
3. Some instances are dismissible, others are not
4. Different instances can hook into different admin hooks
5. All instances share the same registration logic through the traits

The power of this design is that you can easily create multiple notice types with different configurations without modifying the component's code. This promotes reusability and makes your components more flexible and maintainable.


## Configuration

The plugin uses a configuration system for various settings:

### Configuration Files

Configuration files are stored in the `/config` directory:

- **Main Configuration**: 
  - `/config/plugin.config.php` - Main configuration file

- **Configuration Structure**:
  ```php
  // Example plugin.config.php
  return [
      /*
      |--------------------------------------------------------------------------
      | Plugin Dependencies
      |--------------------------------------------------------------------------
      |
      | Define the list of plugins that must be installed and activated
      | for the plugin to function properly.
      |
      */
      'dependencies' => [
          'required_plugins' => [
              'woocommerce/woocommerce.php' => 'WooCommerce',
              'advanced-custom-fields/acf.php' => 'Advanced Custom Fields',
          ],
      ],

      /*
      |--------------------------------------------------------------------------
      | Services
      |--------------------------------------------------------------------------
      |
      | Non-component services, utilities, data objects, API clients, etc.
      | These are registered in the container but don't implement Component.
      |
      */
      'services' => [
          DatabaseManager::class => create(DefaultDatabaseManager::class)
              ->constructor(
                  get(PluginCore::class),
                  get(Configuration::class)
              ),
          
          SettingsManagerFactory::class => create(DefaultSettingsManagerFactory::class)
              ->constructor(get(PluginMetadata::class)),
          
          TemplateRenderer::class => create(DefaultTemplateRenderer::class)
              ->constructor(get(PluginCore::class)),
          
          // Logger Factory
          LoggerFactory::class => create(LoggerFactory::class)
              ->constructor(get(Configuration::class), get('plugin.name')),
          
          // Default logger
          LoggerInterface::class => factory(function($container){
              return $container->get(LoggerFactory::class)->create('default');
          }),
      ],

      /*
      |--------------------------------------------------------------------------
      | Components
      |--------------------------------------------------------------------------
      |
      | Components that implement Component and will be registered
      | with the DefaultComponentManager for lifecycle management.
      |
      */
      'components' => [
          DependencyManager::class => create(DependencyManager::class)
              ->constructor(get(PluginCore::class)),
          
          DependencyNotice::class => create(DependencyNotice::class)
              ->constructor(get(DependencyManager::class)),
          
          DatabaseInstaller::class => create(DatabaseInstaller::class)
              ->constructor(get(DatabaseManager::class), get(LoggerInterface::class)),
      ],

      /*
      |--------------------------------------------------------------------------
      | Logging Configuration
      |--------------------------------------------------------------------------
      */
      'logging' => [
          'channels' => [
              'default' => [
                  'handlers' => ['stream', 'error_log'],
                  'processors' => [],
              ],
              'app' => [
                  'handlers' => ['stream'],
                  'processors' => [],
              ],
          ],
          'handlers' => [
              'stream' => [
                  'class' => StreamHandler::class,
                  'constructor' => [
                      'stream' => WP_CONTENT_DIR . '/debug.log',
                      'level' => Level::Debug,
                  ],
                  'formatter' => 'line',
              ],
              'error_log' => [
                  'class' => ErrorLogHandler::class,
                  'constructor' => [
                      'messageType' => ErrorLogHandler::OPERATING_SYSTEM,
                      'level' => Level::Error,
                  ],
                  'formatter' => 'line',
              ],
          ],
          'formatters' => [
              'line' => [
                  'class' => LineFormatter::class,
                  'constructor' => [
                      'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                      'dateFormat' => 'Y-m-d H:i:s',
                  ],
              ],
          ],
      ],

      /*
      |--------------------------------------------------------------------------
      | Database Configuration
      |--------------------------------------------------------------------------
      */
      'database' => [
          'version' => '1.0.1',
          'delete_tables_on_uninstall' => true,
          'delete_options_on_uninstall' => true,
          'tables' => [
              'plugin_settings' => "CREATE TABLE {table_name} (
                  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  setting_key varchar(191) NOT NULL,
                  setting_value longtext,
                  setting_group varchar(100) DEFAULT 'general',
                  is_autoload tinyint(1) DEFAULT 0,
                  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (id),
                  UNIQUE KEY idx_setting_key_group (setting_key, setting_group),
                  KEY idx_setting_group (setting_group),
                  KEY idx_autoload (is_autoload)
              ) {charset_collate};"
          ]
      ],

      /*
      |--------------------------------------------------------------------------
      | Asset Configuration
      |--------------------------------------------------------------------------
      */
      'assets' => [
          'version_strategy' => 'file_time', // 'file_time', 'plugin_version', 'manual'
          'minify_in_production' => true,
          'combine_css' => false,
          'combine_js' => false,
      ],
  ];
  ```

### Accessing Configuration

You can access configuration values using dot notation:

```php
// Get a configuration value
$apiKey = $this->core->get_config()->get('api.key', 'default_key');

// Check if a configuration value exists
if ($this->core->get_config()->has('feature.enabled')) {
    // Do something
}

// Set a configuration value at runtime
$this->core->get_config()->set('cache.ttl', 3600);
```

## Creating a New Plugin

To create a new plugin using this Plugin Base:

1. Create a new class that extends `Plugin`
2. Implement the `initialize()` method to set up your plugin
3. Implement the `get_services()` method to register your services
4. Call `YourPlugin::init_plugin(__FILE__)` in your main plugin file

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
YourPlugin::init_plugin(__FILE__);
```

Your plugin class:

```php
<?php

namespace YourNamespace;

use WebMoves\PluginBase\PluginBase;
use WebMoves\PluginBase\Settings\MenuAdminPage;
use WebMoves\PluginBase\Pages\AbstractSettingsPage;
use WebMoves\PluginBase\Settings\DefaultSettingsBuilder;

class YourPlugin extends PluginBase
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
            AbstractSettingsPage::class => new AbstractSettingsPage(
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

The Plugin Base uses PHP-DI for dependency injection. Services can be registered in three ways:

1. In the `get_services()` method of your plugin class:
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

2. In the configuration file:
```php
// config/plugin.config.php
return [
    'services' => [
        MyService::class => function() {
            return new MyService();
        },
    ],
];
```

3. At runtime using the `set()` method:
```php
$this->core->set('my-service', new MyService());
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
use WebMoves\PluginBase\Pages\AbstractSettingsPage;
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
        AbstractSettingsPage::class => new AbstractSettingsPage(
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
use WebMoves\PluginBase\Pages\AbstractAdminPage;

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

Admin pages implement the `Component` interface and use the `HasAdminMenu` trait, which:

1. Registers the page with WordPress during the `admin_menu` action
2. Handles the rendering process with pre-render setup
3. Manages page hooks and capabilities

The `can_register()` method ensures pages are only registered in the admin area and when the user has the required capability.

## License

This project is licensed under the GPL v2 or later.