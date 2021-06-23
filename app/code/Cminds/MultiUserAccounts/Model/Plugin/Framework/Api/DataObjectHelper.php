<?php

namespace Cminds\MultiUserAccounts\Model\Plugin\Framework\Api;

use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\CustomAttributesDataInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\ObjectFactory;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Reflection\MethodsMap;
use Magento\Framework\Reflection\TypeProcessor;
use Magento\Framework\Api\SimpleDataObjectConverter;

class DataObjectHelper extends \Magento\Framework\Api\DataObjectHelper
{
    const SUBBACCOUNT_INTERFACE_NAME = '\Cminds\MultiUserAccounts\Api\Data\SubaccountInterface';
    const SUBBACCOUNT_TRANSPORT_INTERFACE_NAME = '\Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface';

    /**
     * @var ObjectFactory
     */
    protected $objectFactory;

    /**
     * @var DataObjectProcessor
     */
    protected $objectProcessor;

    /**
     * @var TypeProcessor
     */
    protected $typeProcessor;

    /**
     * @var ExtensionAttributesFactory
     */
    protected $extensionFactory;

    /**
     * @var JoinProcessorInterface
     */
    protected $joinProcessor;

    /**
     * @var MethodsMap
     */
    protected $methodsMapProcessor;

    /**
     * @var SimpleDataObjectConverter
     */
    private $simpleDataObjectConverter;

    /**
     * @param ObjectFactory              $objectFactory
     * @param DataObjectProcessor        $objectProcessor
     * @param TypeProcessor              $typeProcessor
     * @param ExtensionAttributesFactory $extensionFactory
     * @param JoinProcessorInterface     $joinProcessor
     * @param MethodsMap                 $methodsMapProcessor
     * @param SimpleDataObjectConverter  $simpleDataObjectConverter
     */
    public function __construct(
        ObjectFactory $objectFactory,
        DataObjectProcessor $objectProcessor,
        TypeProcessor $typeProcessor,
        ExtensionAttributesFactory $extensionFactory,
        JoinProcessorInterface $joinProcessor,
        MethodsMap $methodsMapProcessor,
        SimpleDataObjectConverter $simpleDataObjectConverter
    ) {
        $this->objectFactory = $objectFactory;
        $this->objectProcessor = $objectProcessor;
        $this->typeProcessor = $typeProcessor;
        $this->extensionFactory = $extensionFactory;
        $this->joinProcessor = $joinProcessor;
        $this->methodsMapProcessor = $methodsMapProcessor;
        $this->simpleDataObjectConverter = $simpleDataObjectConverter;
    }

    /**
     * Around populate with array plugin.
     *
     * @param \Magento\Framework\Api\DataObjectHelper $subject
     * @param callable $proceed
     * @param $dataObject
     * @param array $data
     * @param $interfaceName
     *
     * @return DataObjectHelper
     */
    public function aroundPopulateWithArray(
        \Magento\Framework\Api\DataObjectHelper $subject,
        callable $proceed,
        $dataObject,
        array $data,
        $interfaceName
    ) {
        $interfaceNameToCheck = (string)$interfaceName;
        if ($interfaceNameToCheck === self::SUBBACCOUNT_INTERFACE_NAME
            || $interfaceNameToCheck === self::SUBBACCOUNT_TRANSPORT_INTERFACE_NAME
        ) {
            return $this->setDataValues($dataObject, $data, $interfaceName);
        }

        return $proceed($dataObject, $data, $interfaceName);
    }

    /**
     * Populate with array.
     *
     * @param $dataObject
     * @param array $data
     * @param $interfaceName
     *
     * @return DataObjectHelper
     */
    public function populateWithArray($dataObject, array $data, $interfaceName)
    {
        if ($dataObject instanceof ExtensibleDataInterface) {
            $data = $this->joinProcessor->extractExtensionAttributes(get_class($dataObject), $data);
        }
        $this->setDataValues($dataObject, $data, $interfaceName);

        return $this;
    }

    /**
     * Set data values.
     *
     * @param $dataObject
     * @param array $data
     * @param $interfaceName
     *
     * @return DataObjectHelper
     */
    private function setDataValues($dataObject, array $data, $interfaceName)
    {
        $dataObjectMethods = get_class_methods(get_class($dataObject));
        foreach ($data as $key => $value) {
            /* First, verify is there any setter for the key on the Service Data Object */
            $camelCaseKey = $this->simpleDataObjectConverter->snakeCaseToUpperCamelCase($key);
            $possibleMethods = [
                'set' . $camelCaseKey,
                'setIs' . $camelCaseKey,
            ];
            if ($key === CustomAttributesDataInterface::CUSTOM_ATTRIBUTES
                && ($dataObject instanceof ExtensibleDataInterface)
                && is_array($data[$key])
                && !empty($data[$key])
            ) {
                foreach ($data[$key] as $customAttribute) {
                    $dataObject->setCustomAttribute(
                        $customAttribute[AttributeInterface::ATTRIBUTE_CODE],
                        $customAttribute[AttributeInterface::VALUE]
                    );
                }
            } elseif ($methodNames = array_intersect($possibleMethods, $dataObjectMethods)) {
                $methodName = array_values($methodNames)[0];
                if (!is_array($value)) {
                    if ($methodName === 'setExtensionAttributes' && $value === null) {
                        // Cannot pass a null value to a method with a typed parameter
                    } else {
                        $dataObject->$methodName($value);
                    }
                } else {
                    $getterMethodName = 'get' . $camelCaseKey;
                    $this->setComplexValue($dataObject, $getterMethodName, $methodName, $value, $interfaceName);
                }
            } elseif ($dataObject instanceof CustomAttributesDataInterface) {
                $dataObject->setCustomAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Set complex value.
     *
     * @param mixed $dataObject
     * @param string $getterMethodName
     * @param string $methodName
     * @param array $value
     * @param string $interfaceName
     *
     * @return DataObjectHelper
     */
    protected function setComplexValue(
        $dataObject,
        $getterMethodName,
        $methodName,
        array $value,
        $interfaceName
    ) {
        if ($interfaceName == null) {
            $interfaceName = get_class($dataObject);
        }
        $returnType = $this->methodsMapProcessor->getMethodReturnType($interfaceName, $getterMethodName);
        if ($this->typeProcessor->isTypeSimple($returnType)) {
            $dataObject->$methodName($value);

            return $this;
        }

        if ($this->typeProcessor->isArrayType($returnType)) {
            $type = $this->typeProcessor->getArrayItemType($returnType);
            $objects = [];
            foreach ($value as $arrayElementData) {
                $object = $this->objectFactory->create($type, []);
                $this->populateWithArray($object, $arrayElementData, $type);
                $objects[] = $object;
            }
            $dataObject->$methodName($objects);

            return $this;
        }

        if (is_subclass_of($returnType, \Magento\Framework\Api\ExtensibleDataInterface::class)) {
            $object = $this->objectFactory->create($returnType, []);
            $this->populateWithArray($object, $value, $returnType);
        } elseif (is_subclass_of($returnType, \Magento\Framework\Api\ExtensionAttributesInterface::class)) {
            foreach ($value as $extensionAttributeKey => $extensionAttributeValue) {
                $extensionAttributeGetterMethodName
                    = 'get' . \Magento\Framework\Api\SimpleDataObjectConverter::snakeCaseToUpperCamelCase(
                        $extensionAttributeKey
                    );
                $methodReturnType = $this->methodsMapProcessor->getMethodReturnType(
                    $returnType,
                    $extensionAttributeGetterMethodName
                );
                $extensionAttributeType = $this->typeProcessor->isArrayType($methodReturnType)
                    ? $this->typeProcessor->getArrayItemType($methodReturnType)
                    : $methodReturnType;

                if ($this->typeProcessor->isTypeSimple($extensionAttributeType)) {
                    $value[$extensionAttributeKey] = $extensionAttributeValue;
                } else {
                    if ($this->typeProcessor->isArrayType($methodReturnType)) {
                        foreach ($extensionAttributeValue as $key => $extensionAttributeArrayValue) {
                            $extensionAttribute = $this->objectFactory->create($extensionAttributeType, []);
                            $this->populateWithArray(
                                $extensionAttribute,
                                $extensionAttributeArrayValue,
                                $extensionAttributeType
                            );
                            $value[$extensionAttributeKey][$key] = $extensionAttribute;
                        }
                    } else {
                        $value[$extensionAttributeKey] = $this->objectFactory->create(
                            $extensionAttributeType,
                            ['data' => $extensionAttributeValue]
                        );
                    }
                }
            }
            $object = $this->extensionFactory->create(get_class($dataObject), ['data' => $value]);
        } else {
            $object = $this->objectFactory->create($returnType, $value);
        }
        $dataObject->$methodName($object);

        return $this;
    }
}
