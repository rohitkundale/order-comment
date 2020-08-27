<?php
/**
 * Copyright © RohitKundale. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace RohitKundale\OrderComment\Plugin\Model\Checkout;

use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Json\Helper\Data;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;

/**
 * Class GuestPaymentInformationManagement
 *
 * @package RohitKundale_OrderComment
 */
class GuestPaymentInformationManagement
{
    /**
     * @var HistoryFactory
     */
    protected $historyFactory;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    protected $historyRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * GuestPaymentInformationManagement constructor.
     *
     * @param Data $jsonHelper
     * @param FilterManager $filterManager
     * @param HistoryFactory $historyFactory
     * @param OrderStatusHistoryRepositoryInterface $historyRepository
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Data $jsonHelper,
        FilterManager $filterManager,
        HistoryFactory $historyFactory,
        OrderStatusHistoryRepositoryInterface $historyRepository,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->jsonHelper        = $jsonHelper;
        $this->filterManager     = $filterManager;
        $this->historyFactory    = $historyFactory;
        $this->historyRepository = $historyRepository;
        $this->orderRepository   = $orderRepository;
    }

    /**
     * @param \Magento\Checkout\Model\GuestPaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return int $orderId
     * @throws \Exception
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Model\GuestPaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress
    ) {
        /** @param string $comment */
        $comment = null;
        // get JSON post data
        $requestBody = file_get_contents('php://input');
        // decode JSON post data into array
        $data = $this->jsonHelper->jsonDecode($requestBody);
        // get order comments from decoded json post data
        // make sure there is a comment to save
        $comment = $data['paymentMethod']['extension_attributes']['comments'];
        if (isset($comment) && $comment) {
            // remove any HTML tags
            $comment = $this->filterManager->stripTags($comment);
            $comment = __('ORDER COMMENT: ') . $comment;
        }
        // run parent method and capture int $orderId
        $orderId = $proceed($cartId, $email, $paymentMethod, $billingAddress);
        // if $comments
        if ($comment) {
            $order = $this->orderRepository->get($orderId);
            // make sure $order exists
            if ($order->getEntityId()) {
                /** @param string $status */
                $status = $order->getStatus();

                /** @var OrderStatusHistoryInterface $history */
                $history = $this->historyFactory->create();
                // set comment history data
                $history->setComment($comment);
                $history->setParentId($orderId);
                $history->setIsVisibleOnFront(1);
                $history->setIsCustomerNotified(0);
                $history->setEntityName('order');
                $history->setStatus($status);
                $this->historyRepository->save($history);
            }
        }
        return $orderId;
    }
}
