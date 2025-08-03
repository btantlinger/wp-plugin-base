<?php

namespace WebMoves\PluginBase\Concerns;

use function WebMoves\PluginBase\__t;
use function WebMoves\PluginBase\__tn;
use function WebMoves\PluginBase\__te;
use function WebMoves\PluginBase\__ta;
use function WebMoves\PluginBase\__tx;

trait HasTranslations
{
	/**
	 * Translate a string using current text domain context
	 *
	 * @param string $text Text to translate
	 * @param string $context Translation context (optional)
	 * @return string Translated text
	 *
	 * @example
	 * ```php
	 * $this->__t('Hello World');
	 * $this->__t('Post', 'noun'); // With context
	 * ```
	 */
	protected function __t(string $text, string $context = ''): string
	{
		return __t($text, $context);
	}

	/**
	 * Translate plural forms using current text domain context
	 *
	 * @param string $singular Singular form
	 * @param string $plural Plural form
	 * @param int $number Number to determine singular/plural
	 * @param string $context Translation context (optional)
	 * @return string Translated text
	 *
	 * @example
	 * ```php
	 * $this->__tn('1 item', '%d items', $count);
	 * $this->__tn('1 post', '%d posts', $count, 'blog posts');
	 * ```
	 */
	protected function __tn(string $singular, string $plural, int $number, string $context = ''): string
	{
		return __tn($singular, $plural, $number, $context);
	}

	/**
	 * Escape and translate for HTML output using current text domain context
	 *
	 * @param string $text Text to translate and escape
	 * @param string $context Translation context (optional)
	 * @return string Translated and escaped text
	 *
	 * @example
	 * ```php
	 * echo '<h1>' . $this->__te('Page Title') . '</h1>';
	 * ```
	 */
	protected function __te(string $text, string $context = ''): string
	{
		return __te($text, $context);
	}

	/**
	 * Escape and translate for HTML attributes using current text domain context
	 *
	 * @param string $text Text to translate and escape for attributes
	 * @param string $context Translation context (optional)
	 * @return string Translated and attribute-escaped text
	 *
	 * @example
	 * ```php
	 * echo '<input placeholder="' . $this->__ta('Enter your name') . '">';
	 * ```
	 */
	protected function __ta(string $text, string $context = ''): string
	{
		return __ta($text, $context);
	}

	/**
	 * Translate with explicit text domain (context switching)
	 *
	 * @param string $text Text to translate
	 * @param string $text_domain Specific text domain to use
	 * @param string $context Translation context (optional)
	 * @return string Translated text
	 *
	 * @example
	 * ```php
	 * // Use a different plugin's translations temporarily
	 * $this->__tx('Settings', 'other-plugin-domain');
	 * ```
	 */
	protected function __tx(string $text, string $text_domain, string $context = ''): string
	{
		return __tx($text, $text_domain, $context);
	}
}
