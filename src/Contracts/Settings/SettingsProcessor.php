<?php

namespace WebMoves\PluginBase\Contracts\Settings;

interface SettingsProcessor
{
	/**
	 * Process settings input for a provider
	 *
	 * @param array $input User input data
	 * @param SettingsProvider $provider Settings provider instance
	 * @return array Result array with either 'errors' key or 'success'/'data' keys
	 */
	public function process(array $input, SettingsProvider $provider): array;
}