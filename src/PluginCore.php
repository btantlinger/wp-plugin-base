<?php

namespace WebMoves\PluginBase;

use DI\Container;
use DI\ContainerBuilder;
use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;
use WebMoves\PluginBase\Contracts\Hooks\ComponentInterface;
use WebMoves\PluginBase\Contracts\Hooks\ComponentManagerInterface;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;

class PluginCore implements PluginCoreInterface
{
    private Container $container;
    private string $plugin_file;
    private string $plugin_version;
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
    public function __construct(string $plugin_file, string $plugin_version, ?string $text_domain = null)
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_version = $plugin_version;
        $this->plugin_name = $this->extract_plugin_name($plugin_file);
        $this->text_domain = $text_domain ?? $this->derive_text_domain();
        
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

        // Register core WordPress hooks
        add_action('plugins_loaded', [$this, 'on_plugins_loaded']);
        add_action('init', [$this, 'on_init']);
        add_action('admin_init', [$this, 'on_admin_init']);

        // Register activation/deactivation hooks
        register_activation_hook($this->plugin_file, [$this, 'on_activation']);
        register_deactivation_hook($this->plugin_file, [$this, 'on_deactivation']);

        $this->initialized = true;
    }

	public function get_plugin_base_dir(): string {
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
        
        // 1. Add plugin-specific definitions FIRST (foundation values)
        $builder->addDefinitions([
            'plugin.file' => $this->plugin_file,
            'plugin.version' => $this->plugin_version,
            'plugin.name' => $this->plugin_name,
            'plugin.text_domain' => $this->text_domain,
            'plugin.path' => plugin_dir_path($this->plugin_file),
            'plugin.url' => plugin_dir_url($this->plugin_file),
            // Add PluginCore instance to the container definitions
            PluginCoreInterface::class => $this,
            PluginCore::class => $this,
        ]);

        // Load core framework dependencies
        $core_dependencies_file = rtrim($this->get_plugin_base_dir(), '/') . '/config/core-dependencies.php';
        if (file_exists($core_dependencies_file)) {
            $core_dependencies = require $core_dependencies_file;
            $builder->addDefinitions($core_dependencies);
        } else {
			throw new \Exception('Plugin core dependencies file not found');
        }

        // Load user dependencies last
        $user_dependencies_file = rtrim($this->get_plugin_base_dir(), '/') . '/config/dependencies.php';
        if (file_exists($user_dependencies_file)) {
            $user_dependencies = require $user_dependencies_file;
            $builder->addDefinitions($user_dependencies);
        } else {
			throw new \Exception('Plugin dependencies file not found');
        }

        // Build the container with all definitions
        $this->container = $builder->build();
    }

    /**
     * Register a service in the container
     *
     * @param string $id Service identifier
     * @param mixed $value Service instance or factory
     * @return void
     */
    public function register_service(string $id, $value): void
    {
        $this->container->set($id, $value);
    }

    /**
     * Get a service from the container
     *
     * @param string $id Service identifier
     * @return mixed
     */
    public function get_service(string $id)
    {
		return $this->container->get( $id );
    }

    /**
     * Register an event handler
     *
     * @param \WebMoves\PluginBase\Contracts\Hooks\ComponentInterface $handler
     *
     * @return void
     */
    public function register_component( ComponentInterface $handler): void
    {
        $component_manager = $this->get_service(ComponentManagerInterface::class);
        $component_manager->register_component($handler);
    }

    /**
     * Register multiple event handlers
     *
     * @param ComponentInterface[] $handlers
     *
     * @return void
     */
    public function register_handlers(array $handlers): void
    {
        foreach ($handlers as $handler) {
            $this->register_component($handler);
        }
    }

    /**
     * Handle plugins_loaded action
     *
     * @return void
     */
    public function on_plugins_loaded(): void
    {
        $database_manager = $this->get_service(DatabaseManagerInterface::class);
        $handler_manager = $this->get_service(ComponentManagerInterface::class);

        $database_manager->maybe_upgrade();
        $handler_manager->initialize_handlers();
    }

    /**
     * Handle init action
     *
     * @return void
     */
    public function on_init(): void
    {
        // Plugin initialization logic
        do_action('plugin_base_init', $this);
    }

    /**
     * Handle admin_init action
     *
     * @return void
     */
    public function on_admin_init(): void
    {
        // Admin initialization logic
        do_action('plugin_base_admin_init', $this);
    }

    /**
     * Handle plugin activation
     *
     * @return void
     */
    public function on_activation(): void
    {
        $database_manager = $this->get_service(DatabaseManagerInterface::class);
        $database_manager->create_tables();
        do_action('plugin_base_activation', $this);
    }

    /**
     * Handle plugin deactivation
     *
     * @return void
     */
    public function on_deactivation(): void
    {
        do_action('plugin_base_deactivation', $this);
    }

    /**
     * Get the container instance
     *
     * @return Container
     */
    public function get_container(): Container
    {
        return $this->container;
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