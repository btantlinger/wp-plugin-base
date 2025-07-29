<?php
namespace WebMoves\PluginBase\Forms;

use WebMoves\PluginBase\Contracts\Forms\SettingsForm;
use WebMoves\PluginBase\Contracts\Forms\FormSubmissionHandler;
use WebMoves\PluginBase\Contracts\Forms\FormRenderer;
use WebMoves\PluginBase\Enums\Lifecycle;

class DefaultSettingsForm implements SettingsForm
{
	private FormSubmissionHandler $submission_handler;
	private FormRenderer $renderer;
	private string $page;

	public function __construct(
		FormSubmissionHandler $submission_handler,
		FormRenderer $renderer,
		string $page
	) {
		$this->submission_handler = $submission_handler;
		$this->renderer = $renderer;
		$this->page = $page;
	}

	public function register(): void
	{
		// Register both submission handling and display elements
		$this->submission_handler->register_form_processing();
		$this->renderer->register_display_elements(
			$this->submission_handler->get_providers(),
			$this->page
		);
	}

	public function render_form(): void
	{
		$this->renderer->render_form($this->submission_handler, $this->page);
	}

	public function render_settings_field(array $args): void
	{
		$this->renderer->render_field($args);
	}

	// Delegate getters to submission handler
	public function get_settings_group(): string
	{
		return $this->submission_handler->get_settings_group();
	}

	public function get_providers(): array
	{
		return $this->submission_handler->get_providers();
	}

	public function get_settings_page(): string
	{
		return $this->page;
	}

	// Expose the composed parts
	public function get_submission_handler(): FormSubmissionHandler
	{
		return $this->submission_handler;
	}

	public function get_renderer(): FormRenderer
	{
		return $this->renderer;
	}

	// Component interface methods
	public function can_register(): bool
	{
		return is_admin() && current_user_can('manage_options');
	}

	public function get_priority(): int
	{
		return 10;
	}

	public function register_on(): Lifecycle
	{
		return Lifecycle::ADMIN_INIT;
	}
}
