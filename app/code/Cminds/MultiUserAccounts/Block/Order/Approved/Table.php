<?php
/**
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Block\Order\Approved;

use Cminds\MultiUserAccounts\Api\SubaccountTransportRepositoryInterface;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount\CollectionFactory as SubaccountCollectionFactory;
use Cminds\MultiUserAccounts\Model\Service\Order\ApproveRequest;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Pricing\Helper\Data as PricingHelperData;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;

/**
 * Cminds MultiUserAccounts waiting for approve orders list block.
 *
 * @package Cminds\MultiUserAccounts\Block\Order\Approved
 */
class Table extends \Cminds\MultiUserAccounts\Block\Order\Approve\Table
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
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * Table constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param SubaccountCollectionFactory $subaccountCollectionFactory
     * @param PricingHelperData $pricingHelperData
     * @param QuoteFactory $quoteFactory
     * @param ViewHelper $viewHelper
     * @param ModuleConfig $moduleConfig
     * @param ApproveRequest $approveRequest
     * @param SubaccountTransportRepositoryInterface $subaccountTransportRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        QuoteCollectionFactory $quoteCollectionFactory,
        SubaccountCollectionFactory $subaccountCollectionFactory,
        PricingHelperData $pricingHelperData,
        QuoteFactory $quoteFactory,
        ViewHelper $viewHelper,
        ModuleConfig $moduleConfig,
        ApproveRequest $approveRequest,
        SubaccountTransportRepositoryInterface $subaccountTransportRepository,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $quoteCollectionFactory,
            $subaccountCollectionFactory,
            $pricingHelperData, $quoteFactory,
            $viewHelper,
            $moduleConfig,
            $approveRequest,
            $subaccountTransportRepository,
            $data
        );
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->quoteFactory = $quoteFactory;
        $this->moduleConfig = $moduleConfig;
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
            /** @var Collection $quoteCollection */
            $quoteCollection = $this->quoteCollectionFactory->create()
                ->addFieldToSelect('*')
                ->setOrder(
                    'updated_at',
                    'ASC'
                );
            $quoteCollection
                ->getSelect()
//                ->where('is_approved=1')
                ->where('subaccount_id='.$customerId);

            $this->quotes = $quoteCollection;
        }
        return $this->quotes;
    }

    /**
     * @param QuoteModel $quoteModel
     * @param string $action
     *
     * @return string
     */
    public function getOrderCreateUrl(QuoteModel $quoteModel)
    {
        $url = $this->getUrl('subaccounts/order_approve/waiting');
        $uenc = base64_encode($url);

        return $this->getUrl(
            'subaccounts/order/create',
            [
                'id' => $quoteModel->getId(),
                'uenc' => $uenc
            ]
        );
    }

    /**
     * @param $quote
     * @return string
     */
    public function getQuoteStatus($quote)
    {
        if (
            $this->moduleConfig->isOrderApprovalRequestAuthorizationRequired() === true
            && $quote->getIsAuthorized() == 0
        ) {
            return 'Not authorized';
        }

        return 'Not approved';
    }
}
