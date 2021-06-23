<?php
/**
 * @category Cminds
 * @package  MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Block\Address;

use Cminds\MultiUserAccounts\Model\Config\Source\Stores as SourceStores;

/**
 * Customer address edit block
 *
 * @package Cminds\MultiUserAccounts\Block\Address
 */
class Edit extends \Magento\Customer\Block\Address\Edit
{
    /**
     * @var SourceStores
     */
    protected $stores;

    /**
     * Edit constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param array $data
     * @param \Magento\Customer\Api\AddressMetadataInterface|null $addressMetadata
     * @param \Magento\Customer\Helper\Address|null $addressHelper
     * @param SourceStores $stores
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        array $data = [],
        \Magento\Customer\Api\AddressMetadataInterface $addressMetadata = null,
        SourceStores $stores
    ) {
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $customerSession,
            $addressRepository,
            $addressDataFactory,
            $currentCustomer,
            $dataObjectHelper,
            $data,
            $addressMetadata
        );

        $this->stores = $stores;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getStoreViewSelect()
    {
        $name = 'store_view';
        $id = 'store_view';
        $title = $this->getAttributeData()->getFrontendLabel($name);

        $defValueAttribute = $this->getAddress()->getCustomAttribute('store_view');
        if (is_null($defValueAttribute)) {
            $defValue = '';
        } else {
            $defValue = $defValueAttribute->getValue();
        }

        $options = $this->stores->toArray();

        $html = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setName(
            $name
        )->setId(
            $id
        )->setTitle(
            $this->escapeHtmlAttr(__($title))
        )->setValue(
            $defValue
        )->setOptions(
            $options
        )->getHtml();

        return $html;
    }
}
