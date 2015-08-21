<?php
class Todopago_Modulodepago2_PaymentController extends Mage_Core_Controller_Front_Action{

    // este m�todo recolecta los datos b�sicos para realizar la primera transacci�n con el gateway
    public function getPayDataAction(){

        $id = Mage::getSingleton('checkout/session')
        ->getLastRealOrderId();

        $order = Mage::getSingleton('sales/order')
        ->loadByIncrementId($id);
        $productos = $order->getItemsCollection();

        $customer_id = $order->getCustomerId();
        $customer = Mage::getModel('customer/customer')->load($customer_id);

        Mage::log("Modulo de pago - Todopago ==> {order_id: $id, customer_id: $customer_id");
        $vertical = Mage::getStoreConfig('payment/modulodepago2/cs_verticales');
        $payDataComercial = array();

        Mage::log("orden desde el controller: ".$order->getCustomerEmail());
        $payDataOperacion = Todopago_Modulodepago2_Model_Cybersource_Factorycybersource::get_cybersource_extractor($vertical,
            $order, $customer)->getDataCS();

        // datos b�sicos para tipo de operaciones de pago
        // Merchant
        $payDataComercial ['URL_OK'] = Mage::getBaseUrl().'modulodepago2/payment/secondStep?Order='.$order->getIncrementId();
        $payDataComercial ['URL_ERROR'] = Mage::getBaseUrl().'modulodepago2/payment/secondStep?Order='.$order->getIncrementId();

        $payDataComercial ['Merchant'] = Mage::helper('modulodepago2/ambiente')->get_merchant();
        $payDataComercial ['Security'] = Mage::helper('modulodepago2/ambiente')->get_security_code();

        // EncodingMethod
        $payDataComercial ['EncodingMethod'] = 'XML';
        // NROCOMERCIO
        $payDataOperacion['MERCHANT'] = Mage::helper('modulodepago2/ambiente')->get_merchant();
        // NROOPERACION
        $payDataOperacion ['OPERATIONID'] = $order->getIncrementId();
        // MONTO
        $payDataOperacion ['AMOUNT'] = number_format($order->getGrandTotal(), 2, ".", "");
        //CURRENCY CODE
        $payDataOperacion ['CURRENCYCODE'] = "032";
        // EMAILCLIENTE (puede ser null)
        $payDataOperacion ['EMAILCLIENTE'] = $order->getCustomerEmail();

        $this->firstStep($payDataComercial, $payDataOperacion);
    }

    public function setCelularAction()
    {
        $quote_id = $this->getRequest()->get($quote_id);
        $celular = $this->getRequest()->get($celular);
        $checkout = Mage::getSingleton('checkout/session')->getQuote();
        $checkout->getBillingAddress()->setData('celular', $celular);
        $checkout->save();
    }

    public function firstStep($payDataComercio, $payDataOperacion){

        $order = new Mage_Sales_Model_Order ();
        $order->loadByIncrementId($payDataOperacion ['OPERATIONID']);

        try{

            require_once(Mage::getBaseDir('lib') . '/metododepago2/TodoPago.php');
            $http_header = $this->get_http_header();
            $wsdl = $this->get_wsdls();
            $end_point = $this->get_end_point();

            $todopago_connector = new TodoPago($http_header,$wsdl, $end_point);

            Mage::log("Modulo de pago - TodoPago ==> payDataComercio --> ".json_encode($payDataComercio));
            Mage::log("Modulo de pago - TodoPago ==> payDataOperacion --> ".json_encode($payDataOperacion));

            $first_step = $todopago_connector->sendAuthorizeRequest($payDataComercio, $payDataOperacion);

            if($first_step["StatusCode"] == 702){
                Mage::log("Modulo de pago - TodoPago ==> respuesta de sendAuthorizeRequest --> reintento SAR");
                $first_step = $todopago_connector->sendAuthorizeRequest($payDataComercio, $payDataOperacion);
            }


            Mage::log("Modulo de pago - TodoPago ==> respuesta de sendAuthorizeRequest -->".json_encode($first_step));

            $order->setData('todopagoclave', $first_step ['RequestKey']);
            $order->save();

            $todopagotable = new Todopago_Modulodepago2_Model_Todopagotable();
            $todopagotable->setOrderId($payDataOperacion ['OPERATIONID']);
            $todopagotable->setRequestKey($first_step ['RequestKey']);
            $todopagotable->setSendauthorizeanswerStatus(Mage::getModel('core/date')->date('Y-m-d H:i:s')." - ".$first_step["StatusCode"]." - ".$first_step['StatusMessage']);
            $todopagotable->save();

            if($first_step["StatusCode"] == -1){

                if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
                    $order->setStatus('test_todopago_processing');
                    $order->addStatusHistoryComment("Todo Pago (TEST): " . $first_step['StatusMessage']);
                }
                else
                {
                    $order->setStatus(Mage::getStoreConfig('payment/todopago_avanzada/order_status'));
                    $order->addStatusHistoryComment("Todo Pago: " . $first_step['StatusMessage']);
                }

                $order->save();
                Mage::log("Modulo de pago - TodoPago ==> redirige a: ".$first_step['URL_Request']);
                $this->_redirectUrl($first_step['URL_Request']);

            } else{

                if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
                    $order->setStatus('test_todopago_canceled');
                    $order->addStatusHistoryComment("Todo Pago (TEST): " . $first_step['StatusMessage']);

                } else{

                    $order->setStatus(Mage::getStoreConfig('payment/todopago_avanzada/estado_denegada'));
                    $order->addStatusHistoryComment("Todo Pago: " . $first_step['StatusMessage']);
                }

                $order->save();
                Mage::log("Modulo de pago - TodoPago ==> redirige a: checkout/onepage/failure");
                Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
            }

        } catch(Exception $e){
           Mage::log("Modulo de pago - TodoPago ==> (Exception)".$e);
           Mage::log("Modulo de pago - TodoPago ==> Exception: ");
           if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
            $order->setStatus('test_todopago_canceled');
            $order->addStatusHistoryComment("Todo Pago (TEST)(Exception): " . $e);
        } else{
            $order->setStatus(Mage::getStoreConfig('payment/todopago_avanzada/estado_denegada'));
            $order->addStatusHistoryComment("Modulo de pago - TodoPago ==> (Exception)" . $e);
        }
        $order->save();
        Mage::log("Modulo de pago - TodoPago ==> redirige a: checkout/onepage/failure");
        Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));

    }
}

public function secondStepAction(){
    $todopagotable = new Todopago_Modulodepago2_Model_Todopagotable();
    $todopagotable->load($this->getRequest()->get('Order'), "order_id");
    if($todopagotable->getAnswerKey() == null){
        $this->lastStep($this->getRequest()->get('Order'), $this->getRequest()->get('Answer'));
    }else{
        Mage_Core_Controller_Varien_Action::_redirect('/', array('_secure' => true));
    }
}

public function lastStep($order_key, $answer_key){

    require_once(Mage::getBaseDir('lib') . '/metododepago2/TodoPago.php');
    $http_header = $this->get_http_header();
    $wsdl = $this->get_wsdls();
    $end_point = $this->get_end_point();

    $todopago_connector = new TodoPago($http_header,$wsdl, $end_point);

        // /a este metodo es al que me va a devolver el gateway en caso que todo salga ok
    Mage::log("Modulo de pago - TodoPago ==> secondStep - orderid: ".$order_id);
    Mage::log("Modulo de pago - TodoPago ==> secondStep - AnswerKey: ".$answer_key);
    $order = new Mage_Sales_Model_Order ();
    $order->loadByIncrementId($order_key);

    //merchant
    $merchant = Mage::helper('modulodepago2/ambiente')->get_merchant();

    // Security
    $security = Mage::helper('modulodepago2/ambiente')->get_security_code();

    $requestkey = $order->getTodopagoclave();

        // ahora vuelvo a consumir web service para confirmar la transaccion
    $optionsAnswer = array(
        'Security' => $security,
        'Merchant' => $merchant,
        'RequestKey' => $requestkey,
        'AnswerKey' => $answer_key
        );

    Mage::log("Modulo de pago - TodoPago ==> secondStep (".$order_id.") - AnswerKey: ".json_encode($optionsAnswer));

    try{

        $second_step = $todopago_connector->getAuthorizeAnswer($optionsAnswer);
        Mage::log("Modulo de pago - TodoPago ==> secondStep (".$order_id.") - $second_step: ".json_encode($second_step));

        $todopagotable = new Todopago_Modulodepago2_Model_Todopagotable();
        $todopagotable->load($order_key, "order_id");
        $todopagotable->setAnswerKey($answer_key);
        $todopagotable->setGetauthorizeanswerStatus(Mage::getModel('core/date')->date('Y-m-d H:i:s')." - ".$second_step["StatusCode"]." - ".$second_step['StatusMessage']);
        $todopagotable->save();

            //para saber si es un cupon
        if(strlen($second_step['Payload']['Answer']["BARCODE"]) > 0){
            if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
                $order->setStatus(Mage::getStoreConfig('test_todopago_offline'));
                $order->addStatusHistoryComment("Todo Pago (TEST): " . $second_step['StatusMessage']);


            } else{
                $order->setStatus(Mage::getStoreConfig('payment/todopago_avanzada/estado_offline'));
                $order->addStatusHistoryComment("Todo Pago: " . $second_step['StatusMessage']);
            }

            $order->save();
            Mage_Core_Controller_Varien_Action::_redirect('modulodepago2/cupon/index', array('_secure' => true,
               'nroop' => $order_id,
               'venc' => $second_step['Payload']['Answer']["COUPONEXPDATE"],
               'total' => $second_step['Payload']['Request']['AMOUNT'],
               'code' => $second_step['Payload']['Answer']["BARCODE"],
               'tipocode' => $second_step['Payload']['Answer']["BARCODETYPE"],
               'empresa' => $second_step['Payload']['Answer']["PAYMENTMETHODNAME"],
               ));
        }

            //caso de transaccion aprovada
        elseif($second_step['StatusCode'] == -1){

            if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
                $order->setStatus('test_todopago_complete');
                $order->addStatusHistoryComment("Todo Pago (TEST): " . $second_step['StatusMessage']);

            } else{

                $order->setStatus(Mage::getStoreConfig('payment/todopago_avanzada/order_aprov'));
                $order->addStatusHistoryComment("Todo Pago: " . $second_step['StatusMessage']);
            }

            $order->save();
            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure' => true));
        }

            //caso de transaccion no aprobada
        elseif($second_step['StatusCode'] != -1){
            if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
                $order->setStatus('test_todopago_canceled');
                $order->addStatusHistoryComment("Todo Pago (TEST): " . $second_step['StatusMessage']);


            } else{
                $order->setStatus(Mage::getStoreConfig('payment/todopago_avanzada/estado_denegada'));
                $order->addStatusHistoryComment("Todo Pago: " . $second_step['StatusMessage']);

            }
            $order->save();
            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));            }
        }
        catch(Exception $e){
            if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
                $order->setStatus('test_todopago_canceled');
                $order->addStatusHistoryComment("Todo Pago (TEST)(Exception): " . $e);

            } else{
                $order->setStatus(Mage::getStoreConfig('payment/todopago_avanzada/estado_denegada'));
                $order->addStatusHistoryComment("Todo Pago (Exception): " . $e);
            }
            $order->save();
            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
        }
    }

    public function urlerrorAction(){
        // este m�to es al que me dva a devolder el gateway en caso que algo salga mal
        $order_id = $this->getRequest()->get('Order');
        Mage::log("Modulo de pago - TodoPago ==> urlerror - orderid: ".$order_id);
        $answer = $this->getRequest()->get('Answer');
        Mage::log("Modulo de pago - TodoPago ==> urlerror - AnswerKey: ".$answer);

        $order = new Mage_Sales_Model_Order ();
        $order->loadByIncrementId($order_id);

        if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
            $order->setStatus('test_todopago_canceled');
            $order->addStatusHistoryComment("Todo Pago (TEST): error en el pago del formulario");

        } else{
            $order->setStatus(Mage::getStoreConfig('payment/todopago_avanzada/estado_denegada'));
            $order->addStatusHistoryComment("Todo Pago: error en el pago del formulario");
        }
        $order->save();
        Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
    }

    private function get_http_header(){
        return json_decode(Mage::getStoreConfig('payment/modulodepago2/header_http'), TRUE);
    }

    private function get_end_point(){
        return Mage::helper('modulodepago2/ambiente')->get_end_point();
    }

    private function get_wsdls(){
        $wsdl['Authorize'] = Mage::getBaseDir('lib').'/metododepago2/Authorize.wsdl';
        $wsdl['Operations'] = Mage::getBaseDir('lib').'/metododepago2/Operations.wsdl';
        return $wsdl;
    }

    private function redirect_first($cart_array){
        Mage_Core_Controller_Varien_Action::_redirect('modulodepago2/formulariocustom/insite',
          array('_secure' => true, 'amount'=>$cart_array['Amount'])
          );
    }

}