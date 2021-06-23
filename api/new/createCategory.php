<?php
exit;
//error_reporting(0);
ini_set('display_errors', 1);

$path = getcwd();
require_once($path.'/../../app/bootstrap.php');
//require_once('ProductCreateFunctions.php');

use \Magento\Framework\App\Bootstrap;

$Baseurl="http://77.68.127.9/index.php";
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend'); 

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
$root_category_id = $storeManager->getStore()->getRootCategoryId();

$categoryFactory = $objectManager->get("\Magento\Catalog\Model\CategoryFactory");

$attributeSets = $objectManager->get('\Magento\Catalog\Model\Product\AttributeSet\Options')->toOptionArray();
$visibilities = $objectManager->get('\Magento\Catalog\Model\Product\Visibility')->getAllOptions();

$username = 'admin_api';
$password = 'admin@123456';
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

$adminToken =  json_decode($token);
if($adminToken == ""){
	$responce = array("status" =>'0', "message"=>'Invalid username or API KEY');
	echo json_encode($responce); exit;
}

function getSubCategory($parentCategoryId,$category_name,$Baseurl,$adminToken){
	
	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	$categoryFactory = $objectManager->get("\Magento\Catalog\Model\CategoryFactory");
	
	$categoryId = 0;
	$current_category = $objectManager->create('Magento\Catalog\Model\Category')->load($parentCategoryId);
	$subcategories = $current_category->getChildrenCategories();
	$subcategoryIdList = [];
	foreach($subcategories as $subcategory){
		$subcategoryIdList[] = $subcategory->getId();
	}
	$collection = $categoryFactory->create()->getCollection();
	$collection->addAttributeToSelect('*');
	$collection->addIdFilter($subcategoryIdList);
	$collection->addAttributeToFilter('name',$category_name)->setPageSize(1);
	if($collection->getSize()){
		$categoryId = $collection->getFirstItem()->getId();
	}else{
		
		$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);
		
		$category_data['parent_id'] = $parentCategoryId;
		$category_data['name'] = $category_name;
		//$category_data['level'] = 1;
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

		$category_result = json_decode($response, TRUE);
		if(isset($category_result['id']) && $category_result['id'] != ''){
			$categoryId = $category_result['id'];
		}
	}
	return $categoryId;
}

$productallData = [];
$productData['categories'] = "SOCKET PRODUCTS/SOCKET CAP/Test/sub test,NUTS/TURRET NUT,category/sub category/sub sub category";
if((isset($productData['categories']) && $productData['categories'] != '')){
	$categoryList =  explode(',',$productData['categories']);
	$category_ids = [];
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
			$categoryId = $collection->getFirstItem()->getId();
			$category_ids[] = $categoryId;
			if(count($categories) > 1){
				$count = 1;
				foreach($categories as $category_name){
					if($count > 1){
						$subcategort_id = getSubCategory($categoryId,$category_name,$Baseurl,$adminToken);
						if($subcategort_id != 0){
							$categoryId = $subcategort_id;
							$category_ids[] = $categoryId;
						}
					}$count++;
				}
			}
			
		}else{
			$categoryId = $root_category_id;
			foreach($categories as $category_name){
				$subcategort_id = getSubCategory($categoryId,$category_name,$Baseurl,$adminToken);
				if($subcategort_id != 0){
					$categoryId = $subcategort_id;
					$category_ids[] = $categoryId;
				}
			}
		}
	}
	$productallData['prdCategories'] = $category_ids;
}

echo '<pre/>';
print_r($productallData); exit;
