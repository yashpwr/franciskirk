<?php

namespace Cminds\MultiUserAccounts\Helper;

use Magento\Backend\Model\UrlInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Cminds MultiUserAccounts email helper.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Email extends AbstractHelper
{
    /**
     * Sender email identity.
     */
    const SENDER_IDENTITY = 'general';

    /**
     * Email template config paths.
     */
    const XML_PATH_EMAIL_CHECKOUT_ORDER_APPROVE_REQUEST_TEMPLATE
        = 'subaccount/email/checkout_order_approve_request/template';
    const XML_PATH_EMAIL_CHECKOUT_ORDER_APPROVED_TEMPLATE
        = 'subaccount/email/checkout_order_approved/template';
    const XML_PATH_EMAIL_CHECKOUT_ORDER_AUTHORIZATION_REQUEST_TEMPLATE
        = 'subaccount/email/checkout_order_authorization_request/template';
    const XML_PATH_EMAIL_CHECKOUT_ORDER_REJECTED_TEMPLATE
        = 'subaccount/email/checkout_order_rejected/template';
    const XML_PATH_EMAIL_CUSTOMER_APPROVE
        = 'subaccount/email/customer_approve/template';

    /**
     * Store manager object.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Inline translation object.
     *
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * Transport builder object.
     *
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * Template id.
     *
     * @var string
     */
    private $templateId;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param StateInterface $inlineTranslation
     * @param TransportBuilder $transportBuilder
     * @param UrlInterface $urlInterface
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        StateInterface $inlineTranslation,
        TransportBuilder $transportBuilder,
        UrlInterface $urlInterface,
        CustomerRepository $customerRepository
    ) {
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->urlInterface = $urlInterface;
        parent::__construct($context);
        $this->customerRepository = $customerRepository;
    }

    /**
     * Return store configuration value of your template field that
     * which id you set for template.
     *
     * @param string $path Path.
     * @param int $storeId Store id.
     *
     * @return mixed
     */
    protected function getConfigValue($path, $storeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return store.
     *
     * @return StoreInterface
     */
    protected function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * Return template id according to store.
     *
     * @param string $xmlPath Xml path.
     *
     * @param null $storeId
     * @return string
     */
    protected function getTemplateId($xmlPath, $storeId = null)
    {
        $storeId = $storeId ?? $this->getStore()->getStoreId();
        return $this->getConfigValue($xmlPath, $storeId);
    }

    /**
     * Generate template.
     *
     * @param array $recipient Recipient data array.
     * @param string $sender Sender email address.
     * @param array $emailVariables Email variables.
     *
     * @return Email
     */
    protected function generateTemplate($recipient, $sender, $emailVariables)
    {
        $this->transportBuilder
            ->setTemplateIdentifier($this->templateId)
            ->setTemplateOptions(
                [
                    'area' => Area::AREA_FRONTEND,
                    'store' => $this->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailVariables)
            ->setFrom($sender)
            ->addTo($recipient['email'], $recipient['name']);

        return $this;
    }

    /**
     * Send email.
     *
     * @param array $recipient Recipient data array.
     * @param array $emailVariables Email variables.
     *
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    protected function sendEmail($recipient, $emailVariables)
    {
        $sender = self::SENDER_IDENTITY;

        $this->inlineTranslation->suspend();
        $this->generateTemplate($recipient, $sender, $emailVariables);

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();
    }

    /**
     * Send checkout order approve request email.
     *
     * @param array $recipient Recipient data array.
     * @param array $emailVariables Email variables.
     *
     * @return Email
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendCheckoutOrderApproveRequestEmail(
        $recipient,
        $emailVariables
    ) {
        $email = $recipient['email'];
        $storeId = null;
        try {
            $customer = $this->customerRepository->get($email);
            $storeId = $customer->getStoreId();
        } catch (\Exception $e) {
            $storeId = null;
        }
        $this->templateId = $this->getTemplateId(
            self::XML_PATH_EMAIL_CHECKOUT_ORDER_APPROVE_REQUEST_TEMPLATE,
            $storeId
        );
        $this->sendEmail($recipient, $emailVariables);

        return $this;
    }

    /**
     * Send checkout order approved email.
     *
     * @param array $recipient Recipient data array.
     * @param array $emailVariables Email variables.
     *
     * @return Email
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendCheckoutOrderApprovedEmail($recipient, $emailVariables)
    {
        $email = $recipient['email'];
        $storeId = null;
        try {
            $customer = $this->customerRepository->get($email);
            $storeId = $customer->getStoreId();
        } catch (\Exception $e) {
            $storeId = null;
        }

        $this->templateId = $this->getTemplateId(
            self::XML_PATH_EMAIL_CHECKOUT_ORDER_APPROVED_TEMPLATE,
            $storeId
        );
        $this->sendEmail($recipient, $emailVariables);

        return $this;
    }

    /**
     * Send checkout order authorization request email.
     *
     * @param array $recipient Recipient data array.
     * @param array $emailVariables Email variables.
     *
     * @return Email
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendCheckoutOrderAuthorizationRequestEmail(
        $recipient,
        $emailVariables
    ) {
        $email = $recipient['email'];
        $storeId = null;
        try {
            $customer = $this->customerRepository->get($email);
            $storeId = $customer->getStoreId();
        } catch (\Exception $e) {
            $storeId = null;
        }

        $this->templateId = $this->getTemplateId(
            self::XML_PATH_EMAIL_CHECKOUT_ORDER_AUTHORIZATION_REQUEST_TEMPLATE,
            $storeId
        );
        $this->sendEmail($recipient, $emailVariables);

        return $this;
    }

    /**
     * Send checkout order rejected email.
     *
     * @param array $recipient Recipient data array.
     * @param array $emailVariables Email variables.
     *
     * @return Email
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendCheckoutOrderRejectedEmail($recipient, $emailVariables)
    {
        $email = $recipient['email'];
        $storeId = null;
        try {
            $customer = $this->customerRepository->get($email);
            $storeId = $customer->getStoreId();
        } catch (\Exception $e) {
            $storeId = null;
        }

        $this->templateId = $this->getTemplateId(
            self::XML_PATH_EMAIL_CHECKOUT_ORDER_REJECTED_TEMPLATE,
            $storeId
        );
        $this->sendEmail($recipient, $emailVariables);

        return $this;
    }

    /**
     * @param CustomerInterface $customer
     * @return $this
     */
    public function sendCustomerApproveEmail(CustomerInterface $customer)
    {
        $recipient = [
            'email' => $this->getConfigValue('trans_email/ident_support/email', $this->getStore()->getId()),
            'name' => $this->getConfigValue('trans_email/ident_support/name', $this->getStore()->getId())
        ];
        $emailVariables = [
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'email' => $customer->getEmail(),
            'url' => $this->urlInterface->getUrl('subaccounts/customer/approve', ['id' => $customer->getId()])
        ];

        $storeId = null;
        try {
            $storeId = $customer->getStoreId();
        } catch (\Exception $e) {
            $storeId = null;
        }

        $this->templateId = $this->getTemplateId(
            self::XML_PATH_EMAIL_CUSTOMER_APPROVE,
            $storeId
        );
        $this->sendEmail($recipient, $emailVariables);

        return $this;
    }
}
