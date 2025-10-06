<?php

namespace WebMoves\PluginBase\Frontend;

use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Enums\Lifecycle;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;

abstract class AbstractTemplateLoader extends AbstractComponent
{
    protected string $template_namespace;
    protected PluginMetadata $metadata;

    public function __construct(PluginMetadata $metadata)
    {
        parent::__construct();
        $this->metadata = $metadata;
        $this->template_namespace = $this->metadata->get_plugin_slug();
    }

    public function register_on(): Lifecycle
    {
        return Lifecycle::INIT;
    }

    public function register(): void
    {
        add_filter('template_include', [$this, 'load_plugin_templates'], $this->get_template_priority());
    }

    public function load_plugin_templates($template): string
    {
        // Only process if this loader should handle the current context
        if (!$this->should_handle_current_context()) {
            return $template;
        }

        foreach ($this->get_template_names() as $template_name) {
            // Check theme override first
            $theme_template = $this->locate_theme_template($template_name);

            if ($theme_template) {
                return $theme_template;
            }

            // Use plugin template as fallback
            $plugin_template = $this->get_plugin_template_path($template_name);

            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Determine if this loader should handle the current context
     */
    abstract protected function should_handle_current_context(): bool;

    /**
     * Get the template names this loader should handle
     */
    abstract protected function get_template_names(): array;

    /**
     * Get the template subdirectory path within the templates/ directory
     */
    abstract protected function get_template_path(): string;

    /**
     * Get the priority for this template loader
     */
    protected function get_template_priority(): int
    {
        return 10; // Default priority - subclasses can override
    }

    /**
     * Locate template in theme with subdirectory support
     */
    protected function locate_theme_template(string $template_name): string
    {
        $search_paths = $this->get_theme_search_paths($template_name);
        $found_template = locate_template($search_paths, false);
        return $found_template ?: '';
    }

    /**
     * Get theme search paths for a template
     */
    protected function get_theme_search_paths(string $template_name): array
    {
        $paths = [];
        $template_path = trim($this->get_template_path(), '/');

        // Most specific: namespaced with template path
        if ($template_path) {
            $paths[] = "{$this->template_namespace}/{$template_path}/{$template_name}";
        }

        // Generic namespaced path
        $paths[] = "{$this->template_namespace}/{$template_name}";

        // Direct theme root (for backward compatibility)
        $paths[] = $template_name;

        return $paths;
    }

    /**
     * Get full path to plugin template
     */
    protected function get_plugin_template_path(string $template_name): string
    {
        $base_path = dirname($this->metadata->get_plugin_file()) . '/templates';
        $template_path = trim($this->get_template_path(), '/');

        if ($template_path) {
            return "{$base_path}/{$template_path}/{$template_name}";
        }

        return "{$base_path}/{$template_name}";
    }
}