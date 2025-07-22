<?php

namespace WebMoves\PluginBase\Contracts\Plugin;

interface PluginMetadata 
{

	/**
	 * Get the unified prefix for this plugin (used for hooks, options, etc.)
	 * Based on plugin slug, safe for use in WordPress contexts
	 */
	public function get_prefix(): string;


	/**
     * Get the plugin name
     */
    public function get_name(): string;

    /**
     * Get the plugin file path
     */
    public function get_file(): string;

    /**
     * Get the plugin version
     */
    public function get_version(): string;

    /**
     * Get the text domain
     */
    public function get_text_domain(): string;

    /**
     * Get the plugin description
     */
    public function get_description(): string;

    /**
     * Get the plugin author
     */
    public function get_author(): string;

    /**
     * Get the plugin URI
     */
    public function get_plugin_uri(): string;

    /**
     * Get the author URI
     */
    public function get_author_uri(): string;

    /**
     * Get the minimum WordPress version required
     */
    public function get_requires_wp(): string;

    /**
     * Get the minimum PHP version required
     */
    public function get_requires_php(): string;

    /**
     * Check if plugin supports network/multisite
     */
    public function get_network(): bool;

    /**
     * Get the domain path for translations
     */
    public function get_domain_path(): string;

    /**
     * Get the update URI
     */
    public function get_update_uri(): string;

    /**
     * Get array of required plugins
     */
    public function get_requires_plugins(): array;

    /**
     * Get the plugin file path (alias for get_file)
     */
    public function get_plugin_file(): string;

    /**
     * Get the plugin slug (directory name)
     */
    public function get_plugin_slug(): string;

    /**
     * Get the plugin basename (directory/filename.php)
     */
    public function get_plugin_basename(): string;
}