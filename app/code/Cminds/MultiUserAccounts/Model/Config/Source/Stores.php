<?php
/**
 * @category Cminds
 * @package  MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Model\Config\Source;

/**
 * Class Stores
 * @package Cminds\MultiUserAccounts\Model\Config\Source
 */
class Stores extends \Magento\Eav\Model\Entity\Attribute\Source\Table
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_store;

    /**
     * @var \Magento\Store\Model\ResourceModel\Store\CollectionFactory
     */
    protected $_storesFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Stores constructor.
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory $attrOptionFactory
     * @param \Magento\Store\Model\System\Store $store
     * @param \Magento\Store\Model\ResourceModel\Store\CollectionFactory $storesFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory $attrOptionFactory,
        \Magento\Store\Model\System\Store $store,
        \Magento\Store\Model\ResourceModel\Store\CollectionFactory $storesFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($attrOptionCollectionFactory, $attrOptionFactory);
        $this->_store = $store;
        $this->_storesFactory = $storesFactory;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param bool $withEmpty
     * @param bool $defaultValues
     * @return array
     */
    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        if (!$this->_options) {
            $collection = $this->_createStoresCollection();
            $this->_options = $this->getStoreValuesForForm($collection, true);
        }

        return $this->_options;
    }

    /**
     * @param $stores
     * @param bool $empty
     * @return array
     */
    public function getStoreValuesForForm($stores, $empty = false)
    {
        $options = [];
        if ($empty) {
            $options[] = ['label' => '--- Select ---', 'value' => ''];
        }

        foreach ($this->_storeManager->getWebsites() as $website) {
            foreach ($this->getGroupCollection() as $group) {
                if ($website->getId() != $group->getWebsiteId()) {
                    continue;
                }
                foreach ($stores as $store) {
                    if ($group->getId() != $store->getGroupId()) {
                        continue;
                    }

                    $options[] = [
                        'label' => $store->getName(),
                        'value' => $store->getId(),
                    ];
                }
            }
        }

        return $options;
    }

    /**
     * @return array
     */
    protected function getGroupCollection()
    {
        $groupCollection = [];
        foreach ($this->_storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $groupCollection[$group->getId()] = $group;
            }
        }

        return $groupCollection;
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
     * Get options in "key-value" format.
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];
        $optionsArray = $this->getAllOptions();

        foreach ($optionsArray as $optionData) {
            $array[$optionData['value']] = $optionData['label'];
        }

        return $array;
    }

    /**
     * @return \Magento\Store\Model\ResourceModel\Store\Collection
     */
    protected function _createStoresCollection()
    {
        return $this->_storesFactory->create();
    }
}