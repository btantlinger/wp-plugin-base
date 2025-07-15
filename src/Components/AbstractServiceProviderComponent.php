<?php

namespace WebMoves\PluginBase\Components;

use WebMoves\PluginBase\Concerns\Components\CanBeServiceProvider;
use WebMoves\PluginBase\Concerns\Components\ComponentRegistration;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;

abstract class AbstractServiceProviderComponent extends AbstractComponent {

	use ComponentRegistration;
	use CanBeServiceProvider;

	use CanBeServiceProvider;

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
	abstract protected function get_provided_services(): array;

}