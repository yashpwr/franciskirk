<?php
namespace Mahesh\CustomerCustomAttribute\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class PaymentMethodDisable implements ObserverInterface {
    protected $_customerSession;
    public function __construct(
       \Magento\Customer\Model\Session $customerSession
    ) {
       $this->_customerSession = $customerSession;
    }
    public function execute(Observer $observer) {
		$payment_method_code = $observer->getEvent()->getMethodInstance()->getCode();
		$result = $observer->getEvent()->getResult();
		if ($this->_customerSession->isLoggedIn()) {
			$account_flag = $this->_customerSession->getCustomer()->getAccountFlag();
			/*$log_writer = new \Zend\Log\Writer\Stream(BP . '/var/log/payment_method_check.log');
			$log_logger = new \Zend\Log\Logger();
			$log_logger->addWriter($log_writer);
			$log_logger->info("account-flag: ".$account_flag.' payment-method: '.$payment_method_code);*/
			if ($account_flag == 0) {
	       		if ($payment_method_code == 'checkmo') {
					$result->setData('is_available', true);
				}
				if ($payment_method_code == 'stripe_payments') {
					$result->setData('is_available', true);
				}
			}elseif ($account_flag == 1) {
	       		if ($payment_method_code == 'checkmo') {
					$result->setData('is_available', false);
				}
				if ($payment_method_code == 'stripe_payments') {
					$result->setData('is_available', true);
				}
			}elseif ($account_flag == 2) {
	       		if ($payment_method_code == 'checkmo') {
					$result->setData('is_available', true);
				}
				if ($payment_method_code == 'stripe_payments') {
					$result->setData('is_available', false);
				}
			}else{
				if ($payment_method_code == 'checkmo') {
					$result->setData('is_available', false);
				}
				if ($payment_method_code == 'stripe_payments') {
					$result->setData('is_available', true);
				}
			}
	    }else{
			if ($payment_method_code == 'checkmo') {
				$result->setData('is_available', false);
			}
			if ($payment_method_code == 'stripe_payments') {
				$result->setData('is_available', true);
			}
		}
    }
}