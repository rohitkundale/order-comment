/**
 * Copyright © RohitKundale. All rights reserved.
 * See COPYING.txt for license details.
 */
var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/place-order': {
                'RohitKundale_OrderComment/js/model/place-order-mixin': true
            },
            'Magento_Checkout/js/action/set-payment-information': {
                'RohitKundale_OrderComment/js/model/set-payment-information-mixin': true
            }
        }
    }
};
