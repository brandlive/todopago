<?php
class Todopago_Modulodepago2_Helper_Connector extends Mage_Core_Helper_Abstract
{
	public function getConnector() {
		require_once(Mage::getBaseDir('lib') . '/metododepago2/TodoPago/lib/Sdk.php');
		
		$http_header = $this->getHeader();
		$mode = $this->getModo();
			
		$todopago_connector = new TodoPago\Sdk($http_header, $mode);
		
		$proxyhost = Mage::getStoreConfig('payment/todopago_servicio/proxyhost');
		$proxyport = Mage::getStoreConfig('payment/todopago_servicio/proxyport');
		$proxypass = Mage::getStoreConfig('payment/todopago_servicio/proxypassword');
		$proxyuser = Mage::getStoreConfig('payment/todopago_servicio/proxyuser');
		
		if(!empty($proxyhost) && !empty($proxyport))
			$todopago_connector->setProxyParameters($proxyhost, $proxyport, $proxyuser, $proxypass);

		return $todopago_connector;
	}
	
	public function getModo() {
		if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
			return "test";
		}
		return "prod";
	}
	
	public function getHeader() {
		$header = json_decode(Mage::getStoreConfig('payment/modulodepago2/header_http'), TRUE);
		if($header == null) {
			$header = array("Authorization" => Mage::getStoreConfig('payment/modulodepago2/header_http'));
		}
        return $header;
    }
}