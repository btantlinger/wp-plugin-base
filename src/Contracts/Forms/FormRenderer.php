<?php

namespace WebMoves\PluginBase\Contracts\Forms;

interface FormRenderer
{
	/**
	 * Render the complete form structure
	 */
	public function render_form(FormSubmissionHandler $handler, string $page): void;


	/**
	 * Register any WordPress sections/fields needed for rendering
	 */
	public function register_display_elements(array $providers, string $page): void;
}
