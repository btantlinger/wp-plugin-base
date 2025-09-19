<?php

namespace WebMoves\PluginBase;

use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Plugin\DefaultPluginCore;
use WebMoves\PluginBase\Plugin\TranslationManager;

/**
 * Base Plugin Class
 * 
 * A singleton base class that provides the foundation for WordPress plugins built with the PluginBase framework.
 * This class handles plugin initialization, service registration, and provides extension points for custom plugin implementations.
 * 
 * ## Usage
 * 
 * Extend this class in your main plugin class and override the extension methods as needed:
 * 
 * ```php
 * class MyPlugin extends Plugin
 * {
 *     public function initialize(): void
 *     {
 *         // Custom plugin initialization logic
 *         $logger = $this->get_core()->get_logger();
 *         $logger->info('My plugin initialized');
 *     }
 * 
 *     public function get_services(): array
 *     {
 *         return [
 *             'my.service' => new MyService(),
 *             MyApiClient::class => new MyApiClient($this->get_core()),
 *         ];
 *     }
 * }
 * ```
 * 
 * Then initialize your plugin in your main plugin file:
 * 
 * ```php
 * // In your main plugin file (e.g., my-plugin.php)
 * MyPlugin::init_plugin(__FILE__);
 * ```
 * 
 * ## Architecture
 * 
 * This class follows the singleton pattern and acts as a facade to the underlying PluginCore system:
 * 
 * - **Singleton Management**: Ensures only one plugin instance exists
 * - **Core Integration**: Creates and manages the PluginCore instance
 * - **Service Registration**: Allows plugins to register custom services
 * - **Extension Points**: Provides hooks for custom initialization logic
 * 
 * ## Extension Points
 * 
 * Subclasses can override these methods to customize behavior:
 * 
 * - `initialize()`: Custom initialization logic after core setup
 * - `get_services()`: Return array of services to register in DI container
 * - `create_core()`: Override to use a custom PluginCore implementation
 * 
 * @package WebMoves\PluginBase
 * @since 1.0.0
 */
class PluginBase {

	/**
	 * Singleton instance
	 * 
	 * @var PluginBase|null
	 */
	private static ?PluginBase $instance = null;

	/**
	 * The plugin core instance that handles lifecycle management
	 * 
	 * @var PluginCore
	 */
	protected PluginCore $core;

	/**
	 * Initialize the plugin singleton
	 * 
	 * Creates the plugin instance using the singleton pattern. This method should be called
	 * once from your main plugin file to bootstrap the entire plugin system.
	 * 
	 * @param string $plugin_file Absolute path to the main plugin file (__FILE__ from main plugin)
	 * @return static The plugin instance
	 * @throws \LogicException If plugin is already initialized
	 * 
	 * @example
	 * ```php
	 * // In your main plugin file
	 * MyPlugin::init_plugin(__FILE__);
	 * ```
	 */
	/**
	 * Singleton instances per plugin class
	 *
	 * @var array<string, PluginBase>
	 */
	private static array $instances = [];

	// ... existing code ...

	/**
	 * Initialize the plugin singleton
	 *
	 * Creates the plugin instance using the singleton pattern. This method should be called
	 * once from your main plugin file to bootstrap the entire plugin system.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file (__FILE__ from main plugin)
	 * @return static The plugin instance
	 * @throws \LogicException If plugin is already initialized
	 *
	 * @example
	 * ```php
	 * // In your main plugin file
	 * MyPlugin::init_plugin(__FILE__);
	 * ```
	 */
	public static function init_plugin(string $plugin_file): static {
		$class = static::class;
		if(isset(static::$instances[$class])) {
			throw new \LogicException('Plugin already initialized');
		}
		$core = static::create_core($plugin_file);
		static::$instances[$class] = new static($core);
		return static::$instances[$class];
	}

	/**
	 * Get the singleton plugin instance
	 *
	 * @return static The plugin instance
	 * @throws \LogicException If plugin has not been initialized
	 *
	 * @example
	 * ```php
	 * $plugin = MyPlugin::get_instance();
	 * $core = $plugin->get_core();
	 * ```
	 */
	public static function get_instance(): static
	{
		$class = static::class;
		if(!isset(static::$instances[$class])) {
			throw new \LogicException('Plugin not initialized');
		}
		return static::$instances[$class];
	}

	/**
	 * Create the plugin core instance
	 * 
	 * This method can be overridden in subclasses to provide a custom PluginCore implementation.
	 * By default, it creates a DefaultPluginCore instance.
	 * 
	 * @param string $plugin_file Path to the main plugin file
	 * @return PluginCore The plugin core instance
	 * 
	 * @example
	 * ```php
	 * // Override to use custom core
	 * protected static function create_core(string $plugin_file): PluginCore
	 * {
	 *     return new MyCustomPluginCore($plugin_file);
	 * }
	 * ```
	 */
	protected static function create_core(string $plugin_file): PluginCore
	{
		return new DefaultPluginCore($plugin_file);
	}

	/**
	 * Private constructor to enforce singleton pattern
	 *
	 * Initializes the plugin by:
	 * 1. Setting up the core
	 * 2. Initializing the core system
	 * 3. Registering custom services from get_services()
	 * 4. Calling the initialize() extension point
	 *
	 * @param PluginCore $core The plugin core instance
	 */
	private function __construct( PluginCore $core ) {

		$this->core = $core;

		// Register and set the text domain for translations
		$text_domain = $this->core->get_text_domain();
		TranslationManager::register_text_domain($text_domain);
		TranslationManager::set_current_text_domain($text_domain);

		$services = $this->get_services();
		foreach ( $services as $id => $service ) {
			$this->core->set( $id, $service );
		}
		$this->core->initialize();
		$this->initialize();
	}

	/**
	 * Plugin initialization hook
	 * 
	 * Override this method in subclasses to add custom initialization logic.
	 * This is called after the core is initialized and services are registered.
	 * 
	 * @return void
	 * 
	 * @example
	 * ```php
	 * public function initialize(): void
	 * {
	 *     $logger = $this->get_core()->get_logger();
	 *     $logger->info('Custom plugin initialization');
	 *     
	 *     // Add custom WordPress hooks
	 *     add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
	 * }
	 * ```
	 */
	public function initialize(): void
	{
		// Override in child classes
	}

	/**
	 * Get custom services for DI container registration
	 * 
	 * Override this method to return an array of services that should be registered
	 * in the dependency injection container. Services can be keyed by class name,
	 * interface name, or custom string identifiers.
	 * 
	 * @return array Associative array of service ID => service instance
	 * 
	 * @example
	 * ```php
	 * public function get_services(): array
	 * {
	 *     return [
	 *         // By class name
	 *         MyApiClient::class => new MyApiClient($this->get_core()),
	 *         
	 *         // By interface name
	 *         PaymentGatewayInterface::class => new StripeGateway(),
	 *         
	 *         // By custom identifier
	 *         'cache.redis' => new RedisCache(),
	 *         'config.api' => new ApiConfiguration(),
	 *     ];
	 * }
	 * ```
	 */
	public function get_services(): array
	{
		return [];
	}

	/**
	 * Get the plugin core instance
	 * 
	 * Provides access to the underlying PluginCore for advanced operations like
	 * accessing the DI container, configuration, logging, etc.
	 * 
	 * @return PluginCore The plugin core instance
	 * 
	 * @example
	 * ```php
	 * $core = $plugin->get_core();
	 * $logger = $core->get_logger();
	 * $config = $core->get_config();
	 * $container = $core->get_container();
	 * ```
	 */
	public function get_core(): PluginCore
	{
		return $this->core;
	}
}