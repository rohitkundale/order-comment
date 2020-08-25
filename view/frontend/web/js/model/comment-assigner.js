/**
 * Copyright © RohitKundale. All rights reserved.
 * See COPYING.txt for license details.
 */

/*global alert*/
define([
    'jquery'
], function ($) {
    'use strict';

    var commentConfig = window.checkoutConfig.show_comment_block;

    /** Override default place order action and add comments to request */
    return function (paymentData) {
        var commentInput,
            comment;

        if (!commentConfig) {
            return;
        }

        commentInput = jQuery('[name="comment-code"]');
        comment = commentInput.val();

        if (paymentData['extension_attributes'] === undefined) {
            paymentData['extension_attributes'] = {};
        }

        paymentData['extension_attributes']['comments'] = comment;
    };
});
