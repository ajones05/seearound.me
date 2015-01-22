<?php 
class My_Plugin_View extends Zend_Controller_Plugin_Abstract {
    public function routeShutdown(Zend_Controller_Request_Abstract $request) {
			$layout = Zend_Layout::startMvc();
			$layout->setLayoutPath(APPLICATION_PATH .'/layouts/scripts');
			$layout->setViewSuffix('html');
			$layout->getInflector()->setStaticRule('suffix', 'php');
    		
			$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();
			$viewRenderer->setViewSuffix('html');
			$viewRenderer->setViewBasePathSpec( ':moduleDir/views' );
			Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
        	
    }
}