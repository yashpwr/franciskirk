<?php

namespace Cminds\MultiUserAccounts\Block\Adminhtml\Subaccount\Edit\Tab;

use Cminds\MultiUserAccounts\Model\Permission as PermissionModel;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

/**
 * Cminds MultiUserAccounts admin subaccount edit tab permission block.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Permission extends Generic implements TabInterface
{
    /**
     * Permission object.
     *
     * @var PermissionModel
     */
    private $permission;
    
    /**
     * Module configuration object
     *
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * Object initialization.
     *
     * @param Context             $context          Context object.
     * @param Registry            $registry         Registry object.
     * @param FormFactory         $formFactory      Form factory object.
     * @param PermissionModel     $permission       Permission object.
     * @param ModuleConfig        $moduleConfig     Module configuration object.
     * @param array               $data             Array data.
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        PermissionModel $permission,
        ModuleConfig $moduleConfig,
        array $data = []
    ) {
        $this->permission = $permission;
        $this->moduleConfig = $moduleConfig;

        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $data
        );
    }

    /**
     * Prepare form method.
     *
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareForm()
    {
        $subaccountTransportDataObject = $this->_coreRegistry
            ->registry('subaccount');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('subaccount_');
        $form->setFieldNameSuffix('subaccount');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Permission')]
        );

        $permissions = $this->permission->getPermissions();
        foreach ($permissions as $permissionCode => $permissionData) {
            // skip account_order_history_view_permission if global setting is disabled
            if ('account_order_history_view_permission' === $permissionCode
                && false === (bool)$this->moduleConfig->getParentCanSeeSubaccountsOrderHistory()
            ) {
                continue;
            }

            $permission = $subaccountTransportDataObject
                ->{$this->permission->getPermissionGetter($permissionCode)}();
            
            $isForced = false;

            if ($this->permission->isPermissionForced($permissionCode, $subaccountTransportDataObject)) {
                $isForced = true;
                $permission = true;
            }
            
            $fieldset->addField(
                $this->permission->getPermissionId($permissionCode),
                'checkbox',
                [
                    'name' => $permissionCode,
                    'label' => $permissionData['description'],
                    'note' =>  $isForced ? __(' (Forced by module configuration)') : '',
                    'required' => false,
                    'value' => 1,
                    'disabled' => $isForced ? 'disabled' : '' ,
                    'checked' => $permission
                        ? 'checked'
                        : '',
                ]
            );
        }

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
        return __('Permission');
    }

    /**
     * Prepare title for tab.
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Permission');
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
