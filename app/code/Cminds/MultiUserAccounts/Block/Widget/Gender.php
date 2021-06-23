<?php

namespace Cminds\MultiUserAccounts\Block\Widget;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\OptionInterface;
use Magento\Customer\Block\Widget\AbstractWidget;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Helper\Address;
use Magento\Customer\Model\Session;

/**
 * Cminds MultiUserAccounts customer account confirm controller plugin.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Jigs
 */
class Gender extends AbstractWidget
{
    /**
     * View helper object.
     *
     * @var ViewHelper
     */
    private $viewHelper;
    
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Create an instance of the Gender widget.
     *
     * @param Context $context
     * @param Address $addressHelper
     * @param CustomerMetadataInterface $customerMetadata
     * @param CustomerRepositoryInterface $customerRepository
     * @param Session $customerSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        Address $addressHelper,
        CustomerMetadataInterface $customerMetadata,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        ViewHelper $viewHelper,
        ModuleConfig $moduleConfig,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $addressHelper, $customerMetadata, $data);
        $this->_isScopePrivate = true;
        $this->moduleConfig = $moduleConfig;
        $this->viewHelper = $viewHelper;
    }

    /**
     * Initialize block.
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate(
            'Cminds_MultiUserAccounts::account/dashboard/widget/gender.phtml'
        );
    }

    /**
     * Check if gender attribute enabled in system.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_getAttribute('gender') ? (bool)$this->_getAttribute('gender')->isVisible() : false;
    }

    /**
     * Check if gender attribute marked as required.
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->_getAttribute('gender') ? (bool)$this->_getAttribute('gender')->isRequired() : false;
    }

    /**
     * Get current customer from session.
     *
     * @return CustomerInterface
     */
    public function getCustomer()
    {
        return $this->customerRepository->getById(
            $this->customerSession->getCustomerId()
        );
    }

    /**
     * Returns options from gender attribute.
     *
     * @return OptionInterface[]
     */
    public function getGenderOptions()
    {
        return $this->_getAttribute('gender')->getOptions();
    }
}
