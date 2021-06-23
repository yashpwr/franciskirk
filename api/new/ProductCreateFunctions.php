<?php
ini_set('display_errors', 1);

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

function createproduct($adminToken,$product_url,$productallData,$Baseurl){
	
	$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);
	
	$category_ids = [];
	/*if($productallData['prdCategories'] != "" ){
		$category_ids = $productallData['prdCategories'];
	}*/
	if(!empty($productallData['prdCategories'])){
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$root_category_id = $storeManager->getStore()->getRootCategoryId();
		$categoryFactory = $objectManager->get("\Magento\Catalog\Model\CategoryFactory");
		
		$categoryList =  $productallData['prdCategories'];
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
		
	}
	
	$custom_attributes = [];
	/*if(isset($productallData['prdCategories'])){
		$custom_attributes = array( 
			array( 'attribute_code' => 'category_ids', 'value' => $productallData['prdCategories'] )
		);
	}*/
	if(!empty($category_ids)){
		$custom_attributes[] = array( 'attribute_code' => 'category_ids', 'value' => $category_ids );
	}
	
	$product_type = strtolower($productallData['prdType']); 	

	$sampleProductData = array(
	    'sku'               => $productallData['sku'],
		'name'              => $productallData['prdName'],
		'visibility'        => $productallData['prdVisibility'], /*'catalog',*/
		'typeId'           => $product_type,
		'status'            => $productallData['prdStatus'],
		'attributeSetId'  => $productallData['attributeSetId'],
		'custom_attributes' => $custom_attributes
	);

	/*if(isset($productallData['prdVisibility']) && $productallData['prdVisibility'] != ''){
		$sampleProductData['visibility'] = $productallData['prdVisibility'];
	}*/

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

	$prd_id = isset($data['id']) ? $data['id'] : '';
	$prd_sku = isset($data['sku']) ? $data['sku'] : '';

	if($prd_id !=''){
		$returnArr = array("status" =>'success', "product_id"=>$prd_id,'sku'=>$prd_sku, "message"=>'Product created successfully');
		return $returnArr;
	}else{
		$returnArr = array("status" =>'failed','sku'=>$productallData['sku'], "message"=>$data['message']);
		return $returnArr;
	}
}

function checkProductExist($sku){
	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	$product = $objectManager->get('Magento\Catalog\Model\Product');
	if($product->getIdBySku($sku)) {
		return 1; 
	}
	return 0;
}

?>