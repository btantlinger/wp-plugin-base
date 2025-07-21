<?php

namespace WebMoves\PluginBase\Concerns\Components;

use WebMoves\PluginBase\Utils;

trait TraitRegistrationHelper {
	protected function ensure_component_registration(): void {
		Utils::ensure_trait_usage($this, ComponentRegistration::class);
	}
}
