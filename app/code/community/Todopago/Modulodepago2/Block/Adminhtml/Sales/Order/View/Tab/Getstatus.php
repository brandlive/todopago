<?php

class Todopago_modulodepago2_Block_Adminhtml_Sales_Order_View_Tab_Getstatus extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface{


	protected $_chat = null;

	protected function _construct(){
		parent::_construct();
		$this->setTemplate('modulodepago2/sales/order/view/tab/getstatus.phtml');
	}

	public function getTabLabel(){
		return $this->__('Información de Pago (TodoPago)');
	}

	public function getTabTitle(){
		return $this->__('Información de Pago (TodoPago)');
	}

	public function canShowTab(){
		return true;
	}

	public function isHidden(){
		return false;
	}

	public function getOrder(){
		return Mage::registry('current_order');
	}

	public function getLastStatus(){
		require_once(Mage::getBaseDir('lib') . '/metododepago2/TodoPago.php');
		$wsdls = $this->get_wsdls();
		
		$http_header = $this->get_http_header();
		
		$end_point = $this->get_end_point();
		
		$connector = new TodoPago($http_header, $wsdls, $end_point);
		$order_id =  $this->getRequest()->get('order_id');
		$id = $this->getOrderIncrementId($order_id);

		if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
			$merchant = Mage::getStoreConfig('payment/todopago_modo/idstore_test');
		} else{
			$merchant = Mage::getStoreConfig('payment/todopago_modo/idstore');
		}

		try{
			return $connector->getStatus(array('MERCHANT'=>$merchant, 'OPERATIONID'=>$id));
		}
		catch(Exception $e){
			$exception['Operations']['Exception']="Error el consumir Web Service Todopago";
			return $exception;
		}
	}

	private  function getOrderIncrementId($order_id){
		$order = Mage::getModel("sales/order")->load($order_id)->getIncrementId();
		return $order; 
	}

	private function get_http_header(){
		return json_decode(Mage::getStoreConfig('payment/modulodepago2/header_http'), TRUE);
	}

	private function get_end_point(){
		if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
			return  Mage::helper('modulodepago2/data')->getDevelopersEndpoint();
		} else{
			return Mage::helper('modulodepago2/data')->getApisEndpoint();
		}
	}

	private function get_wsdls(){
		$wsdl['Authorize'] = Mage::getBaseDir('lib').'/metododepago2/Authorize.wsdl';
		$wsdl['Operations'] = Mage::getBaseDir('lib').'/metododepago2/Operations.wsdl';
		return $wsdl;
	}
}