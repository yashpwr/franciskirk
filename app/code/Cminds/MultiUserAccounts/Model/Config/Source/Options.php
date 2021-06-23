<?php
/**
 * @category Cminds
 * @package  MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Model\Config\Source;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class Options
 * @package Cminds\MultiUserAccounts\Model\Config\Source
 */
class Options extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var CollectionFactory
     */
    protected $_customerFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var UrlInterface
     */
    protected $_urlInterface;

    /**
     * @var Http
     */
    protected $_request;

    /**
     * @var SubaccountFactory
     */
    protected $_subAccountFactory;

    /**
     * @param CollectionFactory     $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface          $urlInterface
     * @param Http                  $request
     * @param SubaccountFactory     $subAccountFactory
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\App\Request\Http $request,
        \Cminds\MultiUserAccounts\Model\SubaccountFactory $subAccountFactory
    ) {
        $this->_customerFactory = $customerFactory;
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
        $this->_request = $request;
        $this->_subAccountFactory = $subAccountFactory;
    }

    /**
     * Get customer collection
     */
    public function getCustomerCollection()
    {
        return $this->_customerFactory->create();
    }

    /**
     * Get subaccount collection
     */
    public function getSubaccountCollection()
    {
        return $this->_subAccountFactory->create()->getCollection();
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $removeCustIds  = [];
        $subAccountsArr = [];
        $this->_options = [];
        $currentCustId  = $this->_request->getParam('id');
        
        if ($currentCustId != "") {
            $subAccountsArr = $this->getSubaccountCollection()->addFieldToFilter('parent_customer_id', $currentCustId)->getData();
            if (count($subAccountsArr) > 0) {
                foreach ($subAccountsArr as $subAccount) {
                    array_push($removeCustIds, $subAccount['customer_id']);
                }
            }
            array_push($removeCustIds, $currentCustId);
            $customerCollection  = $this->getCustomerCollection()->setOrder('email', 'ASC');
            $i = 1;
            $this->_options[0] = ['label' => '--- Select Parent Account ---', 'value' => '0'];
            foreach ($customerCollection as $customer) {
                if (!in_array($customer->getId(), $removeCustIds)) {
                    $this->_options[$i] = ['label' => $customer->getEmail(), 'value' => $customer->getId()];
                    $i++;
                }
            }
        } else {
            $customerCollection  = $this->getCustomerCollection();
            
            $i = 1;
            $this->_options[0] = ['label' => '--- Select Parent Account ---', 'value' => '0'];
            foreach ($customerCollection as $customer) {
                $this->_options[$i] = ['label' => $customer->getEmail(), 'value' => $customer->getId()];
                $i++;
            }
        }
        return $this->_options;
    }

    /**
     * Get a text for option value
     *
     * @param string|integer $value
     * @return string|bool
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }

    /**
     * Retrieve flat column definition
     *
     * @return array
     */
    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        return [
            $attributeCode => [
                'unsigned' => false,
                'default' => null,
                'extra' => null,
                'type' => Table::TYPE_INTEGER,
                'nullable' => true,
                'comment' => 'Parent Accounts Options  ' . $attributeCode . ' column',
            ],
        ];
    }
}
