<?php
namespace Mahesh\CustomerCustomAttribute\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\SetFactory;

class InstallData implements InstallDataInterface
{
	private $eavSetupFactory;
	private $attributeSetFactory;

	public function __construct(EavSetupFactory $eavSetupFactory, Config $eavConfig, SetFactory $attributeSetFactory)
	{
		$this->eavSetupFactory = $eavSetupFactory;
		$this->eavConfig       = $eavConfig;
		$this->attributeSetFactory       = $attributeSetFactory;
	}

	public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
		
		$customerEntity = $this->eavConfig->getEntityType('customer');
		$attributeSetId = $customerEntity->getDefaultAttributeSetId();

		$attributeSet = $this->attributeSetFactory->create();
		$attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
		
		$eavSetup->addAttribute(
			\Magento\Customer\Model\Customer::ENTITY,
			'account_flag',
			[
				'type'         => 'int',
				'label'        => 'Account Flag',
				'input'        => 'boolean',
				'source'	   => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
				'required'     => false,
				'visible'      => true,
				'user_defined' => true,
				'position'     => 999,
				'system'       => 0,
			]
		);
		$account_flag = $this->eavConfig->getAttribute(Customer::ENTITY, 'account_flag');

		// more used_in_forms ['adminhtml_checkout','adminhtml_customer','adminhtml_customer_address','customer_account_edit','customer_address_edit','customer_register_address']

		$flagData = array(
			'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => ['adminhtml_customer']
        );
		$account_flag->setData($flagData);
		
		// $account_flag->setData(
		// 	'attribute_set_id' => $attributeSetId,
  //           'attribute_group_id' => $attributeGroupId,
  //           'used_in_forms' => ['adminhtml_customer']
		// );
		$account_flag->save();
	}
}