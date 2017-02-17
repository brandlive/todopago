<?php
class Todopago_Modulodepago2_Helper_Todopagolog extends Mage_Core_Helper_Abstract
{
	
	public function log($message, $level = null, $file = 'todopago.log'){
		$magento_version = Mage::getVersion();
		$modulo_vesion 	 = Mage::getConfig()->getModuleConfig("Todopago_Modulodepago2")->version;
		$php_version 	 = phpversion();
		Mage::log("[Mag:$magento_version - TP:$modulo_vesion] ".$message, $level, $file);
	}


}