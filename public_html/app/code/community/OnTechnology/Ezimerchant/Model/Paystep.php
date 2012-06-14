<?php
/**
 * ezimerchant Payment Extension
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
 * @category   Ezimerchant
 * @package    OnTechnology_Ezimerchant
 * @author     On Technology
 * @copyright  Copyright (c) 2010 On Technology Pty. Ltd. (http://www.ezimerchant.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class OnTechnology_Ezimerchant_Model_Paystep extends Mage_Payment_Model_Method_Abstract
{
    protected $logfile = null;

    const REQUEST_AMOUNT_EDITABLE = 'N';

    protected $_code = 'paystep';
    protected $_formBlockType = 'ezimerchant/paystep_form';
    protected $_allowCurrencyCode = array('AUD', 'EUR', 'GBP', 'NZD', 'USD');
    
    public function assignData($data)
    {
        $details = array();
        if ($this->getUsername())
        {
            $details['username'] = $this->getUsername();
        }
        if (!empty($details)) 
        {
            $this->getInfoInstance()->setAdditionalData(serialize($details));
        }
        return $this;
    }

    public function getUsername()
    {
        return $this->getConfigData('username');
    }
    
    public function getPassword()
    {
        return $this->getConfigData('password');
    }
    
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function validate()
    {
        parent::validate();
        $currency_code = $this->getQuote()->getBaseCurrencyCode();
        if (!in_array($currency_code,$this->_allowCurrencyCode)) {
            Mage::throwException(Mage::helper('dps')->__('Selected currency code ('.$currency_code.') is not compatible with Payment Express'));
        }
        return $this;
    }

    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment)
    {
       return $this;
    }

    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment)
    {

    }

    public function canCapture()
    {
        return true;
    }


    public function getOrderPlaceRedirectUrl()
    {

	$logfile = fopen('/var/www/vhosts/jschiller.ezimerchant.biz/httpdocs/log.txt', 'a');

	LogToFile($logfile, 'getOrderPlaceRedirectUrl()');

	$quote = Mage::getSingleton('checkout/session')->getQuote();

	LogToFile($logfile, '1');

	$billingAddress = $quote->getShippingAddress();

	$shippingAddress = $quote->getShippingAddress();

	$items = $quote->getAllVisibleItems();

	if(isset($items))
		LogToFile($logfile, 'WE HAVE IT');
	else
		LogToFile($logfile, 'WE DONT HAVE IT');

/*
	LogToFile($logfile, 'Billing Name:'.$billingAddress->getName());
	LogToFile($logfile, 'Billing Company:'.$billingAddress->getCompany());
	LogToFile($logfile, 'Billing Street1:'.$billingAddress->getStreet1());
	LogToFile($logfile, 'Billing Street2:'.$billingAddress->getStreet2());
	LogToFile($logfile, 'Billing Street3:'.$billingAddress->getStreet3());
	LogToFile($logfile, 'Billing Street4:'.$billingAddress->getStreet4());
	LogToFile($logfile, 'Billing City:'.$billingAddress->getCity());
	LogToFile($logfile, 'Billing Region ID:'.$billingAddress->getRegionId());
	LogToFile($logfile, 'Billing Region Code:'.$billingAddress->getRegionCode());
	LogToFile($logfile, 'Billing Region:'.$billingAddress->getRegion());
	LogToFile($logfile, 'Billing Postal Code:'.$billingAddress->getPostcode());
	LogToFile($logfile, 'Billing Country:'.$billingAddress->getCountry());
	LogToFile($logfile, 'Billing Email:'.$billingAddress->getEmail());
	LogToFile($logfile, 'Billing Phone:'.$billingAddress->getTelephone());
	LogToFile($logfile, 'Billing Fax:'.$billingAddress->getFax());

	LogToFile($logfile, 'Shipping Name:'.$shippingAddress->getName());
	LogToFile($logfile, 'Shipping Company:'.$shippingAddress->getCompany());
	LogToFile($logfile, 'Shipping Street1:'.$shippingAddress->getStreet1());
	LogToFile($logfile, 'Shipping Street2:'.$shippingAddress->getStreet2());
	LogToFile($logfile, 'Shipping Street3:'.$shippingAddress->getStreet3());
	LogToFile($logfile, 'Shipping Street4:'.$shippingAddress->getStreet4());
	LogToFile($logfile, 'Shipping City:'.$shippingAddress->getCity());
	LogToFile($logfile, 'Shipping Region ID:'.$shippingAddress->getRegionId());
	LogToFile($logfile, 'Shipping Region Code:'.$shippingAddress->getRegionCode());
	LogToFile($logfile, 'Shipping Region:'.$shippingAddress->getRegion());
	LogToFile($logfile, 'Shipping Postal Code:'.$shippingAddress->getPostcode());
	LogToFile($logfile, 'Shipping Country:'.$shippingAddress->getCountry());
	LogToFile($logfile, 'Shipping Email:'.$shippingAddress->getEmail());
	LogToFile($logfile, 'Shipping Phone:'.$shippingAddress->getTelephone());
	LogToFile($logfile, 'Shipping Fax:'.$shippingAddress->getFax());
*/
	$paramhash = array();

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

	$itemidx = 0;
	$item = $items[0]->toArray();

	foreach ($items as $item)
	{
		$item = $item->toArray();

		LogToFile($logfile, print_r($item, true));

	        $paramhash["PRODUCTQUANTITY(" . $itemidx . ")"] = $item['qty'];
	        $paramhash["PRODUCTCODE(" . $itemidx . ")"] = $item['product']['sku'];
	        $paramhash["PRODUCTNAME(" . $itemidx . ")"] = $item['product']['name'];
	        $paramhash["PRODUCTPRICE(" . $itemidx . ")"] = $item['price'];
		
		$itemidx = $itemidx + 1;
	}

	$paramhash["ACTION"] = "CreateOrder";
	$paramhash["CANCELURL"] = Mage::getUrl('checkout/onepage/fail');
	$paramhash["RETURNURL"] = Mage::getUrl('checkout/onepage/success');
	$paramhash["NOTIFYURL"] = Mage::getUrl('checkout/onepage/notify');
	
	LogToFile($logfile, print_r($paramhash, true));

	fclose($logfile);
	
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

return (string)$xml->entry->link["href"];
	

			

//          return 'http://www.google.com/';
    }

    
    public function isAvailable($quote = null)
	{
		if($this->getDebug())
		{
	    	$writer = new Zend_Log_Writer_Stream($this->getLogPath());
			$logger = new Zend_Log($writer);
			$logger->info("entering isAvailable()");
		}
	
		$groupAccess = $this->getConfigData('customer_group_access');
		$group = $this->getConfigData('customer_group');
		
		if($this->getDebug())
		{
			$logger->info("Customer Group Access: " . $groupAccess);
			$logger->info("Customer Group: " . $group);
			$logger->info("Quoted Customer Group: " . $quote->getCustomerGroupId());
		}
		
		if($groupAccess == 0 || $group === '')
		{
			// No restrictions on access
			return true;
		}
		elseif($groupAccess == 1)
		{
			// Only allow customer to access this method if they are part of the
			// specified group
			if($quote->getCustomerGroupId() == $group)
			{
				return true;
			}
		}
		elseif($groupAccess == 2)
		{
			// Only allow customer to access this method if they are NOT part
			// of the specified group
			if($quote->getCustomerGroupId() != $group)
			{
				return true;
			}
		}
		
		// Default, restrict access
		return false;
	}

}


function LogToFile($logfile, $message)
{
	fprintf($logfile, $message);
	fflush($logfile);
}

