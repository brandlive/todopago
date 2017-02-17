<?php 
#File: app/code/local/Brandlive/Todopago/Model/System/Validation/Cancelationcroncollectionlimit.php
class Brandlive_Todopago_Model_System_Validation_Cleancroncollectionlimit extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $minutes = $this->getValue(); //get the value from our config

        if(!is_numeric($minutes)){
            Mage::throwException("Error: El Limite de ordenes a cancelar por cron debe ser un número igual o menor a 50.");                     
        }else{
            if($minutes > 50){
                Mage::throwException("Error: El Limite de ordenes a cancelar por cron debe ser como máximo 50.");         
            }else{
                //call original save method so whatever happened 
                //before still happens (the value saves)
                return parent::save();  
            }
        }
        
    }
}