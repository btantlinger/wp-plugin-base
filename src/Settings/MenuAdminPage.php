<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\PluginCoreInterface;


class MenuAdminPage extends AbstractAdminPage {

	public function __construct(string $page_slug, string $page_title, string $menu_title)
	{
		parent::__construct($page_slug, $page_title, $menu_title, null, null);
	}

	protected function render_admin_page(): void {

		echo $this->page_slug;

	}

	public function get_priority(): int {
		return 5;
	}

}