<?php



class OnTechnology_Ezimerchant_Block_Paystep_Form extends Mage_Payment_Block_Form
{
	protected function _construct()
	{
		$this->setTemplate('ontechnology/ezimerchant/form.phtml');
		parent::_construct();
	}
}
