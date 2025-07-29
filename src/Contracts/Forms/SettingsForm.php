<?php

namespace WebMoves\PluginBase\Contracts\Forms;

use WebMoves\PluginBase\Contracts\Components\Component;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;

interface SettingsForm extends Component
{
	/**
	 * Render the complete settings page
	 */
	public function render_form(): void;

	/**
	 * Render a single settings field
	 * (Called by WordPress settings field callback)
	 */
	public function render_settings_field(array $args): void;

	/**
	 * Get the settings group identifier
	 */
	public function get_settings_group(): string;

	/**
	 * Get the settings page identifier
	 */
	public function get_settings_page(): string;

	/**
	 * Get all registered settings providers
	 *
	 * @return SettingsProvider[]
	 */
	public function get_providers(): array;



	/**
	 * Get the submission handler
	 */
	public function get_submission_handler(): FormSubmissionHandler;

	/**
	 * Get the renderer
	 */
	public function get_renderer(): FormRenderer;
}
