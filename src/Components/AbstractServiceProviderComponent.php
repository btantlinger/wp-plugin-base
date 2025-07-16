<?php

namespace WebMoves\PluginBase\Components;

use WebMoves\PluginBase\Concerns\Components\HasComponents;
use WebMoves\PluginBase\Concerns\Components\ComponentRegistration;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;

abstract class AbstractServiceProviderComponent extends AbstractComponent {

	use ComponentRegistration;
	use HasComponents;

	use HasComponents;

	public function __construct(
		protected PluginCoreInterface $core
	) {
		parent::__construct();
	}



	/**
	 * Get services to register
	 *
	 * @return array<string, mixed> Service definitions
	 */
	abstract protected function get_components(): array;

}