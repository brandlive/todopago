<?php
class Todopago_Modulodepago2_Model_Cybersource_Retail extends Todopago_Modulodepago2_Model_Cybersource_Cybersource{

    protected function completeCSVertical(){
        $payDataOperacion = array();
        //Llamo al metodo de billingAdress para capturar el nombre del usuario
        $billingAdress = $this->order->getBillingAddress();
        $shippingAdress = $this->order->getShippingAddress();
        $payDataOperacion ['CSSTCITY'] = $this->getField($shippingAdress->getCity());
        $payDataOperacion ['CSSTCOUNTRY'] = $this->getField($shippingAdress->getCountry());

        $email = $this->getField($shippingAdress->getEmail());
        if( empty($email) )
             $payDataOperacion ['CSSTEMAIL'] = $this->getField($this->order->getCustomerEmail());
        else $payDataOperacion ['CSSTEMAIL'] = $this->getField($shippingAdress->getEmail());  

        $payDataOperacion ['CSSTFIRSTNAME'] = Mage::helper('modulodepago2/data')->removeSpecialChars($this->getField($billingAdress->getFirstname()));
        $payDataOperacion ['CSSTLASTNAME'] = Mage::helper('modulodepago2/data')->removeSpecialChars($this->getField($billingAdress->getLastname()));
        $payDataOperacion ['CSSTPHONENUMBER'] = $this->getField($shippingAdress->getTelephone());
        if(!$payDataOperacion ['CSSTPHONENUMBER'] || strlen($payDataOperacion ['CSSTPHONENUMBER']) < 6) $payDataOperacion ['CSSTPHONENUMBER'] = '111111111';
        $payDataOperacion ['CSSTPOSTALCODE'] = $this->getField($shippingAdress->getPostcode());
        $payDataOperacion ['CSSTSTATE'] = strtoupper(substr($this->getField($shippingAdress->getRegion()), 0, 1));
        $payDataOperacion ['CSSTSTREET1'] =$this->getField($shippingAdress->getStreet1());
        $payDataOperacion ['CSMDD12'] = Mage::getStoreConfig('payment/modulodepago2/cs_deadline');
        $payDataOperacion ['CSMDD13'] = $this->getField($this->order->getShippingDescription());
                //$payData ['CSMDD14'] = "";
                //$payData ['CSMDD15'] = "";
        $payDataOperacion ['CSMDD16'] = $this->getField($this->order->getCuponCode());
        $payDataOperacion = array_merge($this->getMultipleProductsInfo(), $payDataOperacion);

        return $payDataOperacion;
    }

    protected function getCategoryArray($product_id){
        return Mage::helper('modulodepago2/data')->getCategoryTodopago($product_id);
    }
}