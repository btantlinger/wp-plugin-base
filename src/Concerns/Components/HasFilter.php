<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait HasFilter
{
    use TraitRegistrationHelper;

    protected function register_has_filter(): void
    {
        $this->ensure_component_registration();

        add_filter(
            $this->get_filter_hook(),
            [$this, 'execute_filter'],
            $this->get_filter_priority(),
            $this->get_filter_accepted_args()
        );
    }

    /**
     * Get the filter hook name
     */
    abstract protected function get_filter_hook(): string;

    /**
     * Get the filter priority
     */
    protected function get_filter_priority(): int
    {
        return 10;
    }

    /**
     * Get the number of accepted arguments
     */
    protected function get_filter_accepted_args(): int
    {
        return 1;
    }

    /**
     * Execute the filter - override this in your component
     * Must return the filtered value
     */
    abstract public function execute_filter(...$args): mixed;
}