<?php

namespace StripeIntegration\Payments\Observer;

use Magento\Framework\Event\ObserverInterface;
use StripeIntegration\Payments\Helper\Logger;

class CustomerRegistration implements ObserverInterface
{
    public function __construct(
        \StripeIntegration\Payments\Model\StripeCustomer $customer,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    )
    {
        $this->customer = $customer;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $magentoCustomer = $observer->getCustomer();
        if (empty($magentoCustomer))
            return;

        $magentoCustomerId = $magentoCustomer->getId();
        if (!is_numeric($magentoCustomerId))
            return;

        $orders = $this->orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', $magentoCustomerId)
            ->addAttributeToSort('created_at', 'DESC')
            ->setPageSize(1);

        if ($orders->count() == 0)
            return;

        $order = $orders->getFirstItem();
        $payment = $order->getPayment();

        if (empty($payment))
            return;

        if (strpos($payment->getMethod(), 'stripe_payments') !== 0)
            return;

        $stripeCustomerId = $payment->getAdditionalInformation("customer_stripe_id");
        if (empty($stripeCustomerId))
            return;

        $this->customer->load($stripeCustomerId, 'stripe_id');
        if (empty($this->customer->getStripeId()))
            return;

        $this->customer->setCustomerId($magentoCustomer->getId())->save();
    }
}
