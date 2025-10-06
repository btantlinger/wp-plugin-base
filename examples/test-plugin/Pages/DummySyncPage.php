<?php

namespace WebMoves\PluginBase\Examples\Pages;

use WebMoves\PluginBase\Contracts\Controllers\FormController;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Examples\Settings\MainPage;
use WebMoves\PluginBase\Pages\DefaultSyncPage;

class DummySyncPage extends DefaultSyncPage {


	public function __construct(
		PluginCore $core,
		array $synchronizers,
		FormController $cancel_controller,
		FormController $delete_controller,

	) {

		$slug = MainPage::PAGE_SLUG . "-sync";
		parent::__construct($core, $slug, $synchronizers, $cancel_controller, $delete_controller);
	}

	public function get_page_title(): string {
		return ('Dummy Sync Page');
	}

	public function get_menu_title(): string {
		return ('Dummy Sync Page');
	}
}