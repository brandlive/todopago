<?php
class Todopago_Modulodepago2_Helper_Ambiente extends Mage_Core_Helper_Abstract
{
	public function get_end_point(){
		$_end_point = "";
		if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
			$_end_point =   'https://developers.todopago.com.ar/services/t/1.1/';
			//$_end_point =   'http://23.23.144.247/services/t/1.1/';
		} else{
			$_end_point =  'https://apis.todopago.com.ar/services/t/1.1/';
		}
		return  $_end_point;
	}


	public function get_merchant(){
		$_merchant="";
		if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
			$_merchant = Mage::getStoreConfig('payment/todopago_modo/idstore_test');
		} else{
			$_merchant = Mage::getStoreConfig('payment/todopago_modo/idstore');
		}
		return $_merchant;
	}

	public function get_security_code(){
		$_security_code ="";
		if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
			$_security_code = Mage::getStoreConfig('payment/todopago_modo/codigo_seguridad_test');
		} else{
			$_security_code = Mage::getStoreConfig('payment/todopago_modo/codigo_seguridad');
		}

		return $_security_code;
	}
}