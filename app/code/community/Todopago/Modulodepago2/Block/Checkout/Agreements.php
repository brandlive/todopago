<?php
class Todopago_Modulodepago2_Block_Checkout_Agreements extends Mage_Checkout_Block_Agreements{
	/**
	* Override block template
	*
	* @return string
	*/
	protected function _toHtml(){
		$this->setTemplate('todopagomodulodepago/checkout/agreements.phtml');
		return parent::_toHtml();
	}
}