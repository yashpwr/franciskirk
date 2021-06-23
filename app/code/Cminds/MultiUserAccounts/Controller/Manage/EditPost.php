<?php
/**
 * @category Cminds
 * @package  MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Controller\Manage;

use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterfaceFactory;
use Cminds\MultiUserAccounts\Api\SubaccountTransportRepositoryInterface;
use Cminds\MultiUserAccounts\Block\Manage\Table;
use Cminds\MultiUserAccounts\Controller\AbstractManage;
use Cminds\MultiUserAccounts\Helper\Email;
use Cminds\MultiUserAccounts\Helper\View;
use Cminds\MultiUserAccounts\Model\AccountManagement as CustomerAccountManagement;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Cminds\MultiUserAccounts\Model\Permission;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session as Session;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Reflection\DataObjectProcessor;

/**
 * Cminds MultiUserAccounts manage edit post controller.
 */
class EditPost extends AbstractManage
{
    /**
     * Session object.
     *
     * @var Session
     */
    private $session;

    /**
     * Subaccount transport repository object.
     *
     * @var SubaccountTransportRepositoryInterface
     */
    private $subaccountTransportRepository;

    /**
     * Subaccount transport factory object.
     *
     * @var SubaccountTransportInterfaceFactory
     */
    private $subaccountTransportDataFactory;

    /**
     * Validator object.
     *
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * Data object processor.
     *
     * @var DataObjectProcessor
     */
    private $dataProcessor;

    /**
     * Data object helper.
     *
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * Permission object.
     *
     * @var Permission
     */
    private $permission;

    /**
     * Custom account management object.
     *
     * @var CustomerAccountManagement
     */
    private $customerAccountManagement;

    /**
     * Customer registry object.
     *
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * Customer Factory.
     *
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var View
     */
    protected $viewHelper;

    /**
     * @var Table
     */
    protected $manageList;

    /**
     * @var Email
     */
    protected $emailHelper;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param SubaccountTransportRepositoryInterface $subaccountTransportRepository
     * @param SubaccountTransportInterfaceFactory $subaccountTransportDataFactory
     * @param Validator $formKeyValidator
     * @param DataObjectProcessor $dataProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param Permission $permission
     * @param CustomerAccountManagement $customerAccountManagement
     * @param CustomerRegistry $customerRegistry
     * @param CustomerRepositoryInterface $customerRepository
     * @param ModuleConfig $moduleConfig
     * @param View $viewHelper
     * @param Table $manageList
     * @param CustomerFactory $customerFactory
     * @param Email $emailHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        SubaccountTransportRepositoryInterface $subaccountTransportRepository,
        SubaccountTransportInterfaceFactory $subaccountTransportDataFactory,
        Validator $formKeyValidator,
        DataObjectProcessor $dataProcessor,
        DataObjectHelper $dataObjectHelper,
        Permission $permission,
        CustomerAccountManagement $customerAccountManagement,
        CustomerRegistry $customerRegistry,
        CustomerRepositoryInterface $customerRepository,
        ModuleConfig $moduleConfig,
        View $viewHelper,
        Table $manageList,
        CustomerFactory $customerFactory,
        Email $emailHelper
    ) {
        $this->session = $customerSession;
        $this->subaccountTransportRepository = $subaccountTransportRepository;
        $this->subaccountTransportDataFactory = $subaccountTransportDataFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->dataProcessor = $dataProcessor;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->permission = $permission;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerRegistry = $customerRegistry;
        $this->customerRepository = $customerRepository;
        $this->moduleConfig = $moduleConfig;
        $this->viewHelper = $viewHelper;
        $this->manageList = $manageList;
        $this->customerFactory = $customerFactory;
        $this->emailHelper = $emailHelper;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('*/*/edit');
        }

        if (!$this->getRequest()->getParam('email') && !$this->getRequest()->getParam('login')) {
            $this->messageManager->addError(__('Some of obligatory fields are not filled'));
            $params = [];

            if ($id = $this->getRequest()->getParam('id')) {
                $params['id'] = $id;
            }

            return $resultRedirect->setPath('*/*/edit', $params);
        }

        $isPost = $this->getRequest()->isPost();
        if ($isPost === false) {
            return $resultRedirect->setPath('*/*/edit');
        }

        //$editedCustomerId = $this->getRequest()->getParam('customer_id');  // get edited subaccount id

        if ($this->session->getSubaccountData()) {
            $parentCustomerId = $this->session
                ->getSubaccountData()->getCustomerId();
        } else {
            $parentCustomerId = $this->session->getCustomerId();
        }

        $subaccountId = $this->getRequest()->getParam('id');

        try {
            $subaccountTransportDataObject = $this->extractSubaccount();

            // divide the roles of approval and authority
            if ($subaccountTransportDataObject->getManageOrderAuthorizePermission()) {
                $subaccountTransportDataObject->setManageOrderApprovalPermission(0);
            }

            //$this->adjustSubaccountsPermissions($subaccountTransportDataObject, $parentCustomerId);

            $savedSubaccountTransportDataObject = $this
                ->subaccountTransportRepository
                ->save($subaccountTransportDataObject);

            //$this->adjustNestedSubaccountsPermissions($subaccountTransportDataObject);

            $subaccountId = $savedSubaccountTransportDataObject->getId();
            $this->changeSubaccountPassword($savedSubaccountTransportDataObject);

            $customerModel = $this->customerRegistry
                ->retrieve($savedSubaccountTransportDataObject->getCustomerId());
            $customerDataModel = $customerModel->getDataModel();
            if (!$subaccountTransportDataObject->getCustomerId()) {
                $this->customerAccountManagement
                    ->sendSubaccountEmailConfirmation(
                        $customerDataModel
                    );
            }

            $customerModel
                ->setData('customer_is_active', $subaccountTransportDataObject->getIsActive() ?: 0)
                ->getResource()
                ->saveAttribute($customerModel, 'customer_is_active');

            $customAttributes = $this
                ->getRequest()
                ->getParam('attribute') ?: [];
            foreach ($customAttributes as $attrCode => $attrValue) {
                if ($attrValue && $attrCode) {
                    $customerModel
                        ->setData($attrCode, $attrValue)
                        ->getResource()
                        ->saveAttribute($customerModel, $attrValue);
                }
            }

            $this->messageManager->addSuccessMessage(__('Subaccount has been saved.'));
            $url = $this->buildUrl('*/*/index', ['_secure' => true]);

            $this->getSession()->setSubaccountFormData(null);

            return $resultRedirect->setUrl($this->_redirect->success($url));
        } catch (InputException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            foreach ($e->getErrors() as $error) {
                $this->messageManager->addErrorMessage($error->getMessage());
            }
        } catch (AuthenticationException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (AlreadyExistsException $e) {
            $this->messageManager->addErrorMessage(
                __('Account with provided email address already exists.')
            );
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __($e->getMessage())
            );
        }

        $this->getSession()->setSubaccountFormData(
            $this->getRequest()->getPostValue()
        );

        if ($subaccountId) {
            $redirectUrl = $this->buildUrl('*/*/edit', ['id' => $subaccountId]);
        } else {
            $redirectUrl = $this->buildUrl('*/*/add');
        }

        return $resultRedirect->setUrl($this->_redirect->error($redirectUrl));
    }

    /**
     * Change subaccount password.
     *
     * @param SubaccountTransportInterface $savedSubaccountTransportDataObject Subaccount
     *     transport data object.
     *
     * @return EditPost
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws InputException
     * @throws \Exception
     */
    private function changeSubaccountPassword(
        SubaccountTransportInterface $savedSubaccountTransportDataObject
    ) {
        $newPass = $this->getRequest()->getPost('password');
        $confPass = $this->getRequest()->getPost('password_confirmation');

        if ($savedSubaccountTransportDataObject->getId() && (
                empty($newPass)
                && empty($confPass)
            )
        ) {
            return $this;
        }

        if ($newPass === '') {
            throw new InputException(__('Please enter new password.'));
        }

        if ($newPass !== $confPass) {
            throw new InputException(__('Confirm your new password.'));
        }

        $this->customerAccountManagement->changePassword(
            $savedSubaccountTransportDataObject->getEmail(),
            '',
            $newPass
        );

        return $this;
    }

    /**
     * Retrieve customer session object.
     *
     * @return Session
     */
    private function getSession()
    {
        return $this->session;
    }

    /**
     * Extract subaccount from request.
     *
     * @return SubaccountTransportInterface
     */
    private function extractSubaccount()
    {
        $existingSubaccountTransportDataArray = $this->getExistingSubaccountData();
        $subaccountData = $this->getRequest()->getParams();
        $subaccountTransportDataObject = $this->subaccountTransportDataFactory->create();

        $subaccountData[$subaccountTransportDataObject::ADDITIONAL_INFORMATION]
            = json_encode($subaccountData[$subaccountTransportDataObject::ADDITIONAL_INFORMATION]);

        $this->dataObjectHelper->populateWithArray(
            $subaccountTransportDataObject,
            array_merge($existingSubaccountTransportDataArray, $subaccountData),
            '\Cminds\MultiUserAccounts\Api\Data\SubaccountInterface'
        );

        $parentCustomer = $this->getSession()->getCustomer();

        $subaccountTransportDataObject
            ->setIsActive(isset($subaccountData['is_active']) ?: $subaccountTransportDataObject::NOT_ACTIVE_FLAG)
            ->setParentCustomerId($parentCustomer->getId());

        if ($this->moduleConfig->changeSubAccountGroup()) {
            $subaccountTransportDataObject->setGroupId($parentCustomer->getGroupId());
        }

        $subaccountTransportDataObject->setStoreId($parentCustomer->getStoreId());

        if (isset($subaccountData[$subaccountTransportDataObject::TAXVAT])) {
            if ((bool)$subaccountTransportDataObject->getForceUsageParentVatPermission()) {
                $subaccountTransportDataObject->setTaxvat(
                    $subaccountTransportDataObject->getTaxvat() ? $subaccountTransportDataObject->getTaxvat() : $parentCustomer->getTaxvat()
                );
            } else {
                $subaccountTransportDataObject->setTaxvat(
                    $subaccountData[$subaccountTransportDataObject::TAXVAT]
                );
            }
        }

        return $subaccountTransportDataObject;
    }

    /**
     * Retrieve existing subaccount data.
     *
     * @return array
     * @throws \Exception
     */
    private function getExistingSubaccountData()
    {
        $existingSubaccountTransportDataArray = [];
        $subaccountId = (int)$this->getRequest()->getParam('id');

        if ($subaccountId) {
            $existingSubaccountTransportDataObject = $this
                ->subaccountTransportRepository
                ->getById($subaccountId);

            $parentCustomerId = (int)$existingSubaccountTransportDataObject
                ->getParentCustomerId();

            if ($this->session->getSubaccountData()) {
                $customerId = $this->session->getSubaccountData()->getCustomerId();
            } else {
                $customerId = (int)$this
                ->getSession()
                ->getCustomerId();
            }

            if ($parentCustomerId !== $customerId) {
                throw new \Exception(
                    __('You are not allowed for this operation.')
                );
            }

            /**
             * Reset subaccount permissions.
             * Will be overwritten by new permissions and recalculated
             * before subaccount entity save.
             */
            $existingSubaccountTransportDataObject->setPermission(0);
            $this->permission->loadSubaccountPermissions(
                $existingSubaccountTransportDataObject
            );

            $existingSubaccountTransportDataArray = $this->dataProcessor
                ->buildOutputDataArray(
                    $existingSubaccountTransportDataObject,
                    '\Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface'
                );
        }

        return $existingSubaccountTransportDataArray;
    }

    /**
     * Return generated url to provided route.
     *
     * @param string $route Route string.
     * @param array $params Params array.
     *
     * @return string
     */
    private function buildUrl($route = '', $params = [])
    {
        return $this->_url->getUrl($route, $params);
    }

    /**
     * @param $subaccountTransportDataObject
     * @param $parentCustomerId
     * @return $this
     */
    protected function adjustSubaccountsPermissions($subaccountTransportDataObject, $parentCustomerId)
    {
        if ($this->viewHelper->isSubaccountLoggedIn()) {
            $currentSubaccountData = $this->subaccountTransportRepository->getByCustomerId($parentCustomerId);
            if ($currentSubaccountData->getCheckoutOrderApprovalPermission()) {
                $subaccountTransportDataObject->setCheckoutOrderApprovalPermission(1);
            }
            if (!$currentSubaccountData->getCheckoutCartViewPermission()) {
                $subaccountTransportDataObject->setCheckoutCartViewPermission(0);
            }
            if (!$currentSubaccountData->getCheckoutViewPermission()) {
                $subaccountTransportDataObject->setCheckoutViewPermission(0);
            }
            if (!$currentSubaccountData->getCheckoutOrderCreatePermission()) {
                $subaccountTransportDataObject->setCheckoutOrderCreatePermission(0);
            }
            if ($currentSubaccountData->getManageOrderApprovalPermission() ||
                $currentSubaccountData->getManageOrderAuthorizePermission()) {
                $subaccountTransportDataObject->setManageOrderApprovalPermission(0);
                $subaccountTransportDataObject->setManageOrderAuthorizePermission(0);
            }
            if (!$currentSubaccountData->getManageOrderAuthorizePermission()) {
                $subaccountTransportDataObject->setManageOrderAuthorizePermission(0);
            }
        }

        return $this;
    }


    /**
     * Recursively remove permissions from subaccounts if specified subaccount
     * has no such permissions.
     *
     * @param SubaccountTransportInterface $subaccount
     */
    protected function adjustNestedSubaccountsPermissions(SubaccountTransportInterface $subaccount)
    {
        $subaccounts = $this->manageList->getSubaccountsByParentId($subaccount->getCustomerId());
        foreach ($subaccounts as $nestedSubaccount) {
            if (!$subaccount->getCheckoutCartViewPermission()) {
                $nestedSubaccount->setCheckoutCartViewPermission(0);
            }
            if (!$subaccount->getCheckoutViewPermission()) {
                $nestedSubaccount->setCheckoutViewPermission(0);
            }
            if (!$subaccount->getCheckoutOrderCreatePermission()) {
                $nestedSubaccount->setCheckoutOrderCreatePermission(0);
            }
            if (!$subaccount->getManageOrderApprovalPermission()) {
                $nestedSubaccount->setManageOrderApprovalPermission(0);
            }
            if ($subaccount->getCheckoutOrderApprovalPermission()) {
                $nestedSubaccount->setCheckoutOrderApprovalPermission(1);
            }
            if (!$subaccount->getManageSubaccounts()) {
                $nestedSubaccount->setManageSubaccounts(0);
            }
            $this->subaccountTransportRepository->save($nestedSubaccount);
            $this->adjustNestedSubaccountsPermissions($nestedSubaccount);
        }

        return $this;
    }
}
