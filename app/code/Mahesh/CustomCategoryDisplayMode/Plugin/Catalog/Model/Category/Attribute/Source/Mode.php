<?php
namespace Mahesh\CustomCategoryDisplayMode\Plugin\Catalog\Model\Category\Attribute\Source;

class Mode {
    /**
     * @param \Magento\Catalog\Model\Category\Attribute\Source\Mode $subject
     * @param $result
     * @return array
     */
    public function afterGetAllOptions(
        \Magento\Catalog\Model\Category\Attribute\Source\Mode $subject,
        $result
    ) {
        $result[] = ['value' => 'subcategories', 'label' => 'Subcategories Only'];
        return $result;
    }
}