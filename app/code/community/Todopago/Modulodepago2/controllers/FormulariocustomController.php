<?php
class Todopago_Modulodepago2_FormulariocustomController extends Mage_Core_Controller_Front_Action
{

	public function insiteAction(){

		$this->loadLayout();
		$this->getLayout()->getBlock("head")->setTitle($this->__("Formulario de pago"));
		$this->renderLayout();
	}

	public function whitepageAction(){
		$this->loadLayout();
		$this->getLayout()->getBlock("head")->setTitle($this->__("Formulario de pago"));
		$this->renderLayout();
	}
}