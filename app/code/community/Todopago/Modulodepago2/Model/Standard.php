<?php

class Todopago_Modulodepago2_Model_Standard extends Mage_Payment_Model_Method_Abstract{

	protected $_code = 'modulodepago2';
	protected $_formBlockType = 'modulodepago2/formstandard';
	protected $_infoBlockType = 'modulodepago2/infostandard';
	protected $_isGateway = true;
	protected $_canCapturePartial = true;
	protected $_canRefund = true;
	protected $_canRefundInvoicePartial = true;
	protected $_canCapture = true;


	public function getOrderPlaceRedirectUrl(){
		return Mage::getUrl('modulodepago2/payment/getPayData', array(
				'_secure' => true,
				'quote' => $this->quote,
			));
	}

	public function assignData($data){
		if(!($data instanceof Varien_Object)){
			$data = new Varien_Object($data);
		}
		$this->quote = $data->getQuote();
		return $this;
	}

}
