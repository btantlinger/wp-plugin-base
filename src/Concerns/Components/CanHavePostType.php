<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait CanHavePostType
{
	use TraitRegistrationHelper;

	protected function register_can_have_post_type(): void
	{
		$this->ensure_component_registration();
		add_action('init', [$this, 'register_post_type']);
	}

	public function register_post_type(): void
	{
		register_post_type(
			$this->get_post_type(),
			$this->get_post_type_args()
		);
	}

	abstract protected function get_post_type(): string;
	abstract protected function get_post_type_args(): array;
}
