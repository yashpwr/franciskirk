<?php
namespace Yash\PaymentRestriction\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;

class PaymentMethodAvailable implements ObserverInterface{
	protected $_customerSession;

	public function __construct(\Psr\Log\LoggerInterface $logger,  \Magento\Customer\Model\Session $customerSession){
		$this->_logger = $logger;     
		$this->_customerSession = $customerSession;
	}

	public function execute(\Magento\Framework\Event\Observer $observer) {
		$result          = $observer->getEvent()->getResult();
		$method_instance = $observer->getEvent()->getMethodInstance();
		$quote           = $observer->getEvent()->getQuote();
		$this->_logger->info($method_instance->getCode());

		$objectManager =   \Magento\Framework\App\ObjectManager::getInstance();
		$storeManager  = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$storeID       = $storeManager->getStore()->getStoreId(); 
		$storeName     = $storeManager->getStore()->getName();

		$customerSession = $objectManager->get('Magento\Customer\Model\Session');

		$connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION'); 

		// $paymentConfig = $objectManager->get('Magento\Payment\Model\Config'); //Get Active payment methods
		// $activePaymentMethods = $paymentConfig->getActiveMethods();

		$testData = 1;

		/*if($customerSession->isLoggedIn()) {
			$user_id = $customerSession->getCustomer()->getId();
			$sql = "Select * FROM customer_payment_options where CUSTOMER_ID = ". $user_id;
			// $sql = "Select * FROM customer_payment_options";
			 if (!empty($result2)) {
				if ($result2[0]['ACCOUNT_FLAG']  == 0) {
					if ($method_instance->getCode() == 'checkmo') {
						$result->setData('is_available', false);
					}
					if ($method_instance->getCode() == 'stripe_payments') {
						$result->setData('is_available', true);
					}
				}elseif ($result2[0]['ACCOUNT_FLAG'] == 1) {
					if ($method_instance->getCode() == 'checkmo') {
						$result->setData('is_available', true);
					}
					if ($method_instance->getCode() == 'stripe_payments') {
						$result->setData('is_available', true);
					}
				}elseif ($result2[0]['ACCOUNT_FLAG'] == 2) {
					if ($method_instance->getCode() == 'checkmo') {
						$result->setData('is_available', true);
					}
					if ($method_instance->getCode() == 'stripe_payments') {
						$result->setData('is_available', false);
					}
				}
			}else{
				if ($method_instance->getCode() == 'checkmo') {
					$result->setData('is_available', false);
				}
				if ($method_instance->getCode() == 'stripe_payments') {
					$result->setData('is_available', true);
				}
			}						
		}else{
			if ($method_instance->getCode() == 'checkmo') {
				$result->setData('is_available', false);
			}
			if ($method_instance->getCode() == 'stripe_payments') {
				$result->setData('is_available', true);
			}
		}*/

		 
		// if ($method_instance->getCode() == 'checkmo') {

		// 	if($customerSession->isLoggedIn()) {

		// 		$user_id = $customerSession->getCustomer()->getId();

		// 		$sql = "Select * FROM customer_payment_options where CUSTOMER_ID = ". $user_id;
		// 		$result2 = $connection->fetchAll($sql);

		// 		if (!empty($result2)) {
		// 			if ($result2[0]['ACCOUNT_FLAG'] == 1) {
		// 				$result->setData('is_available', true);
		// 			}
		// 		}else{
		// 			$result->setData('is_available', false);
		// 		}
		// 	}
		// 	else{
		// 		$result->setData('is_available', false);
		// 	}
		// }

	}
}