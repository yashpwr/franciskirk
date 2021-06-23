<?php
namespace Cminds\MultiUserAccounts\Helper;

use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Cminds\MultiUserAccounts\Exception\WrongAddressException;
use Cminds\MultiUserAccounts\Api\SubaccountTransportRepositoryInterface;
use Exception;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Framework\Registry;

/**
 * Cminds MultiUserAccounts order create helper.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class OrderCreate extends AbstractHelper
{
    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;
    
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * Subaccount transport repository object.
     *
     * @var SubaccountTransportRepositoryInterface
     */
    private $subaccountTransportRepository;
    
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @param Context $context
     * @param QuoteManagement $quoteManagement
     * @param CustomerFactory $customerFactory
     * @param CustomerRepository $customerRepository
     * @param ModuleConfig $moduleConfig
     * @param Session $customerSession
     * @param SubaccountTransportRepositoryInterface $subaccountTransportRepository
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
        QuoteManagement $quoteManagement,
        CustomerFactory $customerFactory,
        CustomerRepository $customerRepository,
        ModuleConfig $moduleConfig,
        Session $customerSession,
        SubaccountTransportRepositoryInterface $subaccountTransportRepository,
        Registry $coreRegistry
    ) {
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->moduleConfig = $moduleConfig;
        $this->customerSession = $customerSession;
        $this->subaccountTransportRepository = $subaccountTransportRepository;
        $this->coreRegistry = $coreRegistry;
        
        parent::__construct($context);
    }

    /**
     * Manually create order from quote object, with specified shipping
     * and payment methods.
     *
     * @param Quote $quote
     * @param $shippingMethod
     * @param $paymentMethod
     * @param int $customerId
     * @return null|string
     * @throws Exception
     * @throws WrongAddressException
     */
    public function createOrderFromQuote(
        Quote $quote,
        $shippingMethod,
        $paymentMethod,
        $customerId = 0
    ) {
        $customer = $this->customerFactory
            ->create()
            ->load($customerId ?: $quote->getCustomerId());

        if ($this->moduleConfig->isNestedSubaccountsAllowed() === true
            && $this->customerSession->getSubaccountData()  // is a subaccount
        ) {
            // skip address plugin for a subaccount user, that tries to create an order on approval
            // ( in case of force address permission for current subaccount )
            $skipPluginFlag =
                \Cminds\MultiUserAccounts\Model\Plugin\Customer\Address\Collection\Load\Plugin::PLUGIN_SKIP;
            $this->coreRegistry->register($skipPluginFlag, true);
        }
        $subCustomer = $this->customerRepository->getById($quote->getCustomerId());
        
        $subaccountTransportDataObject = $this
            ->subaccountTransportRepository
            ->getByCustomerId($quote->getSubaccountId());

        $forceCompanyName = (bool)($subaccountTransportDataObject->getForceUsageParentCompanyNamePermission()
            || $this->moduleConfig->isForceUseParentAccountDetailsForOrderEnabled()
        );
        $forceVat = (bool)($subaccountTransportDataObject->getForceUsageParentVatPermission()
            || $this->moduleConfig->isForceUseParentAccountDetailsForOrderEnabled()
        );
        $forceAddresses = (bool)($subaccountTransportDataObject->getForceUsageParentAddressesPermission()
            || $this->moduleConfig->isForceUseParentAccountDetailsForOrderEnabled()
        );
        
        $parentCustomer = null;
        if ($forceCompanyName || $forceVat || $forceAddresses) {
            /** @var Customer $parentCustomer */
            $parentCustomer = $this->customerFactory->create()
                ->load($this->customerSession->getCustomerId());
        }
        $billingAddress = $customer->getDefaultBillingAddress();
        $shippingAddress = $customer->getDefaultShippingAddress();

        // force company name
        if ($subaccountTransportDataObject->getForceUsageParentCompanyNamePermission()
            && !$subaccountTransportDataObject->getForceUsageParentAddressesPermission() // no need to force company name if address is forced
            && $parentCustomer
        ) {
            $billingAddress['company'] = $parentCustomer->getDefaultBillingAddress()->getCompany();
            $shippingAddress['company'] = $parentCustomer->getDefaultShippingAddress()->getCompany();
        }

        // force addresses
        if ($forceAddresses && $parentCustomer) {
            $billingAddress = $parentCustomer->getDefaultBillingAddress();
            $shippingAddress = $parentCustomer->getDefaultShippingAddress();
        }

        if (isset($skipPluginFlag)) {
            $this->coreRegistry->unregister($skipPluginFlag);
        }

        if ($billingAddress && $shippingAddress) {
            
            $billingData = $billingAddress->getData();
            $shippingData = $shippingAddress->getData();

            unset($billingData['attributes']);
            unset($shippingData['attributes']);
            
            $quote->setCurrency();
            $quote->assignCustomer($subCustomer);
            
            // force taxvat
            if ($forceVat && $parentCustomer) {
                $quote->setCustomerTaxvat($parentCustomer->getTaxvat());
                $quote->getCustomer()->setTaxvat($parentCustomer->getTaxvat());
                if (!$forceAddresses) {
                    $shippingData['vat_id'] = $billingData['vat_id'] =
                        $parentCustomer->getDefaultBillingAddress()->getVatId();
                }
            }

            $quote
                ->getBillingAddress()
                ->addData($billingData);
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress
                ->addData($shippingData)
                ->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($shippingMethod);

            $rate = $shippingAddress->getShippingRateByCode($shippingMethod);

            if (!$shippingMethod || !$rate) {
                throw new WrongAddressException(
                    __('Default shipping method is not available for specified customer address.')
                );
            }

            $quote
                ->setPaymentMethod($paymentMethod)
                ->save();
            $quote
                ->getPayment()
                ->importData(['method' => $paymentMethod]);
            $quote
                ->collectTotals()
                ->save();

            $order = $this->quoteManagement->submit($quote);
            if (!$order) {
                throw new WrongAddressException(__('The order was not created.'));
            }

            $order->setEmailSent(1);

            return $order->getIncrementId();
        }

        throw new WrongAddressException(
            __("Customer {$customer->getEmail()} has no default address specified.")
        );
    }
}
