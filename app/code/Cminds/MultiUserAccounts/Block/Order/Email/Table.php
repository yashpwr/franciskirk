<?php
/**
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Block\Order\Email;

use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;

/**
 * Cminds MultiUserAccounts Order Email Table Block.
 *
 * @package Cminds\MultiUserAccounts\Block\Order\Email
 */
class Table extends AbstractCart
{
    /**
     * @var array
     */
    private $quotes;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * Object initialization.
     *
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param QuoteFactory $quoteFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        QuoteCollectionFactory $quoteCollectionFactory,
        QuoteFactory $quoteFactory,
        array $data = []
    ) {
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->quoteFactory = $quoteFactory;

        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $data
        );
    }

    /**
     * @return array|bool
     */
    public function getQuotes()
    {
        $customerId = $this->_customerSession->getCustomerId();

        if ($customerId === null) {
            return false;
        }

        $subaccountTransportDataObject = $this->_customerSession
            ->getSubaccountData();

        if (!empty($subaccountTransportDataObject) && !$this->quotes) {
            $customerEmail = $subaccountTransportDataObject->getEmail();

            /** @var Collection $quoteCollection */
            $quoteCollection = $this->quoteCollectionFactory->create()
                ->addFieldToSelect('*')
                ->setOrder(
                    'updated_at',
                    'ASC'
                );
            $quoteCollection
                ->getSelect()
                ->where("`is_active`=1 AND `customer_email`='".$customerEmail."'");

            $this->quotes = $quoteCollection;
        }

        return $this->quotes;
    }

    /**
     * Get all cart items.
     *
     * @param   int $quoteId
     *
     * @return  array
     */
    public function getQuoteItems($quoteId)
    {
        /** @var \Magento\Quote\Model\Quote $quoteModel */
        $quoteModel = $this->quoteFactory
            ->create()
            ->loadByIdWithoutStore($quoteId);

        return $quoteModel->getAllVisibleItems();
    }
}
