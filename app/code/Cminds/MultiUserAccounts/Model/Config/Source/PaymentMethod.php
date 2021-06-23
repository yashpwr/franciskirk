<?php
namespace Cminds\MultiUserAccounts\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Config;

class PaymentMethod implements ArrayInterface
{
    /**
     * @var Config
     */
    protected $paymentConfig;

    /**
     * @param Config $paymentConfig
     */
    public function __construct(
        Config $paymentConfig
    ) {
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $list = [];
        foreach ($this->paymentConfig->getActiveMethods() as $paymentMethod) {
            $list[] = [
                'value' => $paymentMethod->getCode(),
                'label' => $paymentMethod->getTitle()
            ];
        }
        return $list;
    }
}
