<?php

class Brandlive_Todopago_Model_Cron
{

    const CLEAN_LOG_FILE = 'todopago_clean_cron.log';

    protected $_todopagoLog;

    public function __construct(){
        $this->_todopagoLog = Mage::helper('modulodepago2/todopagolog');
        $this->_helper      = Mage::helper('modulodepago2/data');
    }
    
    public function cleanPendingPaymentOrders()
    {
        foreach (Mage::app()->getStores() as $store) {      
            $this->_cleanPendingPaymentOrders($store->getWebsiteId(), $store->getId());          
        }   
    }
    
    protected function _cleanPendingPaymentOrders($website, $store)
    {
        $this->_todopagoLog->log("Entra a ".__METHOD__,Zend_Log::INFO,self::CLEAN_LOG_FILE);

        // Validate Active
        if(!$this->_helper->getCleanCron($website)) {
            return;
        }
        
        Mage::app()->setCurrentStore($store);           

        $logEnabled  = $this->_helper->getCleanCronLog($website);

        /* Format our dates */
        $date = Mage::getModel('core/date');
        $currentTimestamp = $date->timestamp(time());
        $minutes = $this->_helper->getCleanCronMinutes($website);
        $expirationTimestamp = strtotime("-" . $minutes .  " minutes", $currentTimestamp);
        $toDate = $date->gmtDate('Y-m-d H:i:s', $expirationTimestamp);

        $limit = $this->_helper->getCleanCronCollectionLimit($website);

        $this->_todopagoLog->log("limit: ".$limit,Zend_Log::INFO,self::CLEAN_LOG_FILE);
                    
        /* Get the collection */
        $orders = Mage::getModel('sales/order')->getCollection()                
                ->join(
                        array('payment' => 'sales/order_payment'),
                            'main_table.entity_id = payment.parent_id', array('payment_method' => 'payment.method')
                      ) 
                ->addAttributeToFilter('payment.method', array('eq' => 'modulodepago2'))
                ->addAttributeToFilter('created_at', array('to' => $toDate))
                ->addAttributeToFilter('store_id', array('eq' => $store))
                ->addAttributeToFilter('status', array('in' => $this->_helper->getCleanCronOrderStatuses()))
                ->addAttributeToSort('increment_id', 'DESC')
                ->setPageSize($limit);

        //if($logEnabled) $this->_todopagoLog->log("QUERY SELECT: ".$orders->getSelect(),Zend_Log::INFO,self::CLEAN_LOG_FILE);
        //$this->_todopagoLog->log("QUERY SELECT: ".$orders->getSelect(),Zend_Log::INFO,self::CLEAN_LOG_FILE);
        $this->_todopagoLog->log("Cantidad de Ordenes encontradas: ".$orders->count(),Zend_Log::INFO,self::CLEAN_LOG_FILE);
                        
        if ($orders->count() > 0){                      

            $connector = Mage::helper('modulodepago2/connector')->getConnector();      

            foreach ($orders as $order) {           
                
                $order_id =  $order->getIncrementId();

                if(Mage::getStoreConfig('payment/modulodepago2/modo_test_prod') == "test"){
                    $merchant = Mage::getStoreConfig('payment/todopago_modo/idstore_test');
                } else{
                    $merchant = Mage::getStoreConfig('payment/todopago_modo/idstore');
                }

                try{
                    $status = $connector->getStatus(array('MERCHANT'=>$merchant, 'OPERATIONID'=>$order_id));
                }
                catch(Exception $e){
                    $exception['Operations']['Exception']="Error el consumir Web Service Todopago";
                    $this->_todopagoLog->log("Error: ".print_r($exception,true),Zend_Log::ERR,self::CLEAN_LOG_FILE);
                    continue;
                }

                //$this->_todopagoLog->log("status: ".print_r($status,true),null,self::CLEAN_LOG_FILE);

                if(count($status)>0){
                    $this->_todopagoLog->log("order_id: ".$order_id,Zend_Log::INFO,self::CLEAN_LOG_FILE);
                    $this->_todopagoLog->log("status: ".print_r($status,true),Zend_Log::INFO,self::CLEAN_LOG_FILE);

                    // Orden Aprobada
                    if($status['Operations']['RESULTCODE'] == -1){ 

                        if($logEnabled) $this->_todopagoLog->log('La Orden '.$order_id.' ya se encuentra APROBADA en Todopago, se aprobará en Magento', Zend_Log::INFO, self::CLEAN_LOG_FILE);
                        $this->_approveOrder($order, $logEnabled);

                    // Orden Rechazada
                    }elseif($status['Operations']['RESULTCODE'] != -1){
                        // Cancelo la orden 
                        if($logEnabled) $this->_todopagoLog->log('La Orden '.$order_id.' ya se encuentra RECHAZADA en Todopago, se cancelará en Magento', Zend_Log::INFO, self::CLEAN_LOG_FILE);
                        $this->_cancelOrder($order,$logEnabled);
                    }else{
                        // No me devolvio un RESULTCODE valido
                        if($logEnabled) $this->_todopagoLog->log('Todopago devolvio un RESULTCODE inválido para la orden: '.$order_id, Zend_Log::ERR, self::CLEAN_LOG_FILE);
                        continue;
                    }

                }else{
                    // Todopago no devolvio info de la orden por lo tanto el usuario no pago y sigue en estado pendiente
                    if($logEnabled) $this->_todopagoLog->log("Todopago no devolvió info de la orden: ".$order_id." ,se cancelará en Magento", Zend_Log::ERR, self::CLEAN_LOG_FILE);
                    $this->_cancelOrder($order,$logEnabled);
                    continue;
                }

            }
            
        }               
        
        return;
    }

    protected function _cancelOrder($order, $logEnabled){   

        $incrementId = $order->getIncrementId();
        if ($order->canCancel()){
            try{
                //Copiado del método Cancel de la clase Order. Lo hago asi para poder enviar un comentario custom
                $order->getPayment()->cancel();
                $order->registerCancellation('Orden cancelada por cron de Todopago', true);                     
                $order->save();                 
                if ($logEnabled) $this->_todopagoLog->log('Orden cancelada: ' . $incrementId , Zend_Log::INFO, self::CLEAN_LOG_FILE);
            }catch (Exception $e){
                if ($logEnabled) $this->_todopagoLog->log('Error al cancelar la orden: ' . $incrementId . ' - ' . $e->getMessage(), Zend_Log::ERR, self::CLEAN_LOG_FILE);
            }           
        }else{
            if ($logEnabled) $this->_todopagoLog->log('La orden: '.$incrementId.' no puede ser cancelada', Zend_Log::ERR, self::CLEAN_LOG_FILE);            
        }

    }   

    protected function _approveOrder($order, $logEnabled){   

        $incrementId = $order->getIncrementId();

        $status = Mage::getStoreConfig('payment/todopago_avanzada/order_aprov');
        $state  = Mage::getResourceModel('sales/order_status_collection')->joinStates()->addFieldToFilter('main_table.status', $status)->getFirstItem()->getState();                         
                        
        try{                    
            $order->setState($state, $status, 'Orden aprobada por cron de Todopago.', true);
            $order->save();
            $order->sendNewOrderEmail();

            if ($logEnabled) $this->_todopagoLog->log('Orden aprobada: ' . $incrementId , Zend_Log::INFO, self::CLEAN_LOG_FILE);
        }catch (Exception $e){
            if ($logEnabled) $this->_todopagoLog->log('Error al intentar aprobar la orden: ' . $incrementId . ' - ' . $e->getMessage(), Zend_Log::ERR, self::CLEAN_LOG_FILE);
        }
    }   
    
}
