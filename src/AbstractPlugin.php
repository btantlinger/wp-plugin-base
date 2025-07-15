<?php

namespace WebMoves\PluginBase;

use WebMoves\PluginBase\Contracts\PluginCoreInterface;

abstract class AbstractPlugin {

	private static ?AbstractPlugin $instance = null;

	protected PluginCoreInterface $core;

	public static function init_plugin(string $plugin_file, string $plugin_version, ?string $text_domain = null): static
	{
		if(!is_null(static::$instance)) {
			throw new \LogicException('Plugin already initialized');
		}

		$core = static::create_core($plugin_file, $plugin_version, $text_domain);
		static::$instance = new static($core);
		return static::$instance;
	}

	public static function get_instance(): static
	{
		if(is_null(static::$instance)) {
			throw new \LogicException('Plugin not initialized');
		}
		return static::$instance;
	}

	protected static function create_core(string $plugin_file, string $plugin_version, ?string $text_domain = null): PluginCoreInterface
	{
		return new PluginCore($plugin_file, $plugin_version, $text_domain);
	}

	private function __construct(PluginCoreInterface $core)
	{
		$this->core = $core;
		$this->core->initialize();
		$services = $this->get_services();
		foreach($services as $id => $service) {
			$this->core->register_service($id, $service);
		}
		$this->initialize();
	}

	public  function initialize(): void
	{

	}

	/**
	 * Get service definitions for the DI container
	 *
	 * @return array<string, mixed> Associative array of service ID => service definition pairs
	 *
	 * Services that implement ComponentInterface will be automatically registered as components.
	 *
	 * Service definitions can be:
	 * - A class name string (will be autowired): 'MyService::class'
	 * - An object instance: 'new MyService()'
	 * - A callable/closure: 'fn() => new MyService($config)'
	 * - A DI factory function: 'DI\create(MyService::class)'
	 * - A DI autowire function: 'DI\autowire(MyService::class)'
	 *
	 * @example
	 * return [
	 *     'logger' => LoggerFactory::class,
	 *     'api.client' => DI\create(ApiClient::class)->constructor(DI\get('logger')),
	 *     'admin.menu' => AdminMenuHandler::class, // Will auto-register as component
	 *     'sync.scheduler' => SyncScheduler::class, // Will auto-register as component
	 * ];
	 */
	public function get_services(): array
	{
		return [];
	}

	public function get_core(): PluginCoreInterface
	{
		return $this->core;
	}
}