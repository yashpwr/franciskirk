<?php
namespace Mahesh\CustomerCustomAttribute\Model\Customer\Source;
use \Magento\Customer\Model\ResourceModel\Group\Collection;

class AccountFlag extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource {

    protected $_options;

    public function getAllOptions() {

        $this->_options = [
        	['label'=>'Select', 'value'=>''],
        	['label'=>'Can pay by card and account', 'value'=>'0'],
        	['label'=>'Card only', 'value'=>'1'],
        	['label'=>'Account only', 'value'=>'2']
        ];

        return $this->_options;
    }
}