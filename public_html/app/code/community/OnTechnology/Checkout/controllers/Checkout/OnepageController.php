<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

include_once('Mage/Checkout/controllers/OnepageController.php');

class OnTechnology_Checkout_Checkout_OnepageController extends Mage_Checkout_OnepageController
{


	 /**
     * Checkout page
     */
    public function indexAction()
    {
        if (!Mage::helper('checkout')->canOnepageCheckout()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The onepage checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }
        $quote = $this->getOnepage()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }	
		
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }
		        
		$this->createtemplateAction();
		$redirectUrl = $this->sendEziAction();
		if ($redirectUrl) {
				$this->getResponse()->setRedirect($redirectUrl);
				return;
            }
		parent::indexAction();
    }

/*   FUNCTION TO CALL THE URL   */

public function sendEziAction(){
		$quote = $this->getOnepage()->getQuote();
		$items = $quote->getAllVisibleItems();
		$billingAddress = $quote->getBillingAddress();
		$shippingAddress = $quote->getShippingAddress();
		$model = Mage::getModel('catalog/product'); //getting product model
		$itemidx = 0;
		$discount_amount = 0;
		$paramhash = array();
		
		foreach ($items as $ite)
		{
		$item = $ite->toArray();
		
		 $helper = Mage::helper('catalog/product_configuration');         
		 if($item['product_type'] == 'configurable')
		 $options = $helper->getConfigurableOptions($ite);
		 else
		 $options = $helper->getCustomOptions($ite);
		 
		
				$_product = $model->load($item['product_id']); //getting product object for particular product id				
				
				$paramhash["PRODUCTQUANTITY(" . $itemidx . ")"] = $item['qty'];
				$paramhash["PRODUCTCODE(" . $itemidx . ")"] = $item['sku'];
				$paramhash["PRODUCTNAME(" . $itemidx . ")"] = $item['name'];
				$paramhash["PRODUCTPRICEINCTAX(" . $itemidx . ")"] = $item['price_incl_tax'];
				$paramhash["PRODUCTID(" . $itemidx . ")"] = $item['product_id'];
				$paramhash["PRODUCTIMAGEURL(" . $itemidx . ")"] = $_product->getImageUrl();
				$attridx = 0;
				foreach($options as $option)
				 {
					$paramhash["PRODUCTATTRIBUTE(".$itemidx.")(".$attridx.")"] = $option['label'];
					$paramhash["PRODUCTATTRIBUTEVALUE(".$itemidx.")(".$attridx.")"] = $option['value'];
					$attridx++;
				 }
				
				if($item['discount_amount'])
				{
					$discount_amount += $item['discount_amount'];
				}
			
			$itemidx = $itemidx + 1;
		}
			if($discount_amount > 0)
			{
			$paramhash["PRODUCTQUANTITY(" . $itemidx . ")"] = 1;
			$paramhash["PRODUCTNAME(" . $itemidx . ")"] = 'Discounts';
			$paramhash["PRODUCTCODE(" . $itemidx . ")"] = 'Discounts';
			$paramhash["PRODUCTPRICEINCTAX(" . $itemidx . ")"] = -$discount_amount;
			}
			$paramhash["COUPONACTIVE"] = 1;
			$couponCode = (string) $quote->getCouponCode();	
			
			if ($couponCode) {
				$paramhash["COUPONCODE"] = $couponCode;
			}
			
			
			
			$paramhash["CURRENCY"] = Mage::app()->getStore()-> getCurrentCurrencyCode();
			$paramhash["PRODUCTTAX"] = 'GST';
			$paramhash["CUSTOM"] = $quote->getId();
			
			
			// Check if costomer is logged in
				if(Mage::getSingleton('customer/session')->isLoggedIn())
				{
				$customer_staff = 0;
				// Get group Id
				$groupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
				$group = Mage::getModel('customer/group')->load($groupId);
				if($group->getCustomerGroupCode() == 'Staff')
				{
					foreach (Mage::getSingleton('customer/session')->getCustomer()->getAddresses() as $address) {            
					$paramhash["BILLNAME(".$customer_staff.")"] = $address->getName();
					$paramhash["BILLCOMPANY(".$customer_staff.")"] = $address->getCompany();
					$paramhash["BILLEMAIL(".$customer_staff.")"] = $quote->getCustomer()->getEmail();
					$paramhash["BILLPHONE(".$customer_staff.")"] = $address->getTelephone();
					$paramhash["BILLFAX(".$customer_staff.")"] = $address->getFax();
					$paramhash["BILLADDRESS1(".$customer_staff.")"] = $address->getStreet1();
					$paramhash["BILLADDRESS2(".$customer_staff.")"] = $address->getStreet2();
					$paramhash["BILLADDRESS3(".$customer_staff.")"] = $address->getStreet3();
					$paramhash["BILLPLACE(".$customer_staff.")"] = $address->getCity();
					$paramhash["BILLDIVISION(".$customer_staff.")"] = $address->getRegion();
					$paramhash["BILLPOSTALCODE(".$customer_staff.")"] = $address->getPostcode();
					$paramhash["BILLCOUNTRYCODE(".$customer_staff.")"] = $address->getCountry();
					$customer_staff++;
					}
				}
				}
			
			
			
				
			if($billingAddress->getEmail())
			{			
			$paramhash["BILLNAME"] = $billingAddress->getName();
			$paramhash["BILLCOMPANY"] = $billingAddress->getCompany();
			$paramhash["BILLEMAIL"] = $billingAddress->getEmail();
			$paramhash["BILLPHONE"] = $billingAddress->getTelephone();
			$paramhash["BILLFAX"] = $billingAddress->getFax();
			$paramhash["BILLADDRESS1"] = $billingAddress->getStreet1();
			$paramhash["BILLADDRESS2"] = $billingAddress->getStreet2();
			$paramhash["BILLADDRESS3"] = $billingAddress->getStreet3();
			$paramhash["BILLPLACE"] = $billingAddress->getCity();
			$paramhash["BILLDIVISION"] = $billingAddress->getRegion();
			$paramhash["BILLPOSTALCODE"] = $billingAddress->getPostcode();
			$paramhash["BILLCOUNTRYCODE"] = $billingAddress->getCountry();
			}
			if($shippingAddress->getEmail())
			{
			$paramhash["SHIPNAME"] = $shippingAddress->getName();
			$paramhash["SHIPCOMPANY"] = $shippingAddress->getCompany();
			$paramhash["SHIPEMAIL"] = $shippingAddress->getEmail();
			$paramhash["SHIPPHONE"] = $shippingAddress->getTelephone();
			$paramhash["SHIPADDRESS1"] = $shippingAddress->getStreet1();
			$paramhash["SHIPADDRESS2"] = $shippingAddress->getStreet2();
			$paramhash["SHIPADDRESS3"] = $shippingAddress->getStreet3();
			$paramhash["SHIPPLACE"] = $shippingAddress->getCity();
			$paramhash["SHIPDIVISION"] = $shippingAddress->getRegion();
			$paramhash["SHIPPOSTALCODE"] = $shippingAddress->getPostcode();
			$paramhash["SHIPCOUNTRYCODE"] = $shippingAddress->getCountry();
			}
			$paramhash["ACTION"] = "CreateOrder";
			$paramhash["READONLY"] = 1;
			$paramhash["CANCELURL"] = Mage::getUrl('checkout/cart');
			$paramhash["RETURNURL"] = Mage::getUrl('checkout/onepage/return/o/{orderid}');
			$paramhash["NOTIFYURL"] = Mage::getUrl('checkout/onepage/notify');
			$paramhash["CALCULATECALLBACKURL"] = Mage::getUrl('checkout/onepage/calculateshipping');
			$paramhash["TEMPLATEURL"] = preg_replace('/index.php\//', '', Mage::getBaseURL()).'checkout_template.php';
	
			$merchantid = Mage::getStoreConfig('payment/ezimerchant/merchantid');
			$apikey = Mage::getStoreConfig('payment/ezimerchant/apikey');

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://api.ezimerchant.com/".$merchantid."/orders/");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded", "X-APIKEY:".$apikey ));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paramhash));


							 $apiresponse = curl_exec($ch);
							 curl_close($ch);

							 $xml = simplexml_load_string($apiresponse);
							 $redirectUrl = (string)$xml->entry->link["href"];
	$this->LogToFile('test.txt', 'check');
            return $redirectUrl;
  	}

/**
	 * Get region collection
	 * @param string $countryCode
	 * @return array
	 */
	public function getRegionCollection($countryCode)
	{
		$regionCollection = Mage::getModel('directory/region_api')->items($countryCode);
		return $regionCollection;
	}	
	
	
/*   FUNCTION AFTER RETURNING FROM EZIMERCHANT.         */
public function returnAction()
    {
						$orderid = $this->getRequest()->getParam('o');					
						
						$merchantid = Mage::getStoreConfig('payment/ezimerchant/merchantid');
						$apikey = Mage::getStoreConfig('payment/ezimerchant/apikey');

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, "https://api.ezimerchant.com/".$merchantid."/orders/".$orderid."/");
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded", "X-APIKEY:".$apikey ));
						

						$apiresponse = curl_exec($ch);
						curl_close($ch);                                                          

						$xml = simplexml_load_string($apiresponse);
						$ordercontent = $xml->entry->content->children('http://api.ezimerchant.com/schemas/2009/');
				
						$billing_address = $ordercontent->orderaddresses->orderaddress[0];
						$name_billing_address = explode(" ", $billing_address->name);
						$shipping_address = $ordercontent->orderaddresses->orderaddress[1];
						$name_shipping_address = explode(" ", $shipping_address->name);
						
						if($name_billing_address[1] == '')
						$name_billing_address[1] = $name_billing_address[0];
						
						if($name_shipping_address[1] == '')
						$name_shipping_address[1] = $name_shipping_address[0];
							
					$customer = Mage::getModel('customer/customer');
					$password = '123456';
					 
					$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
					$customer->loadByEmail($billing_address->email);
										
					
					$regionCollection = $this->getRegionCollection($billing_address->countrycode);
					foreach($regionCollection as $region) 
					{
							if($region['code'] == $billing_address->division)
							{
								$regionsel_id = $region['region_id'];
							}		
					}
						
					$regionCollection_ship = $this->getRegionCollection($shipping_address->countrycode);
					foreach($regionCollection_ship as $region_ship) 
					{
							if($region_ship['code'] == $shipping_address->division)
							{
								$regionsel_id_ship = $region_ship['region_id'];
							}		
					}

					$addressData_billing = array(
							'firstname' => $name_billing_address[0],
							'lastname' => $name_billing_address[1]." ".$name_billing_address[2],
							'street' => $billing_address->address1.','.$billing_address->address2,
							'city' => $billing_address->place,
							'postcode' => $billing_address->postalcode,
							'telephone' => $billing_address->phone,
							'country_id' => $billing_address->countrycode,
							'region_id' => $regionsel_id, // id from directory_country_region table// id from directory_country_region table
					);

					$addressData_shipping = array(
							'firstname' => $name_shipping_address[0],
							'lastname' => $name_shipping_address[1]." ".$name_shipping_address[2],
							'street' => $shipping_address->address1.','.$shipping_address->address2,
							'city' => $shipping_address->place,
							'postcode' => $shipping_address->postalcode,
							'telephone' => $shipping_address->phone,
							'country_id' => $shipping_address->countrycode,
							'region_id' => $regionsel_id_ship, // id from directory_country_region table// id from directory_country_region table
					);
					
					
					
					
					
								$check = $ordercontent->custom;
								
					
					
				
					
					
					
					$quoteId = $check;
							
					$quote = Mage::getModel('sales/quote')->load($quoteId);
					$login = 0;

					if ($customer->getId()) 
					{
						// for customer orders:
						$quote->assignCustomer($customer);
						Mage::getSingleton('customer/session')->loginById($customer->getId());
						$login = 1;
					} 
					else 
					{
						// for guest orders only:	

						$websiteId = Mage::app()->getWebsite()->getId();
						$storeId = Mage::app()->getStore()->getId();
						$customer = Mage::getModel("customer/customer");
						$customer->setWebsiteId($websiteId);
						$customer->setStoreId($storeId);						
						$customer->setEmail($billing_address->email);
						$customer->setFirstname($name_billing_address[0]);
						$customer->setLastname($name_billing_address[1]);
						$customer->setPassword($password);
							try 
							{
								$customer->save();
								$customer->setConfirmation(null);
								$customer->save();
								$customerId = $customer->getId();
								
								
								$customerAddress = Mage::getModel('customer/address');
								
								$customerAddress->setData($addressData_billing)
														->setCustomerId($customer->getId())
														->setIsDefaultBilling('1')
														->setSaveInAddressBook('1');
		
								$customerAddress->save();
											
											
								$customerAddress->setData($addressData_shipping)
														->setCustomerId($customer->getId())
														->setIsDefaultShipping('1')
														->setSaveInAddressBook('1');
		
								$customerAddress->save();
								
								//Make a "login" of new customer
								
							}
							catch (Exception $ex) 
							{
								//Zend_Debug::dump($ex->getMessage());
							}
						$customer_new		= Mage::getModel('customer/customer')->load($customerId); 
						$quote->assignCustomer($customer_new);
						
					}
					 
					 $product_model    = Mage::getModel('catalog/product');
					 $products 		   = $ordercontent->orderlines->orderline;
					 
					 
					 
					 foreach($products as $product)
					{						
							if('FREIGHT' == $product->productcode)
							{									
								$shipping_price = (float)$product->priceinctax;
								$shipping_name = $product->productname;	
							}
					}
					 
					

					$billingAddress  = $quote->getBillingAddress()->addData($addressData_billing);
					$shippingAddress = $quote->getShippingAddress()->addData($addressData_shipping);
						
						if($ordercontent->coupons->couponcode)
								{
								$couponCode = $ordercontent->coupons->couponcode;
								}
						$quote->getShippingAddress()->setCollectShippingRates(true);
						$quote->setCouponCode(strlen($couponCode) ? $couponCode : '')->collectTotals()->save();
											
					$quote->getShippingAddress()->collectShippingRates();					
					$groups = $quote->getShippingAddress()->getGroupedAllShippingRates(); 
						
						foreach ($groups as $code => $rates) 
						{
							foreach ($rates as $rate) 
							{
								if($rate->getCarrierTitle() == strip_tags(trim($shipping_name)))
								{
									$set_shipping_method = $rate->getCode();
									$ship_price = $rate->getPrice();
								}
							} 
						}
					
					$quote->setTotalsCollectedFlag(0);
					// shipping method an collect
					$quote->getShippingAddress()->setShippingMethod($set_shipping_method);
					$quote->getShippingAddress()->setCollectShippingRates(true);
					$quote->getShippingAddress()->collectShippingRates();
					$quote->collectTotals()->save();
					
                /*$ccTypeRegExpList = array(
                    //Solo, Switch or Maestro. International safe
                    'SO' => '/(^(6334)[5-9](\d{11}$|\d{13,14}$))|(^(6767)(\d{12}$|\d{14,15}$))/', // Solo only
                    'SM' => '/(^(5[0678])\d{11,18}$)|(^(6[^05])\d{11,18}$)|(^(601)[^1]\d{9,16}$)|(^(6011)\d{9,11}$)|(^(6011)\d{13,16}$)|(^(65)\d{11,13}$)|(^(65)\d{15,18}$)|(^(49030)[2-9](\d{10}$|\d{12,13}$))|(^(49033)[5-9](\d{10}$|\d{12,13}$))|(^(49110)[1-2](\d{10}$|\d{12,13}$))|(^(49117)[4-9](\d{10}$|\d{12,13}$))|(^(49118)[0-2](\d{10}$|\d{12,13}$))|(^(4936)(\d{12}$|\d{14,15}$))/',

                    'SS'  => '/^((6759[0-9]{12})|(6334|6767[0-9]{12})|(6334|6767[0-9]{14,15})|(5018|5020|5038|6304|6759|6761|6763[0-9]{12,19})|(49[013][1356][0-9]{12})|(633[34][0-9]{12})|(633110[0-9]{10})|(564182[0-9]{10}))([0-9]{2,3})?$/', // Maestro / Solo
                    'VI'  => '/^4[0-9]{12}([0-9]{3})?$/',             // Visa
                    'MC'  => '/^5[1-5][0-9]{14}$/',                   // Master Card
                    'AE'  => '/^3[47][0-9]{13}$/',                    // American Express
                    'DI'  => '/^6011[0-9]{12}$/',                     // Discovery
                    'JCB' => '/^(3[0-9]{15}|(2131|1800)[0-9]{11})$/', // JCB
                );  */            								
								
					$quoteId = $quote->getId();
					$getquote    = Mage::getModel('sales/quote')->load($quoteId);
					$items    = $getquote->getAllItems();
					$getquote->reserveOrderId();
					$quotePaymentObj = $getquote->getPayment(); // Mage_Sales_Model_Quote_Payment
					$quotePaymentObj->setMethod('checkmo');

       /* $enckey = (string)Mage::getConfig()->getNode('global/crypt/key');
        $cc_number_enc = base64_encode(Varien_Crypt::factory()->init($enckey)->encrypt((string)$ordercontent->orderpayments->orderpayment->cardnumber));
					
$quotePaymentObj->setData('method', 'ccsave');	
$quotePaymentObj->setData('cc_owner', (string)$ordercontent->orderpayments->orderpayment->cardname);
$quotePaymentObj->setData('cc_number', (string)$ordercontent->orderpayments->orderpayment->cardnumber);
$quotePaymentObj->setData('cc_number_enc', $cc_number_enc);
$quotePaymentObj->setData('cc_last4', substr((string)$ordercontent->orderpayments->orderpayment->cardnumber, -4));
$quotePaymentObj->setData('cc_exp_month', (int)$ordercontent->orderpayments->orderpayment->expirymonth);
$quotePaymentObj->setData('cc_exp_year',  (int)$ordercontent->orderpayments->orderpayment->expiryyear);	*/
						
					
					//$quotePaymentObj->importData(array('method' => 'ccsave',
					//	'cc_owner' => 'ffffffffff',
					//	'cc_number' => (string)$ordercontent->orderpayments->orderpaymnet->cardnumber,
					//	'cc_type' => 'VI',
					//	'cc_exp_month' => (int)$ordercontent->orderpayments->orderpayment->expirymonth,
					//	'cc_exp_year' => (int)$ordercontent->orderpayments->orderpayment->expiryyear,
					//	'cc_cid' => 'xxx'));
			
					

					$getquote->setPayment($quotePaymentObj);
					
							$convertquote=Mage::getSingleton('sales/convert_quote');
							$orderObj = $convertquote->addressToOrder($getquote->getShippingAddress());
							$orderPaymentObj=$convertquote->paymentToOrderPayment($quotePaymentObj);
						
							$orderObj->setBillingAddress($convertquote->addressToOrderAddress($getquote->getBillingAddress()));
							$orderObj->setShippingAddress($convertquote->addressToOrderAddress($getquote->getShippingAddress()));
							$orderObj->setPayment($convertquote->paymentToOrderPayment($getquote->getPayment()));
							
							foreach ($items as $item) {
								//@var $item Mage_Sales_Model_Quote_Item
								
								$item->setData('price_incl_tax', $item->getData('base_price_incl_tax'));
								$item->setData('tax_amount', $item->getData('base_tax_amount'));
								$item->setData('row_total_incl_tax', $item->getData('base_row_total_incl_tax'));
								
							
								$orderItem = $convertquote->itemToOrderItem($item);
								
								if ($item->getParentItem()) {
									$orderItem->setParentItem($orderObj->getItemByQuoteItemId($item->getParentItem()->getId()));
								}
									$productId = $item->getProductId();
									if ($productId) {
										$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
										$stockItem->subtractQty($item->getQty());
										$stockItem->save();
										}
								$orderObj->addItem($orderItem);
							}											

							
							$orderObj->setData('subtotal', $orderObj->getData('base_subtotal'));
							$orderObj->setData('tax_amount', $orderObj->getData('base_tax_amount'));
							$orderObj->setData('grand_total', $orderObj->getData('base_grand_total'));														
							
														

							$orderObj->setCanShipPartiallyItem(false);
							$orderObj->setIncrementId($orderid);
							//$orderObj->collectTotals();
							$totalDue=$orderObj->getTotalDue();
												
							$orderObj->place();
							$orderObj->save(); 
							$orderObj->sendNewOrderEmail(); 
							$orderId=$orderObj->getId();
					 /*  IF PAYMENT DONE SUCCESSFULLY, INVOICE IS CREATED*/
							if($ordercontent->paymentstatus == 'paid')
							{
							$invorderObj=Mage::getModel('sales/order')->load($orderId);
							$convertOrderObj=Mage::getSingleton('sales/convert_order');
							$invoiceObj=$convertOrderObj->toInvoice($invorderObj);

							foreach ($invorderObj->getAllItems() as $item) {

								$invoiceItem = $convertOrderObj->itemToInvoiceItem($item);
	
								if ($item->getParentItem()) {
									$invoiceItem->setParentItem($invoiceObj->getItemById($item->getParentItem()->getId()));
								}
								
								$invoiceItem->setQty($item->getQtyToInvoice());							
								
								$invoiceObj->addItem($invoiceItem);
							}

							$invoiceObj->collectTotals();
							$invoiceObj->register();

							$orderPaymentObj=$invorderObj->getPayment();
							$orderPaymentObj->pay($invoiceObj);

							$invoiceObj->getOrder()->setIsInProcess(true);
							$transactionObj = Mage::getModel('core/resource_transaction');
							$transactionObj->addObject($invoiceObj);
							$transactionObj->addObject($invoiceObj->getOrder());
							$transactionObj->save();

							$invoiceObj->save();
							$invoiceId=$invoiceObj->getId();
							}
			if(!$login)
			Mage::getSingleton('customer/session')->loginById($customer->getId());
			$this->_redirect('checkout/onepage/success/o/'.$orderObj->getIncrementId());


    }	
	
	
	/*   FUNCTION AFTER RETURNING FROM EZIMERCHANT.         */
public function notifyAction()
    {					
						//$data = $this->getRequest()->getPost();
						//$orderid = $data['eziorderid'];	
						//$orderid = $this->getRequest()->getParam('o');					
						
						$merchantid = Mage::getStoreConfig('payment/ezimerchant/merchantid');
						$apikey = Mage::getStoreConfig('payment/ezimerchant/apikey');
$xmlinput = file_get_contents("php://input");
if(strlen($xmlinput)>0) 
{
	$xml = simplexml_load_string($xmlinput);
}
else
{
						$data = $this->getRequest()->getPost();
						$orderid = $data['eziorderid'];	
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, "https://api.ezimerchant.com/".$merchantid."/orders/".$orderid."/");
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded", "X-APIKEY:".$apikey ));
						

						$apiresponse = curl_exec($ch);
						curl_close($ch);  
						$xml = simplexml_load_string($apiresponse);						
}


						
						$ordercontent = $xml->entry->content->children('http://api.ezimerchant.com/schemas/2009/');
						$orderid  = (string)$ordercontent->orderid;				
						$billing_address = $ordercontent->orderaddresses->orderaddress[0];
						$name_billing_address = explode(" ", $billing_address->name);
						$shipping_address = $ordercontent->orderaddresses->orderaddress[1];
						$name_shipping_address = explode(" ", $shipping_address->name);
						
						if($name_billing_address[1] == '')
						$name_billing_address[1] = $name_billing_address[0];
						
						if($name_shipping_address[1] == '')
						$name_shipping_address[1] = $name_shipping_address[0];
							
					$customer = Mage::getModel('customer/customer');
					$password = '123456';
					 
					$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
					$customer->loadByEmail($billing_address->email);
										
					
					$regionCollection = $this->getRegionCollection($billing_address->countrycode);
					foreach($regionCollection as $region) 
					{
							if($region['code'] == $billing_address->division)
							{
								$regionsel_id = $region['region_id'];
							}		
					}
						
					$regionCollection_ship = $this->getRegionCollection($shipping_address->countrycode);
					foreach($regionCollection_ship as $region_ship) 
					{
							if($region_ship['code'] == $shipping_address->division)
							{
								$regionsel_id_ship = $region_ship['region_id'];
							}		
					}

					$addressData_billing = array(
							'firstname' => $name_billing_address[0],
							'lastname' => $name_billing_address[1]." ".$name_billing_address[2],
							'street' => $billing_address->address1.','.$billing_address->address2,
							'city' => $billing_address->place,
							'postcode' => $billing_address->postalcode,
							'telephone' => $billing_address->phone,
							'country_id' => $billing_address->countrycode,
							'region_id' => $regionsel_id, // id from directory_country_region table// id from directory_country_region table
					);

					$addressData_shipping = array(
							'firstname' => $name_shipping_address[0],
							'lastname' => $name_shipping_address[1]." ".$name_shipping_address[2],
							'street' => $shipping_address->address1.','.$shipping_address->address2,
							'city' => $shipping_address->place,
							'postcode' => $shipping_address->postalcode,
							'telephone' => $shipping_address->phone,
							'country_id' => $shipping_address->countrycode,
							'region_id' => $regionsel_id_ship, // id from directory_country_region table// id from directory_country_region table
					);
					
					
					
					
					if($ordercontent->custom)
					{
								$check = $ordercontent->custom;				
					
					$quoteId = $check;
							
					$quote = Mage::getModel('sales/quote')->load($quoteId);
					$login = 0;
					}
					else
					{
						$quote = Mage::getModel('sales/quote');
	
											$storeObj = $quote->getStore()->load(1);
											$quote->setStore($storeObj);
											$productModel=Mage::getModel('catalog/product');	
											

											/*$quote->assignCustomer($customer);*/
											$quote->setIsMultiShipping(false);
											/*$customer_billing_address = $customer ->getDefaultBillingAddress();
											$customer_shipping_address = $customer ->getDefaultShippingAddress();
											$quote_billing_address = Mage::getModel('sales/quote_address')
															->importCustomerAddress($customer_billing_address);
											$quote_shipping_address = Mage::getModel('sales/quote_address')
															->importCustomerAddress($customer_shipping_address);
											$quote->setBillingAddress ($quote_billing_address);
											$quote->setBillingAddress ($quote_shipping_address);*/
											$products 		   = $ordercontent->orderlines->orderline;
					 
					 
					 
											foreach($products as $product)
											{
											if('FREIGHT' != $product->productcode)
											{
											$productObj=$productModel->load($product->productid);
												$quoteItem=Mage::getModel('sales/quote_item')->setProduct($productObj);
												$quoteItem->setQuote($quote);
												$quoteItem->setQty($product->quantity);
												$quote->addItem($quoteItem);
												}
											}
											$quote->collectTotals(); 
    $quote->save();
	$quoteId = $quote->getId();
					}
					if ($customer->getId()) 
					{
						// for customer orders:
						$quote->assignCustomer($customer);
						Mage::getSingleton('customer/session')->loginById($customer->getId());
						$login = 1;
					} 
					else 
					{
						// for guest orders only:	

						$websiteId = Mage::app()->getWebsite()->getId();
						$storeId = Mage::app()->getStore()->getId();
						$customer = Mage::getModel("customer/customer");
						$customer->setWebsiteId($websiteId);
						$customer->setStoreId($storeId);						
						$customer->setEmail($billing_address->email);
						$customer->setFirstname($name_billing_address[0]);
						$customer->setLastname($name_billing_address[1]);
						$customer->setPassword($password);
							try 
							{
								$customer->save();
								$customer->setConfirmation(null);
								$customer->save();
								$customerId = $customer->getId();
								
								
								$customerAddress = Mage::getModel('customer/address');
								
								$customerAddress->setData($addressData_billing)
														->setCustomerId($customer->getId())
														->setIsDefaultBilling('1')
														->setSaveInAddressBook('1');
		
								$customerAddress->save();
											
											
								$customerAddress->setData($addressData_shipping)
														->setCustomerId($customer->getId())
														->setIsDefaultShipping('1')
														->setSaveInAddressBook('1');
		
								$customerAddress->save();
								
								//Make a "login" of new customer
								
							}
							catch (Exception $ex) 
							{
								//Zend_Debug::dump($ex->getMessage());
							}
						$customer_new		= Mage::getModel('customer/customer')->load($customerId); 
						$quote->assignCustomer($customer_new);
						
					}
					 
					 //$product_model    = Mage::getModel('catalog/product');
					 //$products 		   = $ordercontent->orderlines->orderline;
					 
					 
					 
					 foreach($products as $product)
					{						
							if('FREIGHT' == $product->productcode)
							{									
								$shipping_price = (float)$product->priceinctax;
								$shipping_name = $product->productname;	
							}
					}
					 
					

					$billingAddress  = $quote->getBillingAddress()->addData($addressData_billing);
					$shippingAddress = $quote->getShippingAddress()->addData($addressData_shipping);
						
						if($ordercontent->coupons->couponcode)
								{
								$couponCode = $ordercontent->coupons->couponcode;
								}
						$quote->getShippingAddress()->setCollectShippingRates(true);
						$quote->setCouponCode(strlen($couponCode) ? $couponCode : '')->collectTotals()->save();
											
					$quote->getShippingAddress()->collectShippingRates();					
					$groups = $quote->getShippingAddress()->getGroupedAllShippingRates(); 
						
						foreach ($groups as $code => $rates) 
						{
							foreach ($rates as $rate) 
							{
								if($rate->getCarrierTitle() == strip_tags(trim($shipping_name)))
								{
									$set_shipping_method = $rate->getCode();
									$ship_price = $rate->getPrice();
								}
							} 
						}
					
					$quote->setTotalsCollectedFlag(0);
					// shipping method an collect
					$quote->getShippingAddress()->setShippingMethod($set_shipping_method);
					$quote->getShippingAddress()->setCollectShippingRates(true);
					$quote->getShippingAddress()->collectShippingRates();
					$quote->collectTotals()->save();
					
                /*$ccTypeRegExpList = array(
                    //Solo, Switch or Maestro. International safe
                    'SO' => '/(^(6334)[5-9](\d{11}$|\d{13,14}$))|(^(6767)(\d{12}$|\d{14,15}$))/', // Solo only
                    'SM' => '/(^(5[0678])\d{11,18}$)|(^(6[^05])\d{11,18}$)|(^(601)[^1]\d{9,16}$)|(^(6011)\d{9,11}$)|(^(6011)\d{13,16}$)|(^(65)\d{11,13}$)|(^(65)\d{15,18}$)|(^(49030)[2-9](\d{10}$|\d{12,13}$))|(^(49033)[5-9](\d{10}$|\d{12,13}$))|(^(49110)[1-2](\d{10}$|\d{12,13}$))|(^(49117)[4-9](\d{10}$|\d{12,13}$))|(^(49118)[0-2](\d{10}$|\d{12,13}$))|(^(4936)(\d{12}$|\d{14,15}$))/',

                    'SS'  => '/^((6759[0-9]{12})|(6334|6767[0-9]{12})|(6334|6767[0-9]{14,15})|(5018|5020|5038|6304|6759|6761|6763[0-9]{12,19})|(49[013][1356][0-9]{12})|(633[34][0-9]{12})|(633110[0-9]{10})|(564182[0-9]{10}))([0-9]{2,3})?$/', // Maestro / Solo
                    'VI'  => '/^4[0-9]{12}([0-9]{3})?$/',             // Visa
                    'MC'  => '/^5[1-5][0-9]{14}$/',                   // Master Card
                    'AE'  => '/^3[47][0-9]{13}$/',                    // American Express
                    'DI'  => '/^6011[0-9]{12}$/',                     // Discovery
                    'JCB' => '/^(3[0-9]{15}|(2131|1800)[0-9]{11})$/', // JCB
                );  */            								
								
					$quoteId = $quote->getId();
					$getquote    = Mage::getModel('sales/quote')->load($quoteId);
					$items    = $getquote->getAllItems();
					$getquote->reserveOrderId();
					$quotePaymentObj = $getquote->getPayment(); // Mage_Sales_Model_Quote_Payment
					$quotePaymentObj->setMethod('checkmo');

       /* $enckey = (string)Mage::getConfig()->getNode('global/crypt/key');
        $cc_number_enc = base64_encode(Varien_Crypt::factory()->init($enckey)->encrypt((string)$ordercontent->orderpayments->orderpayment->cardnumber));
					
$quotePaymentObj->setData('method', 'ccsave');	
$quotePaymentObj->setData('cc_owner', (string)$ordercontent->orderpayments->orderpayment->cardname);
$quotePaymentObj->setData('cc_number', (string)$ordercontent->orderpayments->orderpayment->cardnumber);
$quotePaymentObj->setData('cc_number_enc', $cc_number_enc);
$quotePaymentObj->setData('cc_last4', substr((string)$ordercontent->orderpayments->orderpayment->cardnumber, -4));
$quotePaymentObj->setData('cc_exp_month', (int)$ordercontent->orderpayments->orderpayment->expirymonth);
$quotePaymentObj->setData('cc_exp_year',  (int)$ordercontent->orderpayments->orderpayment->expiryyear);	*/
						
					
					//$quotePaymentObj->importData(array('method' => 'ccsave',
					//	'cc_owner' => 'ffffffffff',
					//	'cc_number' => (string)$ordercontent->orderpayments->orderpaymnet->cardnumber,
					//	'cc_type' => 'VI',
					//	'cc_exp_month' => (int)$ordercontent->orderpayments->orderpayment->expirymonth,
					//	'cc_exp_year' => (int)$ordercontent->orderpayments->orderpayment->expiryyear,
					//	'cc_cid' => 'xxx'));
			
					

					$getquote->setPayment($quotePaymentObj);
					
							$convertquote=Mage::getSingleton('sales/convert_quote');
							$orderObj = $convertquote->addressToOrder($getquote->getShippingAddress());
							$orderPaymentObj=$convertquote->paymentToOrderPayment($quotePaymentObj);
						
							$orderObj->setBillingAddress($convertquote->addressToOrderAddress($getquote->getBillingAddress()));
							$orderObj->setShippingAddress($convertquote->addressToOrderAddress($getquote->getShippingAddress()));
							$orderObj->setPayment($convertquote->paymentToOrderPayment($getquote->getPayment()));
							
							foreach ($items as $item) {
								//@var $item Mage_Sales_Model_Quote_Item
								
								$item->setData('price_incl_tax', $item->getData('base_price_incl_tax'));
								$item->setData('tax_amount', $item->getData('base_tax_amount'));
								$item->setData('row_total_incl_tax', $item->getData('base_row_total_incl_tax'));
								
							
								$orderItem = $convertquote->itemToOrderItem($item);
								
								if ($item->getParentItem()) {
									$orderItem->setParentItem($orderObj->getItemByQuoteItemId($item->getParentItem()->getId()));
								}
									$productId = $item->getProductId();
									if ($productId) {
										$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
										$stockItem->subtractQty($item->getQty());
										$stockItem->save();
										}
								$orderObj->addItem($orderItem);
							}											

							
							$orderObj->setData('subtotal', $orderObj->getData('base_subtotal'));
							$orderObj->setData('tax_amount', $orderObj->getData('base_tax_amount'));
							$orderObj->setData('grand_total', $orderObj->getData('base_grand_total'));														
							
														

							$orderObj->setCanShipPartiallyItem(false);
							$orderObj->setIncrementId($orderid);
							//$orderObj->collectTotals();
							$totalDue=$orderObj->getTotalDue();
												
							$orderObj->place();
							$orderObj->save(); 
							$orderObj->sendNewOrderEmail(); 
							$orderId=$orderObj->getId();
					 /*  IF PAYMENT DONE SUCCESSFULLY, INVOICE IS CREATED*/
							if($ordercontent->paymentstatus == 'paid')
							{
							$invorderObj=Mage::getModel('sales/order')->load($orderId);
							$convertOrderObj=Mage::getSingleton('sales/convert_order');
							$invoiceObj=$convertOrderObj->toInvoice($invorderObj);

							foreach ($invorderObj->getAllItems() as $item) {

								$invoiceItem = $convertOrderObj->itemToInvoiceItem($item);
	
								if ($item->getParentItem()) {
									$invoiceItem->setParentItem($invoiceObj->getItemById($item->getParentItem()->getId()));
								}
								
								$invoiceItem->setQty($item->getQtyToInvoice());							
								
								$invoiceObj->addItem($invoiceItem);
							}

							$invoiceObj->collectTotals();
							$invoiceObj->register();

							$orderPaymentObj=$invorderObj->getPayment();
							$orderPaymentObj->pay($invoiceObj);

							$invoiceObj->getOrder()->setIsInProcess(true);
							$transactionObj = Mage::getModel('core/resource_transaction');
							$transactionObj->addObject($invoiceObj);
							$transactionObj->addObject($invoiceObj->getOrder());
							$transactionObj->save();

							$invoiceObj->save();
							$invoiceId=$invoiceObj->getId();
							}

    }	
	
	function LogToFile($logfile, $message)
		{	
			//$fp = fopen($logfile, 'w+');
			$fp = fopen(Mage::getBaseDir() . DS. $logfile, "a+");
			fwrite($fp, $message);
			fclose($fp);
		}
	
	
	public function calculateshippingAction()
	{
	
        try {
            $data = $this->getRequest()->getPost();
			} catch (Exception $e) {
				Mage::logException($e);
			}
			
			if($data['COUPONCODE'])
			$couponCode  =  $data['COUPONCODE'];
			else
			 $couponCode = '';
			 if(!$data['CUSTOM'])
			 {
			  /*$customer = Mage::getModel('customer/customer')
    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
   ->loadByEmail($data['EMAIL']);*/
	foreach ($data as $key => $value)
				{
				$this->LogToFile('test.txt', $key);
				$this->LogToFile('test.txt', $value);
					preg_match_all('/PRODUCTNAME\((.+?)\)/is', $key, $new);
					if($new[1][0] >= "0" && $new[1][0] > $prod_count)$prod_count = $new[1][0];
				}
				
				
    $quote = Mage::getModel('sales/quote');
	
	$storeObj = $quote->getStore()->load(1);
    $quote->setStore($storeObj);
	$productModel=Mage::getModel('catalog/product');	
	

    /*$quote->assignCustomer($customer);*/
    $quote->setIsMultiShipping(false);
    /*$customer_billing_address = $customer ->getDefaultBillingAddress();
    $customer_shipping_address = $customer ->getDefaultShippingAddress();
    $quote_billing_address = Mage::getModel('sales/quote_address')
                    ->importCustomerAddress($customer_billing_address);
    $quote_shipping_address = Mage::getModel('sales/quote_address')
                    ->importCustomerAddress($customer_shipping_address);
    $quote->setBillingAddress ($quote_billing_address);
    $quote->setBillingAddress ($quote_shipping_address);*/
    
    for($i=0;$i<=$prod_count;$i++)
	{
	$productObj=$productModel->load($data['PRODUCTID('.$i.')']);
        $quoteItem=Mage::getModel('sales/quote_item')->setProduct($productObj);
        $quoteItem->setQuote($quote);
        $quoteItem->setQty($data['PRODUCTQUANTITY('.$i.')']);
        $quote->addItem($quoteItem);
	}
	$quote->collectTotals(); 
    $quote->save();
	$quoteId = $quote->getId();
	}
	else
	{
	$quoteId = $data['CUSTOM'];	 
	}
		
		$quote = Mage::getModel('sales/quote')->load($quoteId);
		
		$model = Mage::getModel('catalog/product'); //getting product model
		$customer = Mage::getModel('customer/customer');
		if($data['EMAIL'])
		{
										 
					$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
					$customer->loadByEmail($data['EMAIL']);
					if ($customer->getId()) {
							$quote->assignCustomer($customer);
							Mage::getSingleton('customer/session')->loginById($customer->getId());
					}		
		}
				
		$regionCollection = $this->getRegionCollection($data['COUNTRYCODE']);
        foreach($regionCollection as $region) {
            if($region['code'] == $data['DIVISION'])
			{
				$regionsel_id = $region['region_id'];
			}		
		}
		
		$addressData_shipping = array(
							'postcode' => $data['POSTALCODE'],
							'country_id' => $data['COUNTRYCODE'],
							'region_id' => $regionsel_id, // id from directory_country_region table// id from directory_country_region table
					);
					$shippingAddress = $quote->getShippingAddress()->addData($addressData_shipping);
					
	$quote->getShippingAddress()->setCollectShippingRates(true);
	$quote->setCouponCode($couponCode)
                ->collectTotals()
                ->save();
	$quote->getShippingAddress()->collectShippingRates();
	$groups = $quote->getShippingAddress()->getGroupedAllShippingRates(); 
	$i=0;
	$paramhash = array();
	foreach ($groups as $code => $rates) {
	foreach ($rates as $rate) {
		$paramhash["FREIGHTNAME(".$i.")"] = $rate->getCarrierTitle();	
		$paramhash["FREIGHTCHARGEINCTAX(".$i.")"] = $rate->getPrice();
		$i++;
	} }





		$items = $quote->getAllVisibleItems();
		$itemidx = 0;
		$discount_amount = 0;
		
		foreach ($items as $ite)
		{
		$item = $ite->toArray();
		
		 $helper = Mage::helper('catalog/product_configuration');         
		 if($item['product_type'] == 'configurable')
		 $options = $helper->getConfigurableOptions($ite);
		 else
		 $options = $helper->getCustomOptions($ite);
		 
		
				$_product = $model->load($item['product_id']); //getting product object for particular product id				
				
				$paramhash["PRODUCTQUANTITY(" . $itemidx . ")"] = $item['qty'];
				$paramhash["PRODUCTCODE(" . $itemidx . ")"] = $item['sku'];
				$paramhash["PRODUCTNAME(" . $itemidx . ")"] = $item['name'];
				$paramhash["PRODUCTPRICEINCTAX(" . $itemidx . ")"] = $item['price_incl_tax'];
				$paramhash["PRODUCTID(" . $itemidx . ")"] = $item['product_id'];
				$paramhash["PRODUCTIMAGEURL(" . $itemidx . ")"] = $_product->getImageUrl();
				$attridx = 0;
				foreach($options as $option)
				 {
					$paramhash["PRODUCTATTRIBUTE(".$itemidx.")(".$attridx.")"] = $option['label'];
					$paramhash["PRODUCTATTRIBUTEVALUE(".$itemidx.")(".$attridx.")"] = $option['value'];
					$attridx++;
				 }
				
				if($item['discount_amount'])
				{
					$discount_amount += $item['discount_amount'];
				}
			
			$itemidx = $itemidx + 1;
		}
			if($discount_amount > 0)
			{
			$paramhash["PRODUCTQUANTITY(" . $itemidx . ")"] = 1;
			$paramhash["PRODUCTNAME(" . $itemidx . ")"] = 'Discounts';
			$paramhash["PRODUCTCODE(" . $itemidx . ")"] = 'Discounts';
			$paramhash["PRODUCTPRICEINCTAX(" . $itemidx . ")"] = -$discount_amount;
			}
			if($data['CUSTOM'])
			$paramhash["CUSTOM"] = $quote->getId();
			echo http_build_query($paramhash);
			
	}
	/*public function paypalreturnAction()
    {
	
	//echo '<pre>';
	$orderid = $this->getRequest()->getParam('o');
	
	
				 $username = Mage::getStoreConfig('payment/ezimerchant/username');
				 $merchantid = Mage::getStoreConfig('payment/ezimerchant/merchantid');
				 $password = Mage::getStoreConfig('payment/ezimerchant/password');
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.ezimerchant.com/".$merchantid."/orders/".$orderid."/");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURL_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded" ));
	curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);

	$apiresponse = curl_exec($ch);
	curl_close($ch);                                                          

	preg_match_all('/<content type=\"application\/xml\">(.+?)<\/content>/is', $apiresponse, $matches);
	
	preg_match_all('/<ezi:(.+?)<\/ezi/is', $matches[1][0], $new);
	//print_r($matches[1][0]);
	
	foreach($new[1] as $val)
	{
		$valuene = explode('>',$val);
		//print_r($valuene);
		if($valuene[0] != 'orderaddresses')
		$newval[$valuene[0]] = $valuene[1];
	}
	
	//$xml = simplexml_load_string($matches[1]);
	
	
$cart = Mage::helper('checkout/cart')->getCart();

     
		

$quote = Mage::getModel('sales/quote')
        ->setStoreId(Mage::app()->getStore('default')->getId());

if ('do customer orders') {
        // for customer orders:
        $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(1)
                ->loadByEmail($newval['email']);
        $quote->assignCustomer($customer);
} else {
        // for guest orders only:
        $quote->setCustomerEmail($newval['email']);
}
 
foreach ($cart->getQuote()->getAllItems() as $item) 
{

$product = Mage::getModel('catalog/product')->load($item->product_id);
$buyInfo = array(
		'qty' => $item->qty,
        );

$quote->addProduct($product, new Varien_Object($buyInfo));

}
 

$addressData = array(
        'firstname' => $newval['name'],
        'lastname' => $newval['name'],
        'street' => $newval['address1'].','.$newval['place'],
        'city' => $newval['division'],
        'postcode' => $newval['postalcode'],
        'telephone' => $newval['phone'],
        'country_id' => $newval['countrycode'],
		'region_id' => 12, // id from directory_country_region table// id from directory_country_region table
);

$billingAddress = $quote->getBillingAddress()->addData($addressData);
$shippingAddress = $quote->getShippingAddress()->addData($addressData);
 
$shippingAddress->setCollectShippingRates(true)->collectShippingRates()
                ->setShippingMethod('freeshipping_freeshipping')
                ->setPaymentMethod('ezimerchant');
 
$quote->getPayment()->importData(array('method' => 'checkmo'));

$quote->collectTotals()->save();
 
$service = Mage::getModel('sales/service_quote', $quote);
$service->submitAll();
$order = $service->getOrder();
 
 $this->_redirect('checkout/onepage/success/o/'.$order->getIncrementId());


    }	*/
	
	/*Function for creating template*/
	
	public function createtemplateAction()
	{
		include('simple_html_dom.php');
		$url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		
		$html = file_get_html($url);

		foreach($html->find('.col-main') as $element) {
			$element->outertext = '{Main}';
		}
		$fp = fopen(Mage::getBaseDir() . DS. "checkout_template.php", "w+");
		fwrite($fp, $html);
		fclose($fp);
	}
	
	
	/**
     * Order success action
     */
    public function successAction()
    {
	if(!$this->getRequest()->getParam('o'))
	{
	$this->_redirect('checkout/cart');
	}
	$cart = Mage::getSingleton('checkout/cart');
 
		$cart->truncate();
		$cart->save();
		$cart->getItems()->clear()->save();

        //$session->clear();
        $this->loadLayout();
        $this->_initLayoutMessages('checkout/session');
        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($this->getRequest()->getParam('o'))));
        $this->renderLayout();
    }
	
	
	public function curcounAction()
    {
	
	$default_currency = Mage::app()->getStore()->getCurrentCurrencyCode(); 
	$countryCode = Mage::getStoreConfig('general/country/default');

	$default_country = Mage::getModel('directory/country')->loadByCode($countryCode);
	

		echo 'default_currency_code='.$default_currency.'&default_country_code='.$default_country->iso2_code;
	
	}	
	
	
	

   

}
