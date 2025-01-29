define(['mage/utils/wrapper', 'Magento_Checkout/js/model/quote'], function (wrapper, quote) {
    'use strict'; return function (placeOrderAction) {
        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
            if (!paymentData.additional_data) { paymentData.additional_data = {}; }
            paymentData.additional_data.form_check = 'true';
            return originalAction(paymentData, messageContainer);
        });
    };
});
