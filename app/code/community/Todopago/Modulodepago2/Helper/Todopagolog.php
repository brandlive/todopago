<?php
class Todopago_Modulodepago2_Helper_Todopagolog extends Mage_Core_Helper_Abstract
{
	public function start_values($order=null, $customer=null){
		$this->order = $order;
		$this->customer = $customer;
	}

	public function log($message){
		$magento_version = Mage::getVersion();
		$modulo_vesion = Mage::getConfig()->getModuleConfig("Todopago_Modulodepago2")->version;
		$php_version = phpversion();
		Mage::log("[TP_M2 v$modulo_vesion - Mag:$magento_version - php: $php_version (order: $this->order - customer: $this->customer)] ".$message );
	}


}