<?php

namespace Cminds\MultiUserAccounts\Helper;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Cminds\MultiUserAccounts\Model\Config;

/**
 * Cminds MultiUserAccounts manage helper.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Manage extends AbstractHelper
{
    /**
     * Session object.
     *
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Customer Repository Interface
     *
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Module Config.
     *
     * @var Config
     */
    private $moduleConfig;

    /**
     * Manage constructor.
     *
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param Config $moduleConfig
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        Config $moduleConfig
    ) {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->moduleConfig = $moduleConfig;

        parent::__construct($context);
    }

    /**
     * Check if converted to parent account customer can manage sub accounts.
     *
     * @param $customerId
     *
     * @return bool
     */
    public function getCanConvertedParentsManageSubAccountsValue($customerId)
    {
        $value = true;

        if (!$this->moduleConfig->canParentAccountManageSubaccounts()) {
            return false;
        }

        return $value;
    }

    public function getParentAccount() {
        return [
            '',
            'company1',
            'account',
            'company1_account@cminds.com',
            'customer@1234',
            1,
            8,
            'one',
            'company1',
            '',
            'account',
            '',
            'company1_account@cminds.com',
            '7/31/1985',
            '',
            1,
            1,
            1,
            'one',
            'company1',
            '',
            'account',
            '',
            'Company 1',
            '',
            'new jersey',
            'US',
            'Florida',
            77777,
            3333333333,
            '',
            '',
            'Middle of nowhere',
            'Middle of nowhere',
            1,1,1,1,1,1,1,1,1,1,1,1,1
        ];
    }

    public function getSubAccountData() {
        return [
            'company1_account@cminds.com',
            'primary1',
            'account',
            'company1sub1_accounts@cminds.com',
            'customer@1234',
            1,
            8,
            'one',
            'primary1',
            '',
            'account',
            '',
            'company1sub1_accounts@cminds.com',
            '7/31/1985',
            '',
            1,
            1,
            1,
            'one',
            'primary1',
            '',
            'account',
            '',
            'Company 1',
            '',
            'new jersey',
            'US',
            'Florida',
            77777,
            3333333333,
            '',
            '',
            '',
            '',
            0,0,1,1,1,1,1,1,1,1,1,1,1
        ];
    }
}
