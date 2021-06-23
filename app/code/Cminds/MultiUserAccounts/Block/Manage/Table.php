<?php

namespace Cminds\MultiUserAccounts\Block\Manage;

use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Cminds\MultiUserAccounts\Helper\Manage as ManageHelper;
use Cminds\MultiUserAccounts\Helper\View as HelperView;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Cminds\MultiUserAccounts\Model\Permission;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount\Collection;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount\CollectionFactory as SubaccountCollectionFactory;
use Cminds\MultiUserAccounts\Model\ResourceModel\SubaccountTransportRepository;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Pager;

/**
 * Cminds MultiUserAccounts manage list block.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Table extends Template
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var SubaccountCollectionFactory
     */
    private $subaccountCollectionFactory;

    /**
     * @var HelperView
     */
    private $helperView;

    /**
     * @var Permission
     */
    private $permission;

    /**
     * @var SubaccountTransportInterface[]
     */
    private $subaccounts;

    /**
     * @var SubaccountTransportRepository
     */
    private $subaccountTransportRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var ManageHelper
     */
    private $manageHelper;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var CollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * Table constructor.
     *
     * @param Context $context
     * @param SubaccountCollectionFactory $subaccountCollectionFactory
     * @param CustomerSession $customerSession
     * @param HelperView $helperView
     * @param Permission $permission
     * @param SubaccountTransportRepository $subaccountTransportRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param ManageHelper $manageHelper
     * @param ModuleConfig $moduleConfig
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        SubaccountCollectionFactory $subaccountCollectionFactory,
        CustomerSession $customerSession,
        HelperView $helperView,
        Permission $permission,
        SubaccountTransportRepository $subaccountTransportRepository,
        CustomerRepositoryInterface $customerRepository,
        ManageHelper $manageHelper,
        ModuleConfig $moduleConfig,
        CollectionFactory $customerCollectionFactory,
        Registry $registry,
        array $data = []
    ) {
        $this->subaccountCollectionFactory = $subaccountCollectionFactory;
        $this->customerSession = $customerSession;
        $this->helperView = $helperView;
        $this->permission = $permission;
        $this->subaccountTransportRepository = $subaccountTransportRepository;
        $this->customerRepository = $customerRepository;
        $this->manageHelper = $manageHelper;
        $this->moduleConfig = $moduleConfig;

        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * @return bool
     */
    public function getCanManageSubaccounts()
    {
        return $this->helperView->canManageSubaccounts();
    }

    /**
     * @return bool|SubaccountTransportInterface[]
     */
    public function getSubaccounts()
    {
        if ($this->customerSession->getSubaccountData()) {
            $customerId = $this->customerSession
                ->getSubaccountData()->getCustomerId();
        } else {
            $customerId = $this->customerSession->getCustomerId();
        }

        if ($customerId === null) {
            return false;
        }

        if (!$this->subaccounts) {
            $this->subaccounts = $this->getSubaccountsByParentId($customerId);
        }

        return $this->subaccounts;
    }

    /**
     * @param $customerId
     * @return SubaccountTransportInterface[]
     */
    public function getSubaccountsByParentId($customerId)
    {
        $subaccounts = [];
        $collection = $this->subaccountCollectionFactory->create()
            ->addFieldToSelect(
                '*'
            )
            ->addFieldToFilter(
                'parent_customer_id',
                $customerId
            )
            ->setOrder(
                'created_at',
                'desc'
            );

        $allowedIds = [];
        if ($query = $this->getQuery()) {
            $allowedIds = $this->processSearch($collection, $query);
        }

        foreach ($collection as $subaccount) {
            if (!count($allowedIds) || in_array($subaccount->getCustomerId(), $allowedIds)) {
                $subaccounts[] = $this->subaccountTransportRepository
                    ->getById($subaccount->getId());
            }
        }

        return $subaccounts;
    }

    protected function processSearch($collection, $query)
    {
        /** @var Collection $collection */
        $customerIds = $collection->getColumnValues('customer_id');
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addAttributeToFilter('entity_id', ['in' => $customerIds])
            ->addAttributeToFilter(
                [
                    [
                        'attribute' => 'firstname', 'like' => '%' . $query . '%'
                    ],
                    [
                        'attribute' => 'lastname', 'like' => '%' . $query . '%'

                    ]
                ]
            );

        return $customerCollection->getColumnValues('entity_id');
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($this->getCustomers()) {
            $pager = $this->getLayout()->createBlock(
                Pager::class,
                'subaccounts.manage.table.pager'
            )->setCollection(
                $this->getCustomers()
            );
            $this->setChild('pager', $pager);
            $this->getCustomers()->load();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * @param   SubaccountTransportInterface $subaccount
     *
     * @return  string
     */
    public function getEditUrl(SubaccountTransportInterface $subaccount)
    {
        return $this->getUrl(
            'subaccounts/manage/edit',
            ['id' => $subaccount->getId()]
        );
    }

    /**
     * @param   SubaccountTransportInterface $subaccount
     *
     * @return  string
     */
    public function getEmulateUrl(SubaccountTransportInterface $subaccount)
    {
        return $this->getUrl(
            'subaccounts/emulate/login',
            ['id' => $subaccount->getId()]
        );
    }

    /**
     * @param   SubaccountTransportInterface $subaccount
     *
     * @return  string
     */
    public function getDeleteUrl(SubaccountTransportInterface $subaccount)
    {
        return $this->getUrl(
            'subaccounts/manage/delete',
            ['id' => $subaccount->getId()]
        );
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('customer/account/');
    }

    /**
     * Concatenate all subaccount name parts into full subaccount name.
     *
     * @param   SubaccountTransportInterface $subaccount
     *
     * @return  string
     */
    public function getSubaccountName(SubaccountTransportInterface $subaccount)
    {
        return $this->helperView->getSubaccountName($subaccount);
    }

    /**
     * Return subaccount status phrase.
     *
     * @param   SubaccountTransportInterface $subaccount
     *
     * @return  Phrase
     */
    public function getSubaccountStatus(
        SubaccountTransportInterface $subaccount
    ) {

        if ($subaccount->getIsActive()) {
            return __('Active');
        } else {
            return __('Inactive');
        }
        return $subaccount->getIsActive() ? __('Active') : __('Inactive');
    }

    /**
     * Get Customer Entity by id.
     *
     * @param $id
     * @return mixed
     */
    private function getCustomerById($id)
    {
        return $this->customerRepository->getById($id);
    }

    /**
     * Retrieve the is active custom customer attribute.
     *
     * @param $customerId
     * @return int
     */
    public function getIsActive($customerId)
    {
        $customer = $this->getCustomerById($customerId);
        $customerActive = $customer->getCustomAttribute('customer_is_active');
        if (isset($customerActive)) {
            $is_active = $customerActive->getValue();
        } else {
            $is_active = 0;
        }

        return $is_active;
    }

    /**
     * Return subaccount permission description html.
     *
     * @param   SubaccountTransportInterface $subaccount
     *
     * @return  string
     */
    public function getSubaccountPermissionDescriptionHtml(
        SubaccountTransportInterface $subaccount
    ) {
        return $this->permission
            ->getSubaccountPermissionDescriptionHtml($subaccount);
    }

    /**
     * Retrieve form action url and set "secure" param to avoid confirm
     * message when we submit form from secure page to unsecured.
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl(
            'subaccounts/manage/add',
            ['_secure' => true]
        );
    }

    public function getQuery()
    {
        return $this->registry->registry('query') ?? null;
    }

    /**
     * Is auth by login enabled
     *
     * @return bool
     */
    public function isLoginAuth()
    {
        return $this->moduleConfig->isLoginAuthEnabled();
    }
}
