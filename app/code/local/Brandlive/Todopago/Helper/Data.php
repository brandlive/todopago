<?php

class Brandlive_Todopago_Helper_Data extends Todopago_Modulodepago2_Helper_Data{
	
	const MIN_CLEAN_CRON_MINUTES = 5;
	const COLLECTION_MAX_LIMIT = 50;

	public function getUrlSuccess($website = null){
		return Mage::app()->getWebsite($website)->getConfig('payment/modulodepago2/url_success');
	}

	public function getUrlFailure($website = null){
		return Mage::app()->getWebsite($website)->getConfig('payment/modulodepago2/url_failure');
	}

	public function getCleanCron($website = null){
		return (bool)Mage::app()->getWebsite($website)->getConfig('payment/modulodepago2/clean_cron');
	}

	public function getCleanCronMinutes($website = null){
		$minutes =  (int)Mage::app()->getWebsite($website)->getConfig('payment/modulodepago2/clean_cron_minutes');
		if ($minutes < self::MIN_CLEAN_CRON_MINUTES) $minutes = self::MIN_CLEAN_CRON_MINUTES;
		return $minutes;
	}

	public function getCleanCronOrderStatuses($website = null){
		$statuses = Mage::app()->getWebsite($website)->getConfig('payment/modulodepago2/clean_cron_order_statuses');
		return explode(',',$statuses);
	}

	public function getCleanCronCollectionLimit($website = null){
		$limit = (int)Mage::app()->getWebsite($website)->getConfig('payment/modulodepago2/clean_cron_collection_limit');
		if ($limit > self::COLLECTION_MAX_LIMIT) $limit = self::COLLECTION_MAX_LIMIT;
		return $limit;
	}

	public function getCleanCronLog($website = null){
		return (bool)Mage::app()->getWebsite($website)->getConfig('payment/modulodepago2/clean_cron_log');
	}
 	
}