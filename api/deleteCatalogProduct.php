<?php
//error_reporting(0);
ini_set('display_errors', 1);

$path = getcwd();
require_once($path.'/../app/bootstrap.php');

use \Magento\Framework\App\Bootstrap;

$Baseurl="http://franciskirk.shop/index.php";
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend'); 

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

$conn = mysqli_connect('localhost','default_md8m1','U&pkfrSC[Zz]vkJka~[56#^5','default_md8m1');
if(!$conn){
	echo "Unable to connect database".mysqli_error($conn);die;
}

$data['file_name'] = 'products.csv';
$csvFile = fopen('delete/'.$data['file_name'], "r");

if(!empty($csvFile)){
	
	$history_filename = date('Ymd-H:i:s')."-products.csv";
	copy("delete/products.csv","delete/history/".$history_filename);
	
	$rowcount = count(file('delete/'.$data['file_name']));
	
	$query = "INSERT INTO product_delete_history (id,file_name,total_records,created_at) VALUES(NULL,'".$history_filename."','".($rowcount - 1)."','".date('Y-m-d H:i:s')."')";
	mysqli_query($conn, $query);
	
	$productData = [];
	$count = 1; $row = [];
	while(($csvData = fgetcsv($csvFile)) !== FALSE){

		if($count == 1){
			foreach($csvData as $value){
				$row[] = strtolower(str_replace(' ','_',$value));;
			}
		}elseif($count > 1){
			foreach($row as $key => $attribute_code){
				$productData[$attribute_code] = $csvData[$key];
			}
			
			if(!empty($productData) && $productData['sku'] != ''){

				$status = 'pending';
				$query = "INSERT INTO product_delete_data (id,sku,status,updated_at,created_at,file_name) VALUES(NULL,'".$productData['sku']."','".$status."','".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."','".$history_filename."')";
				if(!mysqli_query($conn, $query)){
					echo "<br/>#".$count." SKU:".$productData['sku']." Unable to add ".mysqli_error($conn);
				}

			}

		}
		
		$count++;

	}

}

echo "<br/><br/>Products are added in queue. When cron job is run products will deleted.";
