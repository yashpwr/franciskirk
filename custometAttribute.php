<?php
exit;
error_reporting(E_ALL);
ini_set('display_errors', 1);

use \Magento\Framework\App\Bootstrap;
require __DIR__ . '/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$state = $objectManager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend');

$eavConfig = $objectManager->get('Magento\Eav\Model\Config');
$eavSetupFactory = $objectManager->get('Magento\Eav\Setup\EavSetupFactory');
$attributeSetFactory = $objectManager->get('Magento\Eav\Model\Entity\Attribute\SetFactory');

$eavSetup = $eavSetupFactory->create();

/*$eavSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY,'account_flag');
echo 'attribute removed';*/

$customerEntity = $eavConfig->getEntityType('customer');
$attributeSetId = $customerEntity->getDefaultAttributeSetId();

$attributeSet = $attributeSetFactory->create();
$attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

$eavSetup->addAttribute(
			\Magento\Customer\Model\Customer::ENTITY,
			'account_flag',
			[
				'type'         => 'varchar',
				'label'        => 'Account Flag',
				'input'        => 'select',
				'source'	   => 'Mahesh\CustomerCustomAttribute\Model\Customer\Source\AccountFlag',
				'required'     => false,
				'visible'      => true,
				'user_defined' => true,
				'position'     => 999,
				'system'       => 0,
			]
		);

$customAttribute = $eavConfig->getAttribute('customer', 'account_flag');
 
        $customAttribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => ['adminhtml_customer']
        ]);
        $customAttribute->save();
echo 'attribute updated';