<?php

namespace Cminds\MultiUserAccounts\Block\Adminhtml\Subaccount\Edit\Tab;

use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Cminds\MultiUserAccounts\Model\Config;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Model\Session as Session;
use Magento\Customer\Helper\Address;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Customer\Api\CustomerMetadataInterface;

/**
 * Cminds MultiUserAccounts admin subaccount edit tab info block.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Info extends Generic implements TabInterface
{
    /**
     * Customer address object.
     *
     * @var Address
     */
    private $customerAddressHelper;

    /**
     * Customer factory object.
     *
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * Data processor object.
     *
     * @var DataObjectProcessor
     */
    private $dataProcessor;

    /**
     * Data object helper.
     *
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * Session object.
     *
     * @var Session
     */
    private $customerSession;

    /**
     * CustomerMetadataInterface object.
     *
     * @var CustomerMetadataInterface
     */
    private $customerMetadata;

    /**
     * @var Config
     */
    private $config;

    /**
     * Object initialization.
     *
     * @param Context $context Context object.
     * @param Registry $registry Registry object.
     * @param FormFactory $formFactory Form factory object.
     * @param DataObjectProcessor $dataProcessor Data processor object.
     * @param Address $address Customer address object.
     * @param CustomerFactory $customerFactory Customer factory object.
     * @param DataObjectHelper $dataObjectHelper Data object helper.
     * @param CustomerMetadataInterface $customerMetadata
     * @param Config $config
     * @param array $data Array data.
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        DataObjectProcessor $dataProcessor,
        Address $address,
        CustomerFactory $customerFactory,
        DataObjectHelper $dataObjectHelper,
        CustomerMetadataInterface $customerMetadata,
        Config $config,
        array $data = []
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->customerAddressHelper = $address;
        $this->customerFactory = $customerFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->customerSession = $context->getBackendSession();
        $this->customerMetadata = $customerMetadata;

        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $data
        );
        $this->config = $config;
    }

    /**
     * Retrieve subaccount transport object.
     *
     * @return SubaccountTransportInterface
     */
    private function getSubaccount()
    {
        $subaccountTransportDataObject = $this->_coreRegistry
            ->registry('subaccount');

        $subaccountFormData = $this->customerSession->getSubaccountFormData(true);
        if ($subaccountFormData !== null) {
            $this->dataObjectHelper->populateWithArray(
                $subaccountTransportDataObject,
                $subaccountFormData,
                \Cminds\MultiUserAccounts\Api\Data\SubaccountInterface::class
            );
        }

        return $subaccountTransportDataObject;
    }

    /**
     * Check if prefix input is mandatory
     *
     * @return bool
     */
    private function isPrefixRequired()
    {
        return 'req' == $this->_scopeConfig->getValue(
            'customer/address/prefix_show',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if suffix input is mandatory
     *
     * @return bool
     */
    private function isSuffixRequired()
    {
        return 'req' == $this->_scopeConfig->getValue(
            'customer/address/suffix_show',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Prepare form method.
     *
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareForm()
    {
        $subaccountTransportDataObject = $this->getSubaccount();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('subaccount_');
        $form->setFieldNameSuffix('subaccount');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Subaccount Information')]
        );

        $parentCustomerId = $this->getRequest()->getParam('parent_customer_id');
        if ($parentCustomerId) {
            $fieldset->addField(
                'parent_customer_id',
                'hidden',
                ['name' => 'parent_customer_id']
            );
            $subaccountTransportDataObject
                ->setParentCustomerId($parentCustomerId);
        }

        if ($subaccountTransportDataObject->getId()) {
            $fieldset->addField(
                'id',
                'hidden',
                ['name' => 'id']
            );
        }
        $fieldset->addField(
            'prefix',
            'text',
            [
                'name' => 'prefix',
                'label' => __('Name Prefix'),
                'required' => $this->isPrefixRequired(),
            ]
        );
        $fieldset->addField(
            'firstname',
            'text',
            [
                'name' => 'firstname',
                'label' => __('First Name'),
                'required' => true,
            ]
        );
        $fieldset->addField(
            'middlename',
            'text',
            [
                'name' => 'middlename',
                'label' => __('Middle Name/Initial'),
            ]
        );
        $fieldset->addField(
            'lastname',
            'text',
            [
                'name' => 'lastname',
                'label' => __('Last Name'),
                'required' => true,
            ]
        );
        $fieldset->addField(
            'suffix',
            'text',
            [
                'name' => 'suffix',
                'label' => __('Name Suffix'),
                'required' => $this->isSuffixRequired(),
            ]
        );
        $fieldset->addField(
            'email',
            'text',
            [
                'name' => 'email',
                'label' => __('Email'),
                'required' => true,
            ]
        );

        if ($this->config->isLoginAuthEnabled()) {
            $fieldset->addField(
                'login',
                'text',
                [
                    'name' => 'login',
                    'label' => __('Login'),
                    'required' => false,
                ]
            );
        }

        $fieldset->addField(
            'dob',
            'date',
            [
                'name' => 'dob',
                'label' => __('Date of Birth'),
                'title' => __('Date of Birth'),
                'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
                'required' => ( 'req' === $this->customerAddressHelper->getConfig('dob_show') ),
                'class' => 'validate-date'
            ]
        );

        $fieldset->addField(
            'gender',
            'select',
            [
                'name' => 'gender',
                'label' => __('Gender'),
                'title' => __('Gender'),
                'required' => ( 'req' === $this->customerAddressHelper->getConfig('gender_show') ),
                'values' => $this->getGenderArray(),
                'class' => 'select'
            ]
        );

        $taxVatShowConfig = $this->customerAddressHelper->getConfig('taxvat_show');
        if (!empty($taxVatShowConfig)) {
            $taxVatRequired = false;
            if ($taxVatShowConfig === 'req') {
                $taxVatRequired = true;
            }
            if ($parentCustomerId) {
                $parentCustomer = $this->customerFactory->create()->load($parentCustomerId);
                if (!empty($parentCustomer->getTaxvat())) {
                    $subaccountTransportDataObject->setTaxvat($parentCustomer->getTaxvat());
                }
            }

            $fieldset->addField(
                'taxvat',
                'text',
                [
                    'name' => 'taxvat',
                    'label' => __('Tax/VAT Number'),
                    'required' => $taxVatRequired,
                    'note' =>  $subaccountTransportDataObject->getForceUsageParentVatPermission() ? '<span class = "su-notice">' . __('Value will be taken from parent account') . '</span>' : '',
                ]
            );
        }

        $isActive = ($subaccountTransportDataObject->getId()
            && $subaccountTransportDataObject->getIsActive())
        || !$subaccountTransportDataObject->getId()
            ? true
            : false;

        $fieldset->addField(
            'is_active',
            'checkbox',
            [
                'name' => 'is_active',
                'label' => __('Is Active'),
                'required' => false,
                'value' => 1,
                'checked' => $isActive
                    ? 'checked'
                    : '',
            ]
        );
        $subaccountTransportDataObject->setIsActive(1);

        $subaccountTransportDataArray = $this->dataProcessor->buildOutputDataArray(
            $subaccountTransportDataObject,
            \Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface::class
        );

        $form->setValues($subaccountTransportDataArray);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab.
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Subaccount Information');
    }

    /**
     * Prepare gender options.
     *
     * @return array
     */
    private function getGenderArray()
    {
        $gender = [];
        $data = $this->customerMetadata->getAttributeMetadata('gender')->__toArray();
        foreach ($data['options'] as $option) {
            $gender[ $option['value'] ] = $option['label'];
        }
        return $gender;
    }

    /**
     * Prepare title for tab.
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Subaccount Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
