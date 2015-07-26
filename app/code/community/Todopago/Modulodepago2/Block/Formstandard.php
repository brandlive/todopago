<?php
class Todopago_Modulodepago2_Block_Formstandard extends Mage_Payment_Block_Form{
	protected function _construct(){
		parent::_construct ();
		
		$this->setTemplate ( 'todopagomodulodepago/standard_form.phtml' );
	}
	
	public function get_pay_methods(){
		$wsdls = $this->get_wsdls();
		$http_header = $this->get_http_header();
		$end_point = $this->get_end_point();
		
		require_once(Mage::getBaseDir('lib') . '/metododepago2/TodoPago.php');
		$connector = new TodoPago($http_header, $wsdls, $end_point);
		
		$merchant = Mage::getStoreConfig('payment/todopago_modo/idstore');	
		
		try{
			return $connector->getAllPaymentMethods(array('MERCHANT'=>$merchant));
		}
		catch(Exception $e){
			$exception['Operations']['Exception']=$e;
			return $exception;
		}
	}
	
	
	private function get_http_header(){
		return json_decode(Mage::getStoreConfig('payment/modulodepago2/header_http'), TRUE);
	}
	
	private function get_end_point(){
		return Mage::getStoreConfig('payment/todopago_modo/todopago_end_point');
	}
	
	private function get_wsdls(){
		return json_decode(Mage::getStoreConfig('payment/todopago_modo/todopago_wsdl'), TRUE);
	}
	
}