<?php
/**
 * @category Cminds
 * @package  MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Ui\Customer\Component\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Class Store
 */
class Store extends Column
{
    /**
     * Store manager
     *
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var string
     */
    protected $storeKey;

    /**
     * Store constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     * @param string $storeKey
     * @param StoreManager $storeManager
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = [],
        $storeKey = 'store_id',
        StoreManager $storeManager
    ) {
        $this->storeKey = $storeKey;
        $this->storeManager = $storeManager;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->prepareItem($item);
            }
        }

        return $dataSource;
    }

    /**
     * Get data
     *
     * @param array $item
     * @return string
     */
    protected function prepareItem(array $item)
    {
        if (!empty($item[$this->storeKey])) {
            $origStores = $item[$this->storeKey];
        }

        if (empty($origStores)) {
            return '';
        }

        if (!is_array($origStores)) {
            $origStores = [$origStores];
        }

        return (string)$this->storeManager->getStore($origStores[0])->getName();
    }
}
