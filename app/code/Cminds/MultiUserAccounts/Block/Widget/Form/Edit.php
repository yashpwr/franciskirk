<?php

namespace Cminds\MultiUserAccounts\Block\Widget\Form;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Block\Account\Dashboard;
use Magento\Customer\Model\Metadata\Form as form;
use Magento\Customer\Block\Form\Register;

/**
 * Cminds MultiUserAccounts customer account confirm controller plugin.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Jigs
 */
class Edit extends Dashboard
{
    /**
     * Retrieve form data.
     *
     * @return array
     */
    protected function getFormData()
    {
        $data = $this->getData('form_data');
        if ($data === null) {
            $formData = $this->customerSession->getCustomerFormData(true);
            $data = [];
            if ($formData) {
                $data['data'] = $formData;
                $data['customer_data'] = 1;
            }
            $this->setData('form_data', $data);
        }

        return $data;
    }

    /**
     * Restore entity data from session. Entity and form code must be defined for the form.
     *
     * @param Form $form
     * @param null $scope
     *
     * @return Register customer data
     */
    public function restoreSessionData(form $form, $scope = null)
    {
        $formData = $this->getFormData();
        if (isset($formData['customer_data']) && $formData['customer_data']) {
            $request = $form->prepareRequest($formData['data']);
            $data = $form->extractData($request, $scope, false);
            $form->restoreData($data);
        }

        return $this;
    }

    /**
     * Return whether the form should be opened in an expanded mode showing the change password fields.
     *
     * @return bool
     */
    public function getChangePassword()
    {
        return $this->customerSession->getChangePassword();
    }

    /**
     * Get minimum password length.
     *
     * @return string
     */
    public function getMinimumPasswordLength()
    {
        return $this->_scopeConfig->getValue(
            AccountManagement::XML_PATH_MINIMUM_PASSWORD_LENGTH
        );
    }

    /**
     * Get minimum password length.
     *
     * @return string
     * @since 100.1.0
     */
    public function getRequiredCharacterClassesNumber()
    {
        return $this->_scopeConfig->getValue(
            AccountManagement::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER
        );
    }

    /**
     * Get account email.
     *
     * @return string
     */
    public function getAccountEmail()
    {
        $subaccountData = $this->customerSession->getSubaccountData();
        if ($subaccountData) {

            return $subaccountData->getEmail();
        }
    }
}
