<?php

namespace WebMoves\PluginBase\Templates;

use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Contracts\Templates\TemplateRenderer;

class DefaultTemplateRenderer implements TemplateRenderer
{
    private string $template_dir;
    private array $global_data = [];

    public function __construct(PluginCore $core)
    {

		$cont = $core->get_container();
		if($cont->has('plugin.path')) {
			$this->template_dir = $cont->get('plugin.path') . 'templates';
		} else {
			$this->template_dir = $this->get_default_template_dir();
		}
		try {
			$this->set_global_data( [
				'text_domain'    => $cont->get( 'plugin.text_domain' ),
				'plugin_url'     => $cont->get( 'plugin.url' ),
				'plugin_path'    => $cont->get( 'plugin.path' ),
				'plugin_version' => $cont->get( 'plugin.version' ),
				'plugin_name'    => $cont->get( 'plugin.name' ),
			] );
		} catch (\Exception $e) {}
    }


    /**
     * Get the default template directory
     */
    private function get_default_template_dir(): string
    {
        return plugin_dir_path(__DIR__) . '../templates';
    }

    /**
     * Set global data available to all templates
     */
    public function set_global_data(array $data): void
    {
        $this->global_data = array_merge($this->global_data, $data);
    }

    /**
     * Render a template and return the output
     */
    public function render(string $template, array $data = []): string
    {
        ob_start();
        $this->display($template, $data);
        return ob_get_clean();
    }

    /**
     * Display a template directly
     */
    public function display(string $template, array $data = []): void
    {
        $template_path = $this->get_template_path($template);

        if (!$template_path) {
            wp_die("Templates not found: {$template}");
        }

        // Merge global data with local data
        $template_data = array_merge($this->global_data, $data);

        // Extract variables into local scope
        extract($template_data, EXTR_SKIP);

        // Include the template
        include $template_path;
    }

    /**
     * Check if a template exists
     */
    public function exists(string $template): bool
    {
        return $this->get_template_path($template) !== false;
    }

    /**
     * Get the full path to a template file
     */
    private function get_template_path(string $template): string|false
    {
        // Allow template overrides in theme
        //$theme_template = get_stylesheet_directory() . '/duffells-sync/' . $template . '.php';
        //if (file_exists($theme_template)) {
        //    return $theme_template;
        //}

        // Check plugin templates
        $plugin_template = $this->template_dir . '/' . $template . '.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return false;
    }

    /**
     * Include a partial template
     */
    public function partial(string $template, array $data = []): void
    {
        $this->display($template, $data);
    }
}