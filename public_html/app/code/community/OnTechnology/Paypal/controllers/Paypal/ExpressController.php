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

include_once('Mage/Paypal/controllers/ExpressController.php');

class OnTechnology_Paypal_Paypal_ExpressController extends Mage_Paypal_ExpressController
{
	/**
     * Config mode type
     *
     * @var string
     */
    protected $_configType = 'paypal/config';

    /**
     * Config method type
     *
     * @var string
     */
    protected $_configMethod = Mage_Paypal_Model_Config::METHOD_WPP_EXPRESS;

    /**
     * Checkout mode type
     *
     * @var string
     */
    protected $_checkoutType = 'paypal/express_checkout';

	 /**
     * Checkout page to redirect to paypal through ezimerchnt
     */
    public function startAction()
    {
	
		
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		
	
	$items = $quote->getAllVisibleItems();
	$billingAddress = $quote->getBillingAddress();
	$shippingAddress = $quote->getShippingAddress();
	$itemidx = 0;
	$discount_amount = 0;
	$paramhash = array();
	
		foreach ($items as $item)
		{
			$item = $item->toArray();

				$paramhash["PRODUCTQUANTITY(" . $itemidx . ")"] = $item['qty'];
				$paramhash["PRODUCTCODE(" . $itemidx . ")"] = $item['sku'];
				$paramhash["PRODUCTNAME(" . $itemidx . ")"] = $item['name'];
				$paramhash["PRODUCTPRICE(" . $itemidx . ")"] = $item['price'];
				
				
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
			$paramhash["PRODUCTPRICE(" . $itemidx . ")"] = -$discount_amount;
			}
			$paramhash["CURRENCY"] = Mage::app()->getStore()-> getCurrentCurrencyCode();
			$paramhash["PRODUCTTAX"] = 'GST';
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
			$paramhash["CANCELURL"] = Mage::getUrl('checkout/onepage/fail');
			$paramhash["RETURNURL"] = Mage::getUrl('checkout/onepage/return/o/{orderid}');
			$paramhash["NOTIFYURL"] = Mage::getUrl('checkout/onepage/notify');
			$paramhash["CALCULATECALLBACKURL"] = Mage::getUrl('checkout/onepage/calculateshipping');
			//$paramhash["TEMPLATEURL"] = preg_replace('/index.php\//', '', Mage::getBaseURL()).'checkout_template.php';
	
				 $username = Mage::getStoreConfig('payment/ezimerchant/username');
				 $merchantid = Mage::getStoreConfig('payment/ezimerchant/merchantid');
				 $password = Mage::getStoreConfig('payment/ezimerchant/password');
				 
				 

							 $ch = curl_init();
							 curl_setopt($ch, CURLOPT_URL, "https://api.ezimerchant.com/".$merchantid."/orders/");
							 curl_setopt($ch, CURLOPT_HEADER, 0);
							 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							 curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
							 curl_setopt($ch, CURL_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded" ));
							 curl_setopt($ch, CURLOPT_USERPWD, "".$username.":".$password);
							 curl_setopt($ch, CURLOPT_POST, 1);
							 curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paramhash));

							 $apiresponse = curl_exec($ch);
							 curl_close($ch);

							 $xml = simplexml_load_string($apiresponse);

								$redirectUrl = (string)$xml->entry->link["href"].'&pp=1';	
								
								$this->getResponse()->setRedirect($redirectUrl);
                return;        
    } 

}
