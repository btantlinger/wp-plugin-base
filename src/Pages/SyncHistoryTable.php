<?php

namespace WebMoves\PluginBase\Pages;

use WebMoves\PluginBase\Contracts\Controllers\Controller;
use WebMoves\PluginBase\Synchronizers\DatabaseSyncService;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Sync History Table
 * 
 * Displays the sync history data in a table format
 */
class SyncHistoryTable extends \WP_List_Table
{
    private string $text_domain;
    private Controller $cancel_sync_controller;
    private Controller $delete_sync_controller;

    /**
     * Constructor
     */
    public function __construct(
        PluginMetadata $metadata, 
        Controller $cancel_sync_controller,
        Controller $delete_sync_controller
    ) {
        parent::__construct([
            'singular' => 'sync',
            'plural'   => 'syncs',
            'ajax'     => false
        ]);
        
       // $this->metadata = $metadata;
        $this->text_domain = $metadata->get_text_domain();
        //$this->sync_service = $sync_service;
        $this->cancel_sync_controller = $cancel_sync_controller;
        $this->delete_sync_controller = $delete_sync_controller;

        // Get the current screen to ensure screen options work properly
        $this->screen = get_current_screen();

        // Enqueue table styles

    }


    /**
     * Get columns
     * 
     * @return array
     */
    public function get_columns()
    {
        return [
            'id'               => __('ID', $this->text_domain),
            'sync_type'        => __('Type', $this->text_domain),
            'status'           => __('Status', $this->text_domain),
            'started_at'       => __('Started', $this->text_domain),
            'completed_at'     => __('Completed', $this->text_domain),
            'duration_seconds' => __('Duration', $this->text_domain),
            'total_items'       => __('Total Items', $this->text_domain),
            'synced_items'      => __('Synced', $this->text_domain),
            'failed_items'      => __('Failed', $this->text_domain),
            'triggered_by'     => __('Triggered By', $this->text_domain),
            'error_message'    => __('Error', $this->text_domain),
            'actions'          => __('Actions', $this->text_domain),
        ];
    }

    /**
     * Get sortable columns
     * 
     * @return array
     */
    public function get_sortable_columns()
    {
        return [
            'id'           => ['id', true],
            'sync_type'    => ['sync_type', false],
            'status'       => ['status', false],
            'started_at'   => ['started_at', true],
            'completed_at' => ['completed_at', false],
        ];
    }

    /**
     * Prepare items
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = get_hidden_columns($this->screen);
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        // Get per page option from screen options, default to 20
        $per_page = $this->get_items_per_page('syncs_per_page', 20);
        $current_page = $this->get_pagenum();

        // Calculate offset for database query
        $offset = ($current_page - 1) * $per_page;

        // Get sorting parameters
        $orderby = !empty($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'started_at';
        $order = !empty($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';

        // Get data with proper pagination from database
        $data = $this->getRecentSyncsWithPagination($per_page, $offset, $orderby, $order);

        // Get total count for pagination
        $total_items = $this->getTotalSyncsCount();

        $this->items = $data;

        // Set up pagination
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    private function getRecentSyncsWithPagination($limit, $offset, $orderby = 'started_at', $order = 'DESC')
    {
        global $wpdb;
        $table_name = DatabaseSyncService::get_table_name();

        // Validate orderby to prevent SQL injection
        $allowed_orderby = ['started_at', 'completed_at', 'sync_type', 'status', 'duration_seconds', 'total_items'];
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'started_at';
        }

        // Validate order
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
         ORDER BY {$orderby} {$order} 
         LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    private function getTotalSyncsCount()
    {
        global $wpdb;
        $table_name = DatabaseSyncService::get_table_name();

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    }

    /**
     * Format the duration
     * 
     * @param int $seconds
     * @return string
     */
    private function format_duration($seconds)
    {
        if (empty($seconds)) {
            return '-';
        }

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        return $minutes . 'm ' . $seconds . 's';
    }

    /**
     * Actions column renderer
     * 
     * @param object $item
     * @return string
     */
    public function column_actions($item)
    {
        $actions = [];
        
        // Show cancel button for running syncs
        if ($item->status === 'running') {
            $cancel_url = $this->cancel_sync_controller->create_action_url([
                'sync_id' => $item->id
            ]);
            
            $actions[] = sprintf(
                '<a href="%s" class="button button-small cancel-sync-btn" onclick="return confirm(\'%s\')">%s</a>',
                esc_url($cancel_url),
                esc_js(__('Are you sure you want to cancel this sync?', $this->text_domain)),
                __('Cancel', $this->text_domain)
            );
        }
        
        // Show delete button for non-running syncs
        if ($item->status !== 'running') {
            $delete_url = $this->delete_sync_controller->create_action_url([
                'sync_id' => $item->id,
                'page' => $_GET['page'] ?? 'duffells-sync' // Add current page parameter
            ]);
            
            $actions[] = sprintf(
                '<a href="%s" class="button button-small delete-sync-btn" onclick="return confirm(\'%s\')">%s</a>',
                esc_url($delete_url),
                esc_js(__('Are you sure you want to delete this sync record? This action cannot be undone.', $this->text_domain)),
                __('Delete', $this->text_domain)
            );
        }
        
        if (empty($actions)) {
            return '-';
        }
        
        return '<div class="sync-actions">' . implode('', $actions) . '</div>';
    }

    /**
     * Default column renderer
     * 
     * @param object $item
     * @param string $column_name
     * @return string
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'sync_type':
            case 'triggered_by':
                return esc_html($item->$column_name);

            case 'status':
                $status = esc_html($item->$column_name);
                $status_class = '';

                switch ($status) {
                    case 'completed':
                        $status_class = 'status-completed';
                        break;
                    case 'running':
                        $status_class = 'status-running';
                        break;
                    case 'failed':
                        $status_class = 'status-failed';
                        break;
                    case 'cancelled':
                        $status_class = 'status-cancelled';
                        break;
                }

                return '<span class="sync-status ' . $status_class . '">' . $status . '</span>';

            case 'started_at':
            case 'completed_at':
                return !empty($item->$column_name) ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->$column_name)) : '-';

            case 'duration_seconds':
                return $this->format_duration($item->$column_name);

            case 'total_items':
            case 'synced_items':
            case 'failed_items':
                return number_format_i18n($item->$column_name);

            case 'error_message':
                return !empty($item->$column_name) ? '<div class="error-message">' . esc_html(substr($item->$column_name, 0, 100)) . (strlen($item->$column_name) > 100 ? '...' : '') . '</div>' : '-';

            case 'actions':
                return $this->column_actions($item);

            default:
                return print_r($item, true);
        }
    }

    /**
     * Render the table
     */
    public function display()
    {
        parent::display();
    }

    /**
     * Message to show when there are no items
     */
    public function no_items()
    {
        _e('No sync history found.', $this->text_domain);
    }
}