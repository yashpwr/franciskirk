<?php
//error_reporting(0);
ini_set('display_errors', 1);
//$path = getcwd();
$path = "/home/default/html/api";
require_once($path.'/../app/bootstrap.php');
//require_once($path.'/ProductUpdateFunctions.php');

use \Magento\Framework\App\Bootstrap;

$Baseurl="http://franciskirk.shop/index.php";

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend');

$conn = mysqli_connect('localhost','default_md8m1','U&pkfrSC[Zz]vkJka~[56#^5','default_md8m1');
if(!$conn){
	echo "Unable to connect database ".mysqli_error($conn)."\n";die;
}

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$categoryFactory = $objectManager->get("\Magento\Catalog\Model\CategoryFactory");
		
$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
$root_category_id = $storeManager->getStore()->getRootCategoryId();

$categoryList[] =  'tools';
foreach($categoryList as $categoryNameList){
	
	$categories =  explode('/',$categoryNameList);
	
	$rootCategory = $objectManager->create('Magento\Catalog\Model\Category')->load($root_category_id);
	$subcategories = $rootCategory->getChildrenCategories();
	$subcategoryIdList = [];
	foreach($subcategories as $subcategory){
		$subcategoryIdList[] = $subcategory->getId();
	}
	$collection = $categoryFactory->create()->getCollection();
	$collection->addAttributeToSelect('*');
	$collection->addIdFilter($subcategoryIdList);
	$collection->addAttributeToFilter('name',$categories[0])->setPageSize(1);
	if($collection->getSize()){
		echo $categoryId = $collection->getFirstItem()->getId();
		echo '<br/>'.$categories[0];
	}else{
		echo 'category not found';
	}
}


exit;


$username = 'admin_api';
$password = 'admin@123456#';
$action = 'update';

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
	$responce = array("status" =>'failed', "message"=>'Invalid username or API KEY');
	echo json_encode($responce); exit;
}

echo $sampleProductData['sku'] = "B/DCS3651LC";
$sampleProductData['weight'] = "1";

echo $product_data = str_replace("u0022","\\\\\"",json_encode( $sampleProductData,JSON_HEX_QUOT));

$productData = trim(preg_replace('/\s+/', ' ', $product_data));
$dataArr = json_decode($productData,true);

echo '<pre/>';
print_r($dataArr);
//exit;

/*function checkProductExist($sku){
	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	$product = $objectManager->get('Magento\Catalog\Model\Product');
	if($product) {
		$product->load($product->getIdBySku($sku));
		echo $product->getName();
	}
	echo 'product not found';
}

checkProductExist($sampleProductData['sku']); exit;*/

$product_url = $Baseurl."/rest/all/V1/products/".$sampleProductData['sku'];

$productData = json_encode(array('product' => $sampleProductData));
$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

$ch = curl_init();
curl_setopt($ch,CURLOPT_URL, $product_url);
curl_setopt($ch,CURLOPT_POSTFIELDS, $productData);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch); 
curl_close($ch);

$data = json_decode($response, TRUE);

echo '<pre/>';
print_r($data);

/*$query = "SELECT * FROM product_import_data WHERE id='170549'";
$result = mysqli_query($conn, $query);
if(mysqli_num_rows($result) > 0){
	

	while($product_import_data = mysqli_fetch_array($result)){
		
		echo $product_import_data['sku'];
		echo $product_import_data['product_data'] = trim(preg_replace('/\s+/', ' ', $product_import_data['product_data']));
		
		$dataArr = json_decode($product_import_data['product_data'],true);
		
		$productallDataArry = $dataArr['data'];
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$responce = array();

		foreach($productallDataArry as $key => $productallData){

			$sku = trim($productallData['sku']);
			$sku = str_replace(" ","",$sku);	
			echo $productallData['sku'] = $sku;
			
		}

	}

}*/