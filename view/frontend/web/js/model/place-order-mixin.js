/**
 * Copyright © RohitKundale. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/utils/wrapper',
    'RohitKundale_OrderComment/js/model/comment-assigner'
], function ($, wrapper, commentAssigner) {
    'use strict';

    return function (placeOrderAction) {

        /** Override default place order action and add comments to request */
        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
            commentAssigner(paymentData);

            return originalAction(paymentData, messageContainer);
        });
    };
});
