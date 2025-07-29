<?php

namespace WebMoves\PluginBase\Forms;

use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Contracts\Settings\SettingsProcessor;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;
use WebMoves\PluginBase\Enums\Lifecycle;
use WebMoves\PluginBase\Contracts\Forms\FormSubmissionHandler;

class SettingsAPISubmissionHandler implements FormSubmissionHandler {

	private array $settings_providers;

	private string $settings_group;

	private string $page;

	private SettingsProcessor $processor;

	private PluginCore $core;

	private FlashData $flash;

	/**
	 * @param array $settings_providers
	 * @param string $settings_group
	 */
	public function __construct(PluginCore $core, string $settings_group, string $page, array $settings_providers) {

		if (empty($settings_group)) {
			throw new \InvalidArgumentException('Settings group cannot be empty');
		}

		$this->settings_providers = $settings_providers;
		$this->settings_group = $settings_group;
		$this->page = $page;
		$this->core = $core;
		$this->flash = $this->core->get(FlashData::class);
		$this->processor = $core->get(SettingsProcessor::class);
	}


	/**
	 * @inheritDoc
	 */
	public function get_form_action(): string {
		return 'options.php';
	}

	/**
	 * @inheritDoc
	 */
	public function get_action_fields(): string {
		$out  = "<input type='hidden' name='option_page' value='" . esc_attr( $this->get_settings_group() ) . "' />";
		$out .= '<input type="hidden" name="action" value="update" />';
		$out .=  wp_nonce_field( $this->get_settings_group() . "-options", '_wpnonce', true, false );
		return 	$out;
	}

	/**
	 * @inheritDoc
	 */
	public function get_settings_group(): string {
		return $this->settings_group;
	}

	public function handle_settings_success(): void
	{
		// Only add success message if we processed a submission and have no errors
		$settings_updated = isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true';
		if ($settings_updated && $this->is_current_settings_page() && !$this->flash->has_errors()) {
			$this->flash->add_success('Settings saved successfully!');
		}
	}

	private function is_current_settings_page(): bool
	{
		$screen = get_current_screen();
		if (!$screen) {
			return false;
		}

		// Check if the current page matches this builder's page
		return strpos($screen->id, $this->page) !== false;
	}

	/**
	 * @inheritDoc
	 */
	public function register_form_processing(): void {

		add_action('current_screen', [$this, 'handle_settings_success']);

		foreach($this->settings_providers as $provider) {
			// Get the option name from the settings manager
			$option_name = $provider->settings()->get_settings_scope();

			// Register single setting for the entire group
			register_setting(
				$this->get_settings_group(),
				$option_name,
				[
					'type' => 'array',
					'sanitize_callback' => function($input) use ($provider) {
						if (!$input) {
							$input = [];
						}
						return $this->validate_and_sanitize_group($input, $provider);
					}
				]
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function get_providers(): array {
		return $this->settings_providers;
	}

	protected function validate_and_sanitize_group(array $input, SettingsProvider $provider): array
	{
		$result = $this->processor->process($input, $provider);

		if (isset($result['errors'])) {
			// Add errors as notices
			$this->flash->add_field_errors($result['errors']);

			// Store form data for redisplay
			$this->flash->set_form_data($provider->settings()->get_settings_scope(), $input);

			return $provider->settings()->get_all_scoped_options();
		}

		// Success
		$this->flash->clear('form_' . $provider->settings()->get_settings_scope());

		return $result['data'];
	}
}