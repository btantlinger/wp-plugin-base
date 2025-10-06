<?php

namespace WebMoves\PluginBase\Examples\Settings;

use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Pages\AbstractAdminPage;



class MainPage extends AbstractAdminPage {

	const PAGE_SLUG = 'test-plugin-base';

	private string $menu_title;
	private string $page_title;

	private $text_domain;

	public function __construct(PluginCore $core) {
		parent::__construct($core, self::PAGE_SLUG, null);
		$this->page_title = ('Test Plugin Base');
		$this->menu_title = ('Test Plugin');
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
		return $this->page_title;
	}

	public function get_menu_title(): string {
		return $this->menu_title;
	}

	protected function create_assets(): array {
		// Create test assets
		return [
			$this->create_style_asset('examples/test-plugin/assets/css/test.css'),
			$this->create_script_asset('examples/test-plugin/assets/js/test.js', ['jquery'])
		];
	}


}