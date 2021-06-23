<?php

namespace StripeIntegration\Payments\Model;

use StripeIntegration\Payments\Helper\Logger;
use StripeIntegration\Payments\Exception;

class StripeCustomer extends \Magento\Framework\Model\AbstractModel
{
    // This is the Customer object, retrieved through the Stripe API
    var $_stripeCustomer = null;
    var $_defaultPaymentMethod = null;

    // The loaded Magento customer object
    var $_magentoCustomer = null;

    public $customerCard = null;
    public $paymentMethodsCache = [];

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Helper\Generic $helper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_config = $config;
        $this->_helper = $helper;
        $this->_customerSession = $customerSession;
        $this->_registry = $registry;
        $this->_appState = $context->getAppState();
        $this->_eventManager = $context->getEventDispatcher();
        $this->_cacheManager = $context->getCacheManager();
        $this->_resource = $resource;
        $this->_resourceCollection = $resourceCollection;
        $this->_logger = $context->getLogger();
        $this->_actionValidator = $context->getActionValidator();

        if (method_exists($this->_resource, 'getIdFieldName')
            || $this->_resource instanceof \Magento\Framework\DataObject
        ) {
            $this->_idFieldName = $this->_getResource()->getIdFieldName();
        }

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_construct();
    }

    protected function _construct()
    {
        $this->_init('StripeIntegration\Payments\Model\ResourceModel\StripeCustomer');

        $this->_config->initStripe();

        $this->_magentoCustomerId = $this->_helper->getCustomerId();

        if (is_numeric($this->_magentoCustomerId) && $this->_magentoCustomerId > 0 && !$this->getStripeId())
        {
            $this->load($this->_magentoCustomerId, 'customer_id');
            $this->updateSessionId();

            // If the customer has registered an account *after* they placed an order,
            // then they will have a Stripe account associated with a Magento customer ID of 0.
            // In this case, try to load the account again by email address. We only do this for the admin area
            // as there is a security risk for people registering with other people's email addresses
            // and taking over their saved cards from Stripe.
            $magentoEmail = $this->_helper->getCustomerEmail(); // This should never return a guest's email address because of _magentoCustomerId
            if (!$this->getStripeId() && $this->_helper->isAdmin() && $magentoEmail)
            {
                $this->load($magentoEmail, 'customer_email');
                if ($this->getId())
                {
                    $this->setCustomerId($this->_magentoCustomerId);
                    $this->save();
                }
            }
        }
        else if (!$this->getStripeId() && !$this->_helper->isAdmin())
        {
            // Guest customer that already exists in Stripe
            $sessionId = $this->_customerSession->getSessionId();
            $this->load($sessionId, 'session_id');
        }
    }

    public function loadFromData($customerStripeId, $customerObject)
    {
        if (empty($customerObject))
            return null;

        if (empty($customerStripeId))
            return null;

        $this->load($customerStripeId, 'stripe_id');

        // For older orders placed by customers that are out of sync
        if (empty($this->getStripeId()))
        {
            $this->setStripeId($customerStripeId);
            $this->setLastRetrieved(time());
        }

        $this->_stripeCustomer = $customerObject;

        return $this;
    }

    protected function updateSessionId()
    {
        if (!$this->getStripeId()) return;
        if ($this->_helper->isAdmin()) return;

        $sessionId = $this->_customerSession->getSessionId();
        if ($sessionId != $this->getSessionId())
        {
            $this->setSessionId($sessionId);
            $this->save();
        }
    }

    // Loads the customer from the Stripe API
    public function createStripeCustomerIfNotExists($noCache = false)
    {
        // If the payment method has not yet been selected, skip this step
        // $quote = $this->_helper->checkoutSession;
        // $paymentMethod = $quote->getPayment()->getMethod();
        // if (empty($paymentMethod) || $paymentMethod != "stripe_payments") return;

        $retrievedSecondsAgo = (time() - $this->getLastRetrieved());

        if (!$this->getStripeId())
        {
            $this->createStripeCustomer();
        }
        // if the customer was retrieved from Stripe in the last 10 minutes, we're good to go
        // otherwise retrieve them now to make sure they were not deleted from Stripe somehow
        else if ($retrievedSecondsAgo > (60 * 10) || $noCache)
        {
            if (!$this->retrieveByStripeID($this->getStripeId()))
            {
                $this->createStripeCustomer();
            }
        }

        return $this->_stripeCustomer;
    }

    public function createStripeCustomer($order = null, $params = null)
    {
        $customer = $this->_helper->getMagentoCustomer();

        if ($customer)
        {
            // Registered Magento customers
            $customerFirstname = $customer->getFirstname();
            $customerLastname = $customer->getLastname();
            $customerEmail = $customer->getEmail();
            $customerId = $customer->getEntityId();
        }
        else if ($order)
        {
            // Guest customers
            $address = $this->_helper->getAddressFrom($order, 'billing');
            $customerFirstname = $address->getFirstname();
            $customerLastname = $address->getLastname();
            $customerEmail = $address->getEmail();
            $customerId = 0;
        }
        else if ($this->_helper->isAdmin())
        {
            // New customer order placed from the admin area
            $quote = $this->_helper->getBackendSessionQuote();
            $quoteModel = $this->_helper->loadQuoteById($quote->getQuoteId());
            $address = $quoteModel->getBillingAddress();
            $customerFirstname = $address->getFirstname();
            $customerLastname = $address->getLastname();
            $customerEmail = $quoteModel->getCustomerEmail();
            $customerId = 0;
        }
        else
        {
            // Guest customer at checkout, with Always Save Cards enabled, or with subscriptions in the cart
            $quote = $this->_helper->getSessionQuote();

            if ($quote)
            {
                $address = $quote->getBillingAddress();
                $customerFirstname = $address->getFirstname();
                $customerLastname = $address->getLastname();
                $customerEmail = $address->getEmail();
                $customerId = 0;

            }
        }

        // This may happen if we are creating an order from the back office
        if (empty($customerId) && empty($customerEmail))
            return;

        // When we are in guest or new customer checkout, we may have already created this customer
        // if ($this->getCustomerStripeIdByEmail() !== false)
        //     return;

        // This is the case for new customer registrations and guest checkouts
        // if (empty($customerId))
        //     $customerId = -1;

        return $this->createNewStripeCustomer($customerFirstname, $customerLastname, $customerEmail, $customerId, $params);
    }

    public function createNewStripeCustomer($customerFirstname, $customerLastname, $customerEmail, $customerId, $params = null)
    {
        try
        {
            if (empty($params))
                $params = [];

            $params["name"] = "$customerFirstname $customerLastname";
            $params["email"] = $customerEmail;

            $this->_stripeCustomer = \Stripe\Customer::create($params);
            $this->_stripeCustomer->save();

            $this->setStripeId($this->_stripeCustomer->id);
            $this->setCustomerId($customerId);
            $this->setLastRetrieved(time());
            $this->setCustomerEmail($customerEmail);
            $this->updateSessionId();

            $this->save();

            return $this->_stripeCustomer;
        }
        catch (\Exception $e)
        {
            if ($this->_helper->isStripeAPIKeyError($e->getMessage()))
            {
                $this->_config->setIsStripeAPIKeyError(true);
                throw new \StripeIntegration\Payments\Exception\SilentException(__($e->getMessage()));
            }
            $msg = __('Could not set up customer profile: %1', $e->getMessage());
            $this->_logger->addError((string)$msg);
            $this->_helper->dieWithError($msg, $e);
        }
    }

    public function addSavedCard($newcard)
    {
        if (!$this->_stripeCustomer)
            $this->_stripeCustomer = $this->retrieveByStripeID($this->getStripeId());

        if (!$this->_stripeCustomer)
            $this->_helper->dieWithError("Could not save the customer's card because the customer could not be created in Stripe!");

        $customer = $this->_stripeCustomer;

        try
        {
            $card = $this->_helper->addSavedCard($customer, $newcard);

            if (!empty($card))
                $this->setCustomerCard($card);
        }
        catch (\Exception $e)
        {
            // The only known scenario for this is if the payment was placed under manual review by a Stripe Radar rule.
            // In that case, the card cannot be added to the customer. There will be a retry when the order is captured from
            // the Magento admin area
            return null;
        }

        return $card;
    }

    public function getDefaultPaymentMethod()
    {
        if (isset($this->_defaultPaymentMethod))
            return $this->_defaultPaymentMethod;

        $customer = $this->retrieveByStripeID();

        if (empty($customer->default_payment_method))
            return null;

        try
        {
            return $this->_defaultPaymentMethod = \Stripe\PaymentMethod::retrieve($customer->default_payment_method);
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    public function retrieveByStripeID($id = null)
    {
        if (isset($this->_stripeCustomer))
            return $this->_stripeCustomer;

        if (empty($id))
            $id = $this->getStripeId();

        if (empty($id))
            return false;

        try
        {
            $this->_stripeCustomer = \Stripe\Customer::retrieve($id);
            $this->setLastRetrieved(time());
            $this->save();

            if (!$this->_stripeCustomer || ($this->_stripeCustomer && isset($this->_stripeCustomer->deleted) && $this->_stripeCustomer->deleted))
                return false;

            return $this->_stripeCustomer;
        }
        catch (\Exception $e)
        {
            if (strpos($e->getMessage(), "No such customer") === 0)
            {
                return $this->createStripeCustomer();
            }
            else
            {
                $this->_logger->addError('Could not retrieve customer profile: '.$e->getMessage());
                return false;
            }
        }
    }

    public function setCustomerCard($card)
    {
        if (is_object($card) && get_class($card) == 'Stripe\Card')
        {
            $this->customerCard = array(
                "last4" => $card->last4,
                "brand" => $card->brand
            );
        }
    }

    public function addCard($source)
    {
        if (!$this->_stripeCustomer)
            $this->_stripeCustomer = $this->retrieveByStripeID($this->getStripeId());

        if (!$this->_stripeCustomer)
            throw new \Exception("Customer with ID " . $this->getStripeId() . " could not be retrieved from Stripe.");

        return $this->_helper->addSavedCard($this->_stripeCustomer, $source);
    }

    public function deleteCard($token)
    {
        if (!$this->_stripeCustomer)
            $this->_stripeCustomer = $this->retrieveByStripeID($this->getStripeId());

        if (!$this->_stripeCustomer)
            throw new \Exception("Customer with ID " . $this->getStripeId() . " could not be retrieved from Stripe.");

        // Deleting a payment method
        if (strpos($token, "pm_") === 0)
        {
            $pm = \Stripe\PaymentMethod::retrieve($token);
            $pm->detach();
            return $pm;
        }

        $card = $this->_stripeCustomer->sources->retrieve($token);
        $obj = clone $card;
        $card->detach();
        return $obj;
    }

    public function listCards($params = array())
    {
        try
        {
            return $this->_helper->listCards($this->_stripeCustomer, $params);
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    // Used in the html templates to generate the customer's saved cards options
    public function getCustomerCards($customerId = null)
    {
        $isAdmin = $this->_helper->isAdmin();

        if (!$this->_config->getSaveCards() && !$isAdmin)
            return [];

        if (!$isAdmin && $this->_helper->isGuest())
            return [];

        if (!$customerId)
            $customerId = $this->getCustomerId();

        if (!$this->getStripeId())
            return [];


        if (!$this->_stripeCustomer)
            $this->_stripeCustomer = $this->retrieveByStripeID($this->getStripeId());

        if (!$this->_stripeCustomer)
            return null;

        return $this->listCards();
    }

    public function getSubscriptions($params = null)
    {
        if (!$this->getStripeId())
            return null;

        $params['customer'] = $this->getStripeId();
        $params['limit'] = 100;
        $params['expand'] = ['data.default_payment_method'];

        $collection = \Stripe\Subscription::all($params);

        if (!isset($this->_subscriptions))
            $this->_subscriptions = [];

        foreach ($collection->data as $subscription)
        {
            $this->_subscriptions[$subscription->id] = $subscription;
        }

        return $this->_subscriptions;
    }

    public function getSubscription($id)
    {
        if (isset($this->_subscriptions) && !empty($this->_subscriptions[$id]))
            return $this->_subscriptions[$id];

        return \Stripe\Subscription::retrieve($id);
    }

    public function findCardByPaymentMethodId($paymentMethodId)
    {
        $customer = $this->retrieveByStripeID();

        if (!$customer)
            return null;

        if (isset($this->paymentMethodsCache[$paymentMethodId]))
            $pm = $this->paymentMethodsCache[$paymentMethodId];
        else
            $pm = $this->paymentMethodsCache[$paymentMethodId] = \Stripe\PaymentMethod::retrieve($paymentMethodId);

        if (!isset($pm->card->fingerprint))
            return null;

        return $this->_helper->findCardByFingerprint($customer, $pm->card->fingerprint);
    }
}
