<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Rokanthemes\RokanBase\Controller\Checkout\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends \Magento\Checkout\Controller\Cart\Add
{
   
    /**
     * Add product to shopping cart action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
	protected $_messege = '';
	protected $_result = [];
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();
        try {
        	
        	if(isset($params['add_unit_price']) && isset($params['unit_qty'])){
				$params['qty'] = $params['unit_qty'];
			}
        	
            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocale()]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                return $this->goBack();
            }
            
            $serializer = $this->_objectManager->get('\Magento\Framework\Serialize\SerializerInterface');
			$eavConfig = $this->_objectManager->get('\Magento\Eav\Model\Config');
			$priceHelper = $this->_objectManager->create('Magento\Framework\Pricing\Helper\Data');
            
            if(isset($params['add_pack_qty']) && $product->getAttributeSetId() == 9 && $product->getData('pack_qty') != ''){
				$pack_qty = $eavConfig->getAttribute('catalog_product', 'pack_qty');
            	$additionalOptions['print_style'] = [
		            'label' => $pack_qty->getData('frontend_label'),
		            //'value' => $product->getData('pack_qty')
		            'value' => 100
		        ];
				$product->addCustomOption('additional_options', $serializer->serialize($additionalOptions));
			}
			
			if(isset($params['add_unit_price']) && $product->getAttributeSetId() == 9 && $product->getData('unit_price') != ''){
				/*$product_unit_price = $priceHelper->currency($product->getData('unit_price'), true, false);
				$unit_price = $eavConfig->getAttribute('catalog_product', 'unit_price');
            	$additionalOptions['print_style'] = [
		            'label' => $unit_price->getData('frontend_label'),
		            'value' => $product_unit_price
		        ];*/
		        $pack_qty = $eavConfig->getAttribute('catalog_product', 'pack_qty');
            	$additionalOptions['print_style'] = [
		            'label' => $pack_qty->getData('frontend_label'),
		            'value' => 1
		        ];
				$product->addCustomOption('additional_options', $serializer->serialize($additionalOptions));
			}
			
			if(!$product->getPriceStructure() && $product->getData('pack_qty') != ''){
				$pack_qty = $eavConfig->getAttribute('catalog_product', 'pack_qty');
            	$additionalOptions['print_style'] = [
		            'label' => $pack_qty->getData('frontend_label'),
		            'value' => $product->getData('pack_qty')
		        ];
				$product->addCustomOption('additional_options', $serializer->serialize($additionalOptions));
			}

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }
			
			if(isset($params['add_unit_price']) && $product->getAttributeSetId() == 9 && $product->getData('unit_price') != ''){
		        $lastItem = null;
		        $items = $this->cart->getQuote()->getAllItems();
		        $total_item = count($items);
		        $count = 1;
		        foreach ($items as $item){
		            if($count == $total_item){
						$lastItem = $item;
					}
					$count++;
		        }
		        if ($lastItem){
		        	if(isset($params['add_unit_price']) && $product->getId() == $lastItem->getProductId() && $product->getData('unit_price') != ''){
		        		
		        		$product_unit_price = $product->getData('unit_price');
						$customerSession = $this->_objectManager->get('Magento\Customer\Model\SessionFactory')->create();
						if($customerSession->isLoggedIn()){
							$customer_id = $customerSession->getCustomer()->getId();
							$customerPriceData = $this->_objectManager->get('\MageWorx\CustomerPrices\Model\ResourceModel\CustomerPrices')->getCustomerProductPrice($customer_id,$product->getId());
							if(!empty($customerPriceData)){
								if($customerPriceData['unit_price_value'] != '' && $customerPriceData['unit_price_type'] == '1' ){
									if($customerPriceData['unit_price_sign'] == '+'){
										$product_unit_price = $product->getData('unit_price') + $customerPriceData['unit_price_value'];
									}elseif($customerPriceData['unit_price_sign'] == '-'){
										$product_unit_price = $product->getData('unit_price') - $customerPriceData['unit_price_value'];
									}else{
										$product_unit_price = $customerPriceData['unit_price_value'];
									}
								}elseif($customerPriceData['unit_price_value'] != '' && $customerPriceData['unit_price_type'] == '2'){
									if($customerPriceData['unit_price_sign'] == '+'){
										$product_unit_price = ($product->getData('unit_price') / 100) * $customerPriceData['unit_price_value'];
										$product_unit_price = $product->getData('unit_price') + $product_unit_price;
									}elseif($customerPriceData['unit_price_sign'] == '-'){
										$product_unit_price = ($product->getData('unit_price') / 100) * $customerPriceData['unit_price_value'];
										$product_unit_price = $product->getData('unit_price') - $product_unit_price;
									}else{
										$product_unit_price = ($product->getData('unit_price') / 100) * $customerPriceData['unit_price_value'];
									}
								}						
							}
						}
		        		
						//Set custom price
				       /* $lastItem->setCustomPrice($product->getData('unit_price'));
				        $lastItem->setOriginalCustomPrice($product->getData('unit_price'));
				        $lastItem->getProduct()->setIsSuperMode(true);*/
				        $lastItem->setCustomPrice($product_unit_price);
				        $lastItem->setOriginalCustomPrice($product_unit_price);
				        $lastItem->getProduct()->setIsSuperMode(true);
					}
		        }
		        
		    }
		    
		    
		    if($product->getPriceStructure() && isset($params['add_unit_price']) && $product->getData('unit_price') != '' && $product->getData('pack_qty') != ''){
		        $lastItem = null;
		        $items = $this->cart->getQuote()->getAllItems();
		        $total_item = count($items);
		        $count = 1;
		        foreach ($items as $item){
		            if($count == $total_item){
						$lastItem = $item;
					}
					$count++;
		        }
		        if ($lastItem){
		        	if($product->getId() == $lastItem->getProductId() && $product->getData('unit_price') != '' && $product->getData('pack_qty') != ''){
		        		$product_unit_price = $product->getData('unit_price');
						$customerSession = $this->_objectManager->get('Magento\Customer\Model\SessionFactory')->create();
						if($customerSession->isLoggedIn()){
							$customer_id = $customerSession->getCustomer()->getId();
							$customerPriceData = $this->_objectManager->get('\MageWorx\CustomerPrices\Model\ResourceModel\CustomerPrices')->getCustomerProductPrice($customer_id,$product->getId());
							if(!empty($customerPriceData)){
								if($customerPriceData['unit_price_value'] != '' && $customerPriceData['unit_price_type'] == '1' ){
									if($customerPriceData['unit_price_sign'] == '+'){
										$product_unit_price = $product->getData('unit_price') + $customerPriceData['unit_price_value'];
									}elseif($customerPriceData['unit_price_sign'] == '-'){
										$product_unit_price = $product->getData('unit_price') - $customerPriceData['unit_price_value'];
									}else{
										$product_unit_price = $customerPriceData['unit_price_value'];
									}
								}elseif($customerPriceData['unit_price_value'] != '' && $customerPriceData['unit_price_type'] == '2'){
									if($customerPriceData['unit_price_sign'] == '+'){
										$product_unit_price = ($product->getData('unit_price') / 100) * $customerPriceData['unit_price_value'];
										$product_unit_price = $product->getData('unit_price') + $product_unit_price;
									}elseif($customerPriceData['unit_price_sign'] == '-'){
										$product_unit_price = ($product->getData('unit_price') / 100) * $customerPriceData['unit_price_value'];
										$product_unit_price = $product->getData('unit_price') - $product_unit_price;
									}else{
										$product_unit_price = ($product->getData('unit_price') / 100) * $customerPriceData['unit_price_value'];
									}
								}						
							}
						}
						
						//Set custom price
						/*$custom_price = $product->getData('unit_price')*$product->getData('pack_qty');
				        $lastItem->setCustomPrice($custom_price);
				        $lastItem->setOriginalCustomPrice($custom_price);
				        $lastItem->getProduct()->setIsSuperMode(true);*/
				        $custom_price = $product_unit_price*$product->getData('pack_qty');
				        $lastItem->setCustomPrice($custom_price);
				        $lastItem->setOriginalCustomPrice($custom_price);
				        $lastItem->getProduct()->setIsSuperMode(true);
					}
		        }
		        
		    }
			
			$this->cart->save();

            /**
             * @todo remove wishlist observer \Magento\Wishlist\Observer\AddToCart
             */
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if (!$this->cart->getQuote()->getHasError()) {
                    $message = __(
                        'You added %1 to your shopping cart.',
                        $product->getName()
                    );
					$this->_messege = $message;
                    $this->messageManager->addSuccessMessage($message);
                }
				$this->_result['html'] = $this->_getHtmlResponeAjaxCart($product);
                return $this->goBack(null, $product);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNotice(
                    $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addError(
                        $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($message)
                    );
                }
				$messages = $this->messageManager->getMessages(true); 
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);
			$mes = $e->getMessage();
			$re_array['html'] = $this->_getHtmlResponeAjaxCartMsg($mes);
			echo json_encode($re_array);
			die;
            // if (!$url) {
                // $cartUrl = $this->_objectManager->get('Magento\Checkout\Helper\Cart')->getCartUrl();
                // $url = $this->_redirect->getRedirectUrl($cartUrl);
            // }
            return $this->goBack($url);

        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
			$re_array['html'] = $this->_getHtmlResponeAjaxCartMsg(__('We can\'t add this item to your shopping cart right now.'));
			echo json_encode($re_array);
			die;
            return $this->goBack();
        }
    }

    /**
     * Resolve response
     *
     * @param string $backUrl
     * @param \Magento\Catalog\Model\Product $product
     * @return $this|\Magento\Framework\Controller\Result\Redirect
     */
    protected function goBack($backUrl = null, $product = null)
    {
        if (!$this->getRequest()->isAjax()) {
            return parent::_goBack($backUrl);
        }
		
        $result = $this->_result;

        if ($backUrl || $backUrl = $this->getBackUrl()) {
            $result['backUrl'] = $backUrl;
        } else {
            if ($product && !$product->getIsSalable()) {
                $result['product'] = [
                    'statusText' => __('Out of stock')
                ];
            }
        }

        $this->getResponse()->representJson(
            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
        );
    }
	protected function _getHtmlResponeAjaxCartMsg($msg)
	{
		$html = '<div class="popup_avaiable" style="color: red;">'.$msg.'<br>
				</div>';
		return $html;
	}
	protected function _getHtmlResponeAjaxCart($product)
	{
		$message = __('You added <a href="'. $product->getProductUrl() .'">%1</a> to your shopping cart.',
                        $product->getName()
                    );
		$html = '<div class="popup_avaiable">'.$message.'<br>
					<div class="action_button">
						<ul>
							<li>
								<button title="'. __('Continue Shopping') . '" class="button btn-continue" onclick="jQuery.fancybox.close();">'. __('Continue Shopping') . '</button>
							</li>
							<li>
								<a title="Checkout" class="button btn-viewcart" href="'. $this->_url->getUrl('checkout/cart') .'"><span>'. __('View cart &amp; checkout'). '</span></a>
							</li>
						</ul>
					</div>
				</div>';
		return $html;
	}
}
