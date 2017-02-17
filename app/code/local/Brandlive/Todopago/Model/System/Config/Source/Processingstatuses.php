<?php

class Brandlive_Todopago_Model_System_Config_Source_Processingstatuses extends Todopago_Modulodepago2_Model_System_Config_Source_Processingstatuses
{
	public function toOptionArray()
    {                       
        return Mage::getResourceModel('sales/order_status_collection')
                    ->addStateFilter(Mage_Sales_Model_Order::STATE_PROCESSING)                    
                    ->toOptionArray();        
    }
}

