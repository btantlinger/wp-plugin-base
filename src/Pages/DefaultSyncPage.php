<?php

namespace WebMoves\PluginBase\Pages;

use WebMoves\PluginBase\Contracts\Controllers\FormController;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;

/**
 * Default concrete implementation of AbstractSyncPage
 * 
 * This provides a ready-to-use sync page that can be configured via dependency injection.
 * Implementors can use this directly by injecting their synchronizers array, or they can
 * extend AbstractSyncPage directly for more customization.
 * 
 * Usage in DI configuration:
 * ```php
 * DefaultSyncPage::class => create(DefaultSyncPage::class)
 *     ->constructor(
 *         get(PluginCore::class),
 *         'my-sync-page',           // page slug
 *         get('synchronizers'),     // injected synchronizers array
 *         get(CancelSyncController::class),
 *         get(DeleteSyncController::class),
 *         'my-plugin-menu'         // parent slug (optional)
 *     )
 * ```
 */
class DefaultSyncPage extends AbstractSyncPage
{
    /**
     * Array of synchronizers injected via constructor
     * 
     * @var array
     */
    private array $synchronizers;

    /**
     * Constructor
     * 
     * @param PluginCore $core The plugin core instance
     * @param string $page_slug The page slug for the admin menu
     * @param array $synchronizers Array of synchronizer instances
     * @param FormController $cancel_controller Controller for cancel sync operations
     * @param FormController $delete_controller Controller for delete sync operations
     * @param string|null $parent_slug Parent menu slug (optional)
     * @param array $assets Additional assets to load (optional)
     */
    public function __construct(
        PluginCore $core,
        string $page_slug,
        array $synchronizers,
        FormController $cancel_controller,
        FormController $delete_controller,
        ?string $parent_slug = null,
        array $assets = []
    ) {
        $this->synchronizers = $synchronizers;

        parent::__construct($core, $page_slug, $cancel_controller, $delete_controller, $parent_slug, $assets);
    }

    /**
     * Get the synchronizers for this sync page
     * 
     * Returns the synchronizers that were injected via the constructor.
     * This implements the abstract method from AbstractSyncPage.
     * 
     * @return array Array of synchronizer instances
     */
    protected function get_synchronizers(): array
    {
        return $this->synchronizers;
    }

    /**
     * Get the default page title for this sync page
     * 
     * Can be overridden by setting the page title in WordPress admin
     * or by extending this class and overriding this method.
     * 
     * @return string The page title
     */
    public function get_page_title(): string
    {
        return __('Sync History', $this->core->get_text_domain());
    }

    /**
     * Get the default menu title for this sync page
     * 
     * Can be overridden by extending this class and overriding this method.
     * 
     * @return string The menu title
     */
    public function get_menu_title(): string
    {
        return __('Sync History', $this->core->get_text_domain());
    }

    /**
     * Get the capability required to access this page
     * 
     * Can be overridden by extending this class and overriding this method.
     * 
     * @return string The capability required
     */
    public function get_capability(): string
    {
        return 'manage_options';
    }
}
