<?php
ini_set('display_errors', 1);

$arrContextOptions=array(
  "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
);  

echo file_get_contents("https://franciskirk.shop/pub/media/catalog/category/TURRET_NUT_3.jpg",false,stream_context_create($arrContextOptions));
//echo file_get_contents("https://i.imgur.com/tNdx8Yp.jpg");
exit;

error_reporting(0);
ini_set('display_errors', 1);
//$path = getcwd();
$path = "/home/default/html/api";
require_once($path.'/../app/bootstrap.php');
//require_once($path.'/ProductUpdateFunctions.php');

use \Magento\Framework\App\Bootstrap;

$Baseurl="http://77.68.127.9/index.php";

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend');

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

$log_writer = new \Zend\Log\Writer\Stream(BP . '/api/files/log/'.date('Ymd-H:i:s').'-product.import.log');
$log_logger = new \Zend\Log\Logger();
$log_logger->addWriter($log_writer);

$bad_writer = new \Zend\Log\Writer\Stream(BP . '/api/files/log/'.date('Ymd-H:i:s').'-product.import.bad');
$bad_logger = new \Zend\Log\Logger();
$bad_logger->addWriter($bad_writer);

/*$returnArr = array("status" =>'failed','sku'=> 'testsku', "message"=>"Product already exist.");
$error = ' '.json_encode($returnArr);

$returnArr = array("status" =>'sucess','sku'=> 'testdata', "message"=>"Product already exist.");
$sucess = ' '.json_encode($returnArr);

$bad_logger->info("---------------- START API Cron ".date('d/m/y H:i:s')." ----------------");
$bad_logger->info($error);
$bad_logger->info("---------------- END API Cron ".date('d/m/y H:i:s')." ----------------");

$log_logger->info("---------------- START API Cron ".date('d/m/y H:i:s')." ----------------");
$log_logger->info($sucess);
$log_logger->info("---------------- END API Cron ".date('d/m/y H:i:s')." ----------------");*/
echo 'done';

exit;

$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
$root_category_id = $storeManager->getStore()->getRootCategoryId();
$categoryFactory = $objectManager->get("\Magento\Catalog\Model\CategoryFactory");

$username = 'admin_api';
$password = 'admin@123456';
$action = 'update';

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

$adminToken =  json_decode($token);
if($adminToken == ""){
	$responce = array("status" =>'0', "message"=>'Invalid username or API KEY');
	echo json_encode($responce);exit;
}

$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

$categories_list = "SOCKET PRODUCTS/Test cat/cat";
$categories =  explode('/',$categories_list);


$category_ids = [];
foreach($categories as $category_name){
	$collection = $categoryFactory->create()->getCollection()->addAttributeToFilter('name',$category_name)->setPageSize(1);
	if ($collection->getSize()) {
	    $category_ids[] = $collection->getFirstItem()->getId();
	}else{
		$category_data['parent_id'] = $root_category_id;
		$category_data['name'] = $category_name;
		$category_data['level'] = 1;
		$category_data['isActive'] = 1;
		$category_data['include_in_menu'] = 1;
		$categoryData = json_encode(array('category' => $category_data));
		$category_url = $Baseurl."/rest/all/V1/categories/";
		
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $category_url);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $categoryData);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch); 
		curl_close($ch);

		$data = json_decode($response, TRUE);
		if(isset($data['id']) && $data['id'] != ''){
			$category_ids[] = $data['id'];
		}
		//echo "<pre>";print_r($data); exit;
		
	}
}

echo "<pre>";
print_r($category_ids);
?>