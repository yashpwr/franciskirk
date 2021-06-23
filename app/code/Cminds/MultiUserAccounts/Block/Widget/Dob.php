<?php

namespace Cminds\MultiUserAccounts\Block\Widget;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Api\ArrayObjectSearch;
use Magento\Customer\Block\Widget\AbstractWidget;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Helper\Address;
use Magento\Framework\View\Element\Html\Date;
use Magento\Framework\Data\Form\FilterFactory;
use Magento\Store\Model\ScopeInterface as ScopeInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;

/**
 * Cminds MultiUserAccounts customer account confirm controller plugin.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Jigs
 */
class Dob extends AbstractWidget
{
    /**
     * Constants for borders of date-type customer attributes.
     */
    const MIN_DATE_RANGE_KEY = 'date_range_min';

    const MAX_DATE_RANGE_KEY = 'date_range_max';

    /**
     * View helper object.
     *
     * @var ViewHelper
     */
    private $viewHelper;
    
    /**
     * Session object.
     *
     * @var CustomerSession
     */
    
    private $customerSession;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * Date inputs
     *
     * @var array
     */
    private $dateInputs = [];

    /**
     * @var Date
     */
    private $dateElement;

    /**
     * @var FilterFactory
     */
    private $filterFactory;

    /**
     * @param Context $context
     * @param Address $addressHelper
     * @param CustomerMetadataInterface $customerMetadata
     * @param Date $dateElement
     * @param FilterFactory $filterFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Address $addressHelper,
        CustomerMetadataInterface $customerMetadata,
        CustomerSession $customerSession,
        ViewHelper $viewHelper,
        ModuleConfig $moduleConfig,
        Date $dateElement,
        FilterFactory $filterFactory,
        array $data = []
    ) {
        $this->dateElement     = $dateElement;
        $this->filterFactory   = $filterFactory;
        $this->moduleConfig = $moduleConfig;
        $this->customerSession = $customerSession;
        $this->viewHelper      = $viewHelper;

        parent::__construct($context, $addressHelper, $customerMetadata, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate(
            'Cminds_MultiUserAccounts::account/dashboard/widget/dob.phtml'
        );
    }

    /**
     * Returns the dob enabled status.
     *
     * @return bool
     */
    public function isEnabled()
    {
        $attributeMetadata = $this->_getAttribute('dob');
        return $attributeMetadata ? (bool)$attributeMetadata->isVisible() : false;
    }

    /**
     * Returns the required status.
     *
     * @return bool
     */
    public function isRequired()
    {
        $attributeMetadata = $this->_getAttribute('dob');
        return $attributeMetadata ? (bool)$attributeMetadata->isRequired() : false;
    }

    /**
     * @param string $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->setTime($date ? strtotime($date) : false);
        $this->setValue($this->applyOutputFilter($date));

        return $this;
    }

    /**
     * Return Data Form Filter or false.
     *
     * @return FilterInterface
     */
    protected function getFormFilter()
    {
        $attributeMetadata = $this->_getAttribute('dob');
        $filterCode = $attributeMetadata->getInputFilter();
        if ($filterCode) {
            $data = [];
            if ($filterCode == 'date') {
                $data['format'] = $this->getDateFormat();
            }
            $filter = $this->filterFactory->create($filterCode, $data);
            return $filter;
        }

        return false;
    }

    /**
     * Apply output filter to value.
     *
     * @param string $value
     *
     * @return string
     */
    protected function applyOutputFilter($value)
    {
        $filter = $this->getFormFilter();
        if ($filter) {
            $value = $filter->outputFilter($value);
        }
        
        return $value;
    }

    /**
     * Return the day.
     *
     * @return string|bool
     */
    public function getDay()
    {
        return $this->getTime() ? date('d', $this->getTime()) : '';
    }

    /**
     * Return the month.
     *
     * @return string|bool
     */
    public function getMonth()
    {
        return $this->getTime() ? date('m', $this->getTime()) : '';
    }

    /**
     * Return the year.
     *
     * @return string|bool
     */
    public function getYear()
    {
        return $this->getTime() ? date('Y', $this->getTime()) : '';
    }

    /**
     * Return label.
     *
     * @return String
     */
    public function getLabel()
    {
        return __('Date of Birth');
    }

    /**
     * Create correct date field.
     *
     * @return string
     */
    public function getFieldHtml()
    {
        $this->dateElement->setData([
            'extra_params' => $this->getHtmlExtraParams(),
            'name' => $this->getHtmlId(),
            'id' => $this->getHtmlId(),
            'class' => $this->getHtmlClass(),
            'value' => $this->getValue(),
            'date_format' => $this->getDateFormat(),
            'image' => $this->getViewFileUrl('Magento_Theme::calendar.png'),
            'years_range' => '-120y:c+nn',
            'max_date' => '-1d',
            'change_month' => 'true',
            'change_year' => 'true',
            'show_on' => 'both',
            'first_day' => $this->getFirstDay()
        ]);

        return $this->dateElement->getHtml();
    }

    /**
     * Return id.
     *
     * @return string
     */
    public function getHtmlId()
    {
        return 'dob';
    }

    /**
     * Return data-validate rules.
     *
     * @return string
     */
    public function getHtmlExtraParams()
    {
        $validators = [];

        if ($this->isRequired()) {
            $validators['required'] = true;
        }

        $validators['validate-date'] = [
            'dateFormat' => $this->getDateFormat()
        ];

        return 'data-validate="' . $this->_escaper->escapeHtml(json_encode($validators)) . '"';
    }

    /**
     * Returns format which will be applied for DOB in javascript.
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->_localeDate->getDateFormatWithLongYear();
    }

    /**
     * Add date input html.
     *
     * @param string $code
     * @param string $html
     * @return void
     */
    public function setDateInput($code, $html)
    {
        $this->dateInputs[$code] = $html;
    }

    /**
     * Sort date inputs by dateformat order of current locale.
     *
     * @param bool $stripNonInputChars
     *
     * @return string
     */
    public function getSortedDateInputs($stripNonInputChars = true)
    {
        $mapping = [];
        if ($stripNonInputChars) {
            $mapping['/[^medy]/i'] = '\\1';
        }
        $mapping['/m{1,5}/i'] = '%1$s';
        $mapping['/e{1,5}/i'] = '%2$s';
        $mapping['/d{1,5}/i'] = '%2$s';
        $mapping['/y{1,5}/i'] = '%3$s';

        $dateFormat = preg_replace(array_keys($mapping), array_values($mapping), $this->getDateFormat());

        return sprintf($dateFormat, $this->dateInputs['m'], $this->dateInputs['d'], $this->dateInputs['y']);
    }

    /**
     * Return minimal date range value.
     *
     * @return string|null
     */
    public function getMinDateRange()
    {
        $dob = $this->_getAttribute('dob');
        if ($dob !== null) {
            $rules = $this->_getAttribute('dob')->getValidationRules();
            $minDateValue = ArrayObjectSearch::getArrayElementByName(
                $rules,
                self::MIN_DATE_RANGE_KEY
            );
            if ($minDateValue !== null) {
                return date("Y/m/d", $minDateValue);
            }
        }

        return null;
    }

    /**
     * Return maximal date range value.
     *
     * @return string|null
     */
    public function getMaxDateRange()
    {
        $dob = $this->_getAttribute('dob');
        if ($dob !== null) {
            $rules = $this->_getAttribute('dob')->getValidationRules();
            $maxDateValue = ArrayObjectSearch::getArrayElementByName(
                $rules,
                self::MAX_DATE_RANGE_KEY
            );
            if ($maxDateValue !== null) {
                return date("Y/m/d", $maxDateValue);
            }
        }

        return null;
    }

    /**
     * Return first day of the week.
     *
     * @return int
     */
    public function getFirstDay()
    {
        return (int)$this->_scopeConfig->getValue(
            'general/locale/firstday',
            ScopeInterface::SCOPE_STORE
        );
    }
}
