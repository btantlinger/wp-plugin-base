<?php
namespace WebMoves\PluginBase\Contracts\Assets;


interface Style extends Asset
{
	/**
	 * Get media attribute for stylesheet
	 */
	public function get_media(): string;
}