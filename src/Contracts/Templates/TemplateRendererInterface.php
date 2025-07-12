<?php

namespace WebMoves\PluginBase\Contracts\Templates;

interface TemplateRendererInterface {
	public function render(string $template, array $data = []): string;
	public function display(string $template, array $data = []): void;
}