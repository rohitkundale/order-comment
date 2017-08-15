<?php
/**
 * Copyright © 2017 RohitKundale. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RohitKundale\OrderComment\Plugin\Model\Checkout;

/**
 * Class GuestPaymentInformationManagement
 *
 * @package RohitKundale_OrderComment
 */
class GuestPaymentInformationManagement
{
    /**
     * @var \Magento\Sales\Model\Order\Status\HistoryFactory
     */
	protected $_historyFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
	protected $_orderFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
	protected $_jsonHelper;

    /**
     * @var \Magento\Framework\Filter\FilterManager
     */
	protected $_filterManager;

    /**
     * GuestPaymentInformationManagement constructor.
     *
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory $historyFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Filter\FilterManager $filterManager,
		\Magento\Sales\Model\Order\Status\HistoryFactory $historyFactory,
		\Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->_jsonHelper = $jsonHelper;
        $this->_filterManager = $filterManager;
		$this->_historyFactory = $historyFactory;
		$this->_orderFactory = $orderFactory;
    }

    /**
     * @param \Magento\Checkout\Model\GuestPaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param $email
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return int $orderId
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
		\Magento\Checkout\Model\GuestPaymentInformationManagement $subject, 
		\Closure $proceed,
        $cartId,
		$email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress
    ) {	
		/** @param string $comment */
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
			}
		}
		// run parent method and capture int $orderId
		$orderId = $proceed($cartId, $email, $paymentMethod, $billingAddress);
		// if $comments
		if ($comment) {
			/** @param \Magento\Sales\Model\OrderFactory $order */
			$order = $this->_orderFactory->create()->load($orderId);
			// make sure $order exists 
			if ($order->getEntityId()) {
				/** @param string $status */
				$status = $order->getStatus();

				/** @param \Magento\Sales\Model\Order\Status\HistoryFactory $history */
				$history = $this->_historyFactory->create();
				// set comment history data
				$history->setComment($comment);
				$history->setParentId($orderId);
				$history->setIsVisibleOnFront(1);
				$history->setIsCustomerNotified(0);
				$history->setEntityName('order');
				$history->setStatus($status);
				$history->save();
			}
		}
		return $orderId;
    }
}