# WordPress Plugin Base Framework

A modern WordPress plugin development framework using hooks, dependency injection, and clean architecture.

## Features

- **Hook Management**: Organized hook handlers for actions, filters, and shortcodes
- **Dependency Injection**: PHP-DI container for service management
- **Template System**: Flexible template rendering with overrides
- **Database Management**: Automated table creation and migrations
- **Settings Management**: Type-safe settings with automatic prefixing and validation

## Basic Usage

### Creating Hook Handlers

```php
<?php

namespace MyPlugin\Hooks;

use WebMoves\PluginBase\Hooks\AbstractHookHandler;

class AdminHookHandler extends AbstractHookHandler
{
    public function register_hooks(): void
    {
        $this->add_action('admin_menu', 'add_admin_pages');
        $this->add_filter('admin_footer_text', 'modify_admin_footer');
    }

    public function add_admin_pages(): void
    {
        // Add admin pages
    }

    public function modify_admin_footer(string $text): string
    {
        return $text . ' | My Plugin';
    }
}
```

### Registering Hook Handlers

```php
// In your main plugin file
$plugin = wm_plugin_base();
$plugin->register_handler(new AdminHookHandler($plugin->get_service(HookManager::class)));
```

## Settings Management

The framework provides a powerful settings system with automatic prefixing, type safety, and validation.

### Creating Settings Providers

```php
<?php

namespace MyPlugin\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerInterface;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactoryInterface;

class ApiSettingsProvider implements SettingsProvider
{
    private SettingsManagerInterface $settings_manager;

    public function __construct(SettingsManagerFactoryInterface $factory)
    {
        // Automatically creates prefix based on class namespace
        // e.g., "myplugin_settings_apisettingsprovider_"
        $this->settings_manager = $factory->create($this);
    }

    public function settings(): SettingsManagerInterface
    {
        return $this->settings_manager;
    }

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
                    'id' => 'api_key',
                    'label' => __('API Key', 'my-plugin'),
                    'type' => 'text',
                    'description' => __('Enter your API key.', 'my-plugin'),
                    'default' => '',
                    'required' => true,
                ],
                'api_timeout' => [
                    'id' => 'api_timeout',
                    'label' => __('API Timeout', 'my-plugin'),
                    'type' => 'text',
                    'description' => __('Timeout in seconds.', 'my-plugin'),
                    'default' => 30,
                    'attributes' => ['type' => 'number', 'min' => 1, 'max' => 300],
                    'sanitize_callback' => 'absint',
                ],
                'enable_caching' => [
                    'id' => 'enable_caching',
                    'label' => __('Enable Caching', 'my-plugin'),
                    'type' => 'checkbox',
                    'description' => __('Cache API responses.', 'my-plugin'),
                    'default' => true,
                ],
            ]
        ];
    }
}
```

### Using Settings in Your Code

```php
<?php

namespace MyPlugin\Services;

use MyPlugin\Settings\ApiSettingsProvider;

class ApiService
{
    private ApiSettingsProvider $settings_provider;

    public function __construct(ApiSettingsProvider $settings_provider)
    {
        $this->settings_provider = $settings_provider;
    }

    public function make_api_call(): array
    {
        $settings = $this->settings_provider->settings();
        
        // Get settings with automatic prefixing
        $api_key = $settings->get_option('api_key');
        $timeout = $settings->get_option('api_timeout', 30);
        $cache_enabled = $settings->get_option('enable_caching', true);
        
        // Make API call with these settings
        return $this->perform_api_request($api_key, $timeout, $cache_enabled);
    }

    public function update_settings(): void
    {
        $settings = $this->settings_provider->settings();
        
        // Update settings
        $settings->set_option('api_key', 'new-api-key');
        $settings->set_option('api_timeout', 60);
        
        // Check for unsaved changes
        if ($settings->is_dirty()) {
            $settings->save(); // Persist changes
        }
    }
}
```

### Settings Features

#### Automatic Prefixing

Each settings provider automatically gets a unique prefix based on its class namespace:

```php
// MyPlugin\Settings\ApiSettingsProvider
// Creates prefix: "myplugin_settings_apisettingsprovider_"

// MyPlugin\Sync\ProductSyncSettings  
// Creates prefix: "myplugin_sync_productsyncsetticngs_"
```

#### Custom Prefixes

You can also specify custom prefixes:

```php
public function __construct(SettingsManagerFactoryInterface $factory)
{
    // Use custom prefix
    $this->settings_manager = $factory->create_with_prefix('my_custom_prefix');
}
```

#### Type Safety

The settings manager provides type-safe access to your settings:

```php
// These are automatically prefixed and stored in WordPress options
$api_key = $settings->get_option('api_key', '');           // string
$timeout = $settings->get_option('api_timeout', 30);       // int
$enabled = $settings->get_option('enable_caching', true);  // bool
```

#### Change Tracking

Track and batch save changes:

```php
$settings->set_option('api_key', 'new-key');
$settings->set_option('timeout', 60);

// Check if there are unsaved changes
if ($settings->is_dirty()) {
    // Save all changes at once
    $success = $settings->save();
}
```

#### Settings Configuration

The `get_settings_configuration()` method returns an array that can be used to:

- Register WordPress settings sections and fields
- Generate admin forms automatically
- Validate and sanitize input
- Provide field descriptions and help text

Supported field types:
- `text` - Text input
- `textarea` - Textarea input
- `checkbox` - Checkbox input
- `select` - Select dropdown
- `radio` - Radio buttons
- `number` - Number input

Each field can include:
- `label` - Display label
- `description` - Help text
- `default` - Default value
- `required` - Whether field is required
- `sanitize_callback` - Custom sanitization
- `validate_callback` - Custom validation
- `attributes` - HTML attributes
- `options` - For select/radio fields
```