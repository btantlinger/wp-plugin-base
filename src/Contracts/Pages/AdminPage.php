<?php

namespace WebMoves\PluginBase\Contracts\Pages;

use WebMoves\PluginBase\Contracts\Components\Component;

interface AdminPage extends Component  {

	public function get_page_title(): string;

	public function get_menu_title(): string;

	public function get_menu_slug(): string;

	public function get_capability(): string;

	public function get_menu_icon(): ?string;

	public function get_menu_position(): ?int;

	public function get_parent_slug(): ?string;

	public function is_submenu_page(): bool;
}