/**
 * Schedule Toggle Handler
 * 
 * Automatically enables/disables schedule interval fields based on
 * the corresponding schedule enabled checkbox state.
 * 
 * Works with any synchronizer that follows the CSS class pattern:
 * - wm-{type}-schedule-enabled (checkbox)
 * - wm-{type}-schedule-interval (select/interval field)
 * 
 * Additional fields can be controlled by using data attributes:
 * - data-schedule-dependent="sync-type" on any field to make it dependent on schedule state
 * - Or by using CSS class pattern: wm-{type}-schedule-dependent
 * 
 * Events triggered:
 * - scheduleToggleInit: When toggle system is initialized
 * - scheduleToggled: When any schedule checkbox changes (main event)
 * - scheduleEnabled: When schedule is enabled for a specific type
 * - scheduleDisabled: When schedule is disabled for a specific type
 * - beforeScheduleToggle: Before the toggle happens (cancellable)
 * - afterScheduleToggle: After all toggle operations complete
 */
(function($) {
    'use strict';

    // Global object for extensibility
    window.ScheduleToggle = {
        /**
         * Get all fields related to a specific sync type
         * @param {string} type - The sync type
         * @returns {jQuery} - All related fields
         */
        getRelatedFields: function(type) {
            return $(`.wm-${type}-schedule-interval, .wm-${type}-schedule-dependent, [data-schedule-dependent="${type}"]`);
        },

        /**
         * Manually toggle schedule for a type
         * @param {string} type - The sync type
         * @param {boolean} enabled - Whether to enable or disable
         */
        toggleType: function(type, enabled) {
            const checkbox = $(`.wm-${type}-schedule-enabled`);
            if (checkbox.length) {
                checkbox.prop('checked', enabled).trigger('change');
            }
        },

        /**
         * Check if schedule is enabled for a type
         * @param {string} type - The sync type
         * @returns {boolean}
         */
        isEnabled: function(type) {
            return $(`.wm-${type}-schedule-enabled`).is(':checked');
        },

        /**
         * Add custom field to be controlled by schedule toggle
         * @param {string} type - The sync type
         * @param {string|jQuery} field - Field selector or jQuery object
         */
        addDependentField: function(type, field) {
            const $field = typeof field === 'string' ? $(field) : field;
            $field.attr('data-schedule-dependent', type);

            // Apply current state
            const isEnabled = this.isEnabled(type);
            toggleFieldState($field, isEnabled, type);
        }
    };

    /**
     * Initialize schedule toggle functionality
     */
    function initScheduleToggle() {
        // Trigger initialization event
        $(document).trigger('scheduleToggleInit');

        // Handle existing elements on page load
        $('.wm-schedule-enabled').each(function() {
            toggleScheduleInterval($(this), true);
        });

        // Handle changes to schedule enabled checkboxes
        $(document).on('change', '.wm-schedule-enabled', function() {
            toggleScheduleInterval($(this), false);
        });
    }

    /**
     * Toggle the schedule interval field based on checkbox state
     * @param {jQuery} checkbox - The schedule enabled checkbox
     * @param {boolean} isInit - Whether this is initial setup
     */
    function toggleScheduleInterval(checkbox, isInit = false) {
        const isEnabled = checkbox.is(':checked');
        const type = extractSyncType(checkbox);

        if (!type) return;

        // Trigger before event (cancellable)
        const beforeEvent = $.Event('beforeScheduleToggle', {
            type: type,
            enabled: isEnabled,
            checkbox: checkbox,
            isInit: isInit
        });
        $(document).trigger(beforeEvent);

        if (beforeEvent.isDefaultPrevented()) {
            return;
        }

        // Find all related fields
        const relatedFields = window.ScheduleToggle.getRelatedFields(type);

        // Toggle each related field
        relatedFields.each(function() {
            toggleFieldState($(this), isEnabled, type);
        });

        // Trigger specific enable/disable events
        const specificEvent = isEnabled ? 'scheduleEnabled' : 'scheduleDisabled';
        $(document).trigger(specificEvent, {
            type: type,
            checkbox: checkbox,
            relatedFields: relatedFields,
            isInit: isInit
        });

        // Trigger main event for backward compatibility and general use
        $(document).trigger('scheduleToggled', {
            type: type,
            enabled: isEnabled,
            checkbox: checkbox,
            relatedFields: relatedFields,
            intervalField: relatedFields.filter(`.wm-${type}-schedule-interval`),
            isInit: isInit
        });

        // Trigger after event
        $(document).trigger('afterScheduleToggle', {
            type: type,
            enabled: isEnabled,
            checkbox: checkbox,
            relatedFields: relatedFields,
            isInit: isInit
        });
    }

    /**
     * Toggle the state of a field based on schedule enabled status
     * @param {jQuery} field - The field to toggle
     * @param {boolean} isEnabled - Whether schedule is enabled
     * @param {string} type - The sync type
     */
    function toggleFieldState(field, isEnabled, type) {
        const container = field.closest('tr, .form-field, .field-container, .form-table');

        // Set field state
        field.prop('disabled', !isEnabled);

        // Add/remove classes for styling
        if (isEnabled) {
            field.removeClass('disabled schedule-disabled');
            container.removeClass('disabled-field schedule-disabled-field');
        } else {
            field.addClass('disabled schedule-disabled');
            container.addClass('disabled-field schedule-disabled-field');
        }

        // Add data attributes for CSS targeting
        field.attr('data-schedule-state', isEnabled ? 'enabled' : 'disabled');
        container.attr('data-schedule-state', isEnabled ? 'enabled' : 'disabled');
    }

    /**
     * Extract sync type from checkbox CSS classes
     * @param {jQuery} checkbox - The checkbox element
     * @returns {string|null} - The sync type or null if not found
     */
    function extractSyncType(checkbox) {
        const classes = checkbox.attr('class').split(/\s+/);

        for (let className of classes) {
            // Look for pattern: wm-{type}-schedule-enabled
            const match = className.match(/^wm-(.+)-schedule-enabled$/);
            if (match) {
                return match[1]; // Return the type part
            }
        }

        return null;
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initScheduleToggle();
    });

    // Re-initialize after AJAX requests (for dynamic content)
    $(document).ajaxComplete(function() {
        initScheduleToggle();
    });

})(jQuery);
