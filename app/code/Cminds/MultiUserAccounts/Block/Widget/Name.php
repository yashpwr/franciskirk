<?php

namespace Cminds\MultiUserAccounts\Block\Widget;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\Address as AddressHelper;
use Magento\Customer\Model\Options;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Block\Widget\AbstractWidget;
use Magento\Customer\Model\Session as CustomerSession;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Cminds MultiUserAccounts customer account confirm controller plugin.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Jigs
 */
class Name extends AbstractWidget
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
     * @var AddressMetadataInterface
     */
    protected $addressMetadata;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @param Context $context
     * @param AddressHelper $addressHelper
     * @param CustomerMetadataInterface $customerMetadata
     * @param Options $options
     * @param ViewHelper $viewHelper View helper object.
     * @param AddressMetadataInterface $addressMetadata
     * @param array $data
     */
    public function __construct(
        Context $context,
        AddressHelper $addressHelper,
        CustomerMetadataInterface $customerMetadata,
        Options $options,
        CustomerSession $customerSession,
        ViewHelper $viewHelper,
        AddressMetadataInterface $addressMetadata,
        array $data = []
    ) {
        $this->options = $options;
        parent::__construct($context, $addressHelper, $customerMetadata, $data);
        $this->customerSession = $customerSession;
        $this->addressMetadata = $addressMetadata;
        $this->viewHelper      = $viewHelper;
        $this->_isScopePrivate = true;
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        $this->setTemplate(
            'Cminds_MultiUserAccounts::account/dashboard/widget/name.phtml'
        );
    }

    /**
     * Get the first name of customer.
     *
     * @return string
     */
    public function getAccountFirstName()
    {
        $subaccountData = $this->customerSession->getSubaccountData();

        if ($this->viewHelper->isSubaccountLoggedIn() == 1) {
            if ($subaccountData) {
                if (method_exists($this->getObject(), 'getCustomerId') // issue on account edit page
                    && (
                        (bool)$this->getObject()->getId() !== true // check if it's a new subaccount creation
                        || (int)$this->getObject()->getId() !== (int)$subaccountData->getCustomerId()
                        // check if we are editing subaccount data
                    )
                ) {
                    return '';
                }
                return $subaccountData->getFirstName();
            }
        } else {
            return $this->getObject()->getFirstName();
        }
    }

    /**
     * Get the last name of customer.
     *
     * @return string
     */
    public function getAccountLastName()
    {
        $subaccountData = $this->customerSession->getSubaccountData();

        if ($this->viewHelper->isSubaccountLoggedIn() == 1) {
            if ($subaccountData) {
                if (method_exists($this->getObject(), 'getCustomerId')
                    && (
                        (bool)$this->getObject()->getId() !== true
                        || (int)$this->getObject()->getId() !== (int)$subaccountData->getCustomerId()
                    )
                ) {
                    return '';
                }
                return $subaccountData->getLastName();
            }
        } else {
            return $this->getObject()->getLastName();
        }
    }

    /**
     * Get the middle name of customer.
     *
     * @return string
     */
    public function getAccountMiddleName()
    {
        $subaccountData = $this->customerSession->getSubaccountData();

        if ($this->viewHelper->isSubaccountLoggedIn() == 1) {
            if ($subaccountData) {
                if (method_exists($this->getObject(), 'getCustomerId')
                    && (
                        (bool)$this->getObject()->getId() !== true
                        || (int)$this->getObject()->getId() !== (int)$subaccountData->getCustomerId()
                    )
                ) {
                    return '';
                }
                return $subaccountData->getMiddleName();
            }
        } else {
            return $this->getObject()->getMiddleName();
        }
    }

    /**
     * Get the suffix name of customer.
     *
     * @return string
     */
    public function getAccountSuffix()
    {
        $subaccountData = $this->customerSession->getSubaccountData();

        if ($this->viewHelper->isSubaccountLoggedIn() == 1) {
            if ($subaccountData) {
                if (method_exists($this->getObject(), 'getCustomerId')
                    && (
                        (bool)$this->getObject()->getId() !== true
                        || (int)$this->getObject()->getId() !== (int)$subaccountData->getCustomerId()
                    )
                ) {
                    return '';
                }
                return $subaccountData->getSuffix();
            }
        } else {
            return $this->getObject()->getSuffix();
        }
    }

    /**
     * Get the prefix name of customer.
     *
     * @return string
     */
    public function getAccountPrefix()
    {
        $subaccountData = $this->customerSession->getSubaccountData();

        if ($this->viewHelper->isSubaccountLoggedIn() == 1) {
            if ($subaccountData) {
                if (method_exists($this->getObject(), 'getCustomerId')
                    && (
                        (bool)$this->getObject()->getId() !== true
                        || (int)$this->getObject()->getId() !== (int)$subaccountData->getCustomerId()
                    )
                ) {
                    return '';
                }
                return $subaccountData->getPrefix();
            }
        } else {
            return $this->getObject()->getPrefix();
        }
    }

    /**
     * Can show config value.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function _showConfig($key)
    {
        return (bool)$this->getConfig($key);
    }

    /**
     * Can show prefix.
     *
     * @return bool
     */
    public function showPrefix()
    {
        return $this->_isAttributeVisible('prefix');
    }

    /**
     * Define if prefix attribute is required.
     *
     * @return bool
     */
    public function isPrefixRequired()
    {
        return $this->_isAttributeRequired('prefix');
    }

    /**
     * Retrieve name prefix drop-down options.
     *
     * @return array|bool
     */
    public function getPrefixOptions()
    {
        $prefixOptions = $this->options->getNamePrefixOptions();

        if ($this->getObject() && !empty($prefixOptions)) {
            $prefixOption = $this->getObject()->getPrefix();
            $oldPrefix = $this->escapeHtml(trim($prefixOption));
            if ($prefixOption !== null && !isset($prefixOptions[$oldPrefix]) && !isset($prefixOptions[$prefixOption])) {
                $prefixOptions[$oldPrefix] = $oldPrefix;
            }
        }

        return $prefixOptions;
    }

    /**
     * Define if middle name attribute can be shown.
     *
     * @return bool
     */
    public function showMiddlename()
    {
        return $this->_isAttributeVisible('middlename');
    }

    /**
     * Define if middlename attribute is required.
     *
     * @return bool
     */
    public function isMiddlenameRequired()
    {
        return $this->_isAttributeRequired('middlename');
    }

    /**
     * Define if suffix attribute can be shown.
     *
     * @return bool
     */
    public function showSuffix()
    {
        return $this->_isAttributeVisible('suffix');
    }

    /**
     * Define if suffix attribute is required.
     *
     * @return bool
     */
    public function isSuffixRequired()
    {
        return $this->_isAttributeRequired('suffix');
    }

    /**
     * Retrieve name suffix drop-down options.
     *
     * @return array|bool
     */
    public function getSuffixOptions()
    {
        $suffixOptions = $this->options->getNameSuffixOptions();
        if ($this->getObject() && !empty($suffixOptions)) {
            $suffixOption = $this->getObject()->getSuffix();
            $oldSuffix = $this->escapeHtml(trim($suffixOption));
            if ($suffixOption !== null && !isset($suffixOptions[$oldSuffix]) && !isset($suffixOptions[$suffixOption])) {
                $suffixOptions[$oldSuffix] = $oldSuffix;
            }
        }

        return $suffixOptions;
    }

    /**
     * Class name getter.
     *
     * @return string
     */
    public function getClassName()
    {
        if (!$this->hasData('class_name')) {
            $this->setData('class_name', 'customer-name');
        }

        return $this->getData('class_name');
    }

    /**
     * Container class name getter.
     *
     * @return string
     */
    public function getContainerClassName()
    {
        $class = $this->getClassName();
        $class .= $this->showPrefix() ? '-prefix' : '';
        $class .= $this->showMiddlename() ? '-middlename' : '';
        $class .= $this->showSuffix() ? '-suffix' : '';

        return $class;
    }

    /**
     * @return string
     */
    protected function _getAttribute($attributeCode)
    {
        if ($this->getForceUseCustomerAttributes() || $this->getObject() instanceof CustomerInterface) {

            return parent::_getAttribute($attributeCode);
        }

        try {
            $attribute = $this->addressMetadata->getAttributeMetadata(
                $attributeCode
            );
        } catch (NoSuchEntityException $e) {

            return null;
        }

        if ($this->getForceUseCustomerRequiredAttributes() && $attribute && !$attribute->isRequired()) {
            $customerAttribute = parent::_getAttribute($attributeCode);
            if ($customerAttribute && $customerAttribute->isRequired()) {
                $attribute = $customerAttribute;
            }
        }

        return $attribute;
    }

    /**
     * Retrieve store attribute label.
     *
     * @param string $attributeCode
     *
     * @return string
     */
    public function getStoreLabel($attributeCode)
    {
        $attribute = $this->_getAttribute($attributeCode);

        return $attribute ? __($attribute->getStoreLabel()) : '';
    }

    /**
     * Get string with frontend validation classes for attribute.
     *
     * @param string $attributeCode
     *
     * @return string
     */
    public function getAttributeValidationClass($attributeCode)
    {
        return $this->_addressHelper->getAttributeValidationClass($attributeCode);
    }

    /**
     * @param string $attributeCode
     *
     * @return bool
     */
    private function _isAttributeRequired($attributeCode)
    {
        $attributeMetadata = $this->_getAttribute($attributeCode);

        return $attributeMetadata ? (bool)$attributeMetadata->isRequired() : false;
    }

    /**
     * @param string $attributeCode
     *
     * @return bool
     */
    private function _isAttributeVisible($attributeCode)
    {
        $attributeMetadata = $this->_getAttribute($attributeCode);
        return $attributeMetadata ? (bool)$attributeMetadata->isVisible() : false;
    }
}
