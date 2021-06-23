<?php
/**
 *  Block OrderApproveMultishipping
 */
namespace Cminds\MultiUserAccounts\Block\Checkout\Button;

use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

use Magento\Framework\Filter\DataObject\GridFactory;
use Magento\Multishipping\Model\Checkout\Type\Multishipping;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Address\Config;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\Session;

/**
 * Cminds MultiUserAccounts checkout order approve button block for multiple shipping checkout.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class OrderApproveMultishipping extends \Magento\Multishipping\Block\Checkout\Addresses
{
    /**
     * View helper object.
     *
     * @var ViewHelper
     */
    private $viewHelper;

    /**
     * Module config object.
     *
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * Customer session
     *
     * @var Session
     */
    protected $customerSession;

    /**
     * GridFactory
     *
     * @var GridFactory
     */
    protected $filterGridFactory;

    /**
     * Multishipping
     *
     * @var Multishipping
     */
    protected $multishipping;

    /**
     * CustomerRepositoryInterface
     *
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Address Config
     *
     * @var Config
     */
    protected $addressConfig;

    /**
     * Address Mapper
     *
     * @var Mapper
     */
    protected $addressMapper;

    /**
     * Object initialization.
     *
     * @param Context         $context Context object.
     * @param ViewHelper      $viewHelper View helper object.
     * @param ModuleConfig    $moduleConfig Module config object.
     * @param array           $data Data array.
     */
    public function __construct(
        Context $context,
        ViewHelper $viewHelper,
        ModuleConfig $moduleConfig,
        GridFactory $filterGridFactory,
        Multishipping $multishipping,
        CustomerRepositoryInterface $customerRepository,
        Config $addressConfig,
        Mapper $addressMapper,
        Session $customerSession,
        array $data = []
    ) {
        $this->viewHelper = $viewHelper;
        $this->moduleConfig = $moduleConfig;
        $this->filterGridFactory = $filterGridFactory;
        $this->multishipping = $multishipping;
        $this->customerRepository = $customerRepository;
        $this->addressConfig = $addressConfig;
        $this->addressMapper = $addressMapper;
        $this->customerSession = $customerSession;
        parent::__construct($context, $filterGridFactory, $multishipping, $customerRepository, $addressConfig, $addressMapper, $data);
    }

    /**
     * Return bool value if button is visible or not.
     *
     * @return bool
     */
    public function isVisible()
    { 
        if ($this->moduleConfig->isEnabled() === false
            || $this->viewHelper->isSubaccountLoggedIn() === false
        ) {
            return false;
        }

        /**
         * Getting permission from cusotmer session
         */
        $customerData = $this->customerSession->getData();
        $approvalPermission = $customerData['subaccount_data']->getCheckoutOrderApprovalPermission();

        $permission = (bool)$approvalPermission;

        if ($permission === false) {
            return false;
        }
        return true;
    }
}