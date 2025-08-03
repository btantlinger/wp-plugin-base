<?php

namespace WebMoves\PluginBase\Taxonomies;

use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Concerns\HasTranslations;
use WebMoves\PluginBase\Contracts\Taxonomies\Taxonomy;
use WebMoves\PluginBase\Concerns\HasInflector;
use WebMoves\PluginBase\Enums\Lifecycle;

abstract class AbstractTaxonomy extends AbstractComponent implements Taxonomy
{
    use HasInflector;
    use HasTranslations;

    /**
     * Get the taxonomy identifier
     */
    abstract public function get_taxonomy(): string;

    /**
     * Get the post types this taxonomy applies to
     */
    abstract public function get_object_types(): array;

    /**
     * Register on INIT lifecycle
     */
    public function register_on(): Lifecycle
    {
        return Lifecycle::INIT;
    }

    /**
     * Register the taxonomy with WordPress
     */
    public function register(): void
    {
        register_taxonomy(
            $this->get_taxonomy(),
            $this->get_object_types(),
            $this->get_config()
        );
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
     * Override this method to customize your taxonomy
     * This is the main extension point!
     */
    protected function get_args(): array
    {
        return [];
    }

    /**
     * Smart defaults that work for most taxonomies
     */
    private function get_default_config(): array
    {
        return [
            'labels' => $this->generate_default_labels(),
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'hierarchical' => false,
            'rewrite' => ['slug' => $this->get_taxonomy()],
        ];
    }

    /**
     * Generate intelligent default labels from taxonomy name
     */
    private function generate_default_labels(): array
    {
        $taxonomy = $this->get_taxonomy();
        $singular = $this->humanize($taxonomy);
        $plural = $this->pluralize($singular);
        $singular_lower = strtolower($singular);
        $plural_lower = strtolower($plural);

        return [
            'name' => $this->__t($plural),
            'singular_name' => $this->__t($singular),
            'search_items' => $this->__t("Search {$plural}"),
            'popular_items' => $this->__t("Popular {$plural}"),
            'all_items' => $this->__t("All {$plural}"),
            'parent_item' => $this->__t("Parent {$singular}"),
            'parent_item_colon' => $this->__t("Parent {$singular}:"),
            'edit_item' => $this->__t("Edit {$singular}"),
            'view_item' => $this->__t("View {$singular}"),
            'update_item' => $this->__t("Update {$singular}"),
            'add_new_item' => $this->__t("Add New {$singular}"),
            'new_item_name' => $this->__t("New {$singular} Name"),
            'separate_items_with_commas' => $this->__t("Separate {$plural_lower} with commas"),
            'add_or_remove_items' => $this->__t("Add or remove {$plural_lower}"),
            'choose_from_most_used' => $this->__t("Choose from the most used {$plural_lower}"),
            'not_found' => $this->__t("No {$plural_lower} found."),
            'no_terms' => $this->__t("No {$plural_lower}"),
            'items_list_navigation' => $this->__t("{$plural} list navigation"),
            'items_list' => $this->__t("{$plural} list"),
            'most_used' => $this->__t('Most Used'),
            'back_to_items' => $this->__t("← Back to {$plural}"),
        ];
    }

    /**
     * Get the generated singular name for this taxonomy
     */
    public function get_singular_name(): string
    {
        return $this->humanize($this->get_taxonomy());
    }

    /**
     * Get the generated plural name for this taxonomy
     */
    public function get_plural_name(): string
    {
        return $this->pluralize($this->get_singular_name());
    }

    /**
     * Get the REST API endpoint URL for this taxonomy
     * Returns null if REST API is not enabled
     */
    public function get_rest_url(): ?string
    {
        $config = $this->get_config();
        
        if (empty($config['show_in_rest'])) {
            return null;
        }

        $rest_base = $config['rest_base'] ?? $this->get_taxonomy();
        return rest_url("wp/v2/{$rest_base}");
    }
}