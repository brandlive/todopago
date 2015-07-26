<?php
class Todopago_Modulodepago2_PaymentController extends Mage_Core_Controller_Front_Action{

    // este m�todo recolecta los datos b�sicos para realizar la primera transacci�n con el gateway
    public function getPayDataAction(){

        $id = Mage::getSingleton('checkout/session')
        ->getLastRealOrderId();

        $order = Mage::getSingleton('sales/order')
        ->loadByIncrementId($id);
        $productos = $order->getItemsCollection();
        $customer = Mage::getModel('customer/customer')->load($customer_id);

        Mage::log("Modulo de pago - Todopago ==> {order_id: $id, customer_id: $customer_id}");

        $payDataComercial = array();
        $payDataOperacion = array();

        // datos b�sicos para tipo de operaciones de pago
        // Merchant
        $payDataComercial ['URL_OK'] = Mage::getBaseUrl().'modulodepago2/payment/secondStep?Order='.$order->getIncrementId();
        $payDataComercial ['URL_ERROR'] = Mage::getBaseUrl().'modulodepago2/payment/secondStep?Order='.$order->getIncrementId();

        if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
            $payDataComercial ['Merchant'] = Mage::getStoreConfig('payment/todopago_modo/idstore_test');
            $payDataComercial ['Security'] = Mage::getStoreConfig('payment/todopago_modo/codigo_seguridad_test');
        } else{
            $payDataComercial['Merchant'] = Mage::getStoreConfig('payment/todopago_modo/idstore');
            $payDataComercial['Security'] = Mage::getStoreConfig('payment/todopago_modo/codigo_seguridad');
        }

        // EncodingMethod
        $payDataComercial ['EncodingMethod'] = 'XML';
        // NROCOMERCIO
        if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
            $payDataOperacion ['MERCHANT'] = Mage::getStoreConfig('payment/todopago_modo/idstore_test');
        } else{
            $payDataOperacion['MERCHANT'] = Mage::getStoreConfig('payment/todopago_modo/idstore');
        }
        // NROOPERACION
        $payDataOperacion ['OPERATIONID'] = $order->getIncrementId();
        // MONTO
        $payDataOperacion ['AMOUNT'] = number_format($order->getGrandTotal(), 2, ".", "");
        //CURRENCY CODE
        $payDataOperacion ['CURRENCYCODE'] = "032";
        // EMAILCLIENTE (puede ser null)
        $payDataOperacion ['EMAILCLIENTE'] = $order->getCustomerEmail();

        // OBLIGATORIOS PREVENSION DE FRAUDE
        if(TRUE){
            // CSBTCITY - Ciudad de facturaci�n
            $payDataOperacion ['CSBTCITY'] = $this->_sanitize_string($order->getBillingAddress()->getCity());
            // CSBTCOUNTRY - pa�s de facturaci�n (ver si magento utiliza C�digo ISO)
            $payDataOperacion ['CSBTCOUNTRY'] = substr($order->getBillingAddress()->getCountry(),0,2);
            // CSBTCUSTOMERID - identificador del usuario (no correo electronico)
            $payDataOperacion ['CSBTCUSTOMERID'] = $order->getBillingAddress()->getCustomerId();
            if($payDataOperacion ['CSBTCUSTOMERID']=="" or $payDataOperacion ['CSBTCUSTOMERID']==null)
            {
                $payDataOperacion ['CSBTCUSTOMERID']= "guest".date("ymdhs");
            }
            // CSBTIPADDRESS - ip de la pc del comprador
            $payDataOperacion ['CSBTIPADDRESS'] = $order->getRemoteIp();
            // CSBTEMAIL - email del usuario al que se le emite la factura
            $payDataOperacion ['CSBTEMAIL'] = $order->getBillingAddress()->getEmail();
            // CSBTFIRSTNAME - nombre de usuario el que se le emite la factura
            $payDataOperacion ['CSBTFIRSTNAME'] = $this->_sanitize_string($order->getBillingAddress()->getFirstname());
            // CSBTLASTNAME - Apellido del usuario al que se le emite la factura
            $payDataOperacion ['CSBTLASTNAME'] = $this->_sanitize_string($order->getBillingAddress()->getLastname());
            // CSBTPOSTALCODE - Código Postal de la dirección de facturación
            $payDataOperacion ['CSBTPOSTALCODE'] = $order->getBillingAddress()->getPostcode();
            // CSBTPHONENUMBER - Tel�fono del usuario al que se le emite la factura. No utilizar guiones, puntos o espacios. Incluir c�digo de pa�s
            $payDataOperacion ['CSBTPHONENUMBER'] = $this->_sanitize_string($order->getBillingAddress()->getTelephone());
            // CSBTSTATE - Provincia de la direcci�n de facturaci�n (hay que cambiar esto!!! por datos hacepatdos por el gateway)
            $payDataOperacion ['CSBTSTATE'] =  strtoupper(substr($order->getBillingAddress()->getRegion(),0,1));
            // CSBTSTREET1 - Domicilio de facturaci�n (calle y nro)
            $payDataOperacion ['CSBTSTREET1'] = $this->_sanitize_string($order->getBillingAddress()->getStreet1());
            // CSBTSTREET2 - Complemento del domicilio. (piso, departamento)_ No Mandatorio
            //$payDataOperacion ['CSBTSTREET2'] = $order->getBillingAddress()->getStreet2();
            // CSPTCURRENCY- moneda
            $payDataOperacion ['CSPTCURRENCY'] = $order->getBaseCurrencyCode();
            // CSPTGRANDTOTALAMOUNT - "999999[.CC]" Con decimales opcional usando el puntos como separador de decimales. No se permiten comas, ni como separador de miles ni como separador de decimales.
            $payDataOperacion ['CSPTGRANDTOTALAMOUNT'] = $payDataOperacion ['AMOUNT'];
            // CSMDD6 - Canal de venta
            //$payDataOperacion ['CSMDD6'] = Mage::getStoreConfig('payment/modulodepago2/cs_canaldeventa');
            // CSMDD7 - Fecha Registro Comprador (num Dias) - ver que pasa si es guest
            $fecha_1 = date('d-m-Y', Mage::getModel('core/date')->timestamp($customer->getCreatedAt()));
            $fecha_2 = date('d-m-Y', Mage::getModel('core/date')->timestamp(Mage::app()->getLocale()->date()));
            $payDataOperacion ['CSMDD7'] = Mage::helper('modulodepago2/data')->diasTranscurridos($fecha_1, $fecha_2);
            // CSMDD8 - Usuario Guest? (Y/N). En caso de ser Y, el campo CSMDD9 no deber� enviarse
            if($order->getCustomerIsGuest()){
                $payDataOperacion ['CSMDD8'] = "N";
            } else{
                // CSMDD9 - Customer password Hash: criptograma asociado al password del comprador final
                $payDataOperacion ['CSMDD9'] = $customer->getPasswordHash();
            }

            // CSMDD11 Customer Cell Phone
            if(!$customer->getCelular()){
                $payDataOperacion ['CSMDD11'] = $payDataOperacion['CSBTPHONENUMBER'];
            } else{
                $payDataOperacion ['CSMDD11'] = $customer->getCelular();
            }
            /*
			* ---RETAIL-----
			* */

            if(true){
                // CSSTCITY - Ciudad de env�o de la orden
                $payDataOperacion ['CSSTCITY'] = $this->_sanitize_string($order->getShippingAddress()->getCity());
                // CSSTCOUNTRY Pa�s de env�o de la orden
                $payDataOperacion ['CSSTCOUNTRY'] = $order->getShippingAddress()->getCountry();
                // CSSTEMAIL Mail del destinatario
                $payDataOperacion ['CSSTEMAIL'] = $order->getShippingAddress()->getEmail();
                // CSSTFIRSTNAME Nombre del destinatario
                $payDataOperacion ['CSSTFIRSTNAME'] = $this->_sanitize_string($order->getShippingAddress()->getFirstname());
                // CSSTLASTNAME Apellido del destinatario
                $payDataOperacion ['CSSTLASTNAME'] = $order->getShippingAddress()->getLastname();
                // CSSTPHONENUMBER N�mero de tel�fono del destinatario
                $payDataOperacion ['CSSTPHONENUMBER'] = $this->_sanitize_string($order->getShippingAddress()->getTelephone());
                // CSSTPOSTALCODE C�digo postal del domicilio de env�o
                $payDataOperacion ['CSSTPOSTALCODE'] = $order->getShippingAddress()->getPostcode();
                // CSSTSTATE Provincia de env�o
                $payDataOperacion ['CSSTSTATE'] = strtoupper(substr($order->getShippingAddress()->getRegion(), 0, 1));
                // CSSTSTREET1 Domicilio de env�o
                $payDataOperacion ['CSSTSTREET1'] = $this->_sanitize_string($order->getShippingAddress()->getStreet1());
                // CSSTSTREET2 Localidad de env�o
                //$payDataOperacion ['CSSTSTREET2'] = $order->getShippingAddress()->getCity();
                // CSMDD12 Shipping DeadLine (Num Dias)
                $payDataOperacion ['CSMDD12'] = Mage::getStoreConfig('payment/modulodepago2/cs_deadline');
                // CSMDD13 M�todo de Despacho
                $payDataOperacion ['CSMDD13'] = $order->getShippingDescription();
                // CSMDD14 Customer requires Tax Bill ? (Y/N) No
                //$payData ['CSMDD14'] = "";
                // CSMDD15 Customer Loyality Number No
                //$payData ['CSMDD15'] = "";
                // CSMDD16 Promotional / Coupon Code
                $payDataOperacion ['CSMDD16'] = $order->getCuponCode();
                // /////////////////////////////////////////////////////////
                // CSITPRODUCTCODE C�digo del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_id = Mage::getModel('catalog/product')->load($item->getProductId())->getTodopagocodigo();
                    $category_cs[] = Mage::helper('modulodepago2/data')->getCategoryTodopago($product_id);
                }
                $payDataOperacion ['CSITPRODUCTCODE'] = join('#', $category_cs);


                // /////////////////////////////////////////////////////////////
                // CSITPRODUCTDESCRIPTION Descripci�n del producto cada un separado con #
                $productos_array = array();
                foreach($productos as $item){
                 $_description = Mage::getModel('catalog/product')->load($item->getProductId())->getDescription();
                 $_description = $this->_sanitize_string($_description);
                 $_description = trim($_description);
                 $_description = substr($_description, 0,15);
                 $productos_array [] = $_description;
             }
             $payDataOperacion ['CSITPRODUCTDESCRIPTION'] = join("#", $productos_array);
                // CSITPRODUCTNAME - Nombre del producto
             $productos_array = array();
             foreach($productos as $item){
                $product_name = $item->getName();
                $productos_array [] = $product_name;
            }
            $payDataOperacion ['CSITPRODUCTNAME'] = join("#", $productos_array);
                // CSITPRODUCTSKU - C�digo identificador del producto
            $productos_array = array();
            foreach($productos as $item){
                $product_name = $item->getSku();
                $productos_array [] = $this->_sanitize_string($product_name);
            }
            $payDataOperacion ['CSITPRODUCTSKU'] = join("#", $productos_array);
                // CSITTOTALAMOUNT - CSITTOTALAMOUNT = CSITUNITPRICE * CSITQUANTITY
            $productos_array = array();
            foreach($productos as $item){
                $product_qty = $item->getQtyOrdered();
                $prdocut_price = $item->getPrice();
                $product_amount = number_format($product_qty * $prdocut_price, 2, ".", "");
                $productos_array [] = $product_amount;
            }
            $payDataOperacion ['CSITTOTALAMOUNT'] = join("#", $productos_array);
                // CSITQUANTITY - Cantidad del producto
            $productos_array = array();
            foreach($productos as $item){
                $product_name = $item->getQtyOrdered();
                $productos_array [] = intval($product_name);
            }
            $payDataOperacion ['CSITQUANTITY'] = join("#", $productos_array);

                // CSITUNITPRICE - "999999[.CC]"
            $productos_array = array();
            foreach($productos as $item){
                $product_name = number_format($item->getPrice(), 2, ".", "");
                $productos_array [] = $product_name;
            }
            $payDataOperacion ['CSITUNITPRICE'] = join("#", $productos_array);
        }
            /*
			* ---SERVICE-----
			* */
            if(false){
                //CSMDD28 - Tipo de Servicio
                $productos_array = array();
                foreach($productos as $item){
                    $product_id = Mage::getModel('catalog/product')->load($item->getProductId())->getTodopagocodigo();
                    $category_cs[] = Mage::helper('modulodepago2/data')->getTipoServicioTodopago($product_id);
                }
                $payDataOperacion['CSMDD28'] = join("#", $category_cs);
                ////////////////////////////////////////////////////////////
                //CSMDD29, CSMDD30, CSMDD31 /////////////////////////////////////////////////////////
                // CSITPRODUCTCODE C�digo del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_id = Mage::getModel('catalog/product')->load($item->getProductId())->getTodopagocodigo();
                    $category_cs[] = Mage::helper('modulodepago2/data')->getCategoryTodopago($product_id);
                }
                $payDataOperacion ['CSITPRODUCTCODE'] = join("#", $category_cs);
                // /////////////////////////////////////////////////////////////
                // CSITPRODUCTDESCRIPTION Descripci�n del producto cada un separado con #
                $productos_array = array();
                foreach($productos as $item){
                    $_description = Mage::getModel('catalog/product')->load($item->getProductId())->getDescription();
                    $_description = strip_tags($_description);
                    $_description = trim($_description);
                    $_description = substr($_description, 0,30);
                    $productos_array [] = $_description;
                }
                $payDataOperacion ['CSITPRODUCTDESCRIPTION'] = join("#", $productos_array);
                // CSITPRODUCTNAME - Nombre del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_name = $item->getName();
                    $productos_array [] = $product_name;
                }
                $payDataOperacion ['CSITPRODUCTNAME'] = join("#", $productos_array);
                // CSITPRODUCTSKU - C�digo identificador del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_name = $item->getSku();
                    $productos_array [] = $product_name;
                }
                $payDataOperacion ['CSITPRODUCTSKU'] = join("#", $productos_array);
                // CSITTOTALAMOUNT - CSITTOTALAMOUNT = CSITUNITPRICE * CSITQUANTITY
                $productos_array = array();
                foreach($productos as $item){
                    $product_qty = $item->getQtyOrdered();
                    $prdocut_price = $item->getPrice();
                    $product_amount = number_format($product_qty * $prdocut_price, 2, ".", "");
                    $productos_array [] = $product_amount;
                }
                $payDataOperacion ['CSITTOTALAMOUNT'] = join("#", $productos_array);
                // CSITQUANTITY - Cantidad del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_name = $item->getQtyOrdered();
                    $productos_array [] = $product_name;
                }
                $payDataOperacion ['CSITQUANTITY'] = join("#", $productos_array);
                // CSITUNITPRICE - "999999[.CC]"
                $productos_array = array();
                foreach($productos as $item){
                    $product_name = number_format($item->getPrice(), 2, ".", "");
                    $productos_array [] = $product_name;
                }
                $payDataOperacion ['CSITUNITPRICE'] = join("#", $productos_array);
                /////////////////////////////////////////////////////////////
            }
            /*
			* ---DIGITAL GOODS-----
			* */
            if(false){
                $productos_array = array();
                foreach($productos as $item){
                    $product_id = Mage::getModel('catalog/product')->load($item->getProductId())->getecidirdelivery();
                    $category_cs[] = Mage::helper('modulodepago2/data')->getTipoDeliveryTodopago($product_id);
                }
                $payDataOperacion ['CSMDD32'] = join("#", $category_cs);
                // CSITPRODUCTCODE C�digo del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_id = Mage::getModel('catalog/product')->load($item->getProductId())->getTodopagocodigo();
                    $category_cs[] = Mage::helper('modulodepago2/data')->getCategoryTodopago($product_id);
                }
                $payDataOperacion ['CSITPRODUCTCODE'] = join("#", $category_cs);
                // /////////////////////////////////////////////////////////////
                // CSITPRODUCTDESCRIPTION Descripci�n del producto cada un separado con #
                $productos_array = array();
                foreach($productos as $item){
                    $_description = Mage::getModel('catalog/product')->load($item->getProductId())->getDescription();
                    $_description = strip_tags($_description);
                    $_description = trim($_description);
                    $_description = substr($_description, 0,50);
                    $productos_array [] = $_description;
                }
                $payDataOperacion ['CSITPRODUCTDESCRIPTION'] = join("#", $productos_array);
                // CSITPRODUCTNAME - Nombre del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_name = $item->getName();
                    $productos_array [] = $product_name;
                }
                $payDataOperacion ['CSITPRODUCTNAME'] = join("#", $productos_array);
                // CSITPRODUCTSKU - C�digo identificador del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_name = $item->getSku();
                    $productos_array [] = $product_name;
                }
                $payDataOperacion ['CSITPRODUCTSKU'] = join("#", $productos_array);
                // CSITTOTALAMOUNT - CSITTOTALAMOUNT = CSITUNITPRICE * CSITQUANTITY
                $productos_array = array();
                foreach($productos as $item){
                    $product_qty = $item->getQtyOrdered();
                    $prdocut_price = $item->getPrice();
                    $product_amount = number_format($product_qty * $prdocut_price, 2, ".", "");
                    $productos_array [] = $product_amount;
                }
                $payDataOperacion ['CSITTOTALAMOUNT'] = join("#", $productos_array);
                // CSITQUANTITY - Cantidad del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_name = $item->getQtyOrdered();
                    $productos_array [] = $product_name;
                }
                $payDataOperacion ['CSITQUANTITY'] = join("#", $productos_array);
                // CSITUNITPRICE - "999999[.CC]"
                $productos_array = array();
                foreach($productos as $item){
                    $product_name = number_format($item->getPrice(), 2, ".", "");
                    $productos_array [] = $product_name;
                }
                $payDataOperacion ['CSITUNITPRICE'] = join("#", $productos_array);
                //////////////////////////////////////////////////////////////////////////
            }
            /*
			* ---TICKETING-----
			* */
            if(false){
                ///////////////////////////////////////////////////////////////////////////
                // /////////////////////////////////////////////////////////
                // CSITPRODUCTCODE C�digo del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_id = Mage::getModel('catalog/product')->load($item->getProductId())->getTodopagocodigo();
                    $category_cs[] = Mage::helper('modulodepago2/data')->getCategoryTodopago($product_id);
                }
                $payDataOperacion ['CSITPRODUCTCODE'] = join("#", $category_cs);
                // /////////////////////////////////////////////////////////////
                // CSITPRODUCTDESCRIPTION Descripci�n del producto cada un separado con #
                $productos_array = array();
                foreach($productos as $item){
                    $_description = Mage::getModel('catalog/product')->load($item->getProductId())->getDescription();
                    $_description = strip_tags($_description);
                    $_description = trim($_description);
                    $_description = substr($_description, 0,50);
                    $productos_array [] = $_description;
                }
                $payDataOperacion ['CSITPRODUCTDESCRIPTION'] = join("#", $productos_array);
                // CSITPRODUCTNAME - Nombre del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_name = $item->getName();
                    $productos_array [] = $product_name;
                }
                $payDataOperacion ['CSITPRODUCTNAME'] = join("#", $productos_array);
                // CSITPRODUCTSKU - C�digo identificador del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_name = $item->getSku();
                    $productos_array [] = $product_name;
                }
                $payDataOperacion ['CSITPRODUCTSKU'] = join("#", $productos_array);
                // CSITTOTALAMOUNT - CSITTOTALAMOUNT = CSITUNITPRICE * CSITQUANTITY
                $productos_array = array();
                foreach($productos as $item){
                    $product_qty = $item->getQtyOrdered();
                    $prdocut_price = $item->getPrice();
                    $product_amount = number_format($product_qty * $prdocut_price, 2, ".", "");
                    $productos_array [] = $product_amount;
                }
                $payDataOperacion ['CSITTOTALAMOUNT'] = join("#", $productos_array);
                // CSITQUANTITY - Cantidad del producto
                $productos_array = array();
                foreach($productos as $item){
                    $product_name = $item->getQtyOrdered();
                    $productos_array [] = $product_name;
                }
                $payDataOperacion ['CSITQUANTITY'] = join("#", $productos_array);
                // CSITUNITPRICE - "999999[.CC]"
                $productos_array = array();
                foreach($productos as $item){
                    $product_name = number_format($item->getPrice(), 2, ".", "");
                    $productos_array [] = $product_name;
                }
                $payDataOperacion ['CSITUNITPRICE'] = join("#", $productos_array);
                /////////////////////////////////////////////////////////////////////////
            }
        }

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
            Mage::log("Modulo de pago - TodoPago ==> Exception: ". $e);
            if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
                $order->setStatus('test_todopago_canceled');
                $order->addStatusHistoryComment("Todo Pago (TEST)(Exception): " . $e);
            } else{
                $order->setStatus(Mage::getStoreConfig('payment/todopago_avanzada/estado_denegada'));
                $order->addStatusHistoryComment("Todo Pago (Exception): " . $e);
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


        if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
            $merchant = Mage::getStoreConfig('payment/todopago_modo/idstore_test');
        } else{
            $merchant = Mage::getStoreConfig('payment/todopago_modo/idstore');
        }
        // Security
        if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
            $security = Mage::getStoreConfig('payment/todopago_modo/codigo_seguridad_test');
        } else{
            $security = Mage::getStoreConfig('payment/todopago_modo/codigo_seguridad');
        }
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

        private function redirect_first($cart_array){
            Mage_Core_Controller_Varien_Action::_redirect('modulodepago2/formulariocustom/insite',
              array('_secure' => true, 'amount'=>$cart_array['Amount'])
              );
        }

        private function _sanitize_string($string){
            $string = htmlspecialchars_decode($string);

            $re = "/\\[(.*?)\\]|<(.*?)\\>/i";
            $subst = "";
            $string = preg_replace($re, $subst, $string);

            $replace = array("!","'","\'","\"","  ","$","#","\\","\n","\r",
                '\n','\r','\t',"\t","\n\r",'\n\r','&nbsp;','&ntilde;',".,",",.","+", "%", "-", ")", "(", "°");
            $string = str_replace($replace, '', $string);

            $cods = array('\u00c1','\u00e1','\u00c9','\u00e9','\u00cd','\u00ed','\u00d3','\u00f3','\u00da','\u00fa','\u00dc','\u00fc','\u00d1','\u00f1');
            $susts = array('Á','á','É','é','Í','í','Ó','ó','Ú','ú','Ü','ü','Ṅ','ñ');
            $string = str_replace($cods, $susts, $string);

            $no_permitidas= array ("á","é","í","ó","ú","Á","É","Í","Ó","Ú","ñ","À","Ã","Ì","Ò","Ù","Ã™","Ã ","Ã¨","Ã¬","Ã²","Ã¹","ç","Ç","Ã¢","ê","Ã®","Ã´","Ã»","Ã‚","ÃŠ","ÃŽ","Ã”","Ã›","ü","Ã¶","Ã–","Ã¯","Ã¤","«","Ò","Ã","Ã„","Ã‹");
            $permitidas= array ("a","e","i","o","u","A","E","I","O","U","n","N","A","E","I","O","U","a","e","i","o","u","c","C","a","e","i","o","u","A","E","I","O","U","u","o","O","i","a","e","U","I","A","E");
            $string = str_replace($no_permitidas, $permitidas ,$string);

            return $string;
        }

    }