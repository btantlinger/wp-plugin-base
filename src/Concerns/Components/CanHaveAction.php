<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait CanHaveAction
{
	use TraitRegistrationHelper;

	protected function register_can_have_action(): void
	{
		$this->ensure_component_registration();

		add_action(
			$this->get_action_hook(),
			[$this, 'execute_action'],
			$this->get_action_priority(),
			$this->get_action_accepted_args()
		);
	}

	/**
	 * Get the action hook name
	 */
	abstract protected function get_action_hook(): string;

	/**
	 * Get the action priority
	 */
	protected function get_action_priority(): int
	{
		return 10;
	}

	/**
	 * Get the number of accepted arguments
	 */
	protected function get_action_accepted_args(): int
	{
		return 1;
	}

	/**
	 * Execute the action - override this in your component
	 */
	abstract public function execute_action(...$args): void;
}