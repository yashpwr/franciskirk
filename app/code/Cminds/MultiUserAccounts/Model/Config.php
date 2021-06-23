<?php
/**
 * @category Cminds
 * @package  MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Cminds MultiUserAccounts config model.
 *
 */
class Config
{
    const XML_PATH_SUBACCOUNT_GENERAL_ENABLE =
        'subaccount/general/enable';
    const XML_PATH_FORCE_USE_PARENT_ACCOUNT_FOR_ORDER =
        'subaccount/general/force_use_parent_account_for_order';
    const XML_PATH_SUBACCOUNT_GENERAL_NOTIFICATION =
        'subaccount/general/notification';
    const XML_PATH_SUBACCOUNT_CREATE_CONFIRM =
        'subaccount/new_subaccount/confirm';
    const XML_PATH_NESTED_ACCOUNTS_ALLOWED
        = 'subaccount/general/allow_nested';
    const XML_PATH_PARENT_CAN_SEE_SUB_ORDER_HISTORY
    = 'subaccount/general/can_see_subaccounts_order_history';
    const XML_PATH_SUB_LOGIN_AUTH_ENABLED
        = 'subaccount/general/auth_by_login';
    const XML_PATH_SUB_LOGIN_AUTH_TEXT
        = 'subaccount/general/auth_by_login_notice';
    const XML_PATH_PARENTACCOUNT_GENERAL_CAN_MANAGE
        = 'parentaccount/general/can_manage';
    const XML_PATH_PARENTACCOUNT_NEW_CUSTOMER_CAN_MANAGE
        = 'parentaccount/new_customer/can_manage';
    const XML_PATH_PARENTACCOUNT_ORDER_APPROVAL_REQUEST_ALL_NOTIFICATION
        = 'parentaccount/order_approval_request/parentaccount_all_notification';
    const XML_PATH_SUBACCOUNT_ORDER_APPROVAL_REQUEST_AUTHORIZATION_REQUIRED
        = 'subaccount/order_approval_request/authorization_required';
    const XML_PATH_SUBACCOUNT_CHANGE_GROUP
        = 'subaccount/subuser_group/change_group';
    const XML_PATH_PARENTACCOUNT_GENERAL_FIELD_TYPE =
        'parentaccount/general/field_type';
    const XML_PATH_PARENTACCOUNT_MAX_SHOW_COUNT =
        'parentaccount/general/max_show_count';
    const XML_PATH_PARENTACCOUNT_GENERAL_STORE_IN_DROPDOWN_LIST =
        'parentaccount/general/store_in_dropdown_list';

    const NOTIFICATION_MAIN_ACCOUNT = 1;
    const NOTIFICATION_SUBACCOUNT = 2;
    const NOTIFICATION_BOTH = 3;

    /**
     * @var null|int
     */
    private $storeId = null;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Already fetched config values.
     *
     * @var array
     */
    private $config = [];

    /**
     * Object initialization.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Store id setter.
     *
     * @param   null|int $storeId
     *
     * @return  Config
     */
    public function setStoreId($storeId = null)
    {
        $this->storeId = $storeId;

        return $this;
    }

    /**
     * Check if the parent account can see the order history of the sub account.
     *
     * @return int
     */
    public function getParentCanSeeSubaccountsOrderHistory()
    {
        return $this->getConfigValue(static::XML_PATH_PARENT_CAN_SEE_SUB_ORDER_HISTORY);
    }

    /**
     * @return bool
     */
    public function isLoginAuthEnabled()
    {
        return (bool) $this->getConfigValue(static::XML_PATH_SUB_LOGIN_AUTH_ENABLED);
    }

    /**
     * @return mixed
     */
    public function getAuthLoginText()
    {
        return $this->getConfigValue(static::XML_PATH_SUB_LOGIN_AUTH_TEXT);
    }

    /**
     * Check max subaccounts to display.
     *
     * @return int
     */
    public function getParentSubaccountsMaxToDisplay()
    {
        return $this->getConfigValue(static::XML_PATH_PARENTACCOUNT_MAX_SHOW_COUNT);
    }

    /**
     * Return config field value.
     *
     * @param string $keyPath Key path.
     *
     * @return mixed
     */
    private function getConfigValue($keyPath)
    {
        if (isset($this->config[$keyPath]) === false) {
            $this->config[$keyPath] = $this->scopeConfig->getValue(
                $keyPath,
                ScopeInterface::SCOPE_STORE,
                $this->storeId
            );
        }

        return $this->config[$keyPath];
    }

    /**
     * Return bool value depends of that if module is enabled or not.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getConfigValue(self::XML_PATH_SUBACCOUNT_GENERAL_ENABLE);
    }

    /**
     * Return true if field type input is text
     * and false if field type is select
     *
     * @return bool
     */
    public function showAsText()
    {
        return (bool)$this->getConfigValue(self::XML_PATH_PARENTACCOUNT_GENERAL_FIELD_TYPE);
    }

    /**
     * Return bool value depends of that if force use parent account details
     * for order is enabled.
     *
     * @return bool
     */
    public function isForceUseParentAccountDetailsForOrderEnabled()
    {
        return (bool)$this->getConfigValue(self::XML_PATH_FORCE_USE_PARENT_ACCOUNT_FOR_ORDER);
    }

    /**
     * Return notification config value.
     *
     * @return int
     */
    public function getNotificationConfig()
    {
        return (int)$this->getConfigValue(self::XML_PATH_SUBACCOUNT_GENERAL_NOTIFICATION);
    }

    /**
     * Return bool value depends of that if the nested subaccounts are allowed to use.
     *
     * @return bool
     */
    public function isNestedSubaccountsAllowed()
    {
        return (bool)$this->getConfigValue(self::XML_PATH_NESTED_ACCOUNTS_ALLOWED);
    }

    /**
     * Return bool value depends of that if confirmation
     * for new subaccounts is required ot not.
     *
     * @return bool
     */
    public function isConfirmationRequired()
    {
        return (bool)$this->getConfigValue(self::XML_PATH_SUBACCOUNT_CREATE_CONFIRM);
    }

    /**
     * Return bool value depends of that if parent account can manage
     * subaccounts is enabled or not.
     *
     * @return bool
     */
    public function canParentAccountManageSubaccounts()
    {
        return (bool)$this->getConfigValue(self::XML_PATH_PARENTACCOUNT_GENERAL_CAN_MANAGE);
    }

    /**
     * Return bool value depends of that if newly created customer can manage
     * subaccounts is enabled or not.
     *
     * @return bool
     */
    public function canNewCustomerManageSubaccounts()
    {
        return (bool)$this->getConfigValue(self::XML_PATH_PARENTACCOUNT_NEW_CUSTOMER_CAN_MANAGE);
    }

    /**
     * Return bool value depends of that if parent account should receive all
     * order approval requests email notifications.
     *
     * @return bool
     */
    public function shouldParentReceiveAllNotifications()
    {
        return (bool)$this->getConfigValue(self::XML_PATH_PARENTACCOUNT_ORDER_APPROVAL_REQUEST_ALL_NOTIFICATION);
    }

    /**
     * Return bool value depends of that if order approval requests
     * authorization is required.
     *
     * @return bool
     */
    public function isOrderApprovalRequestAuthorizationRequired()
    {
        return (bool)$this->getConfigValue(self::XML_PATH_SUBACCOUNT_ORDER_APPROVAL_REQUEST_AUTHORIZATION_REQUIRED);
    }

    /**
     * @return bool
     */
    public function createOrderOnApprove()
    {
        return (bool)$this->getConfigValue('parentaccount/order_approval_request/order_create');
    }

    /**
     * @return string
     */
    public function getApprovedOrderShippingMethod()
    {
        return $this->getConfigValue('parentaccount/order_approval_request/shipping_method');
    }

    /**
     * @return string
     */
    public function getApprovedOrderPaymentMethod()
    {
        return $this->getConfigValue('parentaccount/order_approval_request/payment_method');
    }

    /**
     * @return bool
     */
    public function parentAccountsNeedsToBeApproved()
    {
        return (bool)$this->getConfigValue('parentaccount/general/admin_approve');
    }

    /**
     * @return bool
     */
    public function subAccountsNeedsToBeApproved()
    {
        return (bool)$this->getConfigValue('subaccount/general/admin_approve');
    }

    /**
     * Return bool value depends of that if change the subuser group.
     *
     * @return bool
     */
    public function changeSubAccountGroup()
    {
        return (bool)$this->getConfigValue(self::XML_PATH_SUBACCOUNT_CHANGE_GROUP);
    }

    /**
     * Return bool value depends of if needs to add store view name to parent email in drop-down list
     * during assign parent account to customer in backend manage customer account.
     *
     * @return bool
     */
    public function removeStoreInDropdownList()
    {
        return (bool)$this->getConfigValue(self::XML_PATH_PARENTACCOUNT_GENERAL_STORE_IN_DROPDOWN_LIST);
    }
}
