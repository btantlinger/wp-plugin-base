<?php

namespace WebMoves\PluginBase;

use WebMoves\PluginBase\Contracts\HookManagerInterface;

class HookManager implements HookManagerInterface
{
    private array $registered_hooks = [];

    /**
     * Register a callback for a WordPress action
     *
     * @param string $hook Hook name
     * @param callable $callback Function/method to call on event
     * @param int $priority Priority number. Lower numbers execute earlier
     * @param int $accepted_args Number of arguments the callback accepts
     * @return void
     */
    public function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void
    {
        add_action($hook, $callback, $priority, $accepted_args);
        $this->track_hook('action', $hook, $callback, $priority);
    }

    /**
     * Register a callback for a WordPress filter
     *
     * @param string $hook Hook name
     * @param callable $callback Function/method to call on event
     * @param int $priority Priority number. Lower numbers execute earlier
     * @param int $accepted_args Number of arguments the callback accepts
     * @return void
     */
    public function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void
    {
        add_filter($hook, $callback, $priority, $accepted_args);
        $this->track_hook('filter', $hook, $callback, $priority);
    }

    /**
     * Remove a registered action hook
     *
     * @param string $hook Hook name
     * @param callable $callback Function/method to remove
     * @param int $priority Priority number used when registering
     * @return bool
     */
    public function remove_action(string $hook, callable $callback, int $priority = 10): bool
    {
        $result = remove_action($hook, $callback, $priority);
        if ($result) {
            $this->untrack_hook('action', $hook, $callback, $priority);
        }
        return $result;
    }

    /**
     * Remove a registered filter hook
     *
     * @param string $hook Hook name
     * @param callable $callback Function/method to remove
     * @param int $priority Priority number used when registering
     * @return bool
     */
    public function remove_filter(string $hook, callable $callback, int $priority = 10): bool
    {
        $result = remove_filter($hook, $callback, $priority);
        if ($result) {
            $this->untrack_hook('filter', $hook, $callback, $priority);
        }
        return $result;
    }

    /**
     * Add a shortcode
     *
     * @param string $tag Shortcode tag
     * @param callable $callback Function/method to call
     * @return void
     */
    public function add_shortcode(string $tag, callable $callback): void
    {
        add_shortcode($tag, $callback);
        $this->registered_hooks['shortcodes'][$tag] = $callback;
    }

    /**
     * Remove a shortcode
     *
     * @param string $tag Shortcode tag
     * @return void
     */
    public function remove_shortcode(string $tag): void
    {
        remove_shortcode($tag);
        unset($this->registered_hooks['shortcodes'][$tag]);
    }

    /**
     * Get all registered hooks
     *
     * @return array
     */
    public function get_registered_hooks(): array
    {
        return $this->registered_hooks;
    }

    /**
     * Remove all registered hooks
     *
     * @return void
     */
    public function remove_all_hooks(): void
    {
        foreach ($this->registered_hooks as $type => $hooks) {
            if ($type === 'shortcodes') {
                foreach ($hooks as $tag => $callback) {
                    $this->remove_shortcode($tag);
                }
                continue;
            }

            foreach ($hooks as $hook_name => $callbacks) {
                foreach ($callbacks as $callback_data) {
                    if ($type === 'action') {
                        $this->remove_action($hook_name, $callback_data['callback'], $callback_data['priority']);
                    } else {
                        $this->remove_filter($hook_name, $callback_data['callback'], $callback_data['priority']);
                    }
                }
            }
        }
    }

    /**
     * Track a registered hook
     *
     * @param string $type Hook type (action or filter)
     * @param string $hook Hook name
     * @param callable $callback Callback function
     * @param int $priority Priority
     * @return void
     */
    private function track_hook(string $type, string $hook, callable $callback, int $priority): void
    {
        $this->registered_hooks[$type][$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];
    }

    /**
     * Untrack a registered hook
     *
     * @param string $type Hook type (action or filter)
     * @param string $hook Hook name
     * @param callable $callback Callback function
     * @param int $priority Priority
     * @return void
     */
    private function untrack_hook(string $type, string $hook, callable $callback, int $priority): void
    {
        if (!isset($this->registered_hooks[$type][$hook])) {
            return;
        }

        foreach ($this->registered_hooks[$type][$hook] as $index => $hook_data) {
            if ($hook_data['callback'] === $callback && $hook_data['priority'] === $priority) {
                unset($this->registered_hooks[$type][$hook][$index]);
                break;
            }
        }

        // Clean up empty arrays
        if (empty($this->registered_hooks[$type][$hook])) {
            unset($this->registered_hooks[$type][$hook]);
        }
    }
}