<?php

namespace WebMoves\PluginBase;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Configuration\ConfigurationManager;
use WebMoves\PluginBase\Contracts\Components\ComponentInterface;
use WebMoves\PluginBase\Contracts\Components\ComponentManagerInterface;
use WebMoves\PluginBase\Contracts\Configuration\ConfigurationManagerInterface;
use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;
use WebMoves\PluginBase\Enums\Lifecycle;
use WebMoves\PluginBase\Logging\LoggerFactory;

class PluginCore implements PluginCoreInterface
{
    private Container $container;
	private ConfigurationManagerInterface $config;

	private string $plugin_file;
    private string $plugin_version;
    private ?string $database_version;
    private string $plugin_name;
    private bool $initialized = false;
    /**
     * The plugin text domain
     *
     * @var string
     */
    private string $text_domain;

    /**
     * Constructor
     *
     * @param string $plugin_file The main plugin file path
     * @param string $plugin_version The plugin version
     * @param string|null $text_domain The plugin text domain (optional, will be derived from plugin name if not provided)
     */
    public function __construct(string $plugin_file, string $plugin_version, ?string $text_domain = null, ?string $database_version = null)
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_version = $plugin_version;
        $this->database_version = $database_version;
        $this->plugin_name = $this->extract_plugin_name($plugin_file);
        $this->text_domain = $text_domain ?? $this->derive_text_domain();

	    $this->config = new ConfigurationManager($this);

	    $this->setup_container();
    }

    /**
     * Extract plugin name from file path or use default
     *
     * @param string $plugin_file
     * @return string
     */
    private function extract_plugin_name(string $plugin_file): string
    {
        $plugin_dir = dirname($plugin_file);
        $plugin_name = basename($plugin_dir);
        
        // If we're in the plugins directory, use the directory name
        if (strpos($plugin_dir, 'plugins') !== false) {
            return $plugin_name;
        }

        // Fallback to 'plugin-base'
        return 'plugin-base';
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

        // Load textdomain early for translations
        add_action('init', [$this, 'load_textdomain']);

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
		// Get plugin info from the calling file
		$plugin_file = WP_UNINSTALL_PLUGIN ? WP_UNINSTALL_PLUGIN : '';
		if (empty($plugin_file)) {
			return;
		}

		// We need to recreate the plugin instance for uninstall
		// This is a limitation of WordPress uninstall hooks - they run in isolation
		try {
			// Try to get plugin data to recreate instance
			if (!function_exists('get_plugin_data')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin_data = get_plugin_data($plugin_file);
			$version = $plugin_data['Version'] ?? '1.0.0';
			$text_domain = $plugin_data['TextDomain'] ?? null;

			// Create temporary instance for uninstall
			$instance = new self($plugin_file, $version, $text_domain);
			$instance->on_lifecycle(Lifecycle::UNINSTALL);

			// Clean up installation marker
			delete_option($instance->get_installation_option_key());

		} catch (\Exception $e) {
			// Log error if possible, but don't break uninstall
			error_log("Plugin uninstall error: " . $e->getMessage());
		}
	}


	/**
	 * Get the option key used to track plugin installation
	 */
	private function get_installation_option_key(): string
	{
		return sanitize_key($this->get_name() . '_installed');
	}



	/**
     * Universal lifecycle handler
     */
    public function on_lifecycle(Lifecycle $lifecycle): void
    {
        $this->get_logger()->info(
            $this->get_name() . ' on_' . $lifecycle->value, 
            ['version' => $this->get_version(), 'lifecycle' => $lifecycle->value]
        );


        // Initialize components for this lifecycle
        $this->initialize_components_for_lifecycle($lifecycle);

        // Fire custom hook for extensibility
        $hook = $this->get_hook_prefix() . '_' . $lifecycle->value;
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
        $builder = new ContainerBuilder();
        $builder->useAutowiring(false);
        $builder->useAttributes(false);

		// Core dependencies required for the plugin to function
        $builder->addDefinitions([
            'plugin.file' => $this->plugin_file,
            'plugin.version' => $this->plugin_version,
            'plugin.database_version' => $this->database_version,
            'plugin.name' => $this->plugin_name,
            'plugin.text_domain' => $this->text_domain,
            'plugin.path' => plugin_dir_path($this->plugin_file),
            'plugin.url' => plugin_dir_url($this->plugin_file),
            // Add PluginCore instance to the container definitions
            PluginCoreInterface::class => $this,
            ConfigurationManagerInterface::class => $this->config,
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
			$this->register_component($component);;
		}
    }

	/**
	 * Get the configuration manager
	 */
	public function get_config(): ConfigurationManagerInterface
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
         * @var $component_manager ComponentManagerInterface
         */
        $component_manager = $this->get(ComponentManagerInterface::class);
        $component_manager->initialize_components_for_lifecycle($lifecycle);
    }




    /**
     * Register a service in the container
     *
     * @param string $id Service identifier
     * @param mixed $value Service instance or factory
     * @return void
     */
    public function set(string $id, mixed $value, bool $auto_register_components=true): void
    {
        $this->container->set($id, $value);
        $object = $this->container->get($id);
        if($auto_register_components && $object instanceof ComponentInterface) {
            $this->register_component($object);
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
     * Register a component
     *
     * @param \WebMoves\PluginBase\Contracts\Components\ComponentInterface $component
     *
     * @return void
     */
    public function register_component(ComponentInterface $component): void
    {
        /**
         * @var $component_manager ComponentManagerInterface
         */
        $component_manager = $this->get(ComponentManagerInterface::class);
        $component_manager->register_component($component);
    }

    /**
     * Check if the given component is registered.
     *
     * @param ComponentInterface $component The component to check.
     *
     * @return bool True if the component is registered, false otherwise.
     */
    public function is_registered(ComponentInterface $component): bool
    {
        /**
         * @var $component_manager ComponentManagerInterface
         */
        $component_manager = $this->get(ComponentManagerInterface::class);
        return $component_manager->is_registered($component);
    }

    /**
     * Handle plugins_loaded action (for backward compatibility and database handling)
     *
     * @return void
     */
    public function on_plugins_loaded(): void
    {
        $this->get_logger()->info($this->get_name() . ' on_plugins_loaded', ['version' => $this->get_version()]);
        
        // Handle database upgrades
        $database_manager = $this->get(DatabaseManagerInterface::class);
        $database_manager->maybe_upgrade();
    }

    public function get_hook_prefix(): string
    {
        return sanitize_title_with_dashes($this->get_name());
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
            $logger = LoggerFactory::createLogger($this->get_name(), $this->get_plugin_file(), $channel);
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
        return $this->plugin_version;
    }

    public function get_database_version(): ?string
    {
        return $this->database_version;
    }

    /**
     * Get plugin name
     *
     * @return string
     */
    public function get_name(): string
    {
        return $this->plugin_name;
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

    /**
     * Get the plugin text domain
     *
     * @return string
     */
    public function get_text_domain(): string
    {
        return $this->text_domain;
    }

    /**
     * Derive text domain from plugin name
     * Converts plugin name to lowercase, replaces spaces with hyphens
     *
     * @return string
     */
    private function derive_text_domain(): string
    {
        return sanitize_title($this->plugin_name);
    }

    /**
     * Load plugin textdomain for translations
     *
     * @return void
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            $this->text_domain,
            false,
            dirname(plugin_basename($this->plugin_file)) . '/languages'
        );
    }
}