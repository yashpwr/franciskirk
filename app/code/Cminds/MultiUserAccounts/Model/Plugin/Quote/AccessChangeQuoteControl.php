<?php

namespace Cminds\MultiUserAccounts\Model\Plugin\Quote;

use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Cminds\MultiUserAccounts\Observer\Checkout\Quote\SubmitBefore;
use Magento\Framework\Registry;

class AccessChangeQuoteControl
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * AccessChangeQuoteControl constructor.
     *
     * @param Registry $registry
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Registry $registry,
        ModuleConfig $moduleConfig
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->registry = $registry;
    }

    /**
     * If there is a quote user change from force use parent details,
     * and the flag is set, we need to approve it.
     *
     * @param \Magento\Authorization\Model\UserContextInterface $subject
     * @param callable $proceed
     *
     * @return int
     */
    public function aroundGetUserId(\Magento\Authorization\Model\UserContextInterface $subject, callable $proceed)
    {
        $data = $this->registry->registry(SubmitBefore::CMINDS_MULTIUSERACCOUNTS_CHANGE_TEMP_USER_ID);
        $result = $proceed();

        if ($data !== null) {
            $updatedUserId = (int)$data['updated_user_id'];

            return $updatedUserId;
        }

        return $result;
    }
}
