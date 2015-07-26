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

	public function get_metodo_de_pago(){
		return Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethodInstance()->getCode();
	}
	
	private function get_http_header(){
		return Mage::getStoreConfig('payment/modulodepago2/header_http');
	}
	
	private function get_end_point(){
		return Mage::getStoreConfig('payment/todopago_modo/todopago_end_point');
	}
	
	private function get_wsdls(){
		return json_decode(Mage::getStoreConfig('payment/todopago_modo/todopago_wsdl'), TRUE);
	}
}