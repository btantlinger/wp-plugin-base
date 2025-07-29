// Test JavaScript for MainPage
(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('MainPage test.js loaded successfully!');

        // Add a click handler to the test button
        $('.test-button').on('click', function(e) {
            e.preventDefault();

            // Show an alert
            alert('Test JavaScript is working! Button clicked.');

            // Change the button text
            $(this).text('JavaScript Works!').css('background-color', '#00a32a');

            // Update the status message
            $('.js-status').text('✅ JavaScript is loaded and working!').css('color', '#00a32a');
        });

        // Add a visual indicator that JS loaded
        $('.asset-status').append('<p class="js-status">✅ JavaScript file loaded successfully</p>');

        // Add some interactive behavior
        $('.main-page-test').hover(
            function() {
                $(this).css('box-shadow', '0 4px 8px rgba(0,115,170,0.3)');
            },
            function() {
                $(this).css('box-shadow', 'none');
            }
        );
    });

})(jQuery);