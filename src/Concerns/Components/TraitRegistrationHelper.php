<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait TraitRegistrationHelper {
	private static array $trait_cache = [];

	protected function ensure_component_registration(): void
	{
		$class = get_class($this);

		// Use static cache - only check once per class
		if (!array_key_exists($class, self::$trait_cache)) {
			self::$trait_cache[$class] = $this->has_component_registration();
		}

		if (!self::$trait_cache[$class]) {
			throw new \LogicException(
				sprintf(
					'Component %s uses capability traits but is missing ComponentRegistration trait. ' .
					'Add "use ComponentRegistration;" to enable automatic registration.',
					$class
				)
			);
		}
	}

	private function has_component_registration(): bool
	{
		// Single call to class_uses() with full namespace check
		return isset(class_uses($this)[ComponentRegistration::class]);
	}

}