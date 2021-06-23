<?php

namespace Cminds\MultiUserAccounts\Controller\Manage;

use Cminds\MultiUserAccounts\Controller\AbstractManage;
use Cminds\MultiUserAccounts\Helper\View;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

/**
 * Cminds MultiUserAccounts manage controller.
 *
 * @category    Cminds
 * @package     Cminds_MultiUserAccounts
 * @author      Piotr Pierzak <piotr@cminds.com>
 */
class Index extends AbstractManage
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var View
     */
    protected $viewHelper;
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ModuleConfig $moduleConfig
     * @param Session $customerSession
     * @param View $viewHelper
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ModuleConfig $moduleConfig,
        Session $customerSession,
        View $viewHelper,
        Registry $registry
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->moduleConfig = $moduleConfig;
        $this->customerSession = $customerSession;
        $this->viewHelper = $viewHelper;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $canManageSubaccounts = $this->viewHelper->canManageSubaccounts();
        if (!$canManageSubaccounts) {
            $this->messageManager->addErrorMessage(
                __('Administrator disabled ability to create or edit subaccounts by you.')
            );
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->registry->register('query', $this->getRequest()->getParam('query'));

        $resultPage->getConfig()->getTitle()->set(__('Manage Subaccounts'));

        return $resultPage;
    }
}
