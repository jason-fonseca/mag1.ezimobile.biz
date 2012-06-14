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
 * @category   ezimerchant
 * @package    OnTechnology_ezimerchant
 * @author     On Technology
 * @copyright  Copyright (c) 2011 On Technology Pty. Ltd. (http://www.ezimerchant.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * ezimerchant Checkout Controller
 *
 */
class OnTechnology_Ezimerchant_PaystepController extends Mage_Core_Controller_Front_Action
{
    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    /**
     * When a customer chooses DPS on Checkout/DPS page
     */
    public function redirectAction()
    {
echo('did I get to redirect?');
die();
        $session = Mage::getSingleton('checkout/session');
        $session->setPxpayQuoteId($session->getQuoteId());
        $this->getResponse()->setBody($this->getLayout()->createBlock('dps/pxpay_redirect')->toHtml());
        $session->unsQuoteId();
    }

    /**
     * When a customer cancels payment from DPS.
     */
    public function cancelAction()
    {
echo('did I get to cancel?');
die();
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getPxpayQuoteId(true));
        $this->_redirect('checkout/cart');
     }

    /**
     * Where ezimerchant returns with a success.
     */
    public function  successAction()
    {
echo('did I get to success?');
die();

        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getPxpayQuoteId(true));
        
        // Set the quote as inactive after returning
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();

        // Send a confirmation email to customer
        $order = Mage::getModel('sales/order');
        $order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        if($order->getId()){
            $order->setStatus('processing')->save();
            $order->sendNewOrderEmail();
        }

        Mage::getSingleton('checkout/session')->unsQuoteId();

        $this->_redirect('checkout/onepage/success');
    }
    
    /**
     * Where ezimerchant returns with a failure.
     */
    public function  failAction()
    {
echo('did I get to fail?');
die();

        $order = Mage::getModel('sales/order');
        $order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        if($order->getId()){
            $order->setStatus('canceled')->save();
        }
        
        $this->_redirect('checkout/onepage/failure');
    }
}
