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
 * @package     Mage_Sales
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class OnTechnology_Sales_Model_Order_Creditmemo extends Mage_Sales_Model_Order_Creditmemo
{
    


    public function refund()
    {	
	
        $this->setState(self::STATE_REFUNDED);
        $orderRefund = Mage::app()->getStore()->roundPrice($this->getOrder()->getTotalRefunded()+$this->getGrandTotal());
        $baseOrderRefund = Mage::app()->getStore()->roundPrice($this->getOrder()->getBaseTotalRefunded()+$this->getBaseGrandTotal());

        if ($baseOrderRefund > Mage::app()->getStore()->roundPrice($this->getOrder()->getBaseTotalPaid())) {

            $baseAvailableRefund = $this->getOrder()->getBaseTotalPaid()- $this->getOrder()->getBaseTotalRefunded();

            Mage::throwException(
                Mage::helper('sales')->__('Maximum amount available to refund is %s',
                    $this->getOrder()->formatBasePrice($baseAvailableRefund)
                )
            );
        }
        $order = $this->getOrder();
        $order->setBaseTotalRefunded($baseOrderRefund);
        $order->setTotalRefunded($orderRefund);

        $order->setBaseSubtotalRefunded($order->getBaseSubtotalRefunded()+$this->getBaseSubtotal());
        $order->setSubtotalRefunded($order->getSubtotalRefunded()+$this->getSubtotal());

        $order->setBaseTaxRefunded($order->getBaseTaxRefunded()+$this->getBaseTaxAmount());
        $order->setTaxRefunded($order->getTaxRefunded()+$this->getTaxAmount());
        $order->setBaseHiddenTaxRefunded($order->getBaseHiddenTaxRefunded()+$this->getBaseHiddenTaxAmount());
        $order->setHiddenTaxRefunded($order->getHiddenTaxRefunded()+$this->getHiddenTaxAmount());

        $order->setBaseShippingRefunded($order->getBaseShippingRefunded()+$this->getBaseShippingAmount());
        $order->setShippingRefunded($order->getShippingRefunded()+$this->getShippingAmount());

        $order->setBaseShippingTaxRefunded($order->getBaseShippingTaxRefunded()+$this->getBaseShippingTaxAmount());
        $order->setShippingTaxRefunded($order->getShippingTaxRefunded()+$this->getShippingTaxAmount());

        $order->setAdjustmentPositive($order->getAdjustmentPositive()+$this->getAdjustmentPositive());
        $order->setBaseAdjustmentPositive($order->getBaseAdjustmentPositive()+$this->getBaseAdjustmentPositive());

        $order->setAdjustmentNegative($order->getAdjustmentNegative()+$this->getAdjustmentNegative());
        $order->setBaseAdjustmentNegative($order->getBaseAdjustmentNegative()+$this->getBaseAdjustmentNegative());

        $order->setDiscountRefunded($order->getDiscountRefunded()+$this->getDiscountAmount());
        $order->setBaseDiscountRefunded($order->getBaseDiscountRefunded()+$this->getBaseDiscountAmount());

        if ($this->getInvoice()) {
            $this->getInvoice()->setIsUsedForRefund(true);
            $this->getInvoice()->setBaseTotalRefunded(
                $this->getInvoice()->getBaseTotalRefunded() + $this->getBaseGrandTotal()
            );
            $this->setInvoiceId($this->getInvoice()->getId());
        }

        if (!$this->getPaymentRefundDisallowed()) {
			if($order->getExtOrderId())
			{
									 
									 $merchantid = Mage::getStoreConfig('payment/ezimerchant/merchantid');
									 $apikey = Mage::getStoreConfig('payment/ezimerchant/apikey');
									 
						$paramhash["ACTION"] = "RefundOrder";
						$paramhash["AMOUNT"] = $orderRefund;
						
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, "https://api.ezimerchant.com/".$merchantid."/orders/".$order->getExtOrderId()."/");
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded", "X-APIKEY:".$apikey ));
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paramhash));

						$apiresponse = curl_exec($ch);
						curl_close($ch);               


						

						$xml = simplexml_load_string($apiresponse);	

						
						
						$refundcontent = $xml->entry->children('http://api.ezimerchant.com/schemas/2009/');
						if($refundcontent->success == 1)
						{
						$order->getPayment()->refund($this);
						}
			}
			else
			{
            $order->getPayment()->refund($this);
			}
        }

        Mage::dispatchEvent('sales_order_creditmemo_refund', array($this->_eventObject=>$this));
        return $this;
    }

    
}
