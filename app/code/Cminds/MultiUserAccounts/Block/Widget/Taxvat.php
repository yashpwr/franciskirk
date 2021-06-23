<?php

namespace Cminds\MultiUserAccounts\Block\Widget;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Block\Widget\AbstractWidget;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Helper\Address;
use Magento\Customer\Model\Session as CustomerSession;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;

/**
 * Cminds MultiUserAccounts customer account confirm controller plugin.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Jigs
 */
class Taxvat extends AbstractWidget
{
    /**
     * View helper object.
     *
     * @var ViewHelper
     */
    private $viewHelper;
    
    /**
     * Session object.
     *
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Address $addressHelper
     * @param CustomerMetadataInterface $customerMetadata
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        ViewHelper $viewHelper,
        ModuleConfig $moduleConfig,
        Address $addressHelper,
        CustomerMetadataInterface $customerMetadata,
        array $data = []
    ) {
        parent::__construct($context, $addressHelper, $customerMetadata, $data);
        $this->moduleConfig = $moduleConfig;
        $this->customerSession = $customerSession;
        $this->viewHelper      = $viewHelper;
        $this->_isScopePrivate = true;
    }

    /**
     * Sets the template.
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate(
            'Cminds_MultiUserAccounts::account/dashboard/widget/taxvat.phtml'
        );
    }

    /**
     * Get is tax enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_getAttribute('taxvat') ? (bool)$this->_getAttribute('taxvat')->isVisible() : false;
    }

    /**
     * Get is tax required.
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->_getAttribute('taxvat') ? (bool)$this->_getAttribute('taxvat')->isRequired() : false;
    }

    /**
     * Get account tax.
     *
     * @return bool
     */
    public function getAccountTaxvat()
    {
        return $this->getTaxvat();
    }
}
