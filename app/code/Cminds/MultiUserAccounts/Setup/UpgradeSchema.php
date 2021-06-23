<?php

namespace Cminds\MultiUserAccounts\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Cminds MultiUserAccounts upgrade schema.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.12', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('quote'),
                'subaccount_id',
                [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => true,
                    'comment' => 'Subaccount ID',
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.24', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('cminds_multiuseraccounts_subaccount'),
                'additional_information',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Additional Information',
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.1.6', '<')) {
            $table = $setup->getTable('quote');
            $setup->getConnection()->addColumn(
                $table,
                'is_authorized',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Is Authorized',
                ]
            );
            $setup->getConnection()->addColumn(
                $table,
                'authorized_range',
                [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '10,2',
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Authorized Range',
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.6.5', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('quote'),
                'compare_price',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'unsigned' => true,
                    'nullable' => true,
                    'default' => '0.0000',
                    'comment' => 'Compare Price',
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.8.0', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('cminds_multiuseraccounts_subaccount'),
                'login',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Login for authorization',
                ]
            );
        }

        $setup->endSetup();
    }
}
