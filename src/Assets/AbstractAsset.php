<?php

namespace WebMoves\PluginBase\Assets;

use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Contracts\Assets\Asset;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Enums\Lifecycle;

abstract class AbstractAsset extends AbstractComponent implements Asset
{
    protected PluginMetadata $metadata;
    protected string $relative_path;
    protected array $dependencies;
    protected string|bool|null $version;
    protected string $enqueue_hook;
    protected ?string $handle;

    public function __construct(
        PluginMetadata $metadata,
        string $relative_path,
        array $dependencies = [],
        string|bool|null $version = null,
        string $enqueue_hook = 'wp_enqueue_scripts',
        ?string $custom_handle = null
    ) {
        $this->metadata = $metadata;
        $this->relative_path = $this->is_url($relative_path) ? $relative_path : ltrim($relative_path, '/');
        $this->dependencies = $dependencies;
        $this->version = $version ?? $metadata->get_version();
        $this->enqueue_hook = $enqueue_hook;
        $this->handle = $custom_handle;
        parent::__construct();
    }

    /**
     * Check if the given path is a URL
     */
    private function is_url(string $path): bool
    {
        return filter_var($path, FILTER_VALIDATE_URL) !== false || 
               str_starts_with($path, '//') || // Protocol-relative URLs
               str_starts_with($path, 'data:'); // Data URLs
    }

    public function get_handle(): string
    {
        if (empty($this->handle)) {
            if ($this->is_url($this->relative_path)) {
                // For URLs, create handle from the URL path/filename
                $parsed_url = parse_url($this->relative_path);
                $path = $parsed_url['path'] ?? '';
                $path_parts = pathinfo($path);
                $filename = $path_parts['filename'] ?? 'external-asset';
                
                // Sanitize the filename and add prefix
                $this->handle = $this->metadata->get_prefix() . 'external-' . sanitize_key($filename);
            } else {
                // Auto-generate handle from plugin prefix + sanitized path
                $path_parts = pathinfo($this->relative_path);
                $filename = $path_parts['filename']; // Without extension

                // Convert path separators and sanitize
                $path_key = str_replace(['/', '\\', '.'], '-', $path_parts['dirname']);
                $path_key = ($path_key === '.' || $path_key === '-') ? '' : $path_key . '-';
                $this->handle = $this->metadata->get_prefix() . $path_key . sanitize_key($filename);
            }
        }
        return $this->handle;
    }

    public function get_src(): string
    {
        // If it's already a URL, return as-is
        if ($this->is_url($this->relative_path)) {
            return $this->relative_path;
        }
        
        // Convert relative path to full URL
        return plugin_dir_url($this->metadata->get_file()) . $this->relative_path;
    }

    public function get_relative_path(): string
    {
        return $this->relative_path;
    }

    public function get_dependencies(): array
    {
        return $this->dependencies;
    }

    public function get_version(): string|bool|null
    {
        return $this->version;
    }

    public function get_enqueue_hook(): string
    {
        return $this->enqueue_hook;
    }

    public function register(): void
    {
        add_action($this->get_enqueue_hook(), [$this, 'maybe_enqueue']);
    }

    public function maybe_enqueue(): void
    {
        if ($this->should_enqueue()) {
            $this->enqueue();
        }
    }

    public function should_enqueue(): bool
    {
        return true;
    }

    public function register_on(): Lifecycle
    {
        return Lifecycle::INIT;
    }

    public function get_priority(): int
    {
        return 10;
    }

    public function can_register(): bool
    {
        return true;
    }

    abstract protected function enqueue(): void;
}