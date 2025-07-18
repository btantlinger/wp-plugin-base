<?php

namespace WebMoves\PluginBase;

class Utils {
	/**
	 * Get all traits used by a class and its parents recursively
	 */
	public static function get_all_traits($class): array {
		$traits = [];

		// Handle both class names and objects
		if (is_object($class)) {
			$class = get_class($class);
		}

		do {
			// Get traits used by current class
			$class_traits = class_uses($class);

			if ($class_traits) {
				// Add traits used by the traits themselves (recursive)
				foreach ($class_traits as $trait) {
					$traits[] = $trait;
					$traits = array_merge($traits, Utils::get_all_traits($trait));
				}
			}

			// Move to parent class
			$class = get_parent_class($class);
		} while ($class);

		// Remove duplicates and return
		return array_unique($traits);
	}
}