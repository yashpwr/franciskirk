<?php

namespace Cminds\MultiUserAccounts\Observer\Customer\SaveAfter;

use Cminds\MultiUserAccounts\Api\Data\SubaccountInterface;
use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Cminds\MultiUserAccounts\Api\SubaccountRepositoryInterface;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Reflection\DataObjectProcessor;

/**
 * Cminds MultiUserAccounts after customer save observer.
 * Will be executed on "customer_save_after" event.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class SubaccountUpdate implements ObserverInterface
{
    /**
     * Customer session object.
     *
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Module config object.
     *
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * View helper object.
     *
     * @var ViewHelper
     */
    private $viewHelper;

    /**
     * Data object helper object.
     *
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * Data object processor object.
     *
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * Subaccount repository object.
     *
     * @var SubaccountRepositoryInterface
     */
    private $subaccountRepository;
    
    /**
     * SubaccountUpdate constructor.
     *
     * @param CustomerSession $customerSession
     * @param ModuleConfig $moduleConfig
     * @param ViewHelper $viewHelper
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param SubaccountRepositoryInterface $subaccountRepository
     */
    public function __construct(
        CustomerSession $customerSession,
        ModuleConfig $moduleConfig,
        ViewHelper $viewHelper,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        SubaccountRepositoryInterface $subaccountRepository
    ) {
        $this->customerSession = $customerSession;
        $this->moduleConfig = $moduleConfig;
        $this->viewHelper = $viewHelper;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->subaccountRepository = $subaccountRepository;
    }

    /**
     * Update sub-account data.
     *
     * @param Observer $observer Observer object.
     *
     * @return SubaccountUpdate
     */
    public function execute(Observer $observer)
    {
        //@TODO check for nested
        if ($this->moduleConfig->isEnabled() === false
            || $this->viewHelper->isSubaccountLoggedIn() === false
        ) {
            return $this;
        }

        /** @var SubaccountTransportInterface $subaccountTransportDataObject */
        $subaccountTransportDataObject = $this->customerSession->getSubaccountData();

        /** @var SubaccountInterface $subaccountDataObject */
        $subaccountDataObject = $this->subaccountRepository
            ->getById($subaccountTransportDataObject->getId());

        /** @var \Magento\Customer\Model\Customer $customerModel */
        $customerModel = $observer
            ->getEvent()
            ->getCustomer();

        $customerData = $customerModel->getData();
        unset($customerData['id'], $customerData['entity_id']);

        $subaccountData = $this->dataObjectProcessor->buildOutputDataArray(
            $subaccountDataObject,
            SubaccountInterface::class
        );

        $this->dataObjectHelper->populateWithArray(
            $subaccountDataObject,
            $customerData,
            SubaccountInterface::class
        );

        $updatedSubaccountData = $this->dataObjectProcessor
            ->buildOutputDataArray(
                $subaccountDataObject,
                SubaccountInterface::class
            );

        $diff = array_diff($updatedSubaccountData, $subaccountData);
        unset($diff['updated_at']);

        if (!empty($diff)) {
            $this->subaccountRepository->save($subaccountDataObject);
        }

        return $this;
    }
}
