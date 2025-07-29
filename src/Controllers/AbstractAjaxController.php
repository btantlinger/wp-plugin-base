<?php

namespace WebMoves\PluginBase\Controllers;


use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Enums\Lifecycle;
use WebMoves\PluginBase\Contracts\Controllers\AjaxController;

abstract class AbstractAjaxController extends AbstractComponent implements AjaxController
{
    protected string $action;
    protected string $text_domain;
    protected PluginMetadata $metadata;
    protected FlashData $flash_data;

    public function __construct(PluginMetadata $metadata, FlashData $flash_data, string $action)
    {
        $this->metadata = $metadata;
        $this->flash_data = $flash_data;
        $this->action = $action;
        $this->text_domain = $metadata->get_text_domain();
        parent::__construct();
    }

	public function get_nonce_key(): string {
		return "_wpnonce";
	}


	/**
     * @inheritDoc
     */
    public function register(): void
    {
        add_action('wp_ajax_' . $this->action, [$this, 'handle_ajax_request']);

        if ($this->allow_ajax_for_non_logged_in_users()) {
            add_action('wp_ajax_nopriv_' . $this->action, [$this, 'handle_ajax_request']);
        }
    }

    /**
     * @inheritDoc
     */
    public function handle_ajax_request(): void
    {
        $data = $_POST; // AJAX typically uses POST data
        $this->handle_request($data);
    }

    /**
     * Main request handling logic for AJAX
     */
    protected function handle_request(array $data): void
    {
        try {
            // Check user capabilities
            if (!$this->can_perform_action($data)) {
                wp_send_json_error([
                    'message' => __('You do not have permission to perform this action.', $this->text_domain)
                ], 403);
                return;
            }

            // Verify nonce
            $nonce_action = $this->get_nonce_action($data);
            if (!wp_verify_nonce($data[$this->get_nonce_key()], $nonce_action)) {
                wp_send_json_error([
                    'message' => __('Security check failed. Please refresh the page and try again.', $this->text_domain)
                ], 403);
                return;
            }

            // Validate request data
            $validation_result = $this->validate_request_data($data);
            if (!$validation_result['valid']) {
                wp_send_json_error(['message' => $validation_result['message']]);
                return;
            }

            // Log the action if needed
            $this->log_action($data);

            // Handle the action
            $result = $this->handle_action($data);

            // Send success response
            wp_send_json_success($result);

        } catch (\Exception $e) {
            $this->handle_exception($e, $data);
        }
    }

    /**
     * Check if the current user can perform this action
     */
    protected function can_perform_action(array $data): bool
    {
        $required_capability = $this->get_required_capability();
        return current_user_can($required_capability);
    }

    /**
     * Get the required capability for this action
     * Override in child classes to specify different capabilities
     */
    protected function get_required_capability(): string
    {
        return 'manage_options';
    }

    /**
     * Validate request data before processing
     * Override in child classes for custom validation
     */
    protected function validate_request_data(array $data): array
    {
        return ['valid' => true, 'message' => ''];
    }

    /**
     * Log the action if logging is enabled
     * Override in child classes for custom logging
     */
    protected function log_action(array $data): void
    {
        if (!$this->should_log_action()) {
            return;
        }

        error_log(sprintf(
            '[%s] User %d performed AJAX action "%s" with data: %s',
            $this->metadata->get_name(),
            get_current_user_id(),
            $this->action,
            wp_json_encode($data)
        ));
    }

    /**
     * Determine if this action should be logged
     */
    protected function should_log_action(): bool
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    /**
     * Handle exceptions during action processing
     */
    protected function handle_exception(\Exception $e, array $data): void
    {
        error_log(sprintf(
            '[%s] Exception in AJAX action "%s": %s',
            $this->metadata->get_name(),
            $this->action,
            $e->getMessage()
        ));

        $message = defined('WP_DEBUG') && WP_DEBUG
            ? $e->getMessage()
            : __('An error occurred while processing your request.', $this->text_domain);

        wp_send_json_error(['message' => $message]);
    }

    /**
     * Whether to allow AJAX requests for non-logged-in users
     * Override in child classes if needed
     */
    protected function allow_ajax_for_non_logged_in_users(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function create_action_url(array $params = []): string
    {
        $params['action'] = $this->action;
        $base_url = admin_url('admin-ajax.php');
        return add_query_arg($params, $base_url);
    }

    /**
     * @inheritDoc
     */
    public function create_ajax_nonce(array $params = []): string
    {
        $mock_data = array_merge($params, ['action' => $this->action]);
        $nonce_action = $this->get_nonce_action($mock_data);

        return wp_create_nonce($nonce_action);
    }

    /**
     * Generate the nonce action string based on request data
     * Child classes MUST implement this to match their nonce generation
     */
    abstract protected function get_nonce_action(array $data): string;

    /**
     * Handle the specific action - must be implemented by child classes
     * @param array $data The request data ($_POST typically)
     * @return mixed The result of the action (will be sent as JSON response)
     */
    abstract protected function handle_action(array $data): mixed;

    /**
     * @inheritDoc
     */
    public function get_action(): string
    {
        return $this->action;
    }

    /**
     * Determine which lifecycle hook to register on
     */
    public function register_on(): Lifecycle
    {
        return Lifecycle::INIT;
    }

    public function get_priority(): int
    {
        return 10;
    }
}