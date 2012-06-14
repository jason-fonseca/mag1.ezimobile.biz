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
 * Listing of customer groups available in the shop.
 *
 * @category   ezimerchant
 * @package    OnTechnology_ezimerchant
 */
class OnTechnology_Ezimerchant_Model_Config_CustomerGroups
{
	public function toOptionArray()
	{
		$storeId = Mage::app()->getStore()->getId();
		$collection = Mage::getModel('customer/group')->setStoreId($storeId)->getCollection();
		
		foreach($collection as $group)
		{
			$options[] = array('value' => (int)$group->getCustomerGroupId(), 'label' => $group->getCustomerGroupCode());
		}
		
		return $options;
	}
}
