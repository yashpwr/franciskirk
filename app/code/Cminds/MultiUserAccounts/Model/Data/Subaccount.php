<?php

namespace Cminds\MultiUserAccounts\Model\Data;

use Cminds\MultiUserAccounts\Api\Data\SubaccountInterface;
use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Cminds MultiUserAccounts subaccount data model.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Subaccount extends AbstractSimpleObject implements SubaccountInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->_get(self::ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->setData(self::ID, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerId()
    {
        return $this->_get(self::CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerId($id)
    {
        $this->setData(self::CUSTOMER_ID, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentCustomerId()
    {
        return $this->_get(self::PARENT_CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setParentCustomerId($id)
    {
        $this->setData(self::PARENT_CUSTOMER_ID, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermission()
    {
        return $this->_get(self::PERMISSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setPermission($permission)
    {
        $this->setData(self::PERMISSION, $permission);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsActive()
    {
        return $this->_get(self::IS_ACTIVE);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsActive($flag)
    {
        $this->setData(self::IS_ACTIVE, $flag);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->_get(self::CREATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt($date)
    {
        $this->setData(self::CREATED_AT, $date);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt()
    {
        return $this->_get(self::UPDATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setUpdatedAt($date)
    {
        $this->setData(self::UPDATED_AT, $date);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccountDataModificationPermission()
    {
        return $this->_get(self::ACCOUNT_DATA_MODIFY_PERMISSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setAccountDataModificationPermission($permission)
    {
        $this->setData(self::ACCOUNT_DATA_MODIFY_PERMISSION, $permission);

        return $this;
    }

    /**
     * Get permission on modifying address book.
     *
     * @return boolean
     */
    public function getAccountAddressBookModificationPermission()
    {
        return $this->_get(self::ACCOUNT_ADDRESS_BOOK_MODIFY_PERMISSION);
    }

    /**
     * Set permission on modifying address book.
     *
     * @param $permission
     *
     * @return $this
     */
    public function setAccountAddressBookModificationPermission($permission)
    {
        $this->setData(self::ACCOUNT_ADDRESS_BOOK_MODIFY_PERMISSION, $permission);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccountOrderHistoryViewPermission()
    {
        return $this->_get(self::ACCOUNT_ORDER_HISTORY_VIEW_PERMISSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setAccountOrderHistoryViewPermission($permission)
    {
        $this->setData(
            self::ACCOUNT_ORDER_HISTORY_VIEW_PERMISSION,
            $permission
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCheckoutOrderCreatePermission()
    {
        return $this->_get(self::CHECKOUT_ORDER_CREATE_PERMISSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setCheckoutOrderCreatePermission($permission)
    {
        $this->setData(self::CHECKOUT_ORDER_CREATE_PERMISSION, $permission);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCheckoutOrderApprovalPermission()
    {
        return $this->_get(self::CHECKOUT_ORDER_APPROVAL_PERMISSION);
    }

    /**
     * {@inheritdoc}
     */
    public function getViewAccountInfo()
    {
        return $this->_get(
            self::VIEW_ACCOUNT_INFO
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setViewAccountInfo($permission)
    {
        $this->setData(
            self::VIEW_ACCOUNT_INFO,
            $permission
        );
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCheckoutOrderApprovalPermission($permission)
    {
        $this->setData(self::CHECKOUT_ORDER_APPROVAL_PERMISSION, $permission);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCheckoutCartViewPermission()
    {
        return $this->_get(self::CHECKOUT_CART_VIEW_PERMISSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setCheckoutCartViewPermission($permission)
    {
        $this->setData(self::CHECKOUT_CART_VIEW_PERMISSION, $permission);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCheckoutViewPermission()
    {
        return $this->_get(self::CHECKOUT_VIEW_PERMISSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setCheckoutViewPermission($permission)
    {
        $this->setData(self::CHECKOUT_VIEW_PERMISSION, $permission);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCheckoutOrderPlacedNotificationPermission()
    {
        return $this->_get(
            self::CHECKOUT_ORDER_PLACED_NOTIFICATION_PERMISSION
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setCheckoutOrderPlacedNotificationPermission($permission)
    {
        $this->setData(
            self::CHECKOUT_ORDER_PLACED_NOTIFICATION_PERMISSION,
            $permission
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getForceUsageParentCompanyNamePermission()
    {
        return $this->_get(
            self::FORCE_USAGE_PARENT_COMPANY_NAME_PERMISSION
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setForceUsageParentCompanyNamePermission($permission)
    {
        $this->setData(
            self::FORCE_USAGE_PARENT_COMPANY_NAME_PERMISSION,
            $permission
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getForceUsageParentVatPermission()
    {
        return $this->_get(
            self::FORCE_USAGE_PARENT_VAT_PERMISSION
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setForceUsageParentVatPermission($permission)
    {
        $this->setData(
            self::FORCE_USAGE_PARENT_VAT_PERMISSION,
            $permission
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getForceUsageParentAddressesPermission()
    {
        return $this->_get(
            self::FORCE_USAGE_PARENT_ADDRESSES_PERMISSION
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setForceUsageParentAddressesPermission($permission)
    {
        $this->setData(
            self::FORCE_USAGE_PARENT_ADDRESSES_PERMISSION,
            $permission
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getManageOrderApprovalPermission()
    {
        return $this->_get(
            self::MANAGE_ORDER_APPROVAL_PERMISSION
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getManageOrderAuthorizePermission()
    {
        return $this->_get(
            self::MANAGE_ORDER_AUTHORIZE_PERMISSION
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getManageSubaccounts()
    {
        return $this->_get(
            self::MANAGE_SUBACCOUNTS
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setManageOrderApprovalPermission($permission)
    {
        $this->setData(
            self::MANAGE_ORDER_APPROVAL_PERMISSION,
            $permission
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setManageOrderAuthorizePermission($permission)
    {
        $this->setData(
            self::MANAGE_ORDER_AUTHORIZE_PERMISSION,
            $permission
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setManageSubaccounts($permission)
    {
        $this->setData(
            self::MANAGE_SUBACCOUNTS,
            $permission
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogin()
    {
        return $this->_get(self::LOGIN);
    }

    /**
     * {@inheritdoc}
     */
    public function setLogin($login)
    {
        $this->setData(
            self::LOGIN,
            $login
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalInformation()
    {
        return $this->_get(
            self::ADDITIONAL_INFORMATION
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setAdditionalInformation($additionalInformation)
    {
        $this->setData(
            self::ADDITIONAL_INFORMATION,
            $additionalInformation
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalInformationValue($key = null)
    {
        $additionalInformation = $this->_get(
            self::ADDITIONAL_INFORMATION
        );

        if (is_array($additionalInformation) === false) {
            $additionalInformation = json_decode($additionalInformation, true);
            $this->setData(
                self::ADDITIONAL_INFORMATION,
                $additionalInformation
            );
        }

        if ($key === null) {
            return $additionalInformation;
        }

        if (isset($additionalInformation[$key])) {
            return $additionalInformation[$key];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setAdditionalInformationValue($key, $value = null)
    {
        if (is_array($key) === true) {
            $additionalInformation = $key;
        } else {
            $additionalInformation = $this->getAdditionalInformation();
            $additionalInformation[$key] = $value;
        }
        $this->setData(
            self::ADDITIONAL_INFORMATION,
            $additionalInformation
        );

        return $this;
    }

    public function convertToParent()
    {
    }
}
