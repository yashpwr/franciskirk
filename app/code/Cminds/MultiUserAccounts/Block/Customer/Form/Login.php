<?php

namespace Cminds\MultiUserAccounts\Block\Customer\Form;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url;
use Magento\Framework\View\Element\Template\Context;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;

class Login extends \Magento\Customer\Block\Form\Login
{
    private $config;

    public function __construct(
        Context $context,
        Session $customerSession,
        Url $customerUrl,
        ModuleConfig $config,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $customerUrl,
            $data
        );
        $this->config = $config;
    }

    public function isLoginAuthEnabled()
    {
        return $this->config->isLoginAuthEnabled();
    }

    public function getLoginAuthNotice()
    {
        return $this->config->getAuthLoginText();
    }

}
