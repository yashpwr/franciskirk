<?php
namespace Cminds\MultiUserAccounts\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount\CollectionFactory as SubaccountCollectionFactory;

/**
 * Cminds MultiUserAccounts subaccount helper.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Subaccount extends AbstractHelper
{
    /**
     * Sub Account Collection Factory.
     *
     * @var SubaccountCollectionFactory
     */
    private $subaccountCollectionFactory;

    /**
     * Object initialization.
     *
     * @param Context $context
     * @param SubaccountCollectionFactory $subaccountCollectionFactory
     */
    public function __construct(
        Context $context,
        SubaccountCollectionFactory $subaccountCollectionFactory
    ) {
        $this->subaccountCollectionFactory = $subaccountCollectionFactory;

        parent::__construct($context);
    }

    /**
     *  Function returns an array of user ids,
     *      that icludes all subbacount from provided master account
     *
     * @param string||int $masterId
     * @return array
     */
    public function getAllSubaccountIds($masterId)
    {
        $subaccountsId = [];
        $parents = [$masterId];

        while (count($parents) > 0) {
            $subaccounts = $this->subaccountCollectionFactory
                ->create()
                ->filterByParentCustomerId($parents);

            $parents = [];
            if (count($subaccounts)) {
                foreach ($subaccounts as $subaccountData) {
                    $parents[] = (int)$subaccountData->getCustomerId();
                }
            }
            $subaccountsId = array_merge($subaccountsId, $parents);
        }

        return $subaccountsId;
    }
}
