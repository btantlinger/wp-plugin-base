<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait HasSchedule {

	use TraitRegistrationHelper;

	protected function register_has_schedule(): void {

		$this->ensure_component_registration();

		$hook = $this->get_schedule_hook();

		add_action($hook, [$this, 'execute_schedule']);

		if (!wp_next_scheduled($hook)) {
			wp_schedule_event(
				$this->get_schedule_start_time(),
				$this->get_schedule_recurrence(),
				$hook
			);
		}
	}

	abstract protected function execute_schedule(): void;
	abstract protected function get_schedule_hook(): string;
	abstract protected function get_schedule_recurrence(): string;
	abstract protected function get_schedule_start_time(): int;

}