<?php

namespace Cminds\MultiUserAccounts\Block\Adminhtml\Customer\Edit\Tab\Subaccount;

use Cminds\MultiUserAccounts\Block\Adminhtml\Customer\
Edit\Tab\Subaccount\Grid\Column\Renderer\Name;
use Cminds\MultiUserAccounts\Block\Adminhtml\Customer\
Edit\Tab\Subaccount\Grid\Column\Renderer\Permission;
use Cminds\MultiUserAccounts\Block\Adminhtml\Customer\
Edit\Tab\Subaccount\Grid\Column\Renderer\Status;
use Cminds\MultiUserAccounts\Model\Config;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount\CollectionFactory;
use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;

/**
 * Cminds MultiUserAccounts customer edit tab grid block to manage subaccounts.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class ManageView extends Extended
{
    /**
     * Core registry object.
     *
     * @var Registry|null
     */
    private $coreRegistry;

    /**
     * Collection factory object.
     *
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Config
     */
    private $moduleConfig;

    /**
     * Object initialization.
     *
     * @param Context $context Context object.
     * @param Data $backendHelper Backend helper object.
     * @param CollectionFactory $collectionFactory Collection factory object.
     * @param Registry $coreRegistry Core registry objects.
     * @param Config $moduleConfig
     * @param array $data Params.
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        Registry $coreRegistry,
        Config $moduleConfig,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->collectionFactory = $collectionFactory;

        parent::__construct(
            $context,
            $backendHelper,
            $data
        );
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Initialize the orders grid.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId('subaccount_manage_view_grid');
        $this->setDefaultSort('id', 'asc');
        $this->setSortable(false);
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);

        $this->setTemplate('manage/grid.phtml');
    }

    /**
     * {@inheritdoc}
     */
    protected function _preparePage()
    {
        $this->getCollection()->setPageSize($this->moduleConfig->getParentSubaccountsMaxToDisplay())->setCurPage(1);
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        $customerId = $this->coreRegistry
            ->registry(RegistryConstants::CURRENT_CUSTOMER_ID);

        $collection = $this->collectionFactory->create()
            ->addFieldToSelect(
                [
                    'entity_id',
                    'is_active',
                    'permission',
                    'login'
                ]
            )
            ->filterByParentCustomerId($customerId)
            ->join(
                'customer_entity',
                'main_table.customer_id = customer_entity.entity_id',
                ['email', 'firstname', 'lastname', 'prefix', 'suffix', 'middlename']
            );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        if (!$this->moduleConfig->isLoginAuthEnabled()) {
            $this->addColumn(
                'email',
                [
                    'header' => __('Email'),
                    'index' => 'email',
                ]
            );
        } else {
            $this->addColumn(
                'login',
                [
                    'header' => __('Login'),
                    'index' => 'login',
                ]
            );
        }
        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
                'renderer' => Name::class,
            ]
        );
        $this->addColumn(
            'permission',
            [
                'header' => __('Permission'),
                'index' => 'permission',
                'renderer' => Permission::class,
            ]
        );
        $this->addColumn(
            'is_active',
            [
                'header' => __('Status'),
                'index' => 'is_active',
                'renderer' => Status::class,
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Get headers visibility.
     *
     * @return bool
     */
    public function getHeadersVisibility()
    {
        return $this->getCollection()->getSize() >= 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            'subaccounts/manage/edit',
            ['id' => $row->getId()]
        );
    }

    /**
     * Return add subaccount url.
     *
     * @return string
     */
    public function getAddSubaccountUrl()
    {
        return $this->getUrl(
            'subaccounts/manage/add',
            [
                'parent_customer_id' => $this->coreRegistry->registry(
                    RegistryConstants::CURRENT_CUSTOMER_ID
                ),
            ]
        );
    }
}
