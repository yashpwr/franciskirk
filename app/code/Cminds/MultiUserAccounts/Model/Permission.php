<?php

namespace Cminds\MultiUserAccounts\Model;

use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Cminds\MultiUserAccounts\Api\Data\SubaccountInterface;
use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;

/**
 * Cminds MultiUserAccounts permission model.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Permission
{
    /**
     * Permission bit values.
     */
    const PERMISSION_ACCOUNT_DATA_MODIFY = 1;
    const PERMISSION_ACCOUNT_ORDER_HISTORY_VIEW = 2;
    const PERMISSION_CHECKOUT_ORDER_CREATE = 4;
    const PERMISSION_CHECKOUT_ORDER_APPROVAL = 8;
    const PERMISSION_CHECKOUT_CART_VIEW = 16;
    const PERMISSION_CHECKOUT_VIEW = 32;
    const PERMISSION_CHECKOUT_ORDER_PLACED_NOTIFICATION = 64;
    const PERMISSION_FORCE_USAGE_PARENT_COMPANY_NAME = 128;
    const PERMISSION_FORCE_USAGE_PARENT_VAT = 256;
    const PERMISSION_FORCE_USAGE_PARENT_ADDRESSES = 512;
    const PERMISSION_MANAGE_ORDER_APPROVAL = 1024;
    const PERMISSION_MANAGE_SUBACCOUNTS = 2048;
    const PERMISSION_ACCOUNT_ADDRESS_BOOK_MODIFY = 4096;
    const PERMISSION_MANAGE_ORDER_AUTHORIZE = 8192;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * Object initialization.
     *
     * @param ModuleConfig      $moduleConfig
     *
     */
    public function __construct(
        ModuleConfig $moduleConfig
    ) {
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Return permission keys.
     *
     * @return array
     */
    public function getPermissionKeys()
    {
        return array_keys($this->getPermissions());
    }

    /**
     * Return permissions with all details.
     *
     * @return array
     */
    public function getPermissions()
    {
        return [
            'account_data_modification_permission' => [
                'value' => self::PERMISSION_ACCOUNT_DATA_MODIFY,
                'description' => __('Can Modify Account Data'),
            ],
            'account_address_book_modification_permission' => [
                'value' => self::PERMISSION_ACCOUNT_ADDRESS_BOOK_MODIFY,
                'description' => __('Can Manage Address Book'),
            ],
            'account_order_history_view_permission' => [
                'value' => self::PERMISSION_ACCOUNT_ORDER_HISTORY_VIEW,
                'description' => __('Can View Master And Other Sub Accounts Order History'),
            ],
            'checkout_order_create_permission' => [
                'value' => self::PERMISSION_CHECKOUT_ORDER_CREATE,
                'description' => __('Can Create Order'),
            ],
            'checkout_order_approval_permission' => [
                'value' => self::PERMISSION_CHECKOUT_ORDER_APPROVAL,
                'description' => __('Require Approval Before Creating An Order'),
            ],
            'checkout_cart_view_permission' => [
                'value' => self::PERMISSION_CHECKOUT_CART_VIEW,
                'description' => __('Can Go To Checkout Cart'),
            ],
            'checkout_view_permission' => [
                'value' => self::PERMISSION_CHECKOUT_VIEW,
                'description' => __('Can Go To Checkout'),
            ],
            'checkout_order_placed_notification_permission' => [
                'value' => self::PERMISSION_CHECKOUT_ORDER_PLACED_NOTIFICATION,
                'description' => __('Will Receive Order Placed Notification'),
            ],
            'force_usage_parent_company_name_permission' => [
                'value' => self::PERMISSION_FORCE_USAGE_PARENT_COMPANY_NAME,
                'description' => __('Force Usage Parent Company Name'),
            ],
            'force_usage_parent_vat_permission' => [
                'value' => self::PERMISSION_FORCE_USAGE_PARENT_VAT,
                'description' => __('Force Usage Parent VAT'),
            ],
            'force_usage_parent_addresses_permission' => [
                'value' => self::PERMISSION_FORCE_USAGE_PARENT_ADDRESSES,
                'description' => __('Force Usage Parent Addresses'),
            ],
            'manage_order_approval_permission' => [
                'value' => self::PERMISSION_MANAGE_ORDER_APPROVAL,
                'description' => 'Can Approve Orders',
            ],
            'manage_order_authorize_permission' => [
                'value' => self::PERMISSION_MANAGE_ORDER_AUTHORIZE,
                'description' => 'Can Authorize Orders',
            ],
            'manage_subaccounts' => [
                'value' => self::PERMISSION_MANAGE_SUBACCOUNTS,
                'description' => 'Can Manage Subaccounts',
            ]
        ];
    }

    /**
     * Return permission id by permission code.
     *
     * @param   string $permissionCode
     *
     * @return  string
     */
    public function getPermissionId($permissionCode)
    {
        return str_replace('_', '-', $permissionCode);
    }

    /**
     * Return permission getter by permission code.
     *
     * @param   string $permissionCode
     *
     * @return  string
     */
    public function getPermissionGetter($permissionCode)
    {
        return 'get'
            . str_replace(
                ' ',
                '',
                ucwords(str_replace('_', ' ', $permissionCode))
            );
    }

    /**
     * Return permission setter by permission code.
     *
     * @param   string $permissionCode
     *
     * @return  string
     */
    public function getPermissionSetter($permissionCode)
    {
        return 'set'
            . str_replace(
                ' ',
                '',
                ucwords(str_replace('_', ' ', $permissionCode))
            );
    }

    /**
     * Prepare subaccount permission data.
     *
     * @param   SubaccountTransportInterface|SubaccountInterface $subaccount
     *
     * @return  Permission
     */
    public function loadSubaccountPermissions($subaccount)
    {
        $subaccountPermission = $subaccount->getPermission();
        $permissions = $this->getPermissions();
        foreach ($permissions as $permissionCode => $permissionData) {
            $method = $this->getPermissionSetter($permissionCode);
            $value = (bool)($subaccountPermission & $permissionData['value']);
            $subaccount->{$method}($value);
        }

        return $this;
    }

    /**
     * Recalculate subaccount permission.
     *
     * @param   SubaccountTransportInterface|SubaccountInterface $subaccount
     *
     * @return  Permission
     */
    public function recalculatePermission($subaccount)
    {
        //not sure this won't break any other functionality, need it for update subaccount permissions.
        $permission = $subaccount->getPermission();
        if ($permission > 0) {
            $permission = 0;
        }
        $permissions = $this->getPermissions();
        foreach ($permissions as $permissionCode => $permissionData) {
            $method = $this->getPermissionGetter($permissionCode);
            $value = $subaccount->{$method}();
            if ($value) {
                $permission = $permission | $permissionData['value'];
            }
        }

        $subaccount->setPermission($permission);

        return $this;
    }

    /**
     * Return subaccount permission description html.
     *
     * @param   SubaccountTransportInterface|SubaccountInterface $subaccount
     *
     * @return  string
     */
    public function getSubaccountPermissionDescriptionHtml($subaccount)
    {
        $subaccountPermission = $subaccount->getPermission();
        $permissions = $this->getPermissions();

        $html = '';
        foreach ($permissions as $permissionKey => $permission) {
            if ($subaccountPermission & $permission['value'] || $this->isPermissionForced($permissionKey, $subaccount)) {
                $html .= '<li>' . $permission['description'] . '</li>';
            }
        }

        if ($html !== '') {
            $orderAmountWoApproval = $subaccount
                ->getAdditionalInformationValue('manage_order_max_amount');
            if ($orderAmountWoApproval) {
                $html .= '<li>'
                    . __(
                        'Order Amount W/o Approval: %1',
                        $orderAmountWoApproval
                    )
                    . '</li>';
            }

            $approvalAmount = $subaccount
                ->getAdditionalInformationValue('manage_order_approval_permission_amount');
            if ($approvalAmount) {
                $html .= '<li>'
                    . __(
                        'Order Approval Permission Amount: %1',
                        $approvalAmount
                    )
                    . '</li>';
            }

            $html = '<ul>' . $html . '</ul>';
        } else {
            $html = __('Account does not have any permission');
        }

        return $html;
    }

    
    /**
     * Check if permission is forced by force_use_parent_account_for_order module configuration
     *
     * @param   string $permissionCode
     * @param   SubaccountTransportInterface|SubaccountInterface $subaccount
     *
     * @return  bool
     */
    public function isPermissionForced($permissionCode, $subaccountData)
    {
        return $this->moduleConfig->isForceUseParentAccountDetailsForOrderEnabled()
            && in_array(
                $permissionCode,
                $subaccountData::FORCED_BY_USE_PARENT_ACCOUNT_DETAILS
            );
    }
}
