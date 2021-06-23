<?php

namespace Cminds\MultiUserAccounts\Block\View\Element\Html\Link;

use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Html\Link\Current;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Cminds MultiUserAccounts manage approved orders link block.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class ManageApprovedOrders extends Current
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ViewHelper
     */
    private $viewHelper;

    /**
     * @var CustomerSession
     */
    private $_customerSession;

    /**
     * Object initialization.
     *
     * @param   Context              $context
     * @param   DefaultPathInterface $defaultPath
     * @param   ModuleConfig         $moduleConfig
     * @param   ViewHelper           $viewHelper
     * @param   CustomerSession      $customerSession
     * @param   array                $data
     */
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        ModuleConfig $moduleConfig,
        ViewHelper $viewHelper,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->viewHelper = $viewHelper;
        $this->_customerSession = $customerSession;

        parent::__construct(
            $context,
            $defaultPath,
            $data
        );
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->moduleConfig->isEnabled() === false ||
            empty($this->_customerSession->getSubaccountData())
        ) {
            return '';
        }

        return parent::_toHtml();
    }
}
