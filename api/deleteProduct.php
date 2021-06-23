<?php
//error_reporting(0);
ini_set('display_errors', 1);
//$path = getcwd();
$path = "/home/default/html/api";
require_once($path.'/../app/bootstrap.php');
require_once($path.'/ProductDeleteFunctions.php');

use \Magento\Framework\App\Bootstrap;

$Baseurl="http://franciskirk.shop/index.php";

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend');

$conn = mysqli_connect('localhost','default_md8m1','U&pkfrSC[Zz]vkJka~[56#^5','default_md8m1');
if(!$conn){
	echo "Unable to connect database".mysqli_error($conn)."\n";die;
}

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$categoryFactory = $objectManager->get("\Magento\Catalog\Model\CategoryFactory");

$username = 'admin_api';
$password = 'admin@123456#';
$action = 'delete';

$query = "SELECT * FROM product_delete_data WHERE status IN ('pending') ORDER BY id ASC LIMIT 0,100";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) > 0){
	
	while($product_delete = mysqli_fetch_array($result)){
		
		$history_filename = str_replace('products.csv','',$product_delete['file_name']);
		$log_writer = new \Zend\Log\Writer\Stream(BP . '/api/delete/log/'.$history_filename.'product.delete.log');
		$log_logger = new \Zend\Log\Logger();
		$log_logger->addWriter($log_writer);

		$bad_writer = new \Zend\Log\Writer\Stream(BP . '/api/delete/log/'.$history_filename.'product.delete.bad');
		$bad_logger = new \Zend\Log\Logger();
		$bad_logger->addWriter($bad_writer);
		
		$query = "SELECT count(*) as total FROM product_delete_data WHERE status='pending' AND file_name='".$product_delete['file_name']."' GROUP BY file_name";
		$product_create_data_result = mysqli_query($conn, $query);
		if(mysqli_num_rows($product_create_data_result) > 0){
			$product_delete_data = mysqli_fetch_array($product_create_data_result);
			$query = "SELECT * FROM product_delete_history WHERE file_name='".$product_delete['file_name']."'";
			$product_delete_history_result = mysqli_query($conn, $query);
			if(mysqli_num_rows($product_delete_history_result) > 0){
				$product_delete_history = mysqli_fetch_array($product_delete_history_result);
				if($product_delete_data['total'] == $product_delete_history['total_records']){
					$log_logger->info("---------------- START API Cron ".date('d/m/y H:i:s')." ----------------");
					$bad_logger->info("---------------- START API Cron ".date('d/m/y H:i:s')." ----------------");
				}
			}
			
		}

		//Authentication rest API magento2, get access token
		$ch = curl_init();
		$data = array("username" => $username, "password" => $password);

		$data_string = json_encode($data);

		$token_url= $Baseurl."/rest/V1/integration/admin/token";

		$ch = curl_init($token_url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string))
		);
		$token = curl_exec($ch);
		
		//print_r($token); exit;

		$adminToken =  json_decode($token);

		if($adminToken == ""){
			$responce = array("status" =>'0', "message"=>'Invalid username or API KEY');
			$bad_logger->info(json_encode($responce)); exit;
		}
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$sku = trim($product_delete['sku']);
		$sku = str_replace(" ","",$sku);

		if(checkProductExist($sku)){
			
				$responce = deleteproduct($sku,$adminToken,$Baseurl);
				if($responce['status'] == 'failed'){
					$bad_logger->info(json_encode($responce));
				}else{
					$log_logger->info(json_encode($responce));
				}

			    $status = $responce['status'];
				$query = "UPDATE product_delete_data SET status='".$status."',updated_at='".date('Y-m-d H:i:s')."' WHERE id='".$product_delete['id']."'";
				mysqli_query($conn, $query);
			
		}else{
			
			$status = 'failed';
			$query = "UPDATE product_delete_data SET status='".$status."',updated_at='".date('Y-m-d H:i:s')."' WHERE id='".$product_delete['id']."'";
			mysqli_query($conn, $query);
			
			$returnArr = array("status" =>'failed','sku'=> $productallData['sku'], "message"=>"Product is not available.");
			$bad_logger->info(json_encode($returnArr));
		}
		
		$query = "SELECT count(*) as total_records FROM product_delete_data WHERE status='pending' AND file_name='".$product_delete['file_name']."' GROUP BY file_name";
		$product_delete_result = mysqli_query($conn, $query);
		if(mysqli_num_rows($product_delete_result) > 0){
			/*product_delete_data = mysqli_fetch_array($product_delete_result);
			$total_records = product_delete_data['total_records'];*/
		}else{
			$log_logger->info("---------------- END API Cron ".date('d/m/y H:i:s')." ----------------");
			$bad_logger->info("---------------- END API Cron ".date('d/m/y H:i:s')." ----------------");
		}
		
	}
	
}