<?php

error_reporting(0);

function getAdminToken($username, $password){
	//Authentication rest API magento2, get access token
	$ch = curl_init();
	$data = array("username" => $username, "password" => $password);
	$data_string = json_encode($data);
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
	return $adminToken;
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
		}else{
			$category = $objectManager->create('Magento\Catalog\Model\Category');
			$category->setPath($current_category->getPath());
			$category->setParentId($parentCategoryId);
			$category->setName($category_name);
			$category->setIsActive(true);
			$category->setIncludeInMenu(true);
			$category->setUrlKey(strtolower(str_replace(' ','-',$category_name)).'-'.time());
			$category->save();
			if($category){
				$categoryId =  $category->getId();
				//$objectManager->create('Magento\Catalog\Model\Category')->load($category->getId())->save();
			}
		}
	}
	return $categoryId;
}

function deactiveCatelogProduct($adminToken,$Baseurl,$sku,$objectManager){

	$result = "";
	 try {		 
			$product_id = $objectManager->get('Magento\Catalog\Model\Product')->getIdBySku($sku);
			$product = $objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
			$product->setStatus(0);	
			$product->save();
			$result = 1;
	 }
    catch (Exception $e) {
        $result = 0;
    }	
	if($result == 1){
		$returnArr = array("status" =>'1', "sku"=>$sku, "message"=>'Product inactived successfully');
		return $returnArr;
	}else{
		return $returnArr = array("status" =>'0', "sku"=>$sku, "message"=>'Error : Something was wrong');
	}
}

function updateproduct($adminToken,$Baseurl,$productallData){

	$prdSpecialPrice = isset( $productallData['prdSpecialPrice']) ? $productallData['prdSpecialPrice'] : '';
	$prdSpecialFromDate = isset( $productallData['prdSpecialFromDate']) ? $productallData['prdSpecialFromDate'] : '';
	$prdSpecialToDate = isset( $productallData['prdSpecialToDate']) ? $productallData['prdSpecialToDate'] : '';

	$description = $productallData['prdDesc'];
	$short_description = $productallData['prdShortDesc'];
	$special_price = $prdSpecialPrice;
	$special_from_date = $prdSpecialFromDate;
	$special_to_date = $prdSpecialToDate;
	$tax_class_id = $productallData['prdTaxId'];
	$store_id= $productallData['store_id'];

	$prd_imge = $productallData['updatePrdImg'];
	$mediaGalleryEntries = array();
	$fist_image_url = "";

	$qty  = isset( $productallData['prdQuantity']) ? $productallData['prdQuantity'] : '0';
	$prdInStock  = isset( $productallData['prdInStock']) ? $productallData['prdInStock'] : '0';
	$prdMngStock  = isset( $productallData['prdMngStock']) ? $productallData['prdMngStock'] : '0';
	$prdMinQty  = isset( $productallData['prdMinQty']) ? $productallData['prdMinQty'] : '0';
	$prdConfigMngStock  = isset( $productallData['prdConfigMngStock']) ? $productallData['prdConfigMngStock'] : '0';
	$prdConfigMinQty  = isset( $productallData['prdConfigMinQty']) ? $productallData['prdConfigMinQty'] : '0';
	$prdMinSaleQty  = isset( $productallData['prdMinSaleQty']) ? $productallData['prdMinSaleQty'] : '0';
	$prdConfigMinSaleQty  = isset( $productallData['prdConfigMinSaleQty']) ? $productallData['prdConfigMinSaleQty'] : '0';
	$prdMaxSaleQty  = isset( $productallData['prdMaxSaleQty']) ? $productallData['prdMaxSaleQty'] : '0';
	$prdConfigMaxSaleQty  = isset( $productallData['prdConfigMaxSaleQty']) ? $productallData['prdConfigMaxSaleQty'] : '0';

	$prdUrlPath  = isset( $productallData['prdUrlPath']) ? $productallData['prdUrlPath'] : '';
	if($prdUrlPath == ""){ $prdUrlPath = str_replace(" ","-",$productallData['prdName']); }

	$sku = $productallData['sku'];

	$prdUrlPath = str_replace(" ","-",$productallData['prdName']."-".$sku);

	$tierPricesFinalArr = array();
	$tierPriceArr = $productallData['prdGroupPrice'];

	if(sizeof($tierPriceArr) > 0){

			$tirepriceArr = array();
		    foreach($tierPriceArr as $PriceVariations){

				$group_id = isset($PriceVariations['groupId']) ?$PriceVariations['groupId'] : '';
				$group_Price = isset( $PriceVariations['price']) ? $PriceVariations['price'] : '';
				$group_qty = isset( $PriceVariations['group_qty']) ? $PriceVariations['group_qty'] : 1;

				if($group_id !='' && $group_qty !='' && $group_Price !='')
				{
					$tireprice = array (
							'customer_group_id' => $group_id,
							'qty' => $group_qty,
							'value' => $group_Price,
						);
					 $tirepriceArr[] = $tireprice;
				}
	        }	
		$tierPricesFinalArr = $tirepriceArr;

	}

	$media_url = $Baseurl.'/rest/V1/products/'.$sku.'/media';
	$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

	// check media gallery

	/*$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, $media_url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$galleryArr = curl_exec($ch); 
	$galleryArr = json_decode($galleryArr);*/

	/*if(sizeof($galleryArr) > 0){

		foreach($galleryArr as $gallery){
				$entryId = $gallery->id;
				$media_delete_url = $Baseurl.'/rest/V1/products/'.$sku.'/media/'.$entryId;
				$media_data = json_encode(array('sku'=>$sku,'entryId'=>$entryId));
				$ch = curl_init();
				curl_setopt($ch,CURLOPT_URL, $media_delete_url);
				curl_setopt($ch,CURLOPT_POSTFIELDS, $media_data);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				 $response = curl_exec($ch);
				curl_close($ch);
		}

	}*/

	if(count($prd_imge) > 0){

				foreach($prd_imge as $key=>$gallery_img){
						//$url = $gallery_img['newImg'];
						$url = $gallery_img;
						if($url != ""){
							
							$arrContextOptions=array(
							  "ssl"=>array(
							        "verify_peer"=>false,
							        "verify_peer_name"=>false,
							    ),
							); 

							$content = file_get_contents($url,false,stream_context_create($arrContextOptions));

							if($content != ""){
								 $image_name = $sku.'_'.time().'.jpg';
								 $mediaGalleryEntries[] =  array("id"=>0,"mediaType"=>"image","file"=>$image_name,"label"=>$productallData['prdName'],"position"=> 0,"disabled"=>false,
								  "types"=>array("image", "small_image", "thumbnail"),
								  "content"=>array("base64EncodedData"=>base64_encode($content),"type"=>"image/jpeg","name"=>$image_name));
							}

						}
				}

	   }

	$product_url = $Baseurl."/rest/".$store_id."/V1/products/".$sku;
	UpdateAttributesForCatelogProduct($productallData,$adminToken,$Baseurl);

	//$custom_attributes = array( array( 'attribute_code' => 'url_key', 'value' => $prdUrlPath));
	
	$category_ids = [];
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
	
	if(!empty($category_ids)){
		$custom_attributes[] = array( 'attribute_code' => 'category_ids', 'value' => $category_ids );
	}

	if($productallData['prdDesc'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'description', 'value' => $productallData['prdDesc'] );
	}
	
	if($productallData['prdShortDesc'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'short_description', 'value' => $productallData['prdShortDesc']);
	}

	if($tax_class_id != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'tax_class_id', 'value' =>$tax_class_id);
	}	

	if($prdSpecialPrice != "" && $prdSpecialPrice > 0){
		$custom_attributes[] = array( 'attribute_code' => 'special_price', 'value' => $prdSpecialPrice);
	}

    if($prdSpecialFromDate != ""){
		$custom_attributes[] = array( 'attribute_code' => 'special_from_date', 'value' => $prdSpecialFromDate);
	}

	if($prdSpecialToDate != ""){
		$custom_attributes[] = array( 'attribute_code' => 'special_to_date', 'value' => $prdSpecialToDate);
	}

	if($productallData['pack_qty'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'pack_qty', 'value' => $productallData['pack_qty'] );
	}

	if($productallData['supplier'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'supplier', 'value' => $productallData['supplier'] );
	}

	/*if($productallData['thread_form'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'thread_form', 'value' => $productallData['thread_form'] );
	}

	if($productallData['diameter'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'diameter', 'value' => $productallData['diameter'] );
	}

	if($productallData['length'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'length', 'value' => $productallData['length'] );
	}

	if($productallData['finish_colour'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'finish_colour', 'value' => $productallData['finish_colour'] );
	}

	if($productallData['grade'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'grade', 'value' => $productallData['grade'] );
	}*/

	if($productallData['type'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'type', 'value' => $productallData['type'] );
	}

	if($productallData['sub_type'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'sub_type', 'value' => $productallData['sub_type'] );
	}

	if($productallData['price_edited'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'price_edited', 'value' => $productallData['price_edited'] );
	}

	if($productallData['price_unit'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'price_unit', 'value' => $productallData['price_unit'] );
	}

	/*if($productallData['fkbrand'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'fkbrand', 'value' => $productallData['fkbrand'] );
	}

	if($productallData['size_option'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'size_option', 'value' => $productallData['size_option'] );
	}

	if($productallData['color'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'color', 'value' => $productallData['color'] );
	}*/

	if($productallData['barcode'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'barcode', 'value' => $productallData['barcode'] );
	}

	if($productallData['thread_type'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'thread_type', 'value' => $productallData['thread_type'] );
	}

	if($productallData['unit_price'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'unit_price', 'value' => $productallData['unit_price'] );
	}

	if($productallData['counter'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'counter', 'value' => $productallData['counter'] );
	}
	
	if($productallData['price_structure'] != "" ){
		$custom_attributes[] = array( 'attribute_code' => 'price_structure', 'value' => $productallData['price_structure'] );
	}

		$attribute_options_values = array();

		$additionalAttributes = $productallData['additionalAttributes'];

		$isNewAttributeCreated = 'No';

	   

	    if(sizeof($additionalAttributes) > 0){

		

			foreach($additionalAttributes as $addAtts){

					

					foreach($addAtts as $addAtt){

				

										$addAtt = (object)($addAtt); 

										

										$addAttCode = $addAtt->key;

										$AttValue = $addAtt->value;

										

										if($AttValue != ''){

										

											// GET Attorbute options

											$attribute_option_url = $Baseurl.'/rest/V1/products/attributes/'.$addAttCode.'/options';

											$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

											$ch = curl_init();

											curl_setopt($ch,CURLOPT_URL, $attribute_option_url);

											curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

											curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);

											curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

											curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

											$options = curl_exec($ch); 

											curl_close($ch);

											$options_list = json_decode($options, TRUE); 

											

											$isThisOptionAvailable = 'No';

											

											if(sizeof($options_list) > 0){

												foreach($options_list as $optionArr){

													

													 $optionlabel = $optionArr['label'];

													 $optionvalue = $optionArr['value'];

													if(strtolower($optionlabel) == strtolower($AttValue)){

														$custom_attributes[] = array( 'attribute_code' => $addAtt->key, 'value' =>$optionvalue);

														$isThisOptionAvailable = 'Yes';

													}

													

												}

												

											}

											

											// Add new Option for Attribute 

											if($isThisOptionAvailable == 'No'){

												

														$optiondata = array(

														   "label" => (string)$addAtt->value,

														   "sortOrder"=> 0,

														   "isDefault"=>false,

														);

														

														// POST Attribute options

														 $attribute_option = json_encode(array('option' => $optiondata,'attributeCode'=>$addAttCode));

														 $attribute_option_url = $Baseurl.'/rest/V1/products/attributes/'.$addAttCode.'/options';

													

														$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

														$ch = curl_init();

														curl_setopt($ch,CURLOPT_URL, $attribute_option_url);

														curl_setopt($ch,CURLOPT_POSTFIELDS, $attribute_option);

														curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

														curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);

														curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

														curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

														$options_id = curl_exec($ch); 

														curl_close($ch);

														$isNewAttributeCreated = 'Yes';

											}

											

										}

										

					}	

			}

	   }





		 if(sizeof($additionalAttributes) > 0 && $isNewAttributeCreated == 'Yes'){

		

				foreach($additionalAttributes as $addAtts){

						

						foreach($addAtts as $addAtt){

					

											$addAtt = (object)($addAtt); 

											

											$addAttCode = $addAtt->key;

											$AttValue = $addAtt->value;

											

											// GET Attorbute options

											$attribute_option_url = $Baseurl.'/rest/V1/products/attributes/'.$addAttCode.'/options';

											$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

											$ch = curl_init();

											curl_setopt($ch,CURLOPT_URL, $attribute_option_url);

											curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

											curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);

											curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

											curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

											$options = curl_exec($ch); 

											curl_close($ch);

											$options_list = json_decode($options, TRUE); 

											//echo "<pre>";print_r($options_list);exit;

											if(sizeof($options_list) > 0){

												foreach($options_list as $optionArr){

													

													$optionlabel = $optionArr['label'];

													$optionvalue = $optionArr['value'];

													if(strtolower($optionlabel) == strtolower($AttValue)){

														$custom_attributes[] = array( 'attribute_code' => $addAtt->key, 'value' =>$optionvalue);

													}

													

												}

												

											}

											

						}	

				}

	   }

	   

	$product_type = strtolower($productallData['prdType']);

			

	/*$sampleProductData = array(

		'sku'               => $productallData['sku'],

		'name'              => $productallData['prdName'],

		//'visibility'        => $productallData['prdVisibility'],

		'typeId'           => $product_type,

		'price'             => $productallData['prdPrice'],

		'status'            => $productallData['prdStatus'],

		'attributeSetId'  => $productallData['attributeSetId'],

		'weight'            => $productallData['prdWeight'],

		'custom_attributes' => $custom_attributes,

		'media_gallery_entries'=>$mediaGalleryEntries,   //mediaGalleryEntries

		'extension_attributes' => array(

				"stockItem"=>array(

					'qty'=>$qty,

					'isInStock'=>$productallData['prdInStock'],

					'manageStock'=>$prdInStock,

//					'min_qty'=>$prdMngStock,

//					'use_config_manage_stock' => $prdConfigMngStock,

//					'min_qty' => $prdMinQty,

//					'use_config_min_qty' => $prdConfigMinQty,

//					'min_sale_qty' => $prdMinSaleQty,

//					'use_config_min_sale_qty' => $prdConfigMinSaleQty,

//					'max_sale_qty' => $prdMaxSaleQty,

//					'use_config_max_sale_qty' => $prdConfigMaxSaleQty

				),

		),

		'tierPrices'=>$tierPricesFinalArr,

	);*/

	

	

	$sampleProductData['sku'] = $productallData['sku'];

	

	if(isset($productallData['prdName']) && $productallData['prdName'] != ''){
		$sampleProductData['name'] = $productallData['prdName'];
	}

	if(isset($productallData['prdVisibility']) && $productallData['prdVisibility'] != ''){
		$sampleProductData['visibility'] = $productallData['prdVisibility'];
	}

	if(isset($productallData['prdPrice']) && $productallData['prdPrice'] != ''){
		$sampleProductData['price'] = $productallData['prdPrice'];
	}

	if(isset($productallData['prdStatus']) && $productallData['prdStatus'] != ''){
		$sampleProductData['status'] = $productallData['prdStatus'];
	}

	if(isset($productallData['attributeSetId']) && $productallData['attributeSetId'] != ''){
		$sampleProductData['attributeSetId'] = $productallData['attributeSetId'];
	}

	if(isset($productallData['prdWeight']) && $productallData['prdWeight'] != ''){
		$sampleProductData['weight'] = $productallData['prdWeight'];
	}

	if(isset($custom_attributes) && !empty($custom_attributes)){
		$sampleProductData['custom_attributes'] = $custom_attributes;
	}

	if(isset($mediaGalleryEntries) && !empty($mediaGalleryEntries)){
		$sampleProductData['media_gallery_entries'] = $mediaGalleryEntries;
	}

	if(isset($tierPricesFinalArr) && !empty($tierPricesFinalArr)){
		$sampleProductData['tierPrices'] = $tierPricesFinalArr;
	}

	$stockItemData = [];
	if(isset($productallData['prdQuantity']) && $productallData['prdQuantity'] != ''){
		$stockItemData['qty'] = $productallData['prdQuantity'];
	}

	if(isset($productallData['prdInStock']) && $productallData['prdInStock'] != ''){
		$stockItemData['is_in_stock'] = $productallData['prdInStock'];
	}

	if(isset($productallData['prdMngStock']) && $productallData['prdMngStock'] != ''){
		$stockItemData['manage_stock'] = $productallData['prdMngStock'];
	}
	if(isset($stockItemData) && !empty($stockItemData)){
		$sampleProductData['extension_attributes']['stockItem'] = $stockItemData;
	}

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

	$data = json_decode($response, TRUE); //echo "<pre>";print_r($data); exit;
	$prd_id = isset($data['id']) ? $data['id'] : '';
	$prd_sku = isset($data['sku']) ? $data['sku'] : '';

	if(isset($mediaGalleryEntries) && !empty($mediaGalleryEntries)){
		
		$media_url = $Baseurl.'/rest/V1/products/'.$sku.'/media';
		$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $media_url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$galleryArr = curl_exec($ch); 
		$galleryArr = json_decode($galleryArr);

		if(!sizeof($galleryArr)){

			$sampleProductDataArr = array(
				'sku' => $productallData['sku'],
				'media_gallery_entries'=>$mediaGalleryEntries,
			);
			$productData = json_encode(array('product' => $sampleProductDataArr));
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
			//echo "<pre>update product media";print_r(json_decode($response, TRUE));exit;

		}
	
	}

	if($prd_id !=''){
		//$returnArr = array("status" =>'1', "product_id"=>$prd_id,"hosted_image_url"=>$url,"image_Posted_url"=>$url, "message"=>'Product updated successfully', 'new_ceated_imgs'=>$imagename,'NewImage'=>$imagename);
		$returnArr = array("status" =>'success', "product_id"=>$prd_id,"sku"=>$prd_sku, "message"=>'Product updated successfully');
		return $returnArr;
	}else{
		$returnArr = array("status" =>'failed','sku'=>$sampleProductData['sku'], "message"=>$data['message']);
		return $returnArr;
	}
}

function createproduct($adminToken,$product_url,$productallData,$Baseurl){

	

	

	$prdSpecialPrice = isset( $productallData['prdSpecialPrice']) ? $productallData['prdSpecialPrice'] : '';

	$prdSpecialFromDate = isset( $productallData['prdSpecialFromDate']) ? $productallData['prdSpecialFromDate'] : '';

	$prdSpecialToDate = isset( $productallData['prdSpecialToDate']) ? $productallData['prdSpecialToDate'] : '';

	

	

			$category_ids = $productallData['prdCategories'];

			$description = $productallData['prdDesc'];

			$short_description = $productallData['prdShortDesc'];

			$special_price = $prdSpecialPrice;

			$special_from_date = $prdSpecialFromDate;

			$special_to_date = $prdSpecialToDate;

			$tax_class_id = $productallData['prdTaxId'];

			$store_id = $productallData['store_id'];

		

	

	$prd_imge = $productallData['prdImg'];

	$mediaGalleryEntries = array();

	if(count($prd_imge) > 0){

			

				foreach($prd_imge as $gallery_img){

					

						$url = $gallery_img; 

						$content = file_get_contents($url);

						$image_name = $prd_sku.'_'.time().'.jpg';

						$mediaGalleryEntries[] =  array("id"=>0,"mediaType"=>"image","label"=>$productallData['prdName'],"position"=> 0,"disabled"=>false,

								  "types"=>array("image", "small_image", "thumbnail"),

								  "content"=>array("base64EncodedData"=>base64_encode($content),"type"=>"image/jpeg","name"=>$image_name));

						  

						

				}

	   }

		 

	

	

	$qty  = isset( $productallData['prdQuantity']) ? $productallData['prdQuantity'] : '0';

	$prdInStock  = isset( $productallData['prdInStock']) ? $productallData['prdInStock'] : '0';

	$prdMngStock  = isset( $productallData['prdMngStock']) ? $productallData['prdMngStock'] : '0';

	$prdMinQty  = isset( $productallData['prdMinQty']) ? $productallData['prdMinQty'] : '0';

	$prdConfigMngStock  = isset( $productallData['prdConfigMngStock']) ? $productallData['prdConfigMngStock'] : '0';

	$prdConfigMinQty  = isset( $productallData['prdConfigMinQty']) ? $productallData['prdConfigMinQty'] : '0';

	$prdMinSaleQty  = isset( $productallData['prdMinSaleQty']) ? $productallData['prdMinSaleQty'] : '0';

	$prdConfigMinSaleQty  = isset( $productallData['prdConfigMinSaleQty']) ? $productallData['prdConfigMinSaleQty'] : '0';

	$prdMaxSaleQty  = isset( $productallData['prdMaxSaleQty']) ? $productallData['prdMaxSaleQty'] : '0';

	$prdConfigMaxSaleQty  = isset( $productallData['prdConfigMaxSaleQty']) ? $productallData['prdConfigMaxSaleQty'] : '0';

	$tax_class_id  = isset( $productallData['prdTaxId']) ? $productallData['prdTaxId'] : '0';

	$product_type = $productallData['prdType'];

	$prdUrlPath  = isset( $productallData['prdUrlPath']) ? $productallData['prdUrlPath'] : '';

	if($prdUrlPath == ""){ $prdUrlPath = str_replace(" ","-",$productallData['prdName']); }

	$sku = $productallData['sku'];

	

	if($prdUrlPath == ""){ $prdUrlPath = str_replace(" ","-",$productallData['prdName']); }

	$prdUrlPath = str_replace(" ","-",$productallData['prdName']."-".$sku);

	

	UpdateAttributesForCatelogProduct($productallData,$adminToken,$Baseurl);

	

	$custom_attributes = array( array( 'attribute_code' => 'category_ids', 'value' => $productallData['prdCategories'] ),

			array( 'attribute_code' => 'description', 'value' => $productallData['prdDesc'] ),

			array( 'attribute_code' => 'short_description', 'value' => $productallData['prdShortDesc']),

			array( 'attribute_code' => 'url_key', 'value' => $prdUrlPath),

			array( 'attribute_code' => 'tax_class_id', 'value' =>$tax_class_id));



	if($prdSpecialPrice != "" && $prdSpecialPrice > 0){

		$custom_attributes[] = array( 'attribute_code' => 'special_price', 'value' => $prdSpecialPrice);

	}

    if($prdSpecialFromDate != ""){

		$custom_attributes[] = array( 'attribute_code' => 'special_from_date', 'value' => $prdSpecialFromDate);

	}

	if($prdSpecialToDate != ""){

		$custom_attributes[] = array( 'attribute_code' => 'special_to_date', 'value' => $prdSpecialToDate);

	}	

	

	$attribute_options_values = array();

		$additionalAttributes = $productallData['additionalAttributes'];

		$isNewAttributeCreated = 'No';

	  

	    if(sizeof($additionalAttributes) > 0){

		

			foreach($additionalAttributes as $addAtts){

					

					foreach($addAtts as $addAtt){

				

										$addAtt = (object)($addAtt); 

										

										$addAttCode = $addAtt->key;

										$AttValue = $addAtt->value;

										

										// GET Attorbute options

										$attribute_option_url = $Baseurl.'/rest/V1/products/attributes/'.$addAttCode.'/options';

										

										$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

										$ch = curl_init();

										curl_setopt($ch,CURLOPT_URL, $attribute_option_url);

										curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

										curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);

										curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

										curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

										$options = curl_exec($ch); 

										curl_close($ch);

										$options_list = json_decode($options, TRUE); 

										

										$isThisOptionAvailable = 'No';

										

										if(sizeof($options_list) > 0){

											foreach($options_list as $optionArr){

												

												$optionlabel = $optionArr['label'];

												$optionvalue = $optionArr['value'];

												if(strtolower($optionlabel) == strtolower($AttValue)){

													$custom_attributes[] = array( 'attribute_code' => $addAtt->key, 'value' =>$optionvalue);

													$isThisOptionAvailable = 'Yes';

												}

												

											}

											

										}

										

										// Add new Option for Attribute 

										if($isThisOptionAvailable == 'No'){

											

													$optiondata = array(

													   "label" => (string)$addAtt->value,

													   "sortOrder"=> 0,

													   "isDefault"=>false,

													);

													

													// POST Attribute options

													$attribute_option = json_encode(array('option' => $optiondata,'attributeCode'=>$addAttCode));

													$attribute_option_url = $Baseurl.'/rest/V1/products/attributes/'.$addAttCode.'/options';

													$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

													$ch = curl_init();

													curl_setopt($ch,CURLOPT_URL, $attribute_option_url);

													curl_setopt($ch,CURLOPT_POSTFIELDS, $attribute_option);

													curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

													curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);

													curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

													curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

													$options_id = curl_exec($ch); 

													curl_close($ch);

													$isNewAttributeCreated = 'Yes';

										}

										

					}	

			}

	   }





		 if(sizeof($additionalAttributes) > 0 && $isNewAttributeCreated == 'Yes'){

		

				foreach($additionalAttributes as $addAtts){

						

						foreach($addAtts as $addAtt){

					

											$addAtt = (object)($addAtt); 

											

											$addAttCode = $addAtt->key;

											$AttValue = $addAtt->value;

											

											// GET Attorbute options

											$attribute_option_url = $Baseurl.'/rest/V1/products/attributes/'.$addAttCode.'/options';

											$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

											$ch = curl_init();

											curl_setopt($ch,CURLOPT_URL, $attribute_option_url);

											curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

											curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);

											curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

											curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

											$options = curl_exec($ch); 

											curl_close($ch);

											$options_list = json_decode($options, TRUE); 

											

											if(sizeof($options_list) > 0){

												foreach($options_list as $optionArr){

													

													$optionlabel = $optionArr['label'];

													$optionvalue = $optionArr['value'];

													if(strtolower($optionlabel) == strtolower($AttValue)){

														$custom_attributes[] = array( 'attribute_code' => $addAtt->key, 'value' =>$optionvalue);

													}

													

												}

												

											}

											

						}	

				}

	   }

	   

	 $tierPricesFinalArr = array();

	 $tierPriceArr = $productallData['prdGroupPrice'];

		

		if(sizeof($tierPriceArr) > 0){

			

				$tirepriceArr = array();

				foreach($tierPriceArr as $PriceVariations){

					

					$group_id = isset($PriceVariations['groupId']) ?$PriceVariations['groupId'] : '';

					$group_Price = isset( $PriceVariations['price']) ? $PriceVariations['price'] : '';

					$group_qty = isset( $PriceVariations['group_qty']) ? $PriceVariations['group_qty'] : 1;

					

					if($group_id !='' && $group_qty !='' && $group_Price !='')

					{

						$tireprice = array (

								'customer_group_id' => $group_id,

								'qty' => $group_qty,

								'value' => $group_Price,

							);

						

						 $tirepriceArr[] = $tireprice;

					}

				}	

			$tierPricesFinalArr = $tirepriceArr;

		}  

	

	$product_type = strtolower($productallData['prdType']); 	

	

	$sampleProductData = array(

	    'sku'               => $productallData['sku'],

		'name'              => $productallData['prdName'],

		//'visibility'        => $productallData['prdVisibility'], /*'catalog',*/

		'typeId'           => $product_type,

		'price'             => $productallData['prdPrice'],

		'status'            => $productallData['prdStatus'],

		'attributeSetId'  => 4,

		'weight'            => $productallData['prdWeight'],

		'custom_attributes' => $custom_attributes,

		'extension_attributes' => array(

				"stockItem"=>array(

					'qty'=>$qty,

					'isInStock'=>$productallData['prdInStock'],

					'manageStock'=>$prdInStock,

				/*'min_qty'=>$prdMngStock,

				'use_config_manage_stock' => $prdConfigMngStock,

				'min_qty' => $prdMinQty,

				'use_config_min_qty' => $prdConfigMinQty,

				'min_sale_qty' => $prdMinSaleQty,

				'use_config_min_sale_qty' => $prdConfigMinSaleQty,

				'max_sale_qty' => $prdMaxSaleQty,

				'use_config_max_sale_qty' => $prdConfigMaxSaleQty*/

			),

		),

		'tierPrices'=>$tierPricesFinalArr

		

		

	);

	

	if(sizeof($mediaGalleryEntries) > 0){

		$sampleProductData['media_gallery_entries'] = $mediaGalleryEntries;

	}

	

	if(isset($productallData['prdVisibility']) && $productallData['prdVisibility'] != ''){

		$sampleProductData['visibility'] = $productallData['prdVisibility'];

	}

	

	/*if($group_id !='' && $group_qty !='' && $group_Price !='')

	{

		$tireprice =array(

			array (

				'customer_group_id' => $group_id,

				'qty' => $group_qty,

				'value' => $group_Price,

			),

		);

		$sampleProductData['tier_prices']=$tireprice;

		

	}*/

	

	$productData = json_encode(array('product' => $sampleProductData));

	$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

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

	

	

	//configurableProductOptions

	$associated_products = array();

	if($product_type == 'configurable'){

		

		SetAssociateProducts($sku,$productallData,$Baseurl, $adminToken);

			

	}

	

	if($prd_id !=''){

		

		$returnArr = array("status" =>'1', "product_id"=>$prd_id,"hosted_image_url"=>$url,"image_Posted_url"=>$url, "message"=>'Product created successfully', 'new_ceated_imgs'=>$imagename,'NewImage'=>$imagename);

		return $returnArr;

		

	

	}

	else{

		$returnArr = array("status" =>'0', "message"=>$data);

		return $returnArr;

	}

}

function createproductoption($adminToken,$product_option_url,$productallData){

	$productOptionData = $productallData['custom_option_fields'];

	$sku = $productallData['sku'];

	$ch = curl_init();

	

	if(sizeof($productOptionData) > 0){

		

			foreach($productOptionData as $productcustiomoption){

				$addtion_infos = $productcustiomoption['additional_fields'];

			

				$sampleProductData =array(

					'product_sku'        => $sku,

					'title'              => $productcustiomoption['title'],

					'type'               => $productcustiomoption['type'],

					'is_require'         => TRUE,

					'sort_order'         =>'1',

					'sku'               => $sku,

					'option_id'			=>'0',

					'values'			=>$addtion_infos

				);

				//print_r($sampleProductData);exit;

				//$sampleProductData['values']=$addtion_infos;

				

				$productData = json_encode(array('option' => $sampleProductData));

				$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

				curl_setopt($ch,CURLOPT_URL, $product_option_url);

				curl_setopt($ch,CURLOPT_POSTFIELDS, $productData);

				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

				curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);

				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				$response = curl_exec($ch);

				$data = json_decode($response, TRUE);

			}

	}	

	curl_close($ch);

	

	

}





function downloadproduimage($url,$prd_sku){

	$prd_sku = str_replace(' ', '-', $prd_sku);

	$prd_sku = strtolower($prd_sku);

	$image_name = $prd_sku.'.jpg';

	$imagepath = getcwd().'/../pub/media/import/'.$image_name;

	$content = file_get_contents($url);

	file_put_contents($imagepath, $content);

	return $image_name;



}





function createAttributeDetailsByCode($attributeCode, $Baseurl, $adminToken){

					

					// GET Attorbute options

					$attData = json_encode(array("attributeSetId"=>0,"attributeGroupId"=>0,"attributeCode"=>$attributeCode,"sortOrder"=>0));

					$attribute_url = $Baseurl.'/rest/V1/products/attributes';

					$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

					$ch = curl_init();

					curl_setopt($ch,CURLOPT_URL, $attribute_url);

					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

					curl_setopt($ch,CURLOPT_POSTFIELDS, $attData);

					curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);

					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					$options = curl_exec($ch); 

					curl_close($ch);

					$res = json_decode($options, TRUE); 

					

		return 1;

}



function getAttributeDetailsByCode($attributeCode){

	

	$attributeCode = $attributeCode;

	

	$entityType = 'catalog_product';

	$attributeId = 0; 

	$objectManager =  \Magento\Framework\App\ObjectManager::getInstance();

	 

	try{

			//$attributeInfo = $objectManager->get(\Magento\Eav\Model\Entity\Attribute::class)->loadByCode($entityType, $attributeCode);

			//$attributeId = $attributeInfo->getAttributeId();	

			$attributeId = $objectManager->get(\Magento\Eav\Model\ResourceModel\Entity\Attribute::class)->getIdByCode($entityType, $attributeCode);

			if(!$attributeId){

				$attributeId = 0;

			}

			

	}catch(Exception $e){

		

	}

	

	return $attributeId;

}





function getProductIDfromSku($sku){

	

	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

	$product = $objectManager->get('Magento\Catalog\Model\Product');

	$pid = $product->getIdBySku($sku);

	

	if($pid){

		return $pid; 

	}

	return 0;



}

function getAttributeIdFromAttrOptions($addAttCode, $AttValue,$Baseurl, $adminToken){

		$return_arr = array();

		$attribute_id = getAttributeDetailsByCode($addAttCode);

		

		if($attribute_id > 0){

			

					// GET Attorbute options

					$attribute_option_url = $Baseurl.'/rest/V1/products/attributes/'.$addAttCode.'/options';

					$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

					$ch = curl_init();

					curl_setopt($ch,CURLOPT_URL, $attribute_option_url);

					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

					curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);

					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					$options = curl_exec($ch); 

					curl_close($ch);

					$options_list = json_decode($options, TRUE); 

					

						if(sizeof($options_list) > 0){

							foreach($options_list as $optionArr){

								

								$optionlabel = $optionArr['label'];

								$optionvalue = $optionArr['value'];

								if(strtolower($optionlabel) == strtolower($AttValue)){

									//$custom_attributes[] = array( 'attribute_code' => $addAtt->key, 'value' =>$optionvalue);

									$option_id = $optionvalue;

								}

								

							}

							

						}

						$return_arr['attribute_id'] = $attribute_id;

						$return_arr['option_id'] = $option_id;

			}

			

		return $return_arr;

}





function UpdateAttributesForCatelogProduct($prdData,$adminToken,$Baseurl){

	

   $sku = $prdData['sku'];

		

	foreach($prdData['additionalAttributes'] as $addAtts){

		

			foreach($addAtts as $addAtt){

				

										$addAtt = (object)($addAtt); 

										$addAttCode = $addAtt->key;

										$addtributeID = getAttributeDetailsByCode($addAttCode);

										

										if($addtributeID <= 0){

											

											//echo $addAttCode.' data'; exit;

										

												/*$label = array (

												   array(

													"store_id" => array("0"),

													"value" => $addAtt->value

													)

												);*/

												$label = array (

												   array(

													"store_id" => 0,

													"label" => $addAtt->value

													)

												);

												

												$options[] = array(

												   "label" => $addAtt->value,

												   "value" => $addAtt->value,

												   "sort_order" => "1",

												   "is_default" => true,

												   "store_labels"=>$label 

												);

												//$options[] = array("label" => $addAtt->value);

												//print_r($options);

												$attData = array(

												   "is_wysiwyg_enabled" =>false,	

												   "is_html_allowed_on_front" =>false,

												   "used_for_sort_by" =>false,

												   "is_filterable" =>false,

												   "is_filterable_in_search" =>false,

												   "is_used_in_grid" =>false,

												   "is_visible_in_grid" =>false,

												   "is_filterable_in_grid" =>false,

												   "position" =>"0",

												   "apply_to" =>["simple","grouped","configurable","virtual","bundle","downloadable"],

												   "is_searchable" =>"1",

												   "is_visible_in_advanced_search" =>"1",

												   "is_comparable" =>"1",

												   "is_used_for_promo_rules" =>"0",

												   "is_visible_on_front" =>"0",

												   "used_in_product_listing" =>"0",

												   "is_visible" =>true,

												   "scope" => "global",

												   "attribute_id" => 0,

												   "attribute_code" => $addAtt->key,

												   "frontend_input" => "select",

												   "entity_type_id" => "4",

												   "is_required" => false,

												   "options"=> $options,

												   "is_user_defined" => true,

												   "default_frontend_label" => $addAtt->key,

												   "frontend_labels" => $label,

												   "backend_type" => "int",

												   "default_value" => "",

												   "is_unique" => "0",

												   "validation_rules" => [],

												   "custom_attributes" => [["attribute_code"=>$addAtt->key,"value"=>$addAtt->value]],

												   //"source_model" => "Magento\Eav\Model\Entity\Attribute\Source\Table",

												);

												

												/*$attData = array(

												   "attributeSetId" =>"0",	

												   "attributeGroupId" =>"0",

												   "attributeCode" => $addAtt->key,

												   "frontend_input" => "select",

												   "sortOrder"=>"0",

												   "scope" => "global",

												   "defaultValue" => "0",

												   "isUnique" => 0,

												   "isRequired" => 0,

												   "applyTo" => array("simple","grouped","configurable","virtual","bundle","downloadable"),

												   //"isConfigurable" => 1,

												   "isSearchable" => 1,

												   "isVisible_in_advanced_search" => 1,

												   "isComparable" => 1,

												   "isUsedForPromoRules" => 1,

												   "isVisibleOnFront" => 1,

												   "usedInProductListing" => 1,

												   "additionalFields" => array(),

												   "frontendLabel" => array(array("store_id" => "0", "label" => $addAtt->key)),

												   "options"=> $options,

												   "frontendLabels"=> $label,

												);*/

												

												$att_post_url = $Baseurl. "/rest/V1/products/attributes";

												

												$attData = json_encode(array("attribute"=>$attData));

												

												$ch = curl_init();

											

												$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);

												curl_setopt($ch,CURLOPT_URL, $att_post_url);

												curl_setopt($ch,CURLOPT_POSTFIELDS, $attData);

												curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

												curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);

												curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

												curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

												$response = curl_exec($ch);

											 $data = json_decode($response, TRUE);

											 

											 try{

											 	

											 if(isset($data['attribute_code'])){

												 $attributeCode = $data['attribute_code'];

												 $attributeGroup = 'General';

												 $attributeSetId = 9;

	        									 $sortOrder = 999;

	        									 $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

	        									 $config = $objectManager->get(\Magento\Catalog\Model\Config::class);

	        									 $attributeGroupId = $config->getAttributeGroupId($attributeSetId, $attributeGroup);

	        									 $attributeManagement = $objectManager->get(\Magento\Eav\Api\AttributeManagementInterface::class);

	        									 $attributeManagement->assign(

											            'catalog_product',

											            $attributeSetId,

											            $attributeGroupId,

											            $attributeCode,

											            $sortOrder

											       );

	        									 /*$productAttributeManagement = $objectManager->get("\Magento\Catalog\Api\ProductAttributeManagementInterface");

	        									 $productAttributeManagement->assign($attributeSetId, $attributeGroupId, $attributeCode, $sortOrder);*/

        									 }

        									 }catch (Exception $e) {

									            echo $e->getMessage(); exit;

									        }

										}

											 

							

			}

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