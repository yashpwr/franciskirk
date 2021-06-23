<?php
error_reporting(0);
ini_set('display_errors', 1);

//$path = getcwd();
$path = "/home/default/html/api";
require_once('../app/bootstrap.php');
require_once('ProductCreateFunctions.php');

use \Magento\Framework\App\Bootstrap;

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend');

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

//$fromDate = date('Y-m-d 00:00:00',strtotime('2021-01-21 00:00:00'));
//$toDate = date('Y-m-d 23:59:59',strtotime('2021-01-21 23:59:59'));

$fromDate = date('Y-m-d 00:00:00');
$toDate = date('Y-m-d 23:59:59');
$date = date('Y-m-d');
if(isset($_REQUEST['date']) && $_REQUEST['date'] != ''){
	$date = $_REQUEST['date'];
	$fromDate = date('Y-m-d 00:00:00',strtotime($date.' 00:00:00'));
	$toDate = date('Y-m-d 23:59:59',strtotime($date.' 23:59:59'));
}

$OrderFactory = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\CollectionFactory');
$orderCollection = $OrderFactory->create()->addFieldToSelect('*');

$orderCollection->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate));
//echo "<pre>"; print_r($orderCollection->getData()); exit;

/*foreach ( $orderCollection as $order ){
	echo "<pre>";
	foreach($order->getAllItems() as $item){
		
		print_r($item->getData());
	}exit;
}*/

if(count($orderCollection)){
	
	try{
	
		$filename = $date."-orders.csv";
		
		unlink('orders/'.$filename);

		/*header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="'.$date.'-orders.csv"');

		$fp = fopen('php://output', 'wb');*/

		$fp = fopen('orders/'.$filename, 'a'); 

		$csv_header = [
				"Order Id",
				"Status",
				"Created At",
				"Customer Name",
				"Email",
				"Product Name",
				"SKU",
				"Product Quntity",
				"Product Price",
				"Billing Customer Name",
				"Billing Street",
				"Billing City",
				"Billing State",
				"Billing Zipcode",
				"Billing Country",
				"Billing Phone",
				"Shipping Customer Name",
				"Shipping Street",
				"Shipping City",
				"Shipping State",
				"Shipping Zipcode",
				"Shipping Country",
				"Shipping Phone",
				"Coupon Code",
				"Payment Method",
				"Shipping Method",
				"Shipping Description",
				"Subtotal",
				"Discount Amount",
				"Shipping Amount",
				"Grand Total",
				"Total Paid",
				"Total Due"
			];
		fputcsv($fp, $csv_header);

		$countryFactory = $objectManager->get('\Magento\Directory\Model\CountryFactory');

		foreach ( $orderCollection as $order ) {
			
			$customer_name = $order->getCustomerFirstname().' '.$order->getCustomerLastname();
			$payment_method =  $order->getPayment()->getMethodInstance()->getTitle();
			
			$shipping_address['firstname'] = $shipping_address['lastname'] = '';
	    	$shipping_address['street'] = $shipping_address['city'] = '';
	    	$shipping_address['region'] = $shipping_address['postcode'] = '';
	    	$shipping_address['telephone'] = $shipping_country_name = '';
			if($order->getData('shipping_address_id') && !empty($order->getShippingAddress()->getData())){
				$shipping_address = $order->getShippingAddress()->getData();
				$shipping_country = $countryFactory->create()->loadByCode($shipping_address['country_id']);
				$shipping_country_name = $shipping_country->getName();
			}
			
			$billing_address = $order->getBillingAddress()->getData();
			$billing_country = $countryFactory->create()->loadByCode($billing_address['country_id']);
			
			$product_name = [];
			$sku = [];
			$qty = [];
			$price = [];
			foreach($order->getAllItems() as $item){
				$product_name[] = $item->getName();
				$sku[] = $item->getSku();
				$qty[] = round($item->getQtyOrdered());
				$price[] = $item->getPrice();
			}

		    $row = [
			    	$order->getIncrementId(),
			    	$order->getStatus(),
			    	$order->getCreatedAt(),
			    	$customer_name,
			    	$order->getCustomerEmail(),
			    	implode(',',$product_name),
			    	implode(',',$sku),
			    	implode(',',$qty),
			    	implode(',',$price),
			    	$billing_address['firstname'].' '.$billing_address['lastname'],
			    	$billing_address['street'],
			    	$billing_address['city'],
			    	$billing_address['region'],
			    	$billing_address['postcode'],
			    	$billing_country->getName(),
			    	$billing_address['telephone'],
			    	$shipping_address['firstname'].' '.$shipping_address['lastname'],
			    	$shipping_address['street'],
			    	$shipping_address['city'],
			    	$shipping_address['region'],
			    	$shipping_address['postcode'],
			    	$shipping_country_name,
			    	$shipping_address['telephone'],
			    	$order->getData('coupon_code'),
			    	$payment_method,
			    	$order->getData('shipping_method'),
			    	$order->getData('shipping_description'),
			    	$order->getSubtotal(),
			    	$order->getData('discount_amount'),
			    	$order->getData('shipping_amount'),
			    	$order->getData('grand_total'),
			    	$order->getData('total_paid'),
			    	$order->getData('total_due')
		    	];
		    fputcsv($fp,$row);
		}
		fclose($fp);

		echo "Orders exported successfully. <a href='https://franciskirk.shop/api/orders/".$filename."' download>Download</a>";
		
	}catch(Exception $e){
		echo $e->getMessage();
	}

}else{
	echo "Order not found.";
}