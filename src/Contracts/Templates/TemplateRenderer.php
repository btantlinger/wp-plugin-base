<?php

namespace WebMoves\PluginBase\Contracts\Templates;

interface TemplateRenderer {
	public function render(string $template, array $data = []): string;

	public function display(string $template, array $data = []): void;

	public function exists(string $template): bool;
}