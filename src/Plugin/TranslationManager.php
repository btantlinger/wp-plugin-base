<?php

namespace WebMoves\PluginBase\Plugin;

class TranslationManager {
	
	private static array $registered_domains = [];
	private static ?string $current_text_domain = null;
	private static array $context_stack = [];
	
	/**
	 * Register a text domain (just track that it exists)
	 */
	public static function register_text_domain(string $text_domain): void
	{
		self::$registered_domains[$text_domain] = true;	
	}
	
	/**
	 * Set the current active text domain
	 */
	public static function set_current_text_domain(string $text_domain): void
	{
		self::$current_text_domain = $text_domain;
	}
	
	/**
	 * Push a text domain onto the context stack (for temporary context switching)
	 */
	public static function push_context(string $text_domain): void
	{
		self::$context_stack[] = self::$current_text_domain;
		self::$current_text_domain = $text_domain;
	}
	
	/**
	 * Pop the previous text domain from context stack
	 */
	public static function pop_context(): void
	{
		if (!empty(self::$context_stack)) {
			self::$current_text_domain = array_pop(self::$context_stack);
		}
	}
	
	/**
	 * Check if a text domain is registered
	 */
	public static function is_registered(string $text_domain): bool
	{
		return isset(self::$registered_domains[$text_domain]);
	}
	
	/**
	 * Get the current active text domain
	 */
	public static function get_current_text_domain(): ?string
	{
		return self::$current_text_domain;
	}
	
	/**
	 * Get all registered text domains
	 */
	public static function get_registered_domains(): array
	{
		return array_keys(self::$registered_domains);
	}
}