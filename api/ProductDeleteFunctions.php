<?php
function checkProductExist($sku){
	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	$product = $objectManager->get('Magento\Catalog\Model\Product');
	if($product->getIdBySku($sku)) {
		return 1; 
	}
	return 0;
}

function deleteproduct($sku,$adminToken,$Baseurl){
	
	$product_url = $Baseurl. "/rest/V1/products/".$sku;

	$productData = json_encode(array('sku'=>$sku));
	$ch = curl_init();

	$setHaders = array('Content-Type:application/json','Authorization:Bearer '.$adminToken);
	curl_setopt($ch,CURLOPT_URL, $product_url);
	curl_setopt($ch,CURLOPT_POSTFIELDS, $productData);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	curl_setopt($ch, CURLOPT_HTTPHEADER, $setHaders);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	
	$data = json_decode($response, TRUE); 
	
	if($data){
		$returnArr = array("status" =>'success', "sku"=>$sku, "message"=>'Product deleted successfully');
		return $returnArr;
	}else{
		return $returnArr = array("status" =>'failed', "sku"=>$sku, "message"=>'Error : Something was wrong');
	}
	
}