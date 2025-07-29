<?php

namespace WebMoves\PluginBase\Controllers;

use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;
use WebMoves\PluginBase\Contracts\Settings\SettingsProcessor;
use WebMoves\PluginBase\Enums\Lifecycle;

class SettingsFormController extends AbstractFormController
{
	private array $settings_providers = [];
	private SettingsProcessor $processor;

	public function __construct(PluginMetadata $metadata, FlashData $flash_data, string $action, array $settings_providers, SettingsProcessor $processor)
	{
		parent::__construct($metadata, $flash_data, $action, 'POST');
		$this->settings_providers = $settings_providers;
		$this->processor = $processor;
	}

	protected function get_nonce_action(array $data): string
	{
		return $this->action;
	}

	public function register_on(): Lifecycle
	{
		return Lifecycle::ADMIN_INIT;
	}

	protected function handle_action(array $data): array
	{
		$results = [];
		$errors = [];

		// Process each provider's settings
		foreach ($this->settings_providers as $provider) {
			$result = $this->process_provider_settings($provider, $data);

			if (!empty($result['errors'])) {
				$errors = array_merge($errors, $result['errors']);

				// Store form data for redisplay on this provider
				$scope = $provider->settings()->get_settings_scope();
				if (isset($data[$scope])) {
					$this->flash_data->set_form_data($scope, $data[$scope]);
				}
			} else {
				$results[] = $result;
			}
		}

		if (!empty($errors)) {
			// Add errors to flash
			$this->flash_data->add_field_errors($errors);

			throw new \Exception('Validation failed');
		}

		// Clear any old form data on success
		foreach ($this->settings_providers as $provider) {
			$scope = $provider->settings()->get_settings_scope();
			$this->flash_data->clear('form_' . $scope);
		}

		return $results;
	}

	private function process_provider_settings(SettingsProvider $provider, array $data): array
	{
		$scope = $provider->settings()->get_settings_scope();
		$input = $data[$scope] ?? [];

		return $this->processor->process($input, $provider);
	}

	protected function get_success_message($result): string
	{
		return __('Settings saved successfully.', $this->text_domain);
	}
}