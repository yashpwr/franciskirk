define(
    [
        'ko',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/method-converter'
    ],
    function (
        ko,
        paymentService,
        quote,
        selectPaymentMethodAction,
        checkoutData,
        methodConverter
    ) {
        'use strict';
        paymentService.setPaymentMethods(methodConverter(window.checkoutConfig.paymentMethods));
        var default_payment_method = ko.observable(window.checkoutConfig.default_payment);
        return function () {
            if(paymentService.getAvailablePaymentMethods().length > 0){
                var methods = paymentService.getAvailablePaymentMethods();
                var method = ko.utils.arrayFirst(methods, function(method){
                    return (default_payment_method())?(method.method == default_payment_method()):true;
                })
                if(method && !quote.paymentMethod()){
                    selectPaymentMethodAction(method);
                    checkoutData.setSelectedPaymentMethod(method.method);
                }
            }
        };
    }
);
