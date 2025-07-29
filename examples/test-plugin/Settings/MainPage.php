<?php

namespace WebMoves\PluginBase\Examples\Settings;

use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Pages\AbstractAdminPage;


class MainPage extends AbstractAdminPage {

	private string $menu_title;
	private string $page_title;

	private $text_domain;

	public function __construct(PluginCore $core, string $page_slug, string $page_title, string $menu_title) {
		parent::__construct($core, $page_slug, null);
		$this->page_title = $page_title;
		$this->menu_title = $menu_title;
		$this->text_domain = $core->get_metadata()->get_text_domain();
	}

	protected function render_admin_page(): void {
		echo '<div class="wrap">';
		echo '<div class="main-page-test">';
		echo '<h1>' . esc_html(get_admin_page_title()) . "</h1>";
		echo '<p>Hello, World! This is a test page created with Web Moves Plugin Base.</p>';
		echo '<p>If you can see this page, it is working!</p>';
		echo '<button class="test-button">Click to Test JavaScript</button>';
		echo '<div class="asset-status">';
		echo '<p>✅ CSS file loaded successfully (you can see the styling)</p>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	public function get_page_title(): string {
		return __($this->page_title, $this->text_domain);
	}

	public function get_menu_title(): string {
		return __($this->menu_title, $this->text_domain);
	}

	protected function create_assets(): array {
		// Create test assets
		return [
			$this->create_style_asset('examples/test-plugin/assets/css/test.css'),
			$this->create_script_asset('examples/test-plugin/assets/js/test.js', ['jquery'])
		];
	}


}