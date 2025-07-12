# WordPress Plugin Base Framework

A modern WordPress plugin development framework using hooks, dependency injection, and clean architecture.

## Features

- **Hook Management**: Organized hook handlers for actions, filters, and shortcodes
- **Dependency Injection**: PHP-DI container for service management
- **Template System**: Flexible template rendering with overrides
- **Database Management**: Automated table creation and migrations

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
``` php
// In your main plugin file
$plugin = wm_plugin_base();
$plugin->register_handler(new AdminHookHandler($plugin->get_service(HookManager::class)));
```
