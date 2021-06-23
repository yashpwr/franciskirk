<?php
/**
 * @category Cminds
 * @package  MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Model\Plugin\Customer\DataProvider;

use Cminds\MultiUserAccounts\Api\ParentaccountInterface;
use Cminds\MultiUserAccounts\Api\SubaccountRepositoryInterface;
use Exception;
use Magento\Customer\Model\Customer\DataProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Request\Http;
use Magento\Backend\Model\Session;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount\CollectionFactory as SubaccountCollectionFactory;
use Cminds\MultiUserAccounts\Model\ResourceModel\SubaccountTransportRepository;
use Cminds\MultiUserAccounts\Model\Service\Convert\Customer\ParentAccount as ParentAccountConverter;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Plugin
{
    /**
     * @var CustomerRepositoryInterface
     *
     */
    private $customerRepositoryInterface;

    /**
     *
     * @var SubaccountCollectionFactory
     */
    private $subaccountCollectionFactory;

    /**
     *
     * @var SubaccountTransportRepository
     */
    private $subaccountTransportRepository;

    /**
     * Adminside Session Object.
     *
     * @var Session
     */
    private $backendSession;

    /**
     * Subaccount repository object.
     *
     * @var SubaccountRepositoryInterface
     */
    private $subaccountRepository;

    /**
     * Http Request Object.
     *
     * @var Http
     */
    private $request;

    /**
     * @var ParentaccountInterface
     */
    private $parentAccountInterface;

    /**
     * Plugin constructor.
     *
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param SubaccountCollectionFactory $subaccountCollectionFactory
     * @param SubaccountTransportRepository $subaccountTransportRepository
     * @param Session $backendSession
     * @param ModuleConfig $moduleConfig
     * @param SubaccountRepositoryInterface $subaccountRepository
     * @param Http $http
     * @param ParentaccountInterface $parentAccountInterface
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepositoryInterface,
        SubaccountCollectionFactory $subaccountCollectionFactory,
        SubaccountTransportRepository $subaccountTransportRepository,
        Session $backendSession,
        ModuleConfig $moduleConfig,
        SubaccountRepositoryInterface $subaccountRepository,
        Http $http,
        ParentaccountInterface $parentAccountInterface
    ) {
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->subaccountCollectionFactory = $subaccountCollectionFactory;
        $this->subaccountTransportRepository = $subaccountTransportRepository;
        $this->backendSession = $backendSession;
        $this->moduleConfig = $moduleConfig;
        $this->subaccountRepository = $subaccountRepository;
        $this->request = $http;
        $this->parentAccountInterface = $parentAccountInterface;
    }

    /**
     * Set customer edit form data with the parent_account_id value.
     *
     * @param DataProvider $dataProvider
     * @param array $data
     *
     * @return array
     */
    public function afterGetData(DataProvider $dataProvider, $data)
    {
        if (!$data) {
            return $data;
        }

        //iterate only one time because array contains only one element
        foreach ($data as $customerId => $customerFormData) {
            if (!isset($customerFormData['customer'])) {
                break;
            }

            if (!$this->backendSession->getParentGroupID()) {
                $this->backendSession->setParentGroupID($data[$customerId]['customer']['group_id']);
            } else {
                $newCustomerGroup = $data[$customerId]['customer']['group_id'];
                $oldCustomerGroup = $this->backendSession->getParentGroupID();

                if ($oldCustomerGroup != $newCustomerGroup) {
                    $this->changeSubAccountGroup($newCustomerGroup, $customerId);
                }
            }

            try {
                $subaccountModel = $this->subaccountRepository
                    ->getByCustomerId($customerId);
                if ($this->moduleConfig->showAsText()) {
                    $parentAccount = $this->getParentAccountById($subaccountModel->getParentCustomerId());
                    if ($parentAccount) {
                        $data[$customerId]['customer']['parent_account_id'] = $parentAccount
                            ->getEmail();
                    }
                } else {
                    $data[$customerId]['customer']['parent_account_id'] = $subaccountModel
                        ->getParentCustomerId();
                }
            } catch (NoSuchEntityException $exception) {
                break;
            }
        }

        return $data;
    }

    /**
     * @param $parentId
     * @return bool|\Cminds\MultiUserAccounts\Api\Data\ApiParentAccountInterface
     */
    private function getParentAccountById($parentId)
    {
        try {
            return $this->parentAccountInterface->getEmailById($parentId);
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Filter which fields to display.
     *
     * @param DataProvider $dataProvider
     * @param array $data
     *
     * @return array
     */
    public function afterGetMeta(DataProvider $dataProvider, $data)
    {
        $id = $this->request->getParam('id');
        if (!$id) {
            return $data;
        }

        /** If there is no entity with id $id, then the code will be automatically executed in the catch block. */
        try {
            $this->subaccountRepository->getByCustomerId($id);

            $exists = true;
        } catch (Exception $exception) {
            $exists = false;
        }

        if ($exists) {
            unset($data['customer']['children']['can_manage_subaccounts']);
        }

        return $data;
    }

    /**
     * Manage Subaccount Group
     *
     * @return $this
     */
    protected function changeSubAccountGroup($newGroup, $customerId)
    {
        if (!$this->moduleConfig->changeSubAccountGroup()) {
            return $this;
        }

        $collection = $this->subaccountCollectionFactory->create()
            ->addFieldToSelect('customer_id')
            ->addFieldToFilter('parent_customer_id', $customerId)
            ->setOrder('created_at', 'desc');

        foreach ($collection as $subaccount) {
            $subaccount->getCustomerId();
            $customer = $this->customerRepositoryInterface->getById($subaccount->getCustomerId());
            $customer->setGroupId($newGroup);
            $this->customerRepositoryInterface->save($customer);
        }

        return $this;
    }
}
