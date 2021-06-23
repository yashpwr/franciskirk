<?php

namespace StripeIntegration\Payments\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use StripeIntegration\Payments\Helper\Logger;

class Button extends Template
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \StripeIntegration\Payments\Model\Config
     */
    protected $config;

    /**
     * @var \StripeIntegration\Payments\Helper\ExpressHelper
     */
    protected $expressHelper;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $localeResolver;

    /**
     * Button constructor.
     *
     * @param Template\Context                       $context
     * @param Registry                               $registry
     * @param PriceCurrencyInterface                 $priceCurrency
     * @param \StripeIntegration\Payments\Model\Config $config
     * @param \StripeIntegration\Payments\Helper\ExpressHelper $expressHelper
     * @param \Magento\Checkout\Helper\Data          $checkoutHelper
     * @param \Magento\Tax\Helper\Data               $taxHelper
     * @param \Magento\Framework\Locale\Resolver     $localeResolver
     * @param array                                  $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        PriceCurrencyInterface $priceCurrency,
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Helper\Generic $paymentsHelper,
        \StripeIntegration\Payments\Helper\ExpressHelper $expressHelper,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Framework\Locale\Resolver $localeResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->priceCurrency = $priceCurrency;
        $this->config = $config;
        $this->expressHelper = $expressHelper;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->checkoutHelper = $checkoutHelper;
        $this->taxHelper = $taxHelper;
        $this->localeResolver = $localeResolver;
        $this->paymentsHelper = $paymentsHelper;
    }

    /**
     * Check Is Block enabled
     * @return bool
     */
    public function isEnabled($location)
    {
        return $this->expressHelper->isEnabled($location);
    }

    /**
     * Get Publishable Key
     * @return string
     */
    public function getPublishableKey()
    {
        return $this->config->getPublishableKey();
    }

    /**
     * Get Button Config
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getButtonConfig()
    {
        return [
            'type' => $this->expressHelper->getStoreConfig('payment/stripe_payments_express/button_type'),
            'theme' => $this->expressHelper->getStoreConfig('payment/stripe_payments_express/button_theme'),
            'height' => $this->expressHelper->getStoreConfig('payment/stripe_payments_express/button_height')
        ];
    }

    public function getProductId()
    {
        $product = $this->registry->registry('product');
        return $product->getId();
    }
    /**
     * Get Quote
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        $quote = $this->checkoutHelper->getCheckout()->getQuote();
        if (!$quote->getId()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $quote = $objectManager->create('Magento\Checkout\Model\Session')->getQuote();
        }

        return $quote;
    }

    /**
     * Get Country Code
     * @return string
     */
    public function getCountry()
    {
        $countryCode = $this->getQuote()->getBillingAddress()->getCountryId();
        if (empty($countryCode)) {
            $countryCode = $this->expressHelper->getDefaultCountry();
        }
        return $countryCode;
    }

    /**
     * Get Label
     * @return string
     */
    public function getLabel()
    {
        return $this->expressHelper->getLabel($this->getQuote());
    }
}
