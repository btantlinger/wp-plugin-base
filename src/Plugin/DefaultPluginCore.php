<?php

namespace WebMoves\PluginBase\Plugin;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Components\DefaultComponentManager;
use WebMoves\PluginBase\Configuration\DefaultConfiguration;
use WebMoves\PluginBase\Contracts\Components\Component;
use WebMoves\PluginBase\Contracts\Components\ComponentManager;
use WebMoves\PluginBase\Contracts\Configuration\Configuration;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Enums\Lifecycle;
use WebMoves\PluginBase\Logging\LoggerFactory;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;

use wpdb;

class DefaultPluginCore implements PluginCore
{
    private Container $container;

	private Configuration $config;

	private ComponentManager $component_manager;

	private string $plugin_file;

	private PluginMetadata $metadata;

    private bool $initialized = false;
    /**
     * The plugin text domain
     *
     * @var string
     */
    //private string $text_domain;

    /**
     * Constructor
     *
     * @param string $plugin_file The main plugin file path
     */
    public function __construct(string $plugin_file)
    {
	    $this->plugin_file = $plugin_file;
	    $this->metadata = new DefaultPluginMetadata($plugin_file);
	    $this->config = new DefaultConfiguration($this);
		$this->component_manager = new DefaultComponentManager();
	    $this->setup_container();

    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // Register lifecycle hooks dynamically
	    $this->setup_plugin_management_hooks();
        $this->setup_lifecycle_hooks();

        // Register activation/deactivation hooks
        register_activation_hook($this->plugin_file, function() {
            $this->on_lifecycle(Lifecycle::ACTIVATE);
        });
        register_deactivation_hook($this->plugin_file, function() {
            $this->on_lifecycle(Lifecycle::DEACTIVATE);
        });

        $this->initialized = true;
    }

    /**
     * Setup WordPress lifecycle hooks dynamically using enum configuration
     */
    private function setup_lifecycle_hooks(): void
    {
        // Register hooks for all runtime lifecycles
        foreach (Lifecycle::getRuntimeLifecycles() as $lifecycle) {
            $hookName = $lifecycle->getHookName();
            $priority = $lifecycle->getHookPriority();
            
            // Use closure to capture the lifecycle enum
            add_action($hookName, function() use ($lifecycle) {
                $this->on_lifecycle($lifecycle);
            }, $priority);
        }

        // Special handling for plugins_loaded (both bootstrap and database handling)
        add_action('plugins_loaded', [$this, 'on_plugins_loaded'], 10);
    }


	/**
	 * Setup plugin management hooks (install, activate, deactivate, uninstall)
	 */
	private function setup_plugin_management_hooks(): void
	{
		// Plugin activation hook
		register_activation_hook($this->plugin_file, function() {
			// Handle install lifecycle on first activation
			if (!get_option($this->get_installation_option_key(), false)) {
				$this->on_lifecycle(Lifecycle::INSTALL);
				add_option($this->get_installation_option_key(), true);
			}

			// Always handle activation
			$this->on_lifecycle(Lifecycle::ACTIVATE);
		});

		// Plugin deactivation hook
		register_deactivation_hook($this->plugin_file, function() {
			$this->on_lifecycle(Lifecycle::DEACTIVATE);
		});

		// Plugin uninstall hook
		register_uninstall_hook($this->plugin_file, [self::class, 'handle_uninstall']);
	}

	/**
	 * Static method to handle uninstall (required by WordPress)
	 * This creates a new instance to handle the uninstall lifecycle
	 */
	public static function handle_uninstall(): void
	{
		// Safety check - only run during actual WordPress uninstall
		if (!defined('WP_UNINSTALL_PLUGIN')) {
			return;
		}

		// Get plugin info from the calling context
		$plugin_file = WP_UNINSTALL_PLUGIN;
		if (empty($plugin_file)) {
			return;
		}

		try {
			// Create instance normally (not "temporary" - this is the real uninstall)
			$instance = new self($plugin_file);

			$instance->on_lifecycle(Lifecycle::UNINSTALL);


			// Clean up any plugin-level options
			delete_option($instance->get_installation_option_key());

		} catch (\Exception $e) {
			error_log("Plugin uninstall error: " . $e->getMessage());
		}
	}

	/**
	 * Get the option key used to track plugin installation
	 */
	private function get_installation_option_key(): string
	{
		return sanitize_key( $this->metadata->get_prefix() . 'installed');
	}

	/**
     * Universal lifecycle handler
     */
    public function on_lifecycle(Lifecycle $lifecycle): void
    {
        $this->get_logger()->info(
	        $this->get_plugin_name() . ' on_' . $lifecycle->value,
            ['version' => $this->get_version(), 'lifecycle' => $lifecycle->value]
        );


        // Initialize components for this lifecycle
        $this->initialize_components_for_lifecycle($lifecycle);

        // Fire custom hook for extensibility
        $hook = $this->get_metadata()->get_prefix() . $lifecycle->value;
        do_action($hook, $this, $lifecycle);
    }

    public function get_plugin_base_dir(): string
    {
        return dirname($this->plugin_file);
    }

    /**
     * Setup the DI container
     *
     * @return void
     */
    private function setup_container(): void
    {
		global $wpdb;

        $builder = new ContainerBuilder();
        $builder->useAutowiring(false);
        $builder->useAttributes(false);

		// Core dependencies required for the plugin to function
        $builder->addDefinitions([
	        'plugin.file'                => $this->plugin_file,
	        'plugin.version'             => $this->metadata->get_version(),
	        'plugin.name'                => $this->metadata->get_name(),
	        'plugin.text_domain'         => $this->metadata->get_text_domain(),
	        'plugin.path'                => plugin_dir_path($this->plugin_file),
	        'plugin.url'                 => plugin_dir_url($this->plugin_file),
            // Add DefaultPluginCore instance to the container definitions
	        PluginCore::class            => $this,
	        ComponentManager::class      => $this->component_manager,
	        PluginMetadata::class => $this->metadata, // Available for injection
	        Configuration::class         => $this->config,
	        wpdb::class                  => $wpdb,
        ]);

	    $services = $this->config->getServices();
	    if (is_array($services)) {
			$builder->addDefinitions($services);
	    }

		$components = $this->config->getComponents();
		if (is_array($components)) {
			$builder->addDefinitions($components);
		}
        $this->container = $builder->build();

		foreach($components as $id => $component) {
			$component = $this->container->get($id);
			$this->get_component_manager()->add($component);
		}
    }

	/**
	 * Get the configuration manager
	 */
	public function get_config(): Configuration
	{
		return $this->config;
	}

	/**
	 * Get configuration value using dot notation
	 */
	public function config(string $key, $default = null)
	{
		return $this->config->get($key, $default);
	}

	/**
     * Initialize components for a specific lifecycle
     */
    private function initialize_components_for_lifecycle(Lifecycle $lifecycle): void
    {
        /**
         * @var $component_manager ComponentManager
         */
        $component_manager = $this->get(ComponentManager::class);
        $component_manager->initialize_components_for_lifecycle($lifecycle);
    }


    /**
     * Register a service in the container
     *
     * @param string $id Service identifier
     * @param mixed $value Service instance or factory
     * @return void
     */
    public function set(string $id, mixed $value, bool $auto_add_components=true): void
    {
        $this->container->set($id, $value);
        $object = $this->container->get($id);
        if( $auto_add_components && $object instanceof Component) {
			$this->get_component_manager()->add($object);
        }
    }

    /**
     * Get a service from the container
     *
     * @param string $id Service identifier
     * @return mixed
     */
    public function get(string $id)
    {
        return $this->container->get($id);
    }


    /**
     * Handle plugins_loaded action (for backward compatibility and database handling)
     *
     * @return void
     */
    public function on_plugins_loaded(): void
    {
        $this->get_logger()->info( $this->get_plugin_name() . ' on_plugins_loaded', [ 'version' => $this->get_version()]);
    }


    public function get_logger(?string $channel=null): LoggerInterface
    {
        $logger = null;
        try {
            if(empty($channel)) {
                $channel = 'default';
            }
            $logger = $this->get_container()->get("logger.$channel");
        } catch (\Exception $e) {

        }

        if(!$logger) {
			$factory = new LoggerFactory($this->config, $this->get_plugin_name());
            $logger = $factory->create($channel);
        }
        return $logger;
    }

    /**
     * Get the container instance
     *
     * @return Container
     */
    public function get_container(): ContainerInterface
    {
        return $this->container;
    }

    public function get_db(): \wpdb
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function get_version(): string
    {
        return $this->metadata->get_version();
    }

    /**
     * Get plugin name
     *
     * @return string
     */
    public function get_plugin_name(): string
    {
        return $this->metadata->get_name();
    }

	/**
	 * Get the plugin text domain
	 *
	 * @return string
	 */
	public function get_text_domain(): string
	{
		return $this->metadata->get_text_domain();
	}

    /**
     * Get plugin file path
     *
     * @return string
     */
    public function get_plugin_file(): string
    {
        return $this->plugin_file;
    }

	public function get_metadata(): PluginMetadata
	{
		return $this->metadata;
	}

	public function get_component_manager(): ComponentManager {
		return $this->component_manager;
	}
}