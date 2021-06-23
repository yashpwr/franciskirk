<?php
//error_reporting(0);
ini_set('display_errors', 1);

$path = getcwd();
require_once($path.'/../../app/bootstrap.php');
//require_once('ProductCreateFunctions.php');

use \Magento\Framework\App\Bootstrap;

$Baseurl="http://franciskirk.shop/index.php";
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend'); 

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$categoryFactory = $objectManager->get("\Magento\Catalog\Model\CategoryFactory");

$attributeSets = $objectManager->get('\Magento\Catalog\Model\Product\AttributeSet\Options')->toOptionArray();
$visibilities = $objectManager->get('\Magento\Catalog\Model\Product\Visibility')->getAllOptions();


$username = 'admin_api';
$password = 'admin@123456#';
$action = 'create';

$conn = mysqli_connect('localhost','default_md8m1','U&pkfrSC[Zz]vkJka~[56#^5','default_md8m1');
if(!$conn){
	echo "Unable to connect database".mysqli_error($conn);die;
}

$data['file_name'] = 'products.csv';
$csvFile = fopen('CreateFiles/'.$data['file_name'], "r");

if(!empty($csvFile)){
	
	$history_filename = date('Ymd-H:i:s')."-products.csv";
	copy("CreateFiles/products.csv","CreateFiles/history/".$history_filename);
	
	$rowcount = count(file('CreateFiles/'.$data['file_name']));
	
	$query = "INSERT INTO product_create_history (id,file_name,total_records,created_at) VALUES(NULL,'".$history_filename."','".($rowcount - 1)."','".date('Y-m-d H:i:s')."')";
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
			
			if(!empty($productData)){


				if(isset($productData['product_type']) && $productData['product_type'] == 'simple'){


					$productDataArr = [];
					$productDataArr['action'] = $action;
					$productDataArr['apiUser'] = $username;
					$productDataArr['apiKey'] = $password;

					$dataArr = [];
					$dataArr['prdType'] = 'simple';
					$dataArr['sku'] = $productData['sku'];
					$dataArr['store_id'] = 'all';
					$dataArr['prdName'] = (isset($productData['name']) && $productData['name'] != '') ? $productData['name'] : '';
					$dataArr['prdStatus'] = (isset($productData['product_online']) && $productData['product_online'] != '') ? $productData['product_online'] : '';

					$visibility = '';
					foreach($visibilities as $value){
						if($value['label']->getText() == $productData['visibility']){
							$visibility = $value['value'];
						}								
					}
					$dataArr['prdVisibility'] = (isset($visibility) && $visibility != '') ? $visibility : '';
					
					$attribute_set_id = '';
					foreach($attributeSets as $attributeSet){
						if($attributeSet['label'] == $productData['attribute_set_code']){
							$attribute_set_id = $attributeSet['value'];
						}
					}
					$dataArr['attributeSetId'] = (isset($attribute_set_id) && $attribute_set_id != '') ? $attribute_set_id : '';

					/*if((isset($productData['categories']) && $productData['categories'] != '')){
						$categories =  explode('/',$productData['categories']);
						$category_ids = [];
						foreach($categories as $category_name){
							$collection = $categoryFactory->create()->getCollection()->addAttributeToFilter('name',$category_name)->setPageSize(1);
							if($collection->getSize()){
								$category_ids[] = $collection->getFirstItem()->getId();
								$dataArr['prdCategories'] = $category_ids;
							}else{
								$dataArr['createCategories'][] = $category_name;
							}
						}
					}*/
					if((isset($productData['categories']) && $productData['categories'] != '')){
						$categories =  explode(',',$productData['categories']);
						$dataArr['prdCategories'] = $categories;
					}

					$productDataArr['data'][] = $dataArr;

					//$product_data = json_encode($productDataArr);
					$product_data = str_replace("u0022","\\\\\"",json_encode( $productDataArr,JSON_HEX_QUOT)); 
					
					/*$product_data_query = "SELECT * FROM product_create_data WHERE sku = '".$productData['sku']."' AND status != 'success' AND file_name='".$data['file_name']."'";
					$product_data_result = mysqli_query($conn, $product_data_query);
					if(mysqli_num_rows($product_data_result) > 0){

						$product_data_row = mysqli_fetch_array($product_data_result);

						$query = "UPDATE product_create_data SET product_data='".$product_data."',updated_at='".date('Y-m-d H:i:s')."' WHERE sku='".$productData['sku']."'";
						if(!mysqli_query($conn, $query)){
							echo "<br/>#".$count." SKU:".$productData['sku']." Unable to update ".mysqli_error($conn);
						}
					}else{*/
						$status = 'pending';
						$query = "INSERT INTO product_create_data (id,sku,product_data,status,updated_at,created_at,file_name) VALUES(NULL,'".$productData['sku']."','".$product_data."','".$status."','".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."','".$history_filename."')";
						if(!mysqli_query($conn, $query)){
							echo "<br/>#".$count." SKU:".$productData['sku']." Unable to insert ".mysqli_error($conn);
						}
					//}
					
				}

			}

		}
		
		$count++;

	}

}


echo "<br/><br/>Products are added in queue. When cron job is run products will created.";
