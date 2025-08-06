<?php

namespace WebMoves\PluginBase\Controllers;


use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Concerns\HasLogger;
use WebMoves\PluginBase\Contracts\Controllers\FormController;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Enums\Lifecycle;

abstract class AbstractFormController extends AbstractComponent implements FormController
{
    protected string $action;
	protected string $text_domain;
	protected PluginMetadata $metadata;
	protected FlashData $flash_data;
	protected bool $preserve_context = true;

	private string $request_method;
	private ?string $base_url = null;

	//protected LoggerInterface $logger;

	use HasLogger;

	/**
     * @param PluginMetadata $metadata
     * @param FlashData $flash_data
     * @param string $action The action identifier (e.g., 'cancel_sync', 'delete_sync')
     * @param string $request_method The request method ('GET' or 'POST')
     */
    public function __construct(PluginMetadata $metadata, FlashData $flash_data, string $action, string $request_method = 'GET')
    {
		//$this->logger = $this->log();
        $this->metadata = $metadata;
        $this->flash_data = $flash_data;
        $this->action = $action;
        $this->request_method = $request_method;
        $this->text_domain = $metadata->get_text_domain();
        parent::__construct();
    }

	public function get_base_url(): ?string
	{
		return $this->base_url;
	}

	public function set_base_url( ?string $base_url ): void
	{
		$this->base_url = $base_url;
	}

	public function set_preserve_context(bool $preserve_context): void
	{
		$this->preserve_context = $preserve_context;
	}

	public function should_preserve_context(): bool
	{
		return $this->preserve_context;
	}

	public function get_nonce_key(): string {
		return "_wpnonce";
	}


	/**
     * @inheritDoc
     */
    public function register(): void
    {
        $data = strtoupper($this->request_method) == 'GET' ? $_GET : $_POST;
        if (isset($data['action'], $data[$this->get_nonce_key()]) && $data['action'] === $this->action) {
            $this->handle_request($data);
        }
    }

    /**
     * Main request handling logic
     */
    protected function handle_request(array $data): void
    {
        try {
            // Check user capabilities
            if (!$this->can_perform_action($data)) {
                $this->handle_unauthorized_access();
                return;
            }

            // Verify nonce
            $nonce_action = $this->get_nonce_action($data);
            if (!wp_verify_nonce($data[$this->get_nonce_key()], $nonce_action)) {
                $this->handle_security_failure();
                return;
            }

            // Validate request data
            $validation_result = $this->validate_request_data($data);
            if (!$validation_result['valid']) {
                $this->handle_validation_failure($validation_result['message']);
                return;
            }

            // Log the action if needed
            $this->log_action($data);

            // Handle the action
            $result = $this->handle_action($data);

            // Handle successful completion
            $this->handle_success($result, $data);

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

        $this->log()->debug(sprintf(
            '[%s] User %d performed action "%s" with data: %s',
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
     * Handle successful action completion
     */
    protected function handle_success($result, array $data): void
    {
        $this->add_success_flash($result);
        
        if ($this->should_redirect_after_action()) {
            $this->redirect_after_action($data);
        }
    }

    /**
     * Handle unauthorized access attempts
     */
    protected function handle_unauthorized_access(): void
    {
        $message = __('You do not have permission to perform this action.', $this->text_domain);
        $this->flash_data->add_error($message);
        wp_die($message);
    }

    /**
     * Handle security/nonce failures
     */
    protected function handle_security_failure(): void
    {
        $message = __('Security check failed. Please refresh the page and try again.', $this->text_domain);
        $this->flash_data->add_error(__('Security check failed', $this->text_domain));
        wp_die($message);
    }

    /**
     * Handle validation failures
     */
    protected function handle_validation_failure(string $message): void
    {
        $this->flash_data->add_error($message);
        if ($this->should_redirect_after_action()) {
            $this->redirect_after_action([]);
        }
    }

    /**
     * Handle exceptions during action processing
     */
    protected function handle_exception(\Exception $e, array $data): void
    {
        $this->log()->error(sprintf(
            '[%s] Exception in action "%s": %s',
            $this->metadata->get_name(),
            $this->action,
            $e->getMessage()
        ));

        $message = defined('WP_DEBUG') && WP_DEBUG 
            ? $e->getMessage() 
            : __('An error occurred while processing your request.', $this->text_domain);

        $this->flash_data->add_error($message);
        if ($this->should_redirect_after_action()) {
            $this->redirect_after_action($data);
        }
    }

    /**
     * Add success flash message for non-AJAX requests
     */
    protected function add_success_flash($result): void
    {
        $message = $this->get_success_message($result);
        if ($message) {
            $this->flash_data->add_success($message);
        }
    }

    /**
     * Get success message based on result
     * Override in child classes for custom success messages
     */
    protected function get_success_message($result): string
    {
        return __('Action completed successfully.', $this->text_domain);
    }

    /**
     * Determine if we should redirect after handling the action
     * Override in child classes if needed
     */
    protected function should_redirect_after_action(): bool
    {
        return true;
    }

    /**
     * Get the parameters to remove from URL during redirect
     * Override in child classes to customize
     */
    protected function get_redirect_params_to_remove(array $data): array
    {
        return ['action', $this->get_nonce_key()];
    }

    /**
     * Redirect after successful action to clean the URL
     */
    protected function redirect_after_action(array $data): void
    {
        $params_to_remove = $this->get_redirect_params_to_remove($data);

        // Build current URL from server variables
        $protocol = is_ssl() ? 'https://' : 'http://';
        $current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $redirect_url = remove_query_arg($params_to_remove, $current_url);

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * @inheritDoc
     */
    public function create_action_url(array $params = []): string
    {
        if ($this->preserve_context) {
            // Start with existing GET parameters to preserve context
            $merged_params = array_merge($_GET, $params);
        } else {
            // Use only the provided parameters
            $merged_params = $params;
        }

        // Always set our action
        $merged_params['action'] = $this->action;

		$base_url = $this->get_base_url();

        // If no base URL provided, use current page
        if ($base_url === null) {
            $protocol = is_ssl() ? 'https://' : 'http://';
            $base_url = $protocol . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
        }

        $url = add_query_arg($merged_params, $base_url);

        $mock_data = $merged_params;
        $nonce_action = $this->get_nonce_action($mock_data);

        return wp_nonce_url($url, $nonce_action);
    }


    /**
     * Generate hidden form fields for POST requests
     *
     * While AJAX controllers typically don't use HTML forms, this method
     * can be useful for:
     * - Debugging (to see what fields would be sent)
     * - Mixed controllers that handle both AJAX and form submissions
     * - Testing scenarios
     */
    public function get_action_fields(array $additional_fields = []): string
    {
        $fields = [];

        $fields[] = sprintf(
            '<input type="hidden" name="action" value="%s" />',
            esc_attr($this->action)
        );

        foreach ($additional_fields as $name => $value) {
            $fields[] = sprintf(
                '<input type="hidden" name="%s" value="%s" />',
                esc_attr($name),
                esc_attr($value)
            );
        }

        // AJAX actions use simpler nonce
        $fields[] = wp_nonce_field($this->action, '_wpnonce', true, false);

        return implode("\n", $fields);
    }



    /**
     * Generate the nonce action string based on request data
     * Child classes MUST implement this to match their nonce generation
     */
    abstract protected function get_nonce_action(array $data): string;

    /**
     * Handle the specific action - must be implemented by child classes
     * @param array $data The request data ($_GET or $_POST)
     *
     */
    abstract protected function handle_action(array $data): array;

    /**
     * @inheritDoc
     */
    public function get_action(): string
    {
        return $this->action;
    }

    /**
     * Get the request method for this controller
     */
    public function get_request_method(): string
    {
        return $this->request_method;
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