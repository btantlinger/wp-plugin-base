<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Settings\FlashData;

class DefaultFlashData implements FlashData
{
    private string $meta_key;
    private array $marked_for_deletion = [];
    private static array $hooks_registered = [];

    public function __construct(string $page_slug = 'default')
    {
        $this->meta_key = 'flash_data_' . $page_slug;
        
        // ✅ ALWAYS register hooks immediately - don't wait
        $this->register_hooks();
    }

    /**
     * Register hooks immediately when instance is created
     */
    private function register_hooks(): void
    {
        $page_key = $this->meta_key;
        
        // Only register once per page key
        if (isset(self::$hooks_registered[$page_key])) {
            return;
        }

        if (is_admin()) {
            add_action('admin_notices', [$this, 'display_notices']);
            add_action('admin_footer', [$this, 'cleanup_expired']);
            add_action('shutdown', [$this, 'cleanup_marked_items']);
        } else {
            add_action('wp_footer', [$this, 'cleanup_expired']);
            add_action('shutdown', [$this, 'cleanup_marked_items']);
        }

        self::$hooks_registered[$page_key] = true;
    }

    /**
     * Display all pending notices
     */
    public function display_notices(): void
    {
        $flash_data = $this->get_all_flash_data();
        if (empty($flash_data)) {
            return;
        }

        $notices_displayed = [];

        foreach ($flash_data as $key => $item) {
            if ($item['type'] === 'notice') {
                $this->render_notice($item['value']);
                $notices_displayed[] = $key;
            }
        }

        // Mark displayed notices for deletion
        foreach ($notices_displayed as $key) {
            $this->mark_for_deletion($key);
        }
    }

    /**
     * Add an admin notice
     */
    public function add_notice(string $message, string $type = 'success', bool $dismissible = true): void
    {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        $flash_data = $this->get_all_flash_data();

        // Generate unique key for notice
        $notice_key = 'notice_' . uniqid();

        $flash_data[$notice_key] = [
            'value' => [
                'message' => $message,
                'type' => $type,
                'dismissible' => $dismissible
            ],
            'timestamp' => time(),
            'type' => 'notice'
        ];

        update_user_meta($user_id, $this->meta_key, $flash_data);
    }

    /**
     * Add an error notice
     */
    public function add_error(string $message, bool $dismissible = true): void
    {
        $this->add_notice($message, 'error', $dismissible);
    }

    /**
     * Add a success notice
     */
    public function add_success(string $message, bool $dismissible = true): void
    {
        $this->add_notice($message, 'success', $dismissible);
    }

    /**
     * Add a warning notice
     */
    public function add_warning(string $message, bool $dismissible = true): void
    {
        $this->add_notice($message, 'warning', $dismissible);
    }

    /**
     * Add multiple field errors as notices
     */
    public function add_field_errors(array $errors): void
    {
        foreach ($errors as $field => $error) {
            $this->add_error("Field '$field': $error");
        }
    }

    /**
     * ✅ Check if there are any error notices
     */
    public function has_errors(): bool
    {
        $flash_data = $this->get_all_flash_data();
        
        foreach ($flash_data as $item) {
            if ($item['type'] === 'notice' && $item['value']['type'] === 'error') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * ✅ Check if there are any notices of a specific type
     */
    public function has_notices(string $type = null): bool
    {
        $flash_data = $this->get_all_flash_data();
        
        foreach ($flash_data as $item) {
            if ($item['type'] === 'notice') {
                if ($type === null || $item['value']['type'] === $type) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * ✅ Get count of notices by type
     */
    public function get_notice_count(string $type = null): int
    {
        $flash_data = $this->get_all_flash_data();
        $count = 0;
        
        foreach ($flash_data as $item) {
            if ($item['type'] === 'notice') {
                if ($type === null || $item['value']['type'] === $type) {
                    $count++;
                }
            }
        }
        
        return $count;
    }

    /**
     * Store form data for redisplay after validation errors
     */
    public function set_form_data(string $form_key, array $data): void
    {
        $this->set('form_' . $form_key, $data);
    }

    /**
     * Get form data for redisplay
     */
    public function get_form_data(string $form_key): array
    {
        $key = 'form_' . $form_key;
        return $this->get($key, []);
    }

    /**
     * Store flash data for later retrieval
     */
    public function set(string $key, $value): void
    {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        $flash_data = $this->get_all_flash_data();

        $flash_data[$key] = [
            'value' => $value,
            'timestamp' => time(),
            'type' => 'data'
        ];

        update_user_meta($user_id, $this->meta_key, $flash_data);
    }

    /**
     * Get flash data and mark for deletion
     */
    public function get(string $key, $default = null)
    {
        $flash_data = $this->get_all_flash_data();

        if (!isset($flash_data[$key]) || $flash_data[$key]['type'] !== 'data') {
            return $default;
        }

        $value = $flash_data[$key]['value'];

        // Mark for deletion
        $this->mark_for_deletion($key);

        return $value;
    }

    /**
     * Clear specific flash data item
     */
    public function clear(string $key): void
    {
        $flash_data = $this->get_all_flash_data();

        if (isset($flash_data[$key])) {
            unset($flash_data[$key]);

            $user_id = get_current_user_id();
            if ($user_id) {
                if (empty($flash_data)) {
                    delete_user_meta($user_id, $this->meta_key);
                } else {
                    update_user_meta($user_id, $this->meta_key, $flash_data);
                }
            }
        }
    }

    /**
     * Get all flash data for current user
     */
    private function get_all_flash_data(): array
    {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return [];
        }

        $flash_data = get_user_meta($user_id, $this->meta_key, true);
        return is_array($flash_data) ? $flash_data : [];
    }

    /**
     * Mark item for deletion at end of request
     */
    private function mark_for_deletion(string $key): void
    {
        if (!in_array($key, $this->marked_for_deletion)) {
            $this->marked_for_deletion[] = $key;
        }
    }

    /**
     * Clean up expired items
     */
    public function cleanup_expired(): void
    {
        $flash_data = $this->get_all_flash_data();
        if (empty($flash_data)) {
            return;
        }

        $current_time = time();
        $expiry_time = 300; // 5 minutes
        $modified = false;

        foreach ($flash_data as $key => $item) {
            if (($current_time - $item['timestamp']) > $expiry_time) {
                unset($flash_data[$key]);
                $modified = true;
            }
        }

        if ($modified) {
            $user_id = get_current_user_id();
            if ($user_id) {
                if (empty($flash_data)) {
                    delete_user_meta($user_id, $this->meta_key);
                } else {
                    update_user_meta($user_id, $this->meta_key, $flash_data);
                }
            }
        }
    }

    /**
     * Actually delete marked items
     */
    public function cleanup_marked_items(): void
    {
        if (empty($this->marked_for_deletion)) {
            return;
        }

        $flash_data = $this->get_all_flash_data();
        $modified = false;

        foreach ($this->marked_for_deletion as $key) {
            if (isset($flash_data[$key])) {
                unset($flash_data[$key]);
                $modified = true;
            }
        }

        if ($modified) {
            $user_id = get_current_user_id();
            if ($user_id) {
                if (empty($flash_data)) {
                    delete_user_meta($user_id, $this->meta_key);
                } else {
                    update_user_meta($user_id, $this->meta_key, $flash_data);
                }
            }
        }

        $this->marked_for_deletion = [];
    }

    /**
     * Render a single notice
     */
    private function render_notice(array $notice): void
    {
        $type = sanitize_html_class($notice['type']);
        $dismissible = $notice['dismissible'] ? 'is-dismissible' : '';

        echo '<div class="notice notice-' . $type . ' ' . $dismissible . '">';
        echo '<p>' . wp_kses_post($notice['message']) . '</p>';
        echo '</div>';
    }
}