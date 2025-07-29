<?php

namespace WebMoves\PluginBase\Settings;

use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;
use WebMoves\PluginBase\Contracts\Settings\SettingsBuilder;
use WebMoves\PluginBase\Enums\Lifecycle;

abstract class AbstractSettingBuilder implements SettingsBuilder
{
	private string $settingsGroup;
	private string $page;
	private array $providers = [];
	private string $text_domain;

	protected LoggerInterface $logger;

	protected FlashData $flash;

	protected $core;


	public function __construct(PluginCore $core, string $settingsGroup, string $page, array $settings_providers = [])
	{
		$this->settingsGroup = $settingsGroup;
		$this->page = $page;
		$this->text_domain = $core->get_metadata()->get_text_domain();
		$this->logger = $core->get_logger('app');
		$this->flash = $core->get(FlashData::class);
		$this->providers = $settings_providers;
		$this->core = $core;
	}

	public function get_plugin_core(): PluginCore {
		return $this->core;
	}


	public function get_settings_group(): string
	{
		return $this->settingsGroup;
	}

	public function get_settings_page(): string
	{
		return $this->page;
	}

	public function get_providers(): array
	{
		return $this->providers;
	}

	public function add_provider(SettingsProvider $provider): void
	{
		$this->providers[] = $provider;
	}

	public function register(): void
	{
		add_action('admin_init', [$this, 'register_settings']);
	}



	public function register_settings(): void
	{
		foreach ($this->providers as $provider) {
			$this->register_provider_configuration($provider);
		}
	}


	protected abstract function register_provider_configuration(SettingsProvider $provider): void;


	/**
	 * Get the value to display in the form field
	 */
	protected function get_field_display_value(SettingsProvider $provider, string $field_key, $default_value)
	{
		// Check for flash data first (from validation errors)
		$flash_value = $this->get_flash_value($provider, $field_key, null);
		if ($flash_value !== null) {
			return $flash_value;
		}

		// Fall back to saved value or default
		return $provider->settings()->get_scoped_option($field_key, $default_value);
	}

	protected function get_flash_value(SettingsProvider $provider, string $field_key, $default = null)
	{
		$form_key = $provider->settings()->get_settings_scope();
		$flash_data = $this->flash->get_form_data($form_key);

		// If no flash data exists, return the default (null)
		if (empty($flash_data)) {
			return $default;
		}

		// Return the specific field value or default
		return $flash_data[$field_key] ?? $default;
	}

	public function get_priority(): int {
		return 10;
	}

	public function get_text_domain(): string {
		return $this->text_domain;
	}

	public function can_register(): bool {
		return is_admin() && current_user_can('manage_options');
	}

	public function register_on(): Lifecycle {
		return Lifecycle::INIT;
	}
}