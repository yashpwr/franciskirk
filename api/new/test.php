<?php
exit;
//error_reporting(0);
ini_set('display_errors', 1);
//$path = getcwd();
$path = "/home/default/html/api/new";
require_once($path.'/../../app/bootstrap.php');
require_once($path.'/ProductCreateFunctions.php');

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
$action = 'create';

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
	echo json_encode($responce); exit;
}

$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

$productallData['sku'] = 'testcreate';
$productallData['prdName'] = 'test';
$productallData['prdVisibility'] = 4;
//$productallData['prdCategories'][] = 1563;

$custom_attributes = [];
/*if(!empty($productallData['prdCategories'])){
	$custom_attributes[] = array( 'attribute_code' => 'category_ids', 'value' => $productallData['prdCategories'] );
}*/

$prdUrlPath = str_replace(" ","-",$productallData['prdName']."-".$productallData['sku']);
$custom_attributes[] = array( 'attribute_code' => 'url_key', 'value' => $prdUrlPath );

$custom_attributes[] = array( 'attribute_code' => 'price_structure', 'value' => '1' );

$sampleProductData = array(
    'sku'               => $productallData['sku'],
	'name'              => $productallData['prdName'],
	'visibility'        => $productallData['prdVisibility'],
	'typeId'           => 'simple',
	'status'            => 1,
	'attributeSetId'  => 9,
	'custom_attributes' => $custom_attributes
);

$product_url = $Baseurl. "/rest/all/V1/products";

$productData = json_encode(array('product' => $sampleProductData));
	
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, $product_url);
	curl_setopt($ch,CURLOPT_POSTFIELDS, $productData);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch); 
	curl_close($ch);
	$data = json_decode($response, TRUE);
	
	echo '<pre/>';
	print_r($data); exit;

	$prd_id = isset($data['id']) ? $data['id'] : '';
	$prd_sku = isset($data['sku']) ? $data['sku'] : '';
