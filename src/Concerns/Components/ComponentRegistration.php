<?php
namespace WebMoves\PluginBase\Concerns\Components;

trait ComponentRegistration {
	private bool $traits_initialized = false;

	final public function register(): void {
		if ($this->traits_initialized || !$this->can_register()) {
			return;
		}

		$this->before_register();
		$this->register_traits();
		$this->after_register();

		$this->traits_initialized = true;
	}

	private function register_traits(): void {

		// Auto-discover and execute trait capabilities
		$traits = class_uses($this);
		foreach ($traits as $trait) {
			$execute_method = $this->get_trait_register_method($trait);
			if (method_exists($this, $execute_method)) {
				$this->$execute_method();
			}
		}
	}

	private function get_trait_register_method(string $trait_name): string {
		// Convert "CanBeCommand" to "register_can_be_command"
		$snake_case = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', basename($trait_name)));
		return "register_{$snake_case}";
	}

	protected function before_register(): void {}
	protected function after_register(): void {}

}