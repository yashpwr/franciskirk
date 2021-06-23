<?php
/**
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Core Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Ui\Component\Listing;

class CustomerDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    protected function _initSelect()
    {
        parent::_initSelect();

        // add main_table alias to the fields of the table to prevent ambiguous exception in where clause
        $tableDescription = $this->getConnection()->describeTable($this->getMainTable());
        foreach ($tableDescription as $columnInfo) {
            $this->addFilterToMap($columnInfo['COLUMN_NAME'], 'main_table.' . $columnInfo['COLUMN_NAME']);
        }

        $this->getSelect()->joinLeft(
            ['ce' => $this->getTable('customer_entity')],
            'main_table.entity_id = ce.entity_id',
            ['store_id']
        );

        return $this;
    }
}