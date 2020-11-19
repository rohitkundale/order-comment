<?php
/**
 * Copyright ©  RohitKundale. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace RohitKundale\OrderComment\Plugin\Model\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Json\Helper\Data;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\OrderFactory;

/**
 * Class PaymentInformationManagement
 *
 * @package RohitKundale_OrderComment
 */
class PaymentInformationManagement
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
     * @var Session
     */
    protected $checkoutSession;

    /**
     * PaymentInformationManagement constructor.
     *
     * @param Data $jsonHelper
     * @param FilterManager $filterManager
     * @param HistoryFactory $historyFactory
     * @param OrderStatusHistoryRepositoryInterface $historyRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param Session $checkoutSession
     */
    public function __construct(
        Data $jsonHelper,
        FilterManager $filterManager,
        HistoryFactory $historyFactory,
        OrderStatusHistoryRepositoryInterface $historyRepository,
        OrderRepositoryInterface $orderRepository,
        Session $checkoutSession
    ) {
        $this->jsonHelper        = $jsonHelper;
        $this->filterManager     = $filterManager;
        $this->historyFactory    = $historyFactory;
        $this->historyRepository = $historyRepository;
        $this->orderRepository   = $orderRepository;
        $this->checkoutSession   = $checkoutSession;
    }

    /**
     * @param \Magento\Checkout\Model\PaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return mixed
     */
    public function aroundSavePaymentInformation(
        \Magento\Checkout\Model\PaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        $comment = null;
        // get JSON post data
        $requestBody = file_get_contents('php://input');
        // decode JSON post data into array
        $data = $this->jsonHelper->jsonDecode($requestBody);
        // get order comments from decoded json post data
        // make sure there is a comment to save
        $comment = isset($data['paymentMethod']['extension_attributes']['comments']) ? $data['paymentMethod']['extension_attributes']['comments'] : false;
        if ($comment) {
            // remove any HTML tags
            $comment = $this->filterManager->stripTags($comment);
            $comment = __('ORDER COMMENT: ') . $comment;
            $this->checkoutSession->setOrderCommentstext($comment);
        }
        // run parent method and capture int $orderId
        return $proceed($cartId, $paymentMethod, $billingAddress);
    }

    /**
     * @param QuoteManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param PaymentInterface|null $paymentMethod
     * @return mixed
     * @throws \Exception
     */
    public function aroundPlaceOrder(
        QuoteManagement $subject,
        \Closure $proceed,
        $cartId,
        PaymentInterface $paymentMethod = null
    ) {
        $comment = $this->checkoutSession->getOrderCommentstext();

        $orderId = $proceed($cartId, $paymentMethod);
        if ($comment) {
            /** @param OrderFactory $order */
            $order = $this->orderRepository->get($orderId);
            // make sure $order is exists
            if ($order->getEntityId()) {
                /** @param string $status */
                $status = $order->getStatus();

                /** @param HistoryFactory $history */
                $history = $this->historyFactory->create();
                // set comment history data
                $history->setComment($comment);
                $history->setParentId($orderId);
                $history->setIsVisibleOnFront(1);
                $history->setIsCustomerNotified(0);
                $history->setEntityName('order');
                $history->setStatus($status);
                $this->historyRepository->save($history);
                $order->setCustomerNote($comment);
                $this->orderRepository->save($order);
                $this->checkoutSession->setOrderCommentstext("");
            }
        }

        return $orderId;
    }
}
