<?php
namespace Cminds\MultiUserAccounts\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Shipping\Model\Config;

class ShippingMethod implements ArrayInterface
{
    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $shippingConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param Config $shippingConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Config $shippingConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->shippingConfig = $shippingConfig;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $list = [];
        foreach ($this->shippingConfig->getActiveCarriers() as $carrierCode => $carrierModel) {
            if ($carrierMethods = $carrierModel->getAllowedMethods()) {
                foreach ($carrierMethods as $methodCode => $method) {
                    $code = $carrierCode . '_' . $methodCode;
                    $list[] = [
                        'value' => $code,
                        'label' => $method
                    ];
                }
            }
        }
        return $list;
    }
}
