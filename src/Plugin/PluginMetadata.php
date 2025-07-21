<?php

namespace WebMoves\PluginBase\Plugin;

class PluginMetadata
{
    private array $data;
    private string $plugin_file;
    
    public function __construct(string $plugin_file)
    {
        $this->plugin_file = $plugin_file;
        $this->load_plugin_data();
    }
    
    private function load_plugin_data(): void
    {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $this->data = get_plugin_data($this->plugin_file, false, false);
    }
    
    public function get_name(): string
    {
        return $this->data['Name'] ?? $this->plugin_file;
    }

	public function get_file(): string
	{
		return $this->plugin_file;
	}
    
    public function get_version(): string
    {
        return $this->data['Version'] ?? '1.0.0';
    }
    
    public function get_text_domain(): string
    {
        return $this->data['TextDomain'] ?: $this->derive_text_domain();
    }
    
    public function get_description(): string
    {
        return $this->data['Description'] ?? '';
    }
    
    public function get_author(): string
    {
        return $this->data['Author'] ?? '';
    }
    
    public function get_database_version(): ?string
    {
        return $this->data['DatabaseVersion'] ?? null;
    }
    
    public function get_plugin_uri(): string
    {
        return $this->data['PluginURI'] ?? '';
    }
    
    public function get_author_uri(): string
    {
        return $this->data['AuthorURI'] ?? '';
    }
    
    public function get_requires_wp(): string
    {
        return $this->data['RequiresWP'] ?? '';
    }
    
    public function get_requires_php(): string
    {
        return $this->data['RequiresPHP'] ?? '8.3';
    }
    
    public function get_network(): bool
    {
        return $this->data['Network'] ?? false;
    }
    
    public function get_domain_path(): string
    {
        return $this->data['DomainPath'] ?? '/languages';
    }
    
    public function get_update_uri(): string
    {
        return $this->data['UpdateURI'] ?? '';
    }
    
    public function get_requires_plugins(): array
    {
        $requires = $this->data['RequiresPlugins'] ?? '';
        return empty($requires) ? [] : array_map('trim', explode(',', $requires));
    }
    
    public function get_all_data(): array
    {
        return $this->data;
    }
    
    public function get_plugin_file(): string
    {
        return $this->plugin_file;
    }
    
    public function get_plugin_slug(): string
    {
        return dirname(plugin_basename($this->plugin_file));
    }
    
    public function get_plugin_basename(): string
    {
        return plugin_basename($this->plugin_file);
    }
    
    private function derive_text_domain(): string
    {
        $plugin_dir = dirname(plugin_basename($this->plugin_file));
        return sanitize_key($plugin_dir !== '.' ? $plugin_dir : pathinfo($this->plugin_file, PATHINFO_FILENAME));
    }
}