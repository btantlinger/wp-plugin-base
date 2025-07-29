<?php

namespace WebMoves\PluginBase\Assets;


class FrontEndScriptAsset extends ScriptAsset
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