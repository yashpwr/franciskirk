<?php

namespace Cminds\MultiUserAccounts\Model\Import;

use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Cminds\MultiUserAccounts\Model\Import\Validator;
use Cminds\MultiUserAccounts\Helper\Import as ImportHelper;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;

class DataResolver 
{
    private $addressFactory;
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ImportHelper
     */
    private $importHelper;

    /**
     * @var RegionInterfaceFactory
     */
    private $regionFactory;


    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    private $connection;
    
    /**
     * @param Registry $coreRegistry
     * @param ResourceConnection $resourceConnection
     * @param Validator $validator
     * @param ImportHelper $importHelper
     * 
    */
    public function __construct(
        Registry $coreRegistry,
        ResourceConnection $resourceConnection,
        Validator $validator,
        ImportHelper $importHelper,
        RegionInterfaceFactory $regionFactory,
        DataObjectHelper $dataObjectHelper,
        AddressInterfaceFactory $addressFactory
    )
    {
        $this->coreRegistry = $coreRegistry;
        $this->resourceConnection = $resourceConnection;
        $this->validator = $validator;
        $this->importHelper = $importHelper;
        $this->regionFactory = $regionFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->addressFactory = $addressFactory;

        $this->connection = $this->resourceConnection->getConnection();
    }

    /**
     * Prepare customer profile data.
     *
     * @param array $sourceData
     *
     * @return array
     */
    public function prepareCustomerProfileData($sourceData)
    {
        $preparedData = $this->validator->getDefaultCustomerProfileData();
        foreach ($preparedData as $key => $value) {
            if (!empty($sourceData[$key])) {
                $preparedData[$key] = $sourceData[$key];
            }
            if ($key === 'is_active') {
                if (isset($sourceData['is_active'])) {
                    $preparedData['is_active'] = $sourceData['is_active'];
                }
            }
        }

        return $preparedData;
    }

    /**
     * Prepare customer address data.
     *
     * @param array $sourceData
     *
     * @return array
     */
    public function prepareCustomerAddressData($sourceData)
    {
        $preparedData = $this->validator->getDefaultCustomerAddressData();

        $preparedData['street'][0] = !empty($sourceData['street_1'])
            ? $sourceData['street_1'] : '';
        $preparedData['street'][1] = !empty($sourceData['street_2'])
            ? $sourceData['street_2'] : '';

        foreach ($preparedData as $key => $value) {
            if (!empty($sourceData[$key])) {
                $preparedData[$key] = $sourceData[$key];
            }
        }

        if (!$this->validator->validateCustomerAddressData($preparedData)) {
            return [];
        }

        return $preparedData;
    }

    public function getRegionDataObject($address) {
        $regionData = [
            RegionInterface::REGION_ID => $address['region_id'],
            RegionInterface::REGION => !empty($address['region'][0]['region'])
                ? $address['region'][0]['region']
                : null,
            RegionInterface::REGION_CODE => !empty($address['region'][0]['region_code'])
                ? $address['region'][0]['region_code']
                : null,
        ];

        $region = $this->regionFactory->create();

        $this->dataObjectHelper->populateWithArray(
            $region,
            $regionData,
            '\Magento\Customer\Api\Data\RegionInterface'
        );

        return $region;
    }

    public function getAddressesObject($customerData) {
        $addresses = $this->addressFactory->create();

        
        $this->dataObjectHelper->populateWithArray(
            $addresses,
            $customerData['address'],
            '\Magento\Customer\Api\Data\AddressInterface'
        );

        return $addresses;
    }

    /**
     * Prepare customer profile data.
     *
     * @param array $sourceData
     *
     * @return array
     */
    public function prepareSubaccountData($sourceData)
    {
        $preparedData = $this->validator->getDefaultSubaccountData();
        foreach ($preparedData as $key => $value) {
            if (isset($sourceData[$key]) && $sourceData[$key] !== '') {
                $preparedData[$key] = $sourceData[$key];
            }
        }

        return $preparedData;
    }

    public function getSubaccountParent($account)
    {
        if (!empty($account['website_id'])) {
            $subaccountParent = $this->importHelper->checkParentWithWebsite($account);
        } else {
            $subaccountParent = $this->importHelper->checkParentAccount($account);
        }

        return $subaccountParent;
    }
}