<?php

namespace WebMoves\PluginBase\Settings;

class FieldValidators
{
    /**
     * Validate that a field is not empty
     */
    public static function required(string $text_domain = 'wm-plugin-base'): callable
    {
        return function($value, $field_config) use ($text_domain) {
            if (empty(trim($value))) {
                $label = $field_config['label'] ?? 'Field';
                return new \WP_Error('required', sprintf(__('%s is required.', $text_domain), $label));
            }
            return true;
        };
    }

    /**
     * Validate email format
     */
    public static function email(string $text_domain = 'wm-plugin-base'): callable
    {
        return function($value, $field_config) use ($text_domain) {
            if (!empty($value) && !is_email($value)) {
                $label = $field_config['label'] ?? 'Email field';
                return new \WP_Error('invalid_email', sprintf(__('%s must be a valid email address.', $text_domain), $label));
            }
            return true;
        };
    }

    /**
     * Validate URL format
     */
    public static function url(string $text_domain = 'wm-plugin-base'): callable
    {
        return function($value, $field_config) use ($text_domain) {
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                $label = $field_config['label'] ?? 'URL field';
                return new \WP_Error('invalid_url', sprintf(__('%s must be a valid URL.', $text_domain), $label));
            }
            return true;
        };
    }

    /**
     * Validate number range
     */
    public static function number_range(int $min = null, int $max = null, string $text_domain = 'wm-plugin-base'): callable
    {
        return function($value, $field_config) use ($min, $max, $text_domain) {
            $label = $field_config['label'] ?? 'Number field';
            
            if (!empty($value) && !is_numeric($value)) {
                return new \WP_Error('invalid_number', sprintf(__('%s must be a number.', $text_domain), $label));
            }

            $num_value = (int) $value;

            if ($min !== null && $num_value < $min) {
                return new \WP_Error('number_too_small', sprintf(__('%s must be at least %d.', $text_domain), $label, $min));
            }

            if ($max !== null && $num_value > $max) {
                return new \WP_Error('number_too_large', sprintf(__('%s must be no more than %d.', $text_domain), $label, $max));
            }

            return true;
        };
    }

    /**
     * Validate minimum length
     */
    public static function min_length(int $min_length, string $text_domain = 'wm-plugin-base'): callable
    {
        return function($value, $field_config) use ($min_length, $text_domain) {
            if (!empty($value) && strlen($value) < $min_length) {
                $label = $field_config['label'] ?? 'Field';
                return new \WP_Error('too_short', sprintf(__('%s must be at least %d characters long.', $text_domain), $label, $min_length));
            }
            return true;
        };
    }

    /**
     * Validate maximum length
     */
    public static function max_length(int $max_length, string $text_domain = 'wm-plugin-base'): callable
    {
        return function($value, $field_config) use ($max_length, $text_domain) {
            if (!empty($value) && strlen($value) > $max_length) {
                $label = $field_config['label'] ?? 'Field';
                return new \WP_Error('too_long', sprintf(__('%s must be no more than %d characters long.', $text_domain), $label, $max_length));
            }
            return true;
        };
    }

    /**
     * Combine multiple validators
     */
    public static function combine(array $validators): callable
    {
        return function($value, $field_config) use ($validators) {
            foreach ($validators as $validator) {
                $result = call_user_func($validator, $value, $field_config);
                if (is_wp_error($result)) {
                    return $result;
                }
            }
            return true;
        };
    }
}