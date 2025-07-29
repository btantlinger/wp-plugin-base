<?php

namespace WebMoves\PluginBase\Contracts\Forms;

use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;

interface FormSubmissionHandler //extends Component
{
	/**
	 * Get the form action URL where the form should submit
	 */
	public function get_form_action(): string;

	/**
	 * Get hidden form fields (nonces, action fields, etc.)
	 */
	public function get_action_fields(): string;

	/**
	 * Get the settings group identifier (for WordPress settings)
	 */
	public function get_settings_group(): string;

	/**
	 * Register any WordPress hooks or handlers needed for form processing
	 */
	public function register_form_processing(): void;

	/**
	 * Get all settings providers this handler manages
	 *
	 * @return SettingsProvider[]
	 */
	public function get_providers(): array;
}