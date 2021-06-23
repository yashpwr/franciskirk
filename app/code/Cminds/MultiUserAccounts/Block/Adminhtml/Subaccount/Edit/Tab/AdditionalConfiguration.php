<?php

namespace Cminds\MultiUserAccounts\Block\Adminhtml\Subaccount\Edit\Tab;

use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Model\Session as Session;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

/**
 * Cminds MultiUserAccounts admin subaccount edit tab additional information
 * block.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class AdditionalConfiguration extends Generic implements TabInterface
{
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
     * Object initialization.
     *
     * @param Context          $context Context object.
     * @param Registry         $registry Registry object.
     * @param FormFactory      $formFactory Form factory object.
     * @param DataObjectHelper $dataObjectHelper Data object helper.
     * @param array            $data Array data.
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        DataObjectHelper $dataObjectHelper,
        array $data = []
    ) {
        $this->dataObjectHelper = $dataObjectHelper;
        $this->customerSession = $context->getBackendSession();

        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $data
        );
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
     * Prepare form method.
     *
     * @return \Magento\Backend\Block\Widget\Form
     * @throws \Magento\Framework\Exception\LocalizedException
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
            ['legend' => __('Subaccount Additional Configuration')]
        );

        $fieldset->addField(
            $subaccountTransportDataObject::ORDER_MAX_AMOUNT,
            'text',
            [
                'name'      => 'additional_information[manage_order_max_amount]',
                'label'     => __('Order Amount Without Approval'),
                'required'  => false,
                'note'      => __('"Can Create Order" permission has to be set.'),
                'value'     => $this->getSubaccount()
                    ->getAdditionalInformationValue(
                        $subaccountTransportDataObject::ORDER_MAX_AMOUNT
                    ),
            ]
        );

        $fieldset->addField(
            $subaccountTransportDataObject::MANAGE_ORDER_APPROVAL_PERMISSION_AMOUNT,
            'text',
            [
                'name'      => 'additional_information[manage_order_approval_permission_amount]',
                'label'     => __('Order Approval Amount'),
                'required'  => false,
                'note'      => __(
                    'Sub-account will be allowed to approve orders with amount '
                    . 'not bigger than above amount. No value or 0 is equal to '
                    . 'no limit. "Can Approve Orders" or "Can Authorize Orders" permission has to be set.'
                ),
                'value'     => $this->getSubaccount()
                    ->getAdditionalInformationValue(
                        $subaccountTransportDataObject::MANAGE_ORDER_APPROVAL_PERMISSION_AMOUNT
                    ),
            ]
        );

        $fieldset->addField(
            $subaccountTransportDataObject::LIMIT_ORDER_TIMES,
            'text',
            [
                'name'      => 'additional_information[manage_limit_order_times]',
                'label'     => __('Order Amount Limit'),
                'required'  => false,
                'note'      => __('Amount limit per order'),
                'value'     => $this->getSubaccount()
                    ->getAdditionalInformationValue(
                        $subaccountTransportDataObject::LIMIT_ORDER_TIMES
                    ),
            ]
        );

        $fieldset->addField(
            $subaccountTransportDataObject::LIMIT_ORDER_MONTH,
            'text',
            [
                'name'      => 'additional_information[manage_limit_order_month]',
                'label'     => __('Order Amount Limit per Month'),
                'required'  => false,
                'note'      => __('Amount limit per month'),
                'value'     => $this->getSubaccount()
                    ->getAdditionalInformationValue(
                        $subaccountTransportDataObject::LIMIT_ORDER_MONTH
                    ),
            ]
        );

        $fieldset->addField(
            $subaccountTransportDataObject::LIMIT_ORDER_WEEK,
            'text',
            [
                'name'      => 'additional_information[manage_limit_order_week]',
                'label'     => __('Order Amount Limit per Week'),
                'required'  => false,
                'note'      => __('Amount limit per week'),
                'value'     => $this->getSubaccount()
                    ->getAdditionalInformationValue(
                        $subaccountTransportDataObject::LIMIT_ORDER_WEEK
                    ),
            ]
        );

        $fieldset->addField(
            $subaccountTransportDataObject::LIMIT_ORDER_DAY,
            'text',
            [
                'name'      => 'additional_information[manage_limit_order_day]',
                'label'     => __('Order Amount Limit per Day'),
                'required'  => false,
                'note'      => __('Amount limit per day'),
                'value'     => $this->getSubaccount()
                    ->getAdditionalInformationValue(
                        $subaccountTransportDataObject::LIMIT_ORDER_DAY
                    ),
            ]
        );

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
        return __('Subaccount Additional Configuration');
    }

    /**
     * Prepare title for tab.
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Subaccount Additional Configuration');
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
