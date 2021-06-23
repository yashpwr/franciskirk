<?php

namespace Cminds\MultiUserAccounts\Setup;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Cminds MultiUserAccounts upgrade data interface
 *
 * @category    Cminds
 * @package     Cminds_MultiUserAccounts
 * @author      Piotr Pierzak <piotr@cminds.com>
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var Config
     */
    private $resourceConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * UpgradeData constructor.
     *
     * @param EavSetupFactory      $eavSetupFactory
     * @param CustomerSetupFactory $customerSetupFactory
     * @param Config               $resourceConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param IndexerRegistry      $indexerRegistry
     * @param EavConfig            $eavConfig
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        CustomerSetupFactory $customerSetupFactory,
        Config $resourceConfig,
        ScopeConfigInterface $scopeConfig,
        IndexerRegistry $indexerRegistry,
        EavConfig $eavConfig
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->resourceConfig = $resourceConfig;
        $this->scopeConfig = $scopeConfig;
        $this->eavConfig = $eavConfig;
        $this->indexerRegistry = $indexerRegistry;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Installs data for a module.
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @return void
     * @throws \Exception
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), '1.1.8', '<')) {
            $customerSetup->addAttribute(
                Customer::ENTITY,
                'can_manage_subaccounts',
                [
                    'type' => 'int',
                    'label' => __('Can Manage Subaccounts'),
                    'input' => 'select',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'required' => false,
                    'default' => 0,
                    'visible' => true,
                    'admin_only' => true,
                    'system' => 0,
                ]
            );

            $customerSetup
                ->getEavConfig()
                ->getAttribute(
                    'customer',
                    'can_manage_subaccounts'
                )
                ->setData(
                    'used_in_forms',
                    ['adminhtml_customer']
                )
                ->save();

            $customerSetup->addAttribute(
                Customer::ENTITY,
                'customer_is_active',
                [
                    'type' => 'int',
                    'label' => __('Is Customer Active'),
                    'input' => 'select',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'required' => false,
                    'default' => 1,
                    'visible' => true,
                    'admin_only' => true,
                    'system' => 0,
                ]
            );
            $customerSetup
                ->getEavConfig()
                ->getAttribute(
                    'customer',
                    'customer_is_active'
                )
                ->setData(
                    'used_in_forms',
                    ['adminhtml_customer']
                )
                ->save();
        }

        if (version_compare($context->getVersion(), '1.10.0') < 0) {
            $customAttributeCode = 'store_view';
            $customerSetup->addAttribute('customer_address', $customAttributeCode, [
                'label' => 'Store View',
                'input' => 'select',
                'type' => 'int',
                'source' => \Cminds\MultiUserAccounts\Model\Config\Source\Stores::class,
                'required' => false,
                'position' => 999,
                'visible' => true,
                'system' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false,
                'frontend_input' => 'select',
                'backend' => ''
            ]);

            $attribute=$customerSetup->getEavConfig()
                ->getAttribute('customer_address',$customAttributeCode)
                ->addData(['used_in_forms' => [
                    'adminhtml_customer_address',
                    'adminhtml_customer',
                    'customer_address_edit',
                    'customer_register_address',
                    'customer_address',
                ]
                ]);
            $attribute->save();
        }


        $indexer = $this->indexerRegistry->get(Customer::CUSTOMER_GRID_INDEXER_ID);
        $indexer->reindexAll();

        $this->eavConfig->clear();

        $setup->endSetup();
    }
}
