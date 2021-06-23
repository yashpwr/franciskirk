define([
    "jquery",
    "jquery/ui"
], function($) {
    "use strict";

    //creating jquery widget
    $.widget('input_dependency.js', {
        _create: function() {
            $(function() {
                // console.log( "ready!" );
                // console.log(this.element);
                if ( $('#checkout-order-approval-permission').is(':checked') )
                    $('.field-name-manage-order-max-amount').removeClass('hidden');
                    
                $('body').on('click', '#checkout-order-approval-permission', function(e) {
                    if ($(this).is(':checked')) {
                        $('.field-name-manage-order-max-amount').removeClass('hidden')
                    } else {
                        $('.field-name-manage-order-max-amount').addClass('hidden')
                    }
                });
                $('body').on('click', '#force-usage-parent-vat-permission', function(e) {
                    if ($(this).is(':checked')) {
                        $('.taxvat-hidden').addClass('hidden')
                    } else {
                        $('.taxvat-hidden').removeClass('hidden')
                    }
                });
            });
        }
    });

    return $.input_dependency.js;
});