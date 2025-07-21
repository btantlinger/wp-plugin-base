<?php

namespace WebMoves\PluginBase;

class Utils {
    private static array $trait_cache = [];

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

    /**
     * Check if a class uses a specific trait (with caching)
     */
    public static function class_uses_trait($class, string $trait_name): bool {
        $class_name = is_object($class) ? get_class($class) : $class;
        $cache_key = $class_name . '::' . $trait_name;

        if (!array_key_exists($cache_key, self::$trait_cache)) {
            $traits = self::get_all_traits($class);
            self::$trait_cache[$cache_key] = in_array($trait_name, $traits);
        }

        return self::$trait_cache[$cache_key];
    }

    /**
     * Ensure a class uses a required trait, throw exception if not
     */
    public static function ensure_trait_usage($class, string $required_trait, string $error_message = null): void {
        if (!self::class_uses_trait($class, $required_trait)) {
            $class_name = is_object($class) ? get_class($class) : $class;
            $default_message = sprintf(
                'Class %s is missing required trait %s',
                $class_name,
                $required_trait
            );
            
            throw new \LogicException($error_message ?: $default_message);
        }
    }
}