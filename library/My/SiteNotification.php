<?php 

class My_SiteNotification {
	
	protected $_messages = array();
	
	public function __construct () {
		$siteNotificationTable = new Application_Model_SiteNotification();
		$siteNotificationRowset = $siteNotificationTable->fetchAll()->toArray();
		
		foreach ($siteNotificationRowset as $siteNotificationRow) {
			$this->_messages[$siteNotificationRow['name']] = array(
				'message' => $siteNotificationRow['text'],
				'subject' => $siteNotificationRow['subject']
			); 
		}
	}
	
	public function getMessage($name, $params = array()) {
		if(isset($this->_messages[$name]['message'])){
			return $this->_fillOutTemplate($this->_messages[$name]['message'], $params);
		}
		else{
			return;
		}
	}

	public function getSubject($name, $params = array()) {
		return $this->_fillOutTemplate($this->_messages[$name]['subject'], $params);
	}
	
	protected function _fillOutTemplate ($template, $params = array()) {
		if (!empty($params)) {
        	foreach ($params as $key=>$value){
            	$template = preg_replace('/%'.$key.'%/', $value, $template);
        	}
			$template = preg_replace('/%[^%]+%/', '', $template);
		}
		return $template;
	}
}