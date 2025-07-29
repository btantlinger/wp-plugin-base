<?php

namespace WebMoves\PluginBase\Contracts\Assets;

use WebMoves\PluginBase\Contracts\Components\Component;

interface Asset extends Component
{
    /**
     * Get the auto-generated asset handle
     */
    public function get_handle(): string;
    
    /**
     * Get the asset source URL (auto-resolved from relative path)
     */
    public function get_src(): string;
    
    /**
     * Get the relative path (as provided by user)
     */
    public function get_relative_path(): string;
    
    /**
     * Get asset dependencies
     */
    public function get_dependencies(): array;
    
    /**
     * Get asset version (defaults to plugin version)
     */
    public function get_version(): string|bool|null;
    
    /**
     * Check if asset should be enqueued for current context
     */
    public function should_enqueue(): bool;
    
    /**
     * Get the WordPress hook to enqueue on
     */
    public function get_enqueue_hook(): string;
}