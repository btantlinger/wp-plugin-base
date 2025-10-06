<?php

namespace WebMoves\PluginBase\Examples;

use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Controllers\CancelSyncController;
use WebMoves\PluginBase\Controllers\CancelSyncRestRoute;
use WebMoves\PluginBase\Controllers\DeleteSyncController;
use WebMoves\PluginBase\Examples\Components\BookReview;
use WebMoves\PluginBase\Examples\Components\Genre;
use WebMoves\PluginBase\PluginBase;
use WebMoves\PluginBase\Examples\Settings\MainPage;
use WebMoves\PluginBase\Examples\Settings\TestAbstractSettingsPage;
use WebMoves\PluginBase\Schedulers\SyncScheduler;
use WebMoves\PluginBase\Settings\DefaultSettingsManagerFactory;
use WebMoves\PluginBase\Settings\GlobalSyncSettings;
use WebMoves\PluginBase\Synchronizers\DatabaseSyncService;
use WebMoves\PluginBase\Synchronizers\DummySynchronizer;
use WebMoves\PluginBase\Examples\Pages\DummySyncPage;

class TestPlugin extends PluginBase
{
    public function initialize(): void
    {
	    $logger = $this->core->get_logger('app');
		$logger->info('Plugin Initialized');
    }

	public function get_services(): array {
/*		$plugin_slug = 'test-plugin-base';

		$flash_data = $this->get_core()->get(FlashData::class);
		$metadata = $this->get_core()->get_metadata();;

		$smf = new DefaultSettingsManagerFactory($metadata);
		$syncService = new DatabaseSyncService();
		$cancel_controller = new CancelSyncController($metadata, $flash_data, $syncService);
		$delete_controller = new DeleteSyncController($metadata, $flash_data, $syncService);


		$synchronizer = new DummySynchronizer($syncService, $metadata, $smf);
		$dummySyncPage = new DummySyncPage($this->get_core(), $synchronizer, $cancel_controller, $delete_controller, $plugin_slug);
		$globalSyncSettings = new GlobalSyncSettings($smf, $metadata);
		$syncScheduler = new SyncScheduler($synchronizer, $syncService, $metadata, $globalSyncSettings);
		$providers = [$synchronizer, $globalSyncSettings];


		return [
			MainPage::class  => new MainPage($this->get_core(), $plugin_slug, "Test Plugin Base", "Test Plugin"),
			TestAbstractSettingsPage::class => new TestAbstractSettingsPage($this->get_core(), 'Test Plugin Base Settings', 'Settings', $plugin_slug, $providers),
			BookReview::class => new BookReview(),
			Genre::class => new Genre(),
			DatabaseSyncService::class => $syncService,
			DummySynchronizer::class => $synchronizer,
			DummySyncPage::class => $dummySyncPage,
			SyncScheduler::class => $syncScheduler,
		];*/
		return [];
	}
}