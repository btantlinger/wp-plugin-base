<?php

namespace WebMoves\PluginBase\Examples\Components;

use WebMoves\PluginBase\Concerns\Components\ComponentRegistration;
use WebMoves\PluginBase\Concerns\Components\HasAction;
use WebMoves\PluginBase\Concerns\Components\HasFilter;
use WebMoves\PluginBase\Concerns\Components\HasShortcode;
use WebMoves\PluginBase\Contracts\Components\Component;
use WebMoves\PluginBase\Enums\Lifecycle;

class AdvancedComponent implements Component {

	use ComponentRegistration;
	use HasAction;
	use HasFilter;
	use HasShortcode;

	private string $footer_msg;

	private string $append_content;

	private array $shortcode_defaults;

	public function __construct(string $footer_msg = '', string $append_content = '', array $shortcode_defaults = ['color' => 'blue', 'title' => 'Hi']) {
		$this->footer_msg = $footer_msg;
		$this->append_content = $append_content;
		$this->shortcode_defaults = $shortcode_defaults;
	}

	public function register_on(): Lifecycle {
		return Lifecycle::INIT;
	}


	protected function get_action_hook(): string {
		return 'wp_footer';
	}

	protected function get_filter_hook(): string {
		return 'the_content';
	}

	protected function get_shortcode_tag(): string {
		return 'my-shortcode';
	}

	protected function after_register(): void {
		// You can also register additional hooks here
		add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
	}

	public function execute_filter( ...$args ): mixed {
		$content = $args[0] ?? '';

		// Add a message to the content
		return $content . '<div class="filtered-content">This content was filtered by AdvancedComponent</div>';
	}

	public function execute_action( ...$args ): void {
		echo '<div class="footer-message">This message was added by AdvancedComponent</div>';
	}

	protected function render_shortcode( $atts, $content = null ): string {
		// Parse attributes with defaults
		$attributes = shortcode_atts($this->shortcode_defaults, $atts);

		// Build the shortcode output
		$output = '<div class="my-shortcode" style="color: ' . esc_attr($attributes['color']) . ';">';
		$output .= '<h3>' . esc_html($attributes['title']) . '</h3>';

		if ($content) {
			$output .= '<div class="content">' . do_shortcode($content) . '</div>';
		}

		$output .= '</div>';
		return $output;
	}

	/**
	 * Enqueue scripts and styles
	 * This is an additional method not required by any trait
	 */
	public function enqueue_scripts(): void
	{
		// Enqueue a stylesheet for our component
		wp_enqueue_style(
			'advanced-component-style',
			plugin_dir_url(__FILE__) . 'assets/css/advanced-component.css',
			[],
			'1.0.0'
		);
	}

	public function get_priority(): int { return 10; }

	public function can_register(): bool {
		//only register on the front end
		return !is_admin();
	}
}