<?php

namespace Cminds\MultiUserAccounts\Block\Magento\Customer\Account\Dashboard;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Model\Session as CustomerSession;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Helper\View;

/**
 * Cminds MultiUserAccounts customer account confirm controller plugin.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * View helper object.
     *
     * @var ViewHelper
     */
    private $viewHelper;
     /**
      * Session object.
      *
      * @var CustomerSession
      */
    private $customerSession;

    /**
     * Cached subscription object
     *
     * @var \Magento\Newsletter\Model\Subscriber
     */
    protected $_subscription;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * @var \Magento\Customer\Helper\View
     */
    protected $_helperView;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param ViewHelper  $viewHelper View helper object.
     * @param \Magento\Customer\Helper\View $helperView
     * @param array $data
     */
    public function __construct(
        Context $context,
        CurrentCustomer $currentCustomer,
        SubscriberFactory $subscriberFactory,
        View $helperView,
        ViewHelper $viewHelper,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->currentCustomer = $currentCustomer;
        $this->_subscriberFactory = $subscriberFactory;
        $this->customerSession = $customerSession;
        $this->_helperView = $helperView;
        $this->viewHelper = $viewHelper;
        parent::__construct($context, $data);
    }

    /**
     * Returns the Magento Customer Model for this block
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {
        try {
            return $this->currentCustomer->getCustomer();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Get the full name of a customer
     *
     * @return string full name
     */
    public function getName()
    {
        $subaccountData = $this->customerSession->getCustomerData();

        if ($this->viewHelper->isSubaccountLoggedIn() == 1) {
            if ($subaccountData) {
                return $subaccountData->getFirstName()." ".$subaccountData->getLastName();
            }
        } else {
            return $this->_helperView->getCustomerName($this->getCustomer());
        }
    }

    /**
     * Get the full name of a customer
     *
     * @return string full name
     */
    public function getSubUserEmail()
    {
        $subaccountData = $this->customerSession->getCustomerData();

        if ($this->viewHelper->isSubaccountLoggedIn() == 1) {
            if ($subaccountData) {
                return $subaccountData->getEmail();
            }
        }
    }

    /**
     * @return string
     */
    public function getChangePasswordUrl()
    {
        return $this->_urlBuilder->getUrl('customer/account/edit/changepass/1');
    }

    /**
     * Get Customer Subscription Object Information
     *
     * @return \Magento\Newsletter\Model\Subscriber
     */
    public function getSubscriptionObject()
    {
        if (!$this->_subscription) {
            $this->_subscription = $this->_createSubscriber();
            $customer = $this->getCustomer();
            if ($customer) {
                $this->_subscription->loadByEmail($customer->getEmail());
            }
        }
        return $this->_subscription;
    }

    /**
     * Gets Customer subscription status
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsSubscribed()
    {
        return $this->getSubscriptionObject()->isSubscribed();
    }

    /**
     * Newsletter module availability
     *
     * @return bool
     */
    public function isNewsletterEnabled()
    {
        return $this->getLayout()
            ->getBlockSingleton(\Magento\Customer\Block\Form\Register::class)
            ->isNewsletterEnabled();
    }

    /**
     * @return \Magento\Newsletter\Model\Subscriber
     */
    protected function _createSubscriber()
    {
        return $this->_subscriberFactory->create();
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        return $this->currentCustomer->getCustomerId() ? parent::_toHtml() : '';
    }
}
