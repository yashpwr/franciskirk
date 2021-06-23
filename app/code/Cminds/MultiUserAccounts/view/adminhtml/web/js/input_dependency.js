require([
    "jquery",
    "jquery/ui"
], function ($) {
    $(function () {
        if ($('#subaccount_checkout-order-approval-permission').is(':checked')!== true) {
            $('.field-manage_order_max_amount').addClass('hidden')
        }
        $('body').on('click', '#subaccount_checkout-order-approval-permission', function (e) {
            if ($(this).is(':checked')) {
                $('.field-manage_order_max_amount').removeClass('hidden')
            } else {
                $('.field-manage_order_max_amount').addClass('hidden')
            }
        });
    });
});
