<?php

namespace Cminds\MultiUserAccounts\Model\Plugin\Webapi\ServiceOutputProcessor;

use Magento\Framework\Webapi\ServiceOutputProcessor;

/**
 * Cminds MultiUserAccounts WebApi output processor plugin.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Plugin
{
    /**
     * Wrapper for process method.
     *
     * @param ServiceOutputProcessor $subject
     * @param callable $proceed
     * @param mixed $data
     * @param string $serviceClassName
     * @param string $serviceMethodName
     * @return array|int|string|bool|float Scalar or array of scalars
     */
    public function aroundProcess(
        ServiceOutputProcessor $subject,
        callable $proceed,
        $data,
        $serviceClassName,
        $serviceMethodName
    ) {
        $result = $proceed($data, $serviceClassName, $serviceMethodName);

        if (is_array($result) === false) {
            return $result;
        }

        switch ($serviceClassName) {
            case 'Cminds\MultiUserAccounts\Api\ParentaccountInterface':
                $this->fixDataValues($result);
                $this->filterDataFields($result, ['password']);
                break;
            case 'Cminds\MultiUserAccounts\Api\SubaccountInterface':
                $this->fixDataValues($result);
                break;
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function fixDataValues(array &$data)
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->fixDataValues($value);
            } elseif ($value === null) {
                $value = '';
            } elseif (is_numeric($value)) {
                $value .= '';
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @param array $fields
     *
     * @return array
     */
    private function filterDataFields(array &$data, array $fields)
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $this->filterDataFields($value, $fields);
            } elseif (in_array($key, $fields, true)) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
