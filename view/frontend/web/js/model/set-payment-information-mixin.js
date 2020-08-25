/**
 * Copyright © RohitKundale. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global alert*/
define([
    'jquery',
    'mage/utils/wrapper',
    'RohitKundale_OrderComment/js/model/agreements-assigner'
], function ($, wrapper, commentAssigner) {
    'use strict';

    return function (placeOrderAction) {

        /** Override place-order-mixin for set-payment-information action as they differs only by method signature */
        return wrapper.wrap(placeOrderAction, function (originalAction, messageContainer, paymentData) {
            commentAssigner(paymentData);

            return originalAction(messageContainer, paymentData);
        });
    };
});
