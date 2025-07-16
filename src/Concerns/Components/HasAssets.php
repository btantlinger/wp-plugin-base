<?php
namespace WebMoves\PluginBase\Concerns\Components;

trait HasAssets
{
	use TraitRegistrationHelper;

	protected function register_has_assets(): void
	{
		$this->ensure_component_registration();

		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
	}

	public function enqueue_frontend_assets(): void
	{
		if ($this->should_load_frontend_assets()) {
			$this->enqueue_assets($this->get_frontend_assets());
		}
	}

	public function enqueue_admin_assets(): void
	{
		if ($this->should_load_admin_assets()) {
			$this->enqueue_assets($this->get_admin_assets());
		}
	}

	protected function enqueue_assets(array $assets): void
	{
		foreach ($assets as $asset) {
			if ($asset['type'] === 'script') {
				wp_enqueue_script(
					$asset['handle'],
					$asset['src'],
					$asset['deps'] ?? [],
					$asset['version'] ?? null,
					$asset['in_footer'] ?? true
				);
			} else {
				wp_enqueue_style(
					$asset['handle'],
					$asset['src'],
					$asset['deps'] ?? [],
					$asset['version'] ?? null,
					$asset['media'] ?? 'all'
				);
			}
		}
	}

	protected function get_frontend_assets(): array { return []; }
	protected function get_admin_assets(): array { return []; }
	protected function should_load_frontend_assets(): bool { return true; }
	protected function should_load_admin_assets(): bool { return true; }
}
