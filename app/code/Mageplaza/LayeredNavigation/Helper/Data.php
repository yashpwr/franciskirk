<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_LayeredNavigation
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\LayeredNavigation\Helper;

/**
 * Class Data
 * @package Mageplaza\LayeredNavigation\Helper
 */
class Data extends \Mageplaza\AjaxLayer\Helper\Data
{
    const FILTER_TYPE_SLIDER = 'slider';
    const FILTER_TYPE_LIST   = 'list';

    /** @var \Mageplaza\LayeredNavigation\Model\Layer\Filter */
    protected $filterModel;

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isEnabled($storeId = null)
    {
        return $this->getConfigGeneral('enable', $storeId) && $this->isModuleOutputEnabled();
    }

    /**
     * @param $filters
     * @return mixed
     */
    public function getLayerConfiguration($filters)
    {
        $filterParams = $this->_getRequest()->getParams();
        foreach ($filterParams as $key => $param) {
            $filterParams[$key] = htmlspecialchars($param);
        }

        $config = new \Magento\Framework\DataObject([
            'active'             => array_keys($filterParams),
            'params'             => $filterParams,
            'isCustomerLoggedIn' => $this->objectManager->create('Magento\Customer\Model\Session')->isLoggedIn(),
            'isAjax'             => $this->ajaxEnabled()
        ]);

        $this->getFilterModel()->getLayerConfiguration($filters, $config);

        return self::jsonEncode($config->getData());
    }

    /**
     * @return \Mageplaza\LayeredNavigation\Model\Layer\Filter
     */
    public function getFilterModel()
    {
        if (!$this->filterModel) {
            $this->filterModel = $this->objectManager->create('Mageplaza\LayeredNavigation\Model\Layer\Filter');
        }

        return $this->filterModel;
    }

    /**
     * @return \Magento\Framework\ObjectManagerInterface
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }
    
    public function getCustomColors($filter)
    {
        $colorStr = $this->getConfigGeneral('custom_colors');
        #echo '<pre>' . var_dump($colorStr). '</pre>';
        $colorStr = trim($colorStr,". \t\n\r\0\x0B");
        $parts = array_map('trim', explode('.', $colorStr));
        $color = [];
        $colors = [];
        foreach($parts as $part) {
            $tcol = array_map('trim', explode(':', $part));
            if (count($tcol) != 2) {
                return "Error in config of <br>Mageplaza > Layered Navigation > Custom Colors <br>in string: $part";
            }
            $acol = array_map('trim', explode(',', $tcol[1]));
            $color[$tcol[0]] = $acol;
            foreach ($acol as $col) {
                $colors[$col] = $tcol[0];
            }
        }
        /** @var $filterModel \Mageplaza\LayeredNavigationPro\Model\Layer\Filter */
        $filterModel = $this->getFilterModel();
        $items = [];
        /** @var $item \Magento\Catalog\Model\Layer\Filter\Item */
        foreach ($filter->getItems() as $item) { if ($item->getCount() > 0) {
            $items[$item->getLabel()] = $item;
        }}
        $icolor = [];
        foreach ($items as $col => $item) {
            if (isset($colors[$col])) {
                $checked = false;
                $resetVal = $filterModel->getFilterValue($filter);
                $value = [];
                foreach ($color[$colors[$col]] as $clabel) {
                    if (!array_key_exists($clabel, $items)) {
                        continue;
                    }
                    if ($filterModel->isSelected($items[$clabel])) {
                        $checked = true;
                    }
                    if (in_array($items[$clabel]->getValue(), $resetVal)) {
                        $resetVal = array_diff($resetVal, [$items[$clabel]->getValue()]);
                    }
                    $value[] = $items[$clabel]->getValue();
                }
                if ($checked) {
                    $params['_query']       = [$filter->getRequestVar() => count($resetVal) ? implode(',', $resetVal) : $filter->getResetValue()];
                    $params['_current']     = true;
                    $params['_use_rewrite'] = true;
                    $params['_escape']      = true;
                    $url = $this->_getUrl('*/*/*', $params);
                } else {
                    $value = array_merge($value, $filterModel->getFilterValue($filter));
                    sort($value);
                    /** @var $pager \Magento\Theme\Block\Html\Pager */
                    $pager = $this->objectManager->get('Magento\Theme\Block\Html\Pager');
                    $query = [
                        $filter->getRequestVar() => implode(',', $value),
                        // exclude current page from urls
                        $pager->getPageVarName() => null,
                    ];
                    $url = $this->_getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
                }
                $icolor[$colors[$col]] = ['checked' => $checked, 'url' => $url];
            }
        }
        ksort($icolor);
        #echo '<pre>', var_dump($icolor), '</pre>';
        return($icolor);
    }
}
