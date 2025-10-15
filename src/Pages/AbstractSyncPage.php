<?php

namespace WebMoves\PluginBase\Pages;

use WebMoves\PluginBase\Contracts\Controllers\FormController;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Controllers\AbstractRestRoute;
use WebMoves\PluginBase\Controllers\SyncStatusRestRoute;

abstract class AbstractSyncPage extends AbstractAdminPage
{

    protected ?string $menu_icon = 'dashicons-update';

    protected ?int $menu_position = 55;

    private string $text_domain;

    private SyncHistoryTable $sync_history_table;

    private FormController $cancel_sync_controller;
    private FormController $delete_sync_controller;


    public function __construct(PluginCore $core, string $page_slug, FormController $cancel_controller, FormController $delete_controller,  ?string $parent_slug = null, array $assets = [])
    {
        $this->text_domain = $core->get_metadata()->get_text_domain();
        $this->core = $core;

        $this->cancel_sync_controller = $cancel_controller;
        $this->delete_sync_controller = $delete_controller;

        parent::__construct($core, $page_slug, $parent_slug, $assets);
        add_filter('set-screen-option', [$this, 'set_screen_option'], 10, 3);
    }


    protected function on_admin_menu_added(): void
    {
        parent::on_admin_menu_added();

        $hook = $this->get_page_hook();

        // Add screen options when the sync history page is loaded
        add_action("load-$hook", [$this, 'set_up_page']);

        //add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    protected function create_assets(): array
    {
        /**
         * @var AbstractRestRoute $route
         */
        $route = $this->core->get(SyncStatusRestRoute::class);
        $localized = [
            'SyncSettings' => [
                'restUrl' => $route->create_action_url(),
                'nonce' => $route->create_rest_nonce(),
                'pollInterval' => 3000, // 3 seconds
            ]
        ];

        return [
            $this->create_style_asset($this->get_framework_asset_path('css/sync-status.css')),
            $this->create_script_asset($this->get_framework_asset_path('js/sync-status.js'), ['jquery'],  null, true, null, $localized),
        ];
    }

    /**
     * Get the correct asset path whether running as standalone plugin or Composer package
     * This also allows users to override by placing assets in their plugin directory
     */
    private function get_framework_asset_path(string $asset_path): string
    {
        // Get the plugin directory from metadata
        $plugin_file = $this->core->get_metadata()->get_file();
        $plugin_dir = plugin_dir_path($plugin_file);

        // First check if user has overridden the asset in their plugin directory
        $plugin_asset_path = 'assets/admin/' . $asset_path;
        $plugin_asset_file = $plugin_dir . $plugin_asset_path;

        if (file_exists($plugin_asset_file)) {
            // User has overridden the asset
            return $plugin_asset_path;
        }

        // Try to detect if framework is running from vendor directory
        $reflection = new \ReflectionClass(\WebMoves\PluginBase\PluginBase::class);
        $framework_file = $reflection->getFileName();

        // Check if we're in a vendor directory
        if (strpos($framework_file, '/vendor/webmoves/plugin-base/') !== false) {
            // Running as Composer package - standard vendor path
            return 'vendor/webmoves/plugin-base/assets/admin/' . $asset_path;
        } elseif (strpos($framework_file, '/vendor/') !== false) {
            // Running as Composer package with different vendor structure
            // Extract the vendor path dynamically
            preg_match('/^(.+\/vendor\/)/', $framework_file, $matches);
            if (!empty($matches[1])) {
                $relative_vendor = str_replace($plugin_dir, '', $matches[1]);
                return $relative_vendor . 'webmoves/plugin-base/assets/admin/' . $asset_path;
            }
        }

        // Fallback: assume standalone plugin structure
        return 'assets/admin/' . $asset_path;
    }

    public function enqueue_admin_assets($hook_suffix): void
    {
        if ($hook_suffix !== $this->get_page_hook()) {
            return;
        }

        wp_enqueue_style(
            'duffells-admin-sync',
            plugin_dir_url(__DIR__) . '../assets/css/sync-status.css',
            [],
            '1.0.0'
        );

        // Enqueue JavaScript for sync status polling
        wp_enqueue_script(
            'duffells-admin-sync',
            plugin_dir_url(__DIR__) . '../assets/js/sync-status.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Localize script with REST API settings
        wp_localize_script('duffells-admin-sync', 'PluginBaseSettings', [
            'restUrl' => rest_url('product-sync/v1/sync-status'),
            'nonce' => wp_create_nonce('wp_rest'),
            'pollInterval' => 3000, // 3 seconds
        ]);
    }

    /**
     * Save screen options
     *
     * @param bool $keep Whether to save or skip saving the screen option
     * @param string $option The option name
     * @param mixed $value The option value
     * @return mixed
     */
    public function set_screen_option($keep, $option, $value)
    {
        if ('syncs_per_page' === $option) {
            return $value;
        }
        return $keep;
    }

    /**
     * Add screen options
     */
    public function set_up_page()
    {
        // Create an instance of our table class with controller dependencies
        $this->sync_history_table = new SyncHistoryTable(
            $this->core->get_metadata(), 
            $this->cancel_sync_controller,
            $this->delete_sync_controller
        );
        $this->sync_history_table->prepare_items();

        // Add per page screen option
        $option = 'per_page';
        $args = [
            'label' => __('Syncs per page', $this->text_domain),
            'default' => 20,
            'option' => 'syncs_per_page'
        ];
        add_screen_option($option, $args);
    }

    protected function render_admin_page(): void {

        if (!current_user_can('manage_options')) {
            return;
        }

        $metadata = $this->core->get_metadata();
        $page_title = get_admin_page_title();
        $current_page = $_REQUEST['page'] ?? '';
        $text_domain = $metadata->get_text_domain();
        $synchronizers = $this->get_synchronizers();

        $this->render_sync_history_template($page_title, $current_page, $text_domain, $synchronizers);
    }

    /**
     * Render the main sync history template
     */
    private function render_sync_history_template(string $page_title, string $current_page, string $text_domain, array $synchronizers): void
    {
        ?>
        <div class="wrap sync-history-wrap">
            <h1><?php echo esc_html($page_title); ?></h1>

            <div class="sync-history-description">
                <p><?php _e('This page shows the history of synchronization operations.', $text_domain); ?></p>
            </div>

            <?php
            foreach($synchronizers as $synchronizer) {
                $this->render_progress_panel($synchronizer, $text_domain);
            }
            ?>

            <form id="sync-history-filter" method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($current_page); ?>" />
                <?php $this->sync_history_table->display(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a progress panel for a synchronizer
     */
    private function render_progress_panel($synchronizer, string $text_domain): void
    {
        $sync_type = $synchronizer->get_sync_type_key();
        $label = $synchronizer->get_sync_type_label();
        ?>
        <div id="<?php echo esc_attr($sync_type); ?>-progress-panel" class="sync-progress-panel" style="display: none;">
            <div class="sync-status-header">
                <h3 class="sync-status-title"><?php echo esc_html($label); ?> Sync</h3>
                <div class="sync-actions">
                    <span class="sync-status-badge sync-status cancelled">Not Started</span>

                </div>
            </div>

            <div class="progress-container" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%;"></div>
                </div>
                <div class="progress-text">
                    <span class="progress-percentage">0%</span>
                    <span class="progress-details">0 / 0 synced</span>
                </div>
            </div>

            <div class="sync-details">
                <div class="sync-stats">
                    <div class="sync-detail-item">
                        <strong><?php _e('Status:', $text_domain); ?></strong>
                        <span class="sync-stat-status">-</span>
                    </div>
                    <div class="sync-detail-item">
                        <strong><?php _e('Total:', $text_domain); ?></strong>
                        <span class="sync-stat-total">0</span>
                    </div>
                    <div class="sync-detail-item">
                        <strong><?php _e('Synced:', $text_domain); ?></strong>
                        <span class="sync-stat-synced">0</span>
                    </div>
                    <div class="sync-detail-item">
                        <strong><?php _e('Failed:', $text_domain); ?></strong>
                        <span class="sync-stat-failed">0</span>
                    </div>
                    <div class="sync-detail-item">
                        <strong><?php _e('Started:', $text_domain); ?></strong>
                        <span class="sync-stat-started">-</span>
                    </div>
                    <div class="sync-detail-item">
                        <strong><?php _e('Next Scheduled:', $text_domain); ?></strong>
                        <span class="sync-stat-next-scheduled">Not scheduled</span>
                    </div>
                </div>
                <button type="button" class="button button-primary sync-action-button"
                        data-sync-type="<?php echo esc_attr($sync_type); ?>"
                        data-action="sync">
                    <?php _e('Sync Now', $text_domain); ?>
                </button>
            </div>
        </div>
        <?php
    }

	protected abstract function get_synchronizers(): array;


}