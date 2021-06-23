<?php

namespace Cminds\MultiUserAccounts\Model\ResourceModel;

use Cminds\MultiUserAccounts\Model\Permission;
use Cminds\MultiUserAccounts\Model\Subaccount as SubaccountModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Customer\Model\ResourceModel\Customer;


/**
 * Cminds MultiUserAccounts subaccount resource model.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Subaccount extends AbstractDb
{
    /**
     * @var Permission
     */
    private $permission;
    private $customer;

    /**
     * Object initialization.
     *
     * @param   Context     $context
     * @param   Permission  $permission
     * @param   null|string $connectionName
     */
    public function __construct(
        Context $context,
        Permission $permission,
        Customer $customer,
        $connectionName = null
    ) {
        $this->permission = $permission;
        $this->customer = $customer;

        parent::__construct(
            $context,
            $connectionName
        );
    }

    /**
     * Resource model construct that should be used for object initialization.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('cminds_multiuseraccounts_subaccount', 'entity_id');
    }

    /**
     * @param $customerId
     * @param $groupId
     */
    public function updateCustomerGroupId($customerId, $groupId)
    {
        $this->getConnection()->update(
            $this->customer->getEntityTable(),
            ['group_id' => $groupId],
            ['entity_id = ?' => $customerId]
        );
    }

    /**
     * Load customer by email
     *
     * @param   SubaccountModel $subaccount
     * @param   int             $customerId
     *
     * @return  Subaccount
     * @throws  LocalizedException
     */
    public function loadByCustomerId(SubaccountModel $subaccount, $customerId)
    {
        $connection = $this->getConnection();

        $bind = ['customer_id' => $customerId];

        $select = $connection
            ->select()
            ->from(
                $this->getTable('cminds_multiuseraccounts_subaccount'),
                ['entity_id']
            )
            ->where('customer_id = :customer_id');

        $subaccountId = $connection->fetchOne($select, $bind);
        if ($subaccountId) {
            $this->load($subaccount, $subaccountId);
        } else {
            $subaccount->setData([]);
        }

        return $this;
    }

    /**
     * Perform actions after object load.
     *
     * @param   AbstractModel $object
     *
     * @return  AbstractDb
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $subaccountDataObject = $object->getDataModel();
        $this->permission->loadSubaccountPermissions($subaccountDataObject);

        $object->updateData($subaccountDataObject);

        return parent::_afterLoad($object);
    }

    /**
     * Return array containing subaccount ids which belongs
     * to parent customer with provided id.
     *
     * @param   int $parentCustomerId
     *
     * @return  array
     */
    public function getSubaccountIdsByParentCustomerId($parentCustomerId)
    {
        $bind = ['parent_customer_id' => (int)$parentCustomerId];
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from(
                $this->getTable('cminds_multiuseraccounts_subaccount'),
                'customer_id'
            )
            ->where('parent_customer_id = :parent_customer_id');

        $result = $connection->fetchCol($select, $bind);
        $result = array_map('intval', $result);

        return $result;
    }

    public function getSubaccountByParentIdAndLogin(int $parentId, string $login)
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), 'customer_id')
            ->where('parent_customer_id = ?', $parentId)
            ->where('login = ?', $login);

        return $this->getConnection()->fetchOne($select);
    }
}
