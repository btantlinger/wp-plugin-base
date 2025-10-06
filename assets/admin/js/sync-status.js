jQuery(document).ready(function($) {
    let pollInterval;
    let isPolling = false;

    // Start polling when page loads
    startPolling();

    // Handle sync action buttons (Cancel/Sync Now)
    $(document).on('click', '.sync-action-button', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const syncType = $button.data('sync-type');
        const action = $button.data('action');
        
        // Disable button to prevent double clicks
        $button.prop('disabled', true);
        
        if (action === 'cancel') {
            handleCancelSync(syncType, $button);
        } else if (action === 'sync') {
            handleStartSync(syncType, $button);
        }
    });

    function handleCancelSync(syncType, $button) {
        if (!confirm('Are you sure you want to cancel this sync?')) {
            $button.prop('disabled', false);
            return;
        }

        $.ajax({
            url: SyncSettings.restUrl + '/cancel',
            method: 'POST',
            headers: {
                'X-WP-Nonce': SyncSettings.nonce
            },
            data: {
                sync_type: syncType
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Status will be updated by the next polling cycle
                    $button.text('Cancelling...');
                } else {
                    alert('Failed to cancel sync: ' + (response.message || 'Unknown error'));
                    $button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Cancel sync error:', error);
                alert('Failed to cancel sync. Please try again.');
                $button.prop('disabled', false);
            }
        });
    }

    function handleStartSync(syncType, $button) {
        if (!confirm('Are you sure you want to start a new sync?')) {
            $button.prop('disabled', false);
            return;
        }

        $.ajax({
            url: SyncSettings.restUrl + '/start',
            method: 'POST',
            headers: {
                'X-WP-Nonce': SyncSettings.nonce
            },
            data: {
                sync_type: syncType
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Status will be updated by the next polling cycle
                    $button.text('Starting...');
                } else {
                    alert('Failed to start sync: ' + (response.message || 'Unknown error'));
                    $button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Start sync error:', error);
                alert('Failed to start sync. Please try again.');
                $button.prop('disabled', false);
            }
        });
    }

    function startPolling() {
        if (isPolling) return;

        isPolling = true;
        pollSyncStatus();

        pollInterval = setInterval(function() {
            pollSyncStatus();
        }, SyncSettings.pollInterval);
    }

    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
        isPolling = false;
    }

    function pollSyncStatus() {

        $.ajax({
            url: SyncSettings.restUrl,
            method: 'GET',
            headers: {
                'X-WP-Nonce': SyncSettings.nonce
            },
            success: function(response) {
                if (response.status === 'success') {
                    const data = response.data;
                    Object.entries(data).forEach(([key, value]) => {
                        updateProgressDisplay(value);
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to fetch sync status:', error);
                // Continue polling even on error, might be temporary
            }
        });
    }

    function updateProgressDisplay(data) {
        //console.log(data);
        const {
            sync_status,
            percent_complete,
            total,
            synced,
            failed,
            is_running,
            next_at_formatted,
            started_at_formatted,
        } = data;

        const progId = data.key + '-progress-panel';
        const progSel = '#' + progId;

        const progressPanel = $(progSel);
        const progressFill = $(progSel + ' .progress-fill');
        const progressContainer = $(progSel + ' .progress-container');
        const progressPercentage = $(progSel + ' .progress-percentage');
        const progressDetails = $(progSel + ' .progress-details');
        const statusBadge = $(progSel + ' .sync-status-badge');
        const statusTitle = $(progSel + ' .sync-status-title');
        const currentStatus = $(progSel + ' .sync-stat-status');
        const totalSkus = $(progSel + ' .sync-stat-total');
        const syncedSkus = $(progSel + ' .sync-stat-synced');
        const nextScheduled = $(progSel + ' .sync-stat-next-scheduled');
        const startedAt = $(progSel + ' .sync-stat-started');
        const failedSkus = $(progSel + ' .sync-stat-failed');
        const actionButton = $(progSel + ' .sync-action-button');

        // Update action button based on sync status
        updateActionButton(actionButton, sync_status, data.key);

        // Show/hide progress panel based on sync status
        if (sync_status === 'running') {
            progressPanel.show();
            progressContainer.show();

            // Update progress bar
            const percentage = Math.min(100, Math.max(0, percent_complete || 0));
            progressFill.css('width', percentage + '%');
            progressPercentage.text(percentage + '%');

            // Update details
            progressDetails.text(`${synced || 0} / ${total || 0} synced`);
            //statusTitle.text(data.label + ' Sync In Progress');
            statusBadge.removeClass('completed failed cancelled').addClass('running').text('Running');

        } else if (sync_status === 'completed') {
            // Show completed status briefly
            progressPanel.show();
            progressContainer.show();
            progressFill.css('width', '100%');
            progressPercentage.text('100%');
            progressDetails.text(`${synced || 0} / ${total || 0} synced`);
            //statusTitle.text(data.label + ' Sync Completed');
            statusBadge.removeClass('running failed cancelled').addClass('completed').text('Completed');

/*            // Hide after 5 seconds and refresh page to show updated table
            setTimeout(function() {
                progressPanel.hide();
                //location.reload(); // Refresh to show updated sync history
            }, 5000);

*/

        } else if (sync_status === 'failed' || sync_status === 'cancelled' || sync_status === 'not_started') {
            // Show failed status
            progressPanel.show();
            progressContainer.hide();

            if(sync_status === 'failed') {
                statusBadge.removeClass('running completed cancelled').addClass('failed').text('Failed');
            } else if(sync_status === 'not_started' ) {
                statusBadge.removeClass('running completed failed').addClass('cancelled').text('Not Started');
            } else {
                statusBadge.removeClass('running completed failed').addClass('cancelled').text('Cancelled');
            }

/*
            // Hide after 10 seconds
            setTimeout(function() {
                progressPanel.hide();
                //location.reload();
            }, 10000);
*/

        } else {
            progressPanel.hide();
        }

        // Update detail fields
        currentStatus.text(sync_status || '-');
        totalSkus.text(total);
        syncedSkus.text(synced);
        failedSkus.text(failed);
        nextScheduled.text(next_at_formatted || 'Not scheduled');
        startedAt.text(started_at_formatted || '-');
    }

    function updateActionButton(button, syncStatus, syncType) {
        // Re-enable button and update text/action based on status
        button.prop('disabled', false);
        
        if (syncStatus === 'running') {
            button.text('Cancel')
                  .removeClass('button-primary')
                  .addClass('button-secondary')
                  .data('action', 'cancel');
        } else {
            button.text('Sync Now')
                  .removeClass('button-secondary')
                  .addClass('button-primary')
                  .data('action', 'sync');
        }
    }

    // Stop polling when page is about to unload
    $(window).on('beforeunload', function() {
        stopPolling();
    });

    // Handle visibility change (pause polling when tab is not active)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else {
            startPolling();
        }
    });
});