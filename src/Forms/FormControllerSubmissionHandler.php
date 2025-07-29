<?php

namespace WebMoves\PluginBase\Forms;

use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Contracts\Controllers\FormController;
use WebMoves\PluginBase\Contracts\Forms\FormSubmissionHandler;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Contracts\Settings\SettingsProcessor;
use WebMoves\PluginBase\Controllers\SettingsFormController;
use WebMoves\PluginBase\Enums\Lifecycle;

class FormControllerSubmissionHandler implements FormSubmissionHandler {

	private ?FormController $controller = null;

	private array $settings_providers;

	private string $settings_group;

	private PluginCore $core;

	/**
	 * @param FormController $controller
	 * @param array $settings_providers
	 * @param string $settings_group
	 */
	public function __construct(PluginCore $core, array $settings_providers, string $settings_group) {

		$this->core = $core;
		$this->settings_providers = $settings_providers;
		$this->settings_group = $settings_group;
	}

	protected function get_controller(): FormController
	{
		if(!$this->controller) {
			$this->controller = new SettingsFormController(
				$this->core->get_metadata(),
				$this->core->get( FlashData::class ),
				'save_settings_' . $this->settings_group,
				$this->get_providers(),
				$this->core->get( SettingsProcessor::class )
			);
		}
		return $this->controller;
	}

	/**
	 * @inheritDoc
	 */
	public function get_form_action(): string {
		$url = $this->get_controller()->create_action_url();
		if (empty($url)) {
			throw new \RuntimeException('Form action URL cannot be empty');
		}
		return $url;
	}

	/**
	 * @inheritDoc
	 */
	public function get_action_fields(): string {
		return $this->get_controller()->get_action_fields();
	}

	/**
	 * @inheritDoc
	 */
	public function get_settings_group(): string {
		return $this->settings_group;
	}

	/**
	 * @inheritDoc
	 */
	public function register_form_processing(): void
	{
		$controller = $this->get_controller();
		if(!$this->core->get_component_manager()->contains($controller)) {
			$this->core->get_component_manager()->add($controller);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function get_providers(): array {
		return $this->settings_providers;
	}
}