<?php

namespace Cminds\MultiUserAccounts\Ui\Plugin\Component\Form;

use Cminds\MultiUserAccounts\Model\Config;
use \Magento\Ui\Component\Form\Field as ParentField;

/**
 * Class Field
 * @package Cminds\MultiUserAccounts\Ui\Plugin\Component\Form
 */
class Field
{
    private $configModel;
    public function __construct(
        Config $configModel
    ) {
        $this->configModel = $configModel;
    }

    /**
     * Change parent_account_id form field data
     * if config is enabled
     *
     * @param ParentField $subject
     */
    public function afterPrepare(ParentField $subject)
    {
        if ($this->configModel->showAsText()) {
            if (!empty($subject->getName()) && $subject->getName() == 'parent_account_id') {
                $data = $subject->getData();
                if (!empty($data['config'])) {
                    $config = $data['config'];
                    $newConfig = [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'formElement' => 'input',
                        'template' => 'ui/form/field',
                        'dataType' => 'text',
                        'options' => '',
                    ];
                    $newConfig = array_merge($config, $newConfig);
                    $newData = [
                        'config' => $newConfig,
                        'options' => '',
                        'js_config' => [
                            'extends' => 'input'
                        ]
                    ];
                    $subject->setData(array_replace($data, $newData));
                }
            }
        }
    }
}