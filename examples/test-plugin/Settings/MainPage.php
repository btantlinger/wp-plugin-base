<?php

namespace WebMoves\PluginBase\Examples\Settings;

use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Settings\AbstractAdminPage;


class MainPage extends AbstractAdminPage {

	private string $menu_title;
	private string $page_title;

	private $text_domain;

	public function __construct(PluginCore $core, string $page_slug, string $page_title, string $menu_title) {
		parent::__construct($page_slug);
		$this->page_title = $page_title;
		$this->menu_title = $menu_title;
		$this->text_domain = $core->get_metadata()->get_text_domain();
	}

	protected function render_admin_page(): void {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html(get_admin_page_title()) . "</h1>";
		echo  "<p>Hello, World!</p>";
		echo '</div>';
	}

	public function get_page_title(): string {
		return __($this->page_title, $this->text_domain);
	}

	public function get_menu_title(): string {
		return __($this->menu_title, $this->text_domain);
	}
}