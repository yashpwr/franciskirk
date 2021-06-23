<?php

namespace Cminds\MultiUserAccounts\Model\Import\Source;

use Cminds\MultiUserAccounts\Model\Import\SourceInterface;
use Cminds\MultiUserAccounts\Model\Import\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv as CsvProcessor;

/**
 * Cminds MultiUserAccounts csv source import model.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Csv implements SourceInterface
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var CsvProcessor
     */
    private $csvProcessor;

    /**
     * Object constructor.
     *
     * @param Validator    $validator
     * @param CsvProcessor $csvProcessor
     */
    public function __construct(
        Validator $validator,
        CsvProcessor $csvProcessor
    ) {
        $this->validator = $validator;
        $this->csvProcessor = $csvProcessor;
    }

    /**
     * {@inheritdoc}
     *
     * return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAccountsData()
    {
        try {
            $data = $this->csvProcessor
                ->setDelimiter(',')
                ->setEnclosure('"')
                ->getData($this->filePath);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        if (count($data) <= 1) {
            throw new LocalizedException(
                __('Import file is empty or contains only headers.')
            );
        }

        $headers = array_shift($data);
        $headersCount = count($headers);
        $this->validator->validateKeys($headers);

        $dataCount = count($data);
        $mappedData = [];
        for ($i = 0; $i < $dataCount; $i++) {
            $mappedRow = [];
            for ($j = 0; $j < $headersCount; $j++) {
                $mappedRow[$headers[$j]] = trim($data[$i][$j]);
            }
            $mappedData[] = $mappedRow;
        }
        $data = $mappedData;
        unset($mappedData, $mappedRow);

        $accounts = [];
        foreach ($data as $row) {
            $row['import_mode'] = true;
            $accounts[$row['email']] = $row;
        }

        $accounts = array_values($accounts);

        $preparedData['accounts'] = $accounts;

        $subaccounts = [];

        foreach ($accounts as $account) {
            if (isset($account['parent_email']) && $account['parent_email']) {
                $subaccounts[$account['email']] = $account;
            }
        }

        $preparedData['subaccounts'] = $subaccounts;

        return $preparedData;
    }

    /**
     * File path setter.
     *
     * @param string $filePath
     *
     * @return Csv
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;

        return $this;
    }
}
