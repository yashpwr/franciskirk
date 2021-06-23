<?php

namespace Cminds\MultiUserAccounts\Model;

use Cminds\MultiUserAccounts\Model\Import\Source\CsvFactory as CsvSourceFactory;
use Cminds\MultiUserAccounts\Model\Import\Source\ApiFactory as ApiSourceFactory;
use Cminds\MultiUserAccounts\Model\Import\SourceInterface;
use Cminds\MultiUserAccounts\Helper\Import as ImportHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Registry;
use Symfony\Component\Console\Output\OutputInterface;
use Cminds\MultiUserAccounts\Model\Import\Process as ImportProcessor;

/**
 * Cminds MultiUserAccounts import model.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 */
class Import
{
    /**
     * Core registry keys.
     */
    const SKIP_CUSTOMER_WELCOME_EMAIL
        = 'cminds_multiuseraccounts_skip_customer_welcome_email';

    /**
     * Source types.
     */
    const SOURCE_CSV = 'csv';
    /**
     *
     */
    const SOURCE_API = 'api';

    /**
     * Environment types.
     */
    const ENVIRONMENT_CLI = 'cli';
    const ENVIRONMENT_API = 'api';

    /**
     * @var ImportHelper
     */
    private $importHelper;

    /**
     * @var CsvSourceFactory
     */
    private $csvSourceFactory;

    /**
     * @var ApiSourceFactory
     */
    private $apiSourceFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var SourceInterface
     */
    private $sourceProcessor;

    /**
     * @var string
     */
    private $environment = self::ENVIRONMENT_API;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var ImportProcessor
     */
    private $importProcessor;

    /**
     * Flag for Update via API
     */
    private $updateFlag = false;

    /**
     * Customer ID passed in the API call
     */
    private $customerId;

    /**
     * @var
     */
    private $parentId;

    /**
     * @var bool
     */
    public $linkFlag = false;

    /**
     * Object constructor.
     *
     * @param ImportHelper $importHelper
     * @param CsvSourceFactory $csvSourceFactory
     * @param ApiSourceFactory $apiSourceFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param IndexerRegistry $indexerRegistry
     * @param Registry $coreRegistry
     * @param ImportProcessor $importProcessor
     */

    public function __construct(
        ImportHelper $importHelper,
        CsvSourceFactory $csvSourceFactory,
        ApiSourceFactory $apiSourceFactory,
        CustomerRepositoryInterface $customerRepository,
        IndexerRegistry $indexerRegistry,
        Registry $coreRegistry,
        ImportProcessor $importProcessor
    ) {
        $this->importHelper = $importHelper;
        $this->csvSourceFactory = $csvSourceFactory;
        $this->apiSourceFactory = $apiSourceFactory;
        $this->customerRepository = $customerRepository;
        $this->indexerRegistry = $indexerRegistry;
        $this->coreRegistry = $coreRegistry;
        $this->importProcessor = $importProcessor;

        $this->environment = self::ENVIRONMENT_API;
    }

    /**
     * @param $bool
     * @return $this
     */
    public function setLinkFlag($bool)
    {
        $this->linkFlag = $bool;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWasLinked()
    {
        return $this->importProcessor->getWasLinked();
    }

    /**
     * @return bool
     */
    public function getWasPromoted()
    {
        return $this->importProcessor->getWasPromoted();
    }

    /**
     * Initialize and retrieve source processor object.
     *
     * @param string $sourceType
     *
     * @return SourceInterface
     * @throws LocalizedException
     */
    public function initSourceProcessor($sourceType)
    {
        switch ($sourceType) {
            case self::SOURCE_CSV:
                $this->sourceProcessor = $this->csvSourceFactory->create();
                break;
            case self::SOURCE_API:
                $this->sourceProcessor = $this->apiSourceFactory->create();
                break;
            default:
                throw new LocalizedException(__('Unhandled source type.'));
        }

        return $this->sourceProcessor;
    }

    /**
     * @param string $environment
     *
     * @return Import
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * @param OutputInterface $output
     *
     * @return Import
     */
    public function setOutputStream(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Return bool value depends if import is running in cli.
     *
     * @return bool
     */
    private function isCliEnvironment()
    {
        return $this->environment === self::ENVIRONMENT_CLI;
    }

    /**
     * Retrieve source processor.
     *
     * @return SourceInterface
     * @throws LocalizedException
     */
    private function getSourceProcessor()
    {
        if (!$this->sourceProcessor instanceof SourceInterface) {
            throw new LocalizedException(
                __('Source processor has been not initialized.')
            );
        }

        return $this->sourceProcessor;
    }

    public function getAccountsData() {
        return $this->getSourceProcessor()->getAccountsData();
    }

    /**
     * @param string $log
     *
     * @return Import
     */
    private function debugLog($log)
    {
        if ($this->isCliEnvironment()) {
            $this->output->writeln($log);
        }

        return $this;
    }

    /**
     * @param $id
     * @return $this
     */
    public function update($id)
    {
        if ($id) {
            $customer = $this->customerRepository->getById($id);
            $updateData = $this->getSourceProcessor()->getAccountsData();
            foreach ($updateData as $array) {
                foreach ($array as $key => $value) {
                    $customer->setData($key, $value);
                }
            }
            $this->customerRepository->save($customer);
        }
        return $this;
    }

    /**
     * Process source data.
     *
     * @return Import
     * @throws \RuntimeException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \DomainException
     * @throws \Exception
     */
    public function execute()
    {
        $this->coreRegistry->register(self::SKIP_CUSTOMER_WELCOME_EMAIL, true);

        $accountsData = $this->getAccountsData();
        $parents = $this->importHelper->getParentsData($accountsData);
        $subaccounts = $this->importHelper->getSubaccountsData($accountsData);

        $this->importProcessor->setEnvironment($this->environment)
            ->setOutputStream($this->output)
            ->setUpdateFlag($this->updateFlag)
            ->setCustomerId($this->customerId)
            ->setLinkFlag($this->linkFlag)
            ->setParentId($this->parentId)
            ;

        if(is_array($parents)){
            $this->importProcessor->processImport($parents, 'parent');
        }
        if(is_array($subaccounts)){
            $this->importProcessor->processImport($subaccounts, 'subaccount');
        }

        $this->debugLog('Reindexing customer grid...');

        $indexer = $this->indexerRegistry->get(Customer::CUSTOMER_GRID_INDEXER_ID);
        $indexer->reindexAll();

        return $this;
    }

    /**
     * Set UpdateFlag to true.
     *
     * @param OutputInterface $output
     * 
     * @return Import
     */
    public function setUpdateFlag($bool = true)
    {
        $this->updateFlag = $bool;
        return $this;
    }

    /**
     * Set customer Id for customer that will be edited.
     *
     * @return Import
     */
    public function setCustomerId($id)
    {
        $this->customerId = $id;
        return $this;
    }


    /**
     * Set parent Id for customer that will be edited.
     *
     * @return Import
     */
    public function setParentId($id)
    {
        $this->parentId = $id;
        return $this;
    }
}
