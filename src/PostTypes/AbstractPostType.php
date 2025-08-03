<?php

namespace WebMoves\PluginBase\PostTypes;

use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Concerns\HasTranslations;
use WebMoves\PluginBase\Contracts\PostTypes\PostType;
use WebMoves\PluginBase\Concerns\HasInflector;
use WebMoves\PluginBase\Enums\Lifecycle;

abstract class AbstractPostType extends AbstractComponent implements PostType
{
    use HasInflector;
    use HasTranslations;

    /**
     * Get the post type identifier
     */
    abstract public function get_post_type(): string;

    /**
     * Register on INIT lifecycle
     */
    public function register_on(): Lifecycle
    {
        return Lifecycle::INIT;
    }

    /**
     * Register the post type with WordPress
     */
    public function register(): void
    {
        register_post_type($this->get_post_type(), $this->get_config());
    }

    /**
     * Get the complete configuration array
     */
    public function get_config(): array
    {
        // Start with smart defaults
        $config = $this->get_default_config();
        
        // Merge with custom args (this is where the magic happens)
        $custom_args = $this->get_args();
        if (!empty($custom_args)) {
            $config = array_merge($config, $custom_args);
        }

        // Always ensure labels are properly merged
        if (isset($custom_args['labels'])) {
            $config['labels'] = array_merge(
                $this->generate_default_labels(),
                $custom_args['labels']
            );
        }

        return $config;
    }

    /**
     * Override this method to customize your post type
     * This is the main extension point!
     */
    protected function get_args(): array
    {
        return [];
    }

    /**
     * Smart defaults that work for most post types
     */
    private function get_default_config(): array
    {
        return [
            'labels' => $this->generate_default_labels(),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'hierarchical' => false,
        ];
    }

    /**
     * Generate intelligent default labels from post type name
     */
    private function generate_default_labels(): array
    {
        $post_type = $this->get_post_type();
        $singular = $this->humanize($post_type);
        $plural = $this->pluralize($singular);
        $singular_lower = strtolower($singular);
        $plural_lower = strtolower($plural);

        return [
            'name' => $this->__t($plural),
            'singular_name' => $this->__t($singular),
            'menu_name' => $this->__t($plural),
            'add_new' => $this->__t('Add New'),
            'add_new_item' => $this->__t("Add New {$singular}"),
            'edit_item' => $this->__t("Edit {$singular}"),
            'view_item' => $this->__t("View {$singular}"),
            'all_items' => $this->__t("All {$plural}"),
            'search_items' => $this->__t("Search {$plural}"),
            'not_found' => $this->__t("No {$plural_lower} found."),
            'not_found_in_trash' => $this->__t("No {$plural_lower} found in Trash."),
        ];
    }

    /**
     * Get the generated singular name for this post type
     */
    public function get_singular_name(): string
    {
        return $this->humanize($this->get_post_type());
    }

    /**
     * Get the generated plural name for this post type
     */
    public function get_plural_name(): string
    {
        return $this->pluralize($this->get_singular_name());
    }

    /**
     * Get the REST API endpoint URL for this post type
     */
    public function get_rest_url(): ?string
    {
        $config = $this->get_config();
        
        if (empty($config['show_in_rest'])) {
            return null;
        }

        $rest_base = $config['rest_base'] ?? $this->get_post_type();
        return rest_url("wp/v2/{$rest_base}");
    }
}