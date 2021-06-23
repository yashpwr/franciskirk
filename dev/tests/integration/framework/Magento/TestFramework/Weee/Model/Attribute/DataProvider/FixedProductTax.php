<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TestFramework\Weee\Model\Attribute\DataProvider;

use Magento\TestFramework\Eav\Model\Attribute\DataProvider\AbstractBaseAttributeData;

/**
 * Product attribute data for attribute with input type fixed product tax.
 */
class FixedProductTax extends AbstractBaseAttributeData
{
    /**
     * @inheritdoc
     */
    public function __construct()
    {
        parent::__construct();
        $this->defaultAttributePostData['used_for_sort_by'] = '0';
    }

    /**
     * @inheritdoc
     */
    public function getAttributeData(): array
    {
        $result = parent::getAttributeData();
        unset($result["{$this->getFrontendInput()}_with_default_value"]);
        unset($result["{$this->getFrontendInput()}_without_default_value"]);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeDataWithCheckArray(): array
    {
        $result = parent::getAttributeDataWithCheckArray();
        unset($result["{$this->getFrontendInput()}_with_default_value"]);
        unset($result["{$this->getFrontendInput()}_without_default_value"]);

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function getFrontendInput(): string
    {
        return 'weee';
    }
}
