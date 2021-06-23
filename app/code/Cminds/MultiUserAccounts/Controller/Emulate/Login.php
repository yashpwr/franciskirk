<?php

namespace Cminds\MultiUserAccounts\Controller\Emulate;

use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterfaceFactory;
use Cminds\MultiUserAccounts\Api\SubaccountTransportRepositoryInterface;
use Cminds\MultiUserAccounts\Controller\AbstractManage;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\Session as Session;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

/**
 * Cminds MultiUserAccounts emulate login controller.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Core Team <info@cminds.com>
 */
class Login extends AbstractManage
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var SubaccountTransportRepositoryInterface
     */
    private $subaccountTransportRepository;

    /**
     * @var SubaccountTransportInterfaceFactory
     */
    private $subaccountTransportDataFactory;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * Object initialization.
     *
     * @param Context $context
     * @param Session $session
     * @param CustomerFactory $customerFactory
     * @param PageFactory $resultPageFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param SubaccountTransportRepositoryInterface $subaccountTransportRepository
     * @param SubaccountTransportInterfaceFactory $subaccountTransportDataFactory
     */
    public function __construct(
        Context $context,
        Session $session,
        CustomerFactory $customerFactory,
        PageFactory $resultPageFactory,
        DataObjectHelper $dataObjectHelper,
        SubaccountTransportRepositoryInterface $subaccountTransportRepository,
        SubaccountTransportInterfaceFactory $subaccountTransportDataFactory
    ) {
        $this->session = $session;
        $this->resultPageFactory = $resultPageFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->subaccountTransportRepository = $subaccountTransportRepository;
        $this->subaccountTransportDataFactory = $subaccountTransportDataFactory;
        parent::__construct($context);
        $this->customerFactory = $customerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $subaccountId = $this->getRequest()->getParam('id');
        try {
            $subaccountTransportDataObject = $this->subaccountTransportRepository->getById($subaccountId);
            if ($this->session->getSubaccountData()) {
                $customerId = $this->session->getSubaccountData()->getCustomerId();
            } else {
                $customerId = (int)$this->session->getCustomerId();
            }

            $parentId = (int)$subaccountTransportDataObject->getParentCustomerId();

            if ($parentId !== $customerId) {
                throw new NoSuchEntityException(__('Subaccount with provided id does not exists.'));
            }
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addWarningMessage($e->getMessage());
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $this->session->logout();

        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($subaccountTransportDataObject->getWebsiteId());
        $customer->loadByEmail($subaccountTransportDataObject->getEmail());
        $this->session->setCustomerAsLoggedIn($customer);
        $this->session->setSubaccountData($subaccountTransportDataObject);
        if ($subaccountTransportDataObject->getManageSubaccounts()) {
            $this->session->setCustomerId($subaccountTransportDataObject->getParentCustomerId());
        }

        $this->messageManager->addSuccessMessage(__('You are logged as subaccount now.'));
        $resultRedirect->setPath('cms/index/index');

        return $resultRedirect;
    }
}
