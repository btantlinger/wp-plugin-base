<?php

namespace WebMoves\PluginBase\Contracts\Settings;

use WebMoves\PluginBase\Contracts\Components\ComponentInterface;

interface SettingsBuilderInterface extends ComponentInterface
{
    /**
     * Add a settings provider to the builder
     *
     * @param SettingsProvider $provider
     * @return void
     */
    public function add_provider(SettingsProvider $provider): void;


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
}