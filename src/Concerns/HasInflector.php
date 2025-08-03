<?php

namespace WebMoves\PluginBase\Concerns;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;

trait HasInflector
{
    private ?Inflector $inflector = null;

    protected function getInflector(): Inflector
    {
        if ($this->inflector === null) {
            $this->inflector = InflectorFactory::create()->build();
        }

        return $this->inflector;
    }

    /**
     * Convert a string to human-readable format
     * product_category → Product Category
     * productCategory → Product Category
     */
    protected function humanize(string $text): string
    {
        // First convert camelCase or PascalCase to underscore
        $text = $this->getInflector()->tableize($text);
        
        // Replace underscores and hyphens with spaces
        $text = str_replace(['_', '-'], ' ', $text);
        
        // Convert to title case
        return $this->titleize($text);
    }

    /**
     * Convert to plural form
     * product → products
     */
    protected function pluralize(string $text): string
    {
        return $this->getInflector()->pluralize($text);
    }

    /**
     * Convert to singular form
     * products → product
     */
    protected function singularize(string $text): string
    {
        return $this->getInflector()->singularize($text);
    }

    /**
     * Convert to title case
     * product category → Product Category
     */
    protected function titleize(string $text): string
    {
        return ucwords(strtolower(trim($text)));
    }

    /**
     * Convert to camelCase
     * product_category → productCategory
     */
    protected function camelize(string $text): string
    {
        return $this->getInflector()->camelize($text);
    }

    /**
     * Convert to PascalCase/StudlyCase
     * product_category → ProductCategory
     */
    protected function classify(string $text): string
    {
        return $this->getInflector()->classify($text);
    }

    /**
     * Convert to underscore_case
     * ProductCategory → product_category
     */
    protected function underscore(string $text): string
    {
        return $this->getInflector()->tableize($text);
    }

    /**
     * Convert to kebab-case
     * ProductCategory → product-category
     */
    protected function dasherize(string $text): string
    {
        return str_replace('_', '-', $this->underscore($text));
    }
}