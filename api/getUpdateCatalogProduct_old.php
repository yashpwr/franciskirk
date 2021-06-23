<?php
error_reporting(0);
ini_set('display_errors', 1);
$path = getcwd();
require_once($path.'/../app/bootstrap.php');
require_once('ProductUpdateFunctions.php');

use \Magento\Framework\App\Bootstrap;

$Baseurl="http://77.68.127.9/index.php";

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend'); 

$conn = mysqli_connect('localhost','default_md8m1','U&pkfrSC[Zz]vkJka~[56#^5','default_md8m1');
if(!$conn){
	echo "Unable to connect database".mysqli_error($conn);die;
}

$fileList = glob('files/*');

foreach($fileList as $file){

	$file_name = basename($file);
	$ext = pathinfo($file_name);

	if($ext['extension'] == 'csv'){

		$last_modified = date("Y-m-d H:i:s", filemtime($file));
		$created_at = date('Y-m-d H:i:s');
		$updated_at = date('Y-m-d H:i:s');
		$query = "SELECT * FROM product_import_csv WHERE file_name='".$file_name."'";
		$result = mysqli_query($conn, $query);

		if(mysqli_num_rows($result) > 0){

			$data = mysqli_fetch_array($result);
			
			if(strtotime($last_modified) > strtotime($data['last_modified'])){
				
				$status = 'pending';
				$query = "UPDATE product_import_csv SET last_modified='".$last_modified."',updated_at='".$updated_at."',status='".$status."' WHERE file_name='".$file_name."'";
				$result = mysqli_query($conn, $query);
				/*if(!$result){
					echo "Unable to update ".mysqli_error($conn); die;
				}*/

			}

		}else{

			$status = 'pending';
			$query = "INSERT INTO product_import_csv (id,file_name,last_modified,updated_at,status,created_at) VALUES(NULL,'".$file_name."','".$last_modified."','".$updated_at."','".$status."','".$created_at."')";
			$result = mysqli_query($conn, $query);
			/*if(!$result){
				echo "Unable to insert ".mysqli_error($conn); die;
			}*/

		}

	}

}

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

$categoryFactory = $objectManager->get("\Magento\Catalog\Model\CategoryFactory");



$username = 'admin_api';
$password = 'admin@123456';
$action = 'update';

$productAllDataArr = [];

//$query = "SELECT * FROM product_import_csv WHERE status = 'pending' ORDER BY id ASC";
$query = "SELECT * FROM product_import_csv WHERE status IN ('pending','in_progress') ORDER BY id ASC LIMIT 0,1";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) > 0){

	

	$action = 'update';

	$responce = array();

	

	while($data = mysqli_fetch_array($result)){

		$csvFile = fopen('files/'.$data['file_name'], "r");

		if(!empty($csvFile)){

			$status = 'in_progress';
			$query = "UPDATE product_import_csv SET status='".$status."' WHERE id='".$data['id']."'";
			mysqli_query($conn, $query);

			$rowcount = count(file('files/'.$data['file_name']));

			$productData = [];

			$count = 1; $row = []; $additionalAttributeRow = [];

			while(($csvData = fgetcsv($csvFile)) !== FALSE){

				

				if($count == 1){

					foreach($csvData as $value){

						$row[] = strtolower(str_replace(' ','_',$value));;

					}

					if(count($row) > 25){

						for($i=25;count($row)>$i;$i++){

							$additionalAttributeRow[$i] = strtolower(str_replace(' ','_',$row[$i]));

						}

					}

					

				}elseif($count > 1){

					foreach($row as $key => $attribute_code){

						$productData[$attribute_code] = $csvData[$key];

					}

					

					if(!empty($productData)){

						$productDataArr = [];
						$productDataArr['action'] = $action;
						$productDataArr['apiUser'] = $username;
						$productDataArr['apiKey'] = $password;

						$dataArr = [];
						$dataArr['prdType'] = 'simple';
						$dataArr['sku'] = $productData['sku'];
						$dataArr['store_id'] = 'all';
						$dataArr['prdName'] = (isset($productData['name']) && $productData['name'] != '') ? $productData['name'] : '';
						$dataArr['prdDesc'] = (isset($productData['description']) && $productData['description'] != '') ? $productData['description'] : '';
						$dataArr['prdShortDesc'] = (isset($productData['short_description']) && $productData['short_description'] != '') ? $productData['short_description'] : '';
						$dataArr['prdWeight'] = (isset($productData['weight']) && $productData['weight'] != '') ? $productData['weight'] : '';
						$dataArr['prdStatus'] = (isset($productData['status']) && $productData['status'] != '') ? $productData['status'] : '';
						$dataArr['prdUrlPath'] = '';
						$dataArr['prdVisibility'] = (isset($productData['visibility']) && $productData['visibility'] != '') ? $productData['visibility'] : '';
						$dataArr['prdQuantity'] = (isset($productData['qty']) && $productData['qty'] != '') ? $productData['qty'] : '';
						$dataArr['prdPrice'] = (isset($productData['price']) && $productData['price'] != '') ? $productData['price'] : 0;
						$dataArr['prdSpecialPrice'] = (isset($productData['special_price']) && $productData['special_price'] != '') ? $productData['special_price'] : '';
						$dataArr['prdSpecialFromDate'] = '';
						$dataArr['prdSpecialToDate'] = '';
						$dataArr['prdInStock'] = (isset($productData['is_in_stock']) && $productData['is_in_stock'] != '') ? $productData['is_in_stock'] : '';
						$dataArr['prdMngStock'] = (isset($productData['manage_stock']) && $productData['manage_stock'] != '') ? $productData['manage_stock'] : '';
						$dataArr['attributeSetId'] = (isset($productData['attribute_set_id']) && $productData['attribute_set_id'] != '') ? $productData['attribute_set_id'] : '';


						$dataArr['pack_qty'] = (isset($productData['pack_qty']) && $productData['pack_qty'] != '') ? $productData['pack_qty'] : '';
						$dataArr['supplier'] = (isset($productData['supplier']) && $productData['supplier'] != '') ? $productData['supplier'] : '';
						

						$dataArr['type'] = (isset($productData['type']) && $productData['type'] != '') ? $productData['type'] : '';

						$dataArr['sub_type'] = (isset($productData['sub_type']) && $productData['sub_type'] != '') ? $productData['sub_type'] : '';

						$dataArr['price_edited'] = (isset($productData['price_edited']) && $productData['price_edited'] != '') ? $productData['price_edited'] : '';

						$dataArr['price_unit'] = (isset($productData['price_unit']) && $productData['price_unit'] != '') ? $productData['price_unit'] : '';

						$dataArr['barcode'] = (isset($productData['barcode']) && $productData['barcode'] != '') ? $productData['barcode'] : '';

						$dataArr['thread_type'] = (isset($productData['thread_type']) && $productData['thread_type'] != '') ? $productData['thread_type'] : '';

						$dataArr['unit_price'] = (isset($productData['unit_price']) && $productData['unit_price'] != '') ? $productData['unit_price'] : '';

						

						if((isset($productData['categories']) && $productData['categories'] != '')){

							

							$categories =  explode('/',$productData['categories']);

							
							$category_ids = [];
							foreach($categories as $category_name){

								$collection = $categoryFactory->create()->getCollection()->addAttributeToFilter('name',$category_name)->setPageSize(1);

								if ($collection->getSize()) {

								    $category_ids[] = $collection->getFirstItem()->getId();
								    $dataArr['prdCategories'] = $category_ids;

								    /*$product = $objectManager->get("\Magento\Catalog\Model\ProductRepository")->get($productData['sku']);
								    if($product){
									    $category_ids = $product->getCategoryIds();
										if(!in_array($categoryId,$category_ids)){
											$category_ids[] = $categoryId;
										}
										$dataArr['prdCategories'] = $category_ids;
									}*/						    
								}else{
									$dataArr['createCategories'][] = $category_name;
								}
							}
						}

						if(isset($productData['images']) && $productData['images'] != ''){
							$dataArr['updatePrdImg'][] = $productData['images'];
							/*$images = explode(',',$productData['images']);
							foreach($images as $image_url){
								$dataArr['updatePrdImg'][] = $image_url;
							}*/
						}

						

						$single_data = [];

						

						if(isset($productData['thread_form']) && $productData['thread_form'] != ''){
							$single_data[] = ['key'=>'thread_form','value'=>$productData['thread_form']];
						}
						if(isset($productData['diameter']) && $productData['diameter'] != ''){
							$single_data[] = ['key'=>'diameter','value'=>$productData['diameter']];
						}
						if(isset($productData['length']) && $productData['length'] != ''){
							$single_data[] = ['key'=>'length','value'=>$productData['length']];
						}
						if(isset($productData['finish_colour']) && $productData['finish_colour'] != ''){
							$single_data[] = ['key'=>'finish_colour','value'=>$productData['finish_colour']];
						}
						if(isset($productData['grade']) && $productData['grade'] != ''){
							$single_data[] = ['key'=>'grade','value'=>$productData['grade']];
						}
						if(isset($productData['fkbrand']) && $productData['fkbrand'] != ''){
							$single_data[] = ['key'=>'fkbrand','value'=>$productData['fkbrand']];
						}
						if(isset($productData['size_option']) && $productData['size_option'] != ''){
							$single_data[] = ['key'=>'size_option','value'=>$productData['size_option']];
						}
						if(isset($productData['color']) && $productData['color']  != ''){
							$single_data[] = ['key'=>'color','value'=>$productData['color']];
						}
						
						if(!empty($additionalAttributeRow)){
							foreach($additionalAttributeRow as $attrribute_code){
								if(isset($productData[$attrribute_code]) && $productData[$attrribute_code] != ''){
									$single_data[] = ['key'=>$attrribute_code,'value'=>$productData[$attrribute_code]];
								}
							}
						}
						
						$dataArr['additionalAttributes']['single_data'] = $single_data;

						$productDataArr['data'][] = $dataArr;

						//$productAllDataArr[] = $productDataArr;

						//$product_data = json_encode($productDataArr,JSON_HEX_QUOT);
						$product_data = str_replace("u0022","\\\\\"",json_encode( $productDataArr,JSON_HEX_QUOT)); 

						$product_data_query = "SELECT * FROM product_import_data WHERE sku = '".$productData['sku']."' AND status != 'success' AND file_name='".$data['file_name']."'";
						$product_data_result = mysqli_query($conn, $product_data_query);
						if(mysqli_num_rows($product_data_result) > 0){

							$product_data_row = mysqli_fetch_array($product_data_result);

							$query = "UPDATE product_import_data SET product_data='".$product_data."',updated_at='".date('Y-m-d H:i:s')."' WHERE sku='".$productData['sku']."'";
							if(!mysqli_query($conn, $query)){
								echo "<br/>#".$count." SKU:".$productData['sku']." Unable to update ".mysqli_error($conn);
							}
						}else{
							$status = 'pending';
							$query = "INSERT INTO product_import_data (id,sku,product_data,status,updated_at,created_at,file_name) VALUES(NULL,'".$productData['sku']."','".$product_data."','".$status."','".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."','".$data['file_name']."')";
							if(!mysqli_query($conn, $query)){
								echo "<br/>#".$count." SKU:".$productData['sku']." Unable to insert ".mysqli_error($conn);
							}
						}
						
					}
				}
				
				if($rowcount == $count){
					$status = 'success';
					$query = "UPDATE product_import_csv SET status='".$status."' WHERE id='".$data['id']."'";
					mysqli_query($conn, $query);
				}
				
				$count++;

			}

		}
	}

}

echo "<br/><br/>Products are added in queue. When cron job is run products will updated.";