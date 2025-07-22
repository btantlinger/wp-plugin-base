<?php

namespace WebMoves\PluginBase\Examples\Settings;

use WebMoves\PluginBase\Settings\AbstractAdminPage;


class MainPage extends AbstractAdminPage {
	protected function render_admin_page(): void {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html(get_admin_page_title()) . "</h1>";
		echo  "<p>Hello, World!</p>";
		echo '</div>';
	}
}