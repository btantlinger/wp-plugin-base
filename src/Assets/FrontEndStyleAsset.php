<?php

namespace WebMoves\PluginBase\Assets;

use WebMoves\PluginBase\Assets\StyleAsset;

class FrontEndStyleAsset extends StyleAsset
{
	public function should_enqueue(): bool
	{
		return !is_admin();
	}

	public function can_register(): bool
	{
		return !is_admin();
	}

}