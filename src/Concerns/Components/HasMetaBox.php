<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait HasMetaBox
{
	use TraitRegistrationHelper;

	protected function register_has_meta_box(): void
	{
		$this->ensure_component_registration();

		add_action('add_meta_boxes', [$this, 'add_meta_box']);
		add_action('save_post', [$this, 'save_meta_box']);
	}

	public function add_meta_box(): void
	{
		add_meta_box(
			$this->get_meta_box_id(),
			$this->get_meta_box_title(),
			[$this, 'render_meta_box'],
			$this->get_meta_box_screen(),
			$this->get_meta_box_context(),
			$this->get_meta_box_priority()
		);
	}

	abstract protected function render_meta_box($post): void;
	abstract protected function save_meta_box($post_id): void;
	abstract protected function get_meta_box_id(): string;
	abstract protected function get_meta_box_title(): string;

	protected function get_meta_box_screen(): string { return 'post'; }
	protected function get_meta_box_context(): string { return 'normal'; }
	protected function get_meta_box_priority(): string { return 'default'; }
}