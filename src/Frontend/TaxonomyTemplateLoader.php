<?php

namespace WebMoves\PluginBase\Frontend;

use WebMoves\PluginBase\Contracts\Taxonomies\Taxonomy;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;

class TaxonomyTemplateLoader extends AbstractTemplateLoader {

	protected Taxonomy $taxonomy;

	public function __construct(PluginMetadata $metadata, Taxonomy $taxonomy)
	{
		parent::__construct($metadata);
		$this->taxonomy = $taxonomy;
	}

	/**
	 * @inheritDoc
	 */
	protected function should_handle_current_context(): bool
	{
		$taxonomy = $this->taxonomy->get_taxonomy();
        
        if (is_tax($taxonomy) || (is_category() && $taxonomy === 'category') || (is_tag() && $taxonomy === 'post_tag')) {
            return true;
        }
        
        return false;
	}

	protected function get_template_names(): array 
    {
        $taxonomy = $this->taxonomy->get_taxonomy();
        $templates = [];
        
        if (is_tax($taxonomy) || is_category() || is_tag()) {
            $term = get_queried_object();
            
            if ($term && isset($term->slug, $term->term_id)) {
                // Most specific to least specific for taxonomy terms
                $templates = [
                    "taxonomy-{$taxonomy}-{$term->slug}.php",     // taxonomy-property_category-residential.php
                    "taxonomy-{$taxonomy}-{$term->term_id}.php",  // taxonomy-property_category-123.php
                    "taxonomy-{$taxonomy}.php",                   // taxonomy-property_category.php
                ];
                
                // Add fallback for built-in taxonomies
                if ($taxonomy === 'category') {
                    $templates[] = 'category.php';
                } elseif ($taxonomy === 'post_tag') {
                    $templates[] = 'tag.php';
                }
                
                // General taxonomy fallback
                $templates[] = 'taxonomy.php';
            }
        }
        
        return $templates;
    }

	protected function get_template_path(): string 
    {
        return 'taxonomies';
    }
}