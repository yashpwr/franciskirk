<?php
/**
 * Cminds MultiUserAccounts manage form edit block.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
namespace Cminds\MultiUserAccounts\Block\Manage\Form;

use Cminds\MultiUserAccounts\Api\Data\SubaccountInterface;
use Cminds\MultiUserAccounts\Api\SubaccountTransportRepositoryInterface;
use Cminds\MultiUserAccounts\Helper\View;
use Cminds\MultiUserAccounts\Model\Config;
use Cminds\MultiUserAccounts\Model\Permission;
use Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory;
use Magento\Customer\Model\Session as Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Helper\Address;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Cminds\MultiUserAccounts\Helper\Manage as ManageHelper;

class Edit extends Template
{
    /**
     * Session object.
     *
     * @var Session
     */
    private $customerSession;

    /**
     * Customer address object.
     *
     * @var Address
     */
    private $customerAddressHelper;

    /**
     * Permission object.
     *
     * @var Permission
     */
    private $permission;

    /**
     * Subaccount object.
     *
     * @var SubaccountInterface
     */
    private $subaccount;

    /**
     * Customer Repository object.
     *
     * @var
     */
    private $customerRepository;

    /**
     * @var ManageHelper
     */
    private $manageHelper;

    /**
     * @var View
     */
    protected $viewHelper;

    /**
     * @var SubaccountTransportRepositoryInterface
     */
    protected $subaccountTransportRepositoryInterface;

    /**
     * @var Config
     */
    protected $moduleConfig;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Context $context
     * @param Session $session
     * @param Permission $permission
     * @param Address $address
     * @param CustomerRepositoryInterface $customerRepository
     * @param ManageHelper $manageHelper
     * @param View $viewHelper
     * @param Config $moduleConfig
     * @param SubaccountTransportRepositoryInterface $subaccountTransportRepositoryInterface
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $session,
        Permission $permission,
        Address $address,
        CustomerRepositoryInterface $customerRepository,
        ManageHelper $manageHelper,
        View $viewHelper,
        Config $moduleConfig,
        SubaccountTransportRepositoryInterface $subaccountTransportRepositoryInterface,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->customerSession = $session;
        $this->permission = $permission;
        $this->customerAddressHelper = $address;
        $this->customerRepository = $customerRepository;
        $this->manageHelper = $manageHelper;
        $this->viewHelper = $viewHelper;
        $this->subaccountTransportRepositoryInterface = $subaccountTransportRepositoryInterface;
        $this->moduleConfig = $moduleConfig;
        $this->collectionFactory = $collectionFactory;

        parent::__construct($context, $data);
    }

    /**
     * Preparing global layout.
     *
     * @return Edit
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $subaccountId = $this->getRequest()->getParam('id');

        if ($subaccountId) {
            $this->pageConfig->getTitle()->set(__('Edit Subaccount'));
        } else {
            $this->pageConfig->getTitle()->set(__('Add Subaccount'));
        }

        return $this;
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
            'subaccounts/manage/editPost',
            ['_secure' => true]
        );
    }

    /**
     * Retrieve back url.
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('subaccounts/manage/index');
    }

    /**
     * Return the Subaccount data stored in session.
     *
     * @return SubaccountInterface
     */
    public function getSubaccount()
    {
        if ($this->subaccount === null) {
            $this->subaccount = $this->customerSession->getSubaccountFormData(true);
        }

        return $this->subaccount;
    }

    /**
     * @return bool
     */
    public function disableStatusCheckbox()
    {
        return $this->moduleConfig->subAccountsNeedsToBeApproved();
    }

    /**
     * Retrive the is active custom customer attribute.
     *
     * @param $customerId
     * @return int
     */
    public function getIsActive($customerId)
    {
        $customer = $this->customerRepository->getById($customerId);
        $customerActive = $customer->getCustomAttribute('customer_is_active');
        if (isset($customerActive)) {
            $is_active = $customerActive->getValue();
        } else {
            $is_active = 0;
        }

        return $is_active;
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer()
    {
        return $this->customerRepository->getById($this->getSubaccount()->getCustomerId());
    }

    /**
     * Get Customer attr that controlls if subs can be edited.
     *
     * @return boolean
     */
    public function getCanManageSubaccounts()
    {
        return $this->viewHelper->canManageSubaccounts();
    }

    /**
     * Return all available permissions.
     *
     * @return array
     */
    public function getPermissions()
    {
        $permissions = $this->permission->getPermissions();

//        if ($this->viewHelper->isSubaccountLoggedIn()) {
//            if ($this->customerSession->getSubaccountData()) {
//                $customerId = $this->customerSession
//                    ->getSubaccountData()->getCustomerId();
//            } else {
//                $customerId = $this->customerSession->getCustomerId();
//            }
//            $customerId = $this->getSubaccount()->getCustomerId();

//            $currentSubaccount = $this->subaccountTransportRepositoryInterface->getByCustomerId(
//                $customerId
//            );

//            if (!$currentSubaccount->getCheckoutCartViewPermission()) {
//                $permissions['checkout_cart_view_permission']['disabled'] = true;
//            }
//            if (!$currentSubaccount->getCheckoutViewPermission()) {
//                $permissions['checkout_view_permission']['disabled'] = true;
//            }
//            if (!$currentSubaccount->getCheckoutOrderCreatePermission()) {
//                $permissions['checkout_order_create_permission']['disabled'] = true;
//            }
//            if (!$currentSubaccount->getManageOrderApprovalPermission()) {
//                $permissions['manage_order_approval_permission']['disabled'] = true;
//            }
//            if (!$currentSubaccount->getManageOrderAuthorizePermission()) {
//                $permissions['manage_order_authorize_permission']['disabled'] = true;
//            }
//            if (!$currentSubaccount->getManageSubaccounts()) {
//                $permissions['manage_subaccounts']['disabled'] = true;
//            }
//            if ($currentSubaccount->getCheckoutOrderApprovalPermission()) {
//                $permissions['checkout_order_approval_permission']['disabled'] = true;
//            }
//        }

        return $permissions;
    }

    /**
     * Check if permission is forced by force_use_parent_account_for_order module configuration
     *
     * @param   string $permissionCode
     *
     * @return  bool
     */
    public function isPermissionForced($permissionCode)
    {
        // TODO: Refactoring probably required.
        // For example change permission model to helper
        // to avoid methods aliasing.
        return $this->permission->isPermissionForced($permissionCode, $this->getSubaccount());
    }

    /**
     * Return permission html id by permission code.
     *
     * @param string $permissionCode Permission code.
     *
     * @return string
     */
    public function getPermissionHtmlId($permissionCode)
    {
        // TODO: Refactoring probably required.
        // For example change permission model
        // to helper to avoid methods aliasing.
        return $this->permission->getPermissionId($permissionCode);
    }

    /**
     * Return permission getter by permission code.
     *
     * @param string $permissionCode Permission code.
     *
     * @return string
     */
    public function getPermissionGetter($permissionCode)
    {
        // TODO: Refactoring probably required.
        // For example change permission model
        // to helper to avoid methods aliasing.
        return $this->permission->getPermissionGetter($permissionCode);
    }

    /**
     * Return config value for tax/vat show.
     *
     * @return null|string
     */
    public function showTaxVatConfiguration()
    {
        return $this->customerAddressHelper->getConfig('taxvat_show');
    }

    /**
     * Retrieve form data, used for customer name widget
     *
     * @return mixed
     */
    public function getFormData()
    {
        $data = $this->getData('form_data');
        if ($data === null) {
            $formData = $this->getSubaccount()->__toArray();
            $data = new \Magento\Framework\DataObject();

            if ($formData) {
                $data->addData($formData);
                $data->setCustomerData(1);
            }

            if (isset($data['region_id'])) {
                $data['region_id'] = (int)$data['region_id'];
            }
            $this->setData('form_data', $data);
        }
        return $data;
    }

    /**
     * @return mixed
     */
    public function getAttributesCollection()
    {
        $collection = $this->collectionFactory->create()
            ->addVisibleFilter();
        $collection->addFieldToFilter('is_visible_on_front', 1);

        return $collection;
    }

    /**
     * Retrieve name widget html
     *
     * @return mixed
     */
    public function getNameWidgetHtml()
    {
        return $this->getLayout()->createBlock(\Magento\Customer\Block\Widget\Name::class)
                ->setObject($this->getFormData())->setForceUseCustomerAttributes(true)->toHtml();
    }

    /**
     * Get moodule config option value for ParentCanSeeSubaccountsOrderHistory
     *
     * @return mixed
     */
    public function getParentCanSeeSubaccountsOrderHistory()
    {
        return (bool)$this->moduleConfig->getParentCanSeeSubaccountsOrderHistory();
    }

    public function isLoginAuthEnabled()
    {
        return (bool) $this->moduleConfig->isLoginAuthEnabled();
    }
}
