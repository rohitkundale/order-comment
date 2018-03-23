<?php
/**
 * Copyright © 2017 RohitKundale. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace RohitKundale\OrderComment\Plugin\Model\Checkout;

use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class PaymentInformationManagement
 *
 * @package RohitKundale_OrderComment
 */
class PaymentInformationManagement
{
    /**
     * @var \Magento\Sales\Model\Order\Status\HistoryFactory
     */
    protected $historyFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $_filterManager;

    /**
     * PaymentInformationManagement constructor.
     *
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory $historyFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Sales\Model\Order\Status\HistoryFactory $historyFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory
    )
    {
        $this->_jsonHelper = $jsonHelper;
        $this->_filterManager = $filterManager;
        $this->historyFactory = $historyFactory;
        $this->orderFactory = $orderFactory;
    }

    public function aroundSavePaymentInformation(
        \Magento\Checkout\Model\PaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    )
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $checkoutSession = $objectManager->create('\Magento\Checkout\Model\Session');

        $comment = NULL;
        // get JSON post data
        $requestBody = file_get_contents('php://input');
        // decode JSON post data into array
        $data = $this->_jsonHelper->jsonDecode($requestBody);
        // get order comments from decoded json post data
        if (isset ($data['comments'])) {
            // make sure there is a comment to save
            if ($data['comments']) {
                // remove any HTML tags
                $comment = $this->_filterManager->stripTags($data['comments']);
                $comment = __('Order Comment: ') . $comment;
                $checkoutSession->setOrderCommentstext($comment);
            }
        }
        // run parent method and capture int $orderId
        $result = $proceed($cartId, $paymentMethod, $billingAddress);

        return $result;
    }

    public function aroundPlaceOrder(
        \Magento\Quote\Model\QuoteManagement $subject,
        \Closure $proceed,
        $cartId,
        PaymentInterface $paymentMethod = null
    )
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $checkoutSession = $objectManager->create('\Magento\Checkout\Model\Session');
        $comment = $checkoutSession->getOrderCommentstext();

        $orderId = $proceed($cartId, $paymentMethod);
        if ($comment) {
            /** @param \Magento\Sales\Model\OrderFactory $order */
            $order = $this->orderFactory->create()->load($orderId);
            // make sure $order is exists
            if ($order->getEntityId()) {
                /** @param string $status */
                $status = $order->getStatus();

                /** @param \Magento\Sales\Model\Order\Status\HistoryFactory $history */
                $history = $this->historyFactory->create();
                // set comment history data
                $history->setComment($comment);
                $history->setParentId($orderId);
                $history->setIsVisibleOnFront(1);
                $history->setIsCustomerNotified(0);
                $history->setEntityName('order');
                $history->setStatus($status);
                $history->save();
                $order->setCustomerNote($comment);
                $order->save();
                $checkoutSession->setOrderCommentstext("");
            }
        }
    }
}