<?php

namespace WebMoves\PluginBase\Contracts\Settings;

use WebMoves\PluginBase\Contracts\Components\Component;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;

interface SettingsBuilder extends Component
{

    /**
     * Render a single settings field
     * (Called by WordPress settings field callback)
     *
     * @param array $args Field arguments from WordPress
     * @return void
     */
    public function render_settings_field(array $args): void;

    /**
     * Render the complete settings page
     * (Called by your settings page template or callback)
     *
     * @return void
     */
    public function render_form(): void;
	
	
	public function get_settings_group(): string;
	

	
	public function get_settings_page();


	/**
	 * Get all registered settings providers
	 *
	 * @return SettingsProvider[] Array of registered settings providers
	 */
	public function get_providers(): array;

	public function get_plugin_core(): PluginCore;
}