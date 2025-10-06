<?php

namespace WebMoves\PluginBase\Configuration\Providers;

use WebMoves\PluginBase\Configuration\FeatureConfigurationProviderInterface;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Contracts\Synchronizers\SyncService;
use WebMoves\PluginBase\Controllers\CancelSyncController;
use WebMoves\PluginBase\Controllers\DeleteSyncController;
use WebMoves\PluginBase\Controllers\SyncStatusRestRoute;
use WebMoves\PluginBase\Controllers\StartSyncRestRoute;
use WebMoves\PluginBase\Controllers\CancelSyncRestRoute;
use WebMoves\PluginBase\Synchronizers\DatabaseSyncService;
use WebMoves\PluginBase\Settings\GlobalSyncSettings;
use WebMoves\PluginBase\BackgroundTasks\StartSyncBackgroundTask;
use WebMoves\PluginBase\Events\SyncTimeoutWatchdog;
use WebMoves\PluginBase\Events\SyncHistoryPurgeEvent;
use WebMoves\PluginBase\Events\ScheduledSyncEvent;
use WebMoves\PluginBase\Pages\DefaultSyncPage;

use function DI\create;
use function DI\get;

/**
 * Configuration provider for synchronization features
 * 
 * This provider handles all the DI configuration needed for sync functionality,
 * including services, components, database tables, and proper merging with
 * existing configurations.
 */
class SyncConfigurationProvider implements FeatureConfigurationProviderInterface
{
    /**
     * Get the synchronization feature configuration
     * 
     * @param array $options Configuration options:
     *   - 'synchronizers': array of synchronizer definitions (default: empty)
     *   - 'sync_page_slug': string slug for sync page (default: 'sync-history')
     *   - 'sync_page_parent': string parent menu slug (default: null)
     * @return array The sync feature configuration
     */
    public function getConfiguration(array $options = []): array
    {
        $synchronizers = $options['synchronizers'] ?? [];
        $syncPageSlug = $options['sync_page_slug'] ?? 'sync-history';
        $syncPageParent = $options['sync_page_parent'] ?? null;

        // Generate sync events for each synchronizer
        $syncEvents = [];
        $syncEventComponents = [];

        foreach ($synchronizers as $index => $synchronizer) {
            // Create a unique event key for this synchronizer
            $eventKey = "sync-event-{$index}";

            // Add event to sync-events array
            $syncEvents[] = get($eventKey);

            // Create the ScheduledSyncEvent component
            $syncEventComponents[$eventKey] = create(\WebMoves\PluginBase\Events\ScheduledSyncEvent::class)
                ->constructor(
                    get(PluginMetadata::class),
                    $synchronizer,
                    get(StartSyncBackgroundTask::class),
                    get(SyncService::class)
                );
        }

        return [
            'services' => [
                // Global sync settings
                GlobalSyncSettings::class => create(GlobalSyncSettings::class)
                    ->constructor(get(SettingsManagerFactory::class), get(PluginMetadata::class)),

	            // Core sync service
                SyncService::class => create(DatabaseSyncService::class)
	                ->constructor(get(\wpdb::class)),

                // Synchronizers array
                'synchronizers' => $synchronizers,

                // Sync events array - dynamically generated from synchronizers
                'sync-events' => $syncEvents,
            ],

            'components' => array_merge([
                // Controllers
                CancelSyncController::class => create(CancelSyncController::class)
                    ->constructor(
                        get(PluginMetadata::class),
                        get(FlashData::class),
                        get(SyncService::class)
                    ),
                DeleteSyncController::class => create(DeleteSyncController::class)
                    ->constructor(
                        get(PluginMetadata::class),
                        get(FlashData::class),
                        get(SyncService::class)
                    ),

                // Default sync page
                DefaultSyncPage::class => create(DefaultSyncPage::class)
                    ->constructor(
                        get(PluginCore::class),
                        $syncPageSlug,
                        get('synchronizers'),
                        get(CancelSyncController::class),
                        get(DeleteSyncController::class),
                        $syncPageParent
                    ),

	            // Background task handler
                StartSyncBackgroundTask::class => create(StartSyncBackgroundTask::class)
	                ->constructor(get(PluginMetadata::class), get('synchronizers')),

                // REST API routes
                SyncStatusRestRoute::class => create(SyncStatusRestRoute::class)
                    ->constructor(
                        get(SyncService::class),
                        get('sync-events')
                    ),
                StartSyncRestRoute::class => create(StartSyncRestRoute::class)
                    ->constructor(
                        get(SyncService::class),
                        get(StartSyncBackgroundTask::class)
                    ),
                CancelSyncRestRoute::class => create(CancelSyncRestRoute::class)
                    ->constructor(get(SyncService::class)),

                // Event handlers
                SyncTimeoutWatchdog::class => create(SyncTimeoutWatchdog::class)
                    ->constructor(
                        get(PluginMetadata::class),
                        get(SyncService::class),
                        get(GlobalSyncSettings::class)
                    ),
                SyncHistoryPurgeEvent::class => create(SyncHistoryPurgeEvent::class)
                    ->constructor(
                        get(PluginMetadata::class),
                        get(\wpdb::class),
                        get(GlobalSyncSettings::class)
                    ),
            ], $syncEventComponents), // Merge in the dynamically generated sync events

            'database' => [
                'tables' => [
                    DatabaseSyncService::get_table_name(false) => DatabaseSyncService::get_table_definition(),
                ]
            ],
        ];
    }

    /**
     * Merge sync configuration with base configuration
     *
     * This method handles intelligent merging, ensuring that:
     * - Database tables are merged properly
     * - Services and components arrays are merged
     * - Existing synchronizers are preserved and new ones added
     * - Settings providers include sync-related providers
     *
     * @param array $baseConfig The base configuration to merge into
     * @param array $options Configuration options for the sync feature
     * @return array The merged configuration
     */
    public function mergeConfiguration(array $baseConfig, array $options = []): array
    {
        $syncConfig = $this->getConfiguration($options);
        $mergedConfig = $baseConfig;

        // Merge services
        if (!isset($mergedConfig['services'])) {
            $mergedConfig['services'] = [];
        }
        $mergedConfig['services'] = array_merge($mergedConfig['services'], $syncConfig['services']);

        // Merge components
        if (!isset($mergedConfig['components'])) {
            $mergedConfig['components'] = [];
        }
        $mergedConfig['components'] = array_merge($mergedConfig['components'], $syncConfig['components']);

        // Merge database configuration
        if (!isset($mergedConfig['database'])) {
            $mergedConfig['database'] = [
                'version' => '1.0.0',
                'delete_tables_on_uninstall' => true,
                'delete_options_on_uninstall' => true,
                'tables' => []
            ];
        }

        if (!isset($mergedConfig['database']['tables'])) {
            $mergedConfig['database']['tables'] = [];
        }

        $mergedConfig['database']['tables'] = array_merge(
            $mergedConfig['database']['tables'],
            $syncConfig['database']['tables']
        );

        // Add sync-related settings providers to existing settings_providers array
        if (isset($mergedConfig['services']['settings_providers']) && is_array($mergedConfig['services']['settings_providers'])) {
            // Add GlobalSyncSettings to existing providers if not already present
            $hasGlobalSync = false;
            foreach ($mergedConfig['services']['settings_providers'] as $provider) {
                if (is_string($provider) && strpos($provider, 'GlobalSyncSettings') !== false) {
                    $hasGlobalSync = true;
                    break;
                }
            }

            if (!$hasGlobalSync) {
                $mergedConfig['services']['settings_providers'][] = get(GlobalSyncSettings::class);
            }

            // Add all synchronizers as settings providers (since each synchronizer IS a SettingsProvider)
            $synchronizers = $options['synchronizers'] ?? [];
            foreach ($synchronizers as $synchronizer) {
                // Check if this synchronizer is already in the settings providers
                $alreadyExists = false;
                foreach ($mergedConfig['services']['settings_providers'] as $existingProvider) {
                    if ($existingProvider === $synchronizer) {
                        $alreadyExists = true;
                        break;
                    }
                }

                if (!$alreadyExists) {
                    $mergedConfig['services']['settings_providers'][] = $synchronizer;
                }
            }
        }

        return $mergedConfig;
    }

    /**
     * Get the feature name
     * 
     * @return string
     */
    public function getFeatureName(): string
    {
        return 'sync';
    }

    /**
     * Get dependencies for the sync feature
     * 
     * @return array Array of required feature names (empty for sync)
     */
    public function getDependencies(): array
    {
        return []; // Sync feature has no dependencies on other features
    }
}
