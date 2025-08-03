<?php
namespace WebMoves\PluginBase;

use WebMoves\PluginBase\Plugin\TranslationManager;

/**
 * Translate a string using current text domain context
 */
function __t(string $text, string $context = ''): string
{
	$text_domain = TranslationManager::get_current_text_domain();

	if (!$text_domain) {
		return $text; // Fallback to original text
	}

	return empty($context)
		? __($text, $text_domain)
		: _x($text, $context, $text_domain);
}

/**
 * Translate plural forms using current text domain context
 */
function __tn(string $singular, string $plural, int $number, string $context = ''): string
{
	$text_domain = TranslationManager::get_current_text_domain();

	if (!$text_domain) {
		return $number === 1 ? $singular : $plural;
	}

	return empty($context)
		? _n($singular, $plural, $number, $text_domain)
		: _nx($singular, $plural, $number, $context, $text_domain);
}

/**
 * Escape and translate for HTML output using current text domain context
 */
function __te(string $text, string $context = ''): string
{
	return esc_html(__t($text, $context));
}

/**
 * Escape and translate for HTML attributes using current text domain context
 */
function __ta(string $text, string $context = ''): string
{
	return esc_attr(__t($text, $context));
}

/**
 * Translate with explicit text domain (for cross-plugin scenarios)
 */
function __tx(string $text, string $text_domain, string $context = ''): string
{
	return empty($context)
		? __($text, $text_domain)
		: _x($text, $context, $text_domain);
}
