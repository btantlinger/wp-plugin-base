<?php

namespace WebMoves\PluginBase\Frontend;

use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\PostTypes\PostType;


class PostTypeTemplateLoader extends AbstractTemplateLoader {

	protected PostType $post_type;

	public function __construct(PluginMetadata $metadata, PostType $post_type)
	{
		parent::__construct($metadata);
		$this->post_type = $post_type;
	}

	/**
	 * @inheritDoc
	 */
	protected function should_handle_current_context(): bool
	{
		$post_type = $this->post_type->get_post_type();
        
        if (is_singular($post_type) || is_post_type_archive($post_type)) {
            return true;
        }
        
        return false;
	}

	/**
	 * @inheritDoc
	 */
	protected function get_template_names(): array 
    {
        $post_type = $this->post_type->get_post_type();
        $templates = [];
        
        if (is_singular($post_type)) {
            $post_id = get_the_ID();
            $post_slug = get_post_field('post_name', $post_id);
            
            // Most specific to least specific for single posts
            $templates = [
                "single-{$post_type}-{$post_slug}.php",  // single-estate_property-luxury-downtown-loft.php
                "single-{$post_type}-{$post_id}.php",    // single-estate_property-123.php
                "single-{$post_type}.php",               // single-estate_property.php
            ];
            
        } else if (is_post_type_archive($post_type)) {
            // Check if post type supports archives
            $config = $this->post_type->get_config();
            $has_archive = $config['has_archive'] ?? false;
            
            if ($has_archive) {
                $templates = [
                    "archive-{$post_type}.php",  // archive-estate_property.php
                ];
            }
        }
        
        return $templates;
    }

	/**
	 * @inheritDoc
	 */
	protected function get_template_path(): string {
		return 'post-types';
	}
}