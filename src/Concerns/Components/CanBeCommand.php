<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait CanBeCommand {

	use TraitRegistrationHelper;

	protected function register_can_be_command(): void {

		$this->ensure_component_registration();

		if (class_exists('WP_CLI')) {
			\WP_CLI::add_command(
				$this->get_command_name(),
				[$this, 'execute_command'],
				[
					'shortdesc' => $this->get_command_description(),
					'synopsis' => $this->get_command_synopsis()
				]
			);
		}
	}

	abstract protected function execute_command($args, $assoc_args): void;
	abstract protected function get_command_name(): string;
	abstract protected function get_command_description(): string;

	protected function get_command_synopsis(): array {
		return [];
	}
}