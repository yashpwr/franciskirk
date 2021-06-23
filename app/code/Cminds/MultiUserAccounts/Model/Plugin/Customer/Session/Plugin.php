<?php

namespace Cminds\MultiUserAccounts\Model\Plugin\Customer\Session;

use Cminds\MultiUserAccounts\Observer\Customer\SaveBefore;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\Registry;

/**
 * Cminds MultiUserAccounts session model plugin.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Plugin
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * Plugin constructor.
     *
     * @param Registry $coreRegistry
     */
    public function __construct(Registry $coreRegistry)
    {
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * @param Session $subject
     * @param \Closure $proceed
     * @param Customer $customer
     *
     * @return Session
     */
    public function aroundSetCustomer(
        Session $subject,
        \Closure $proceed,
        Customer $customer
    ) {
        $this->coreRegistry->register(SaveBefore::SKIP_PERMISSION_CHECK, true);
        $proceed($customer);
        $this->coreRegistry->unregister(SaveBefore::SKIP_PERMISSION_CHECK);

        return $subject;
    }
}
