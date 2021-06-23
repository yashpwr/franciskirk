<?php

namespace StripeIntegration\Payments\Model\ResourceModel\StripeCustomer;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('StripeIntegration\Payments\Model\StripeCustomer', 'StripeIntegration\Payments\Model\ResourceModel\StripeCustomer');
    }
}
