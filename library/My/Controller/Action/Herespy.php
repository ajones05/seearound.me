<?php
class My_Controller_Action_Herespy extends My_Controller_Action_Abstract {
    
    public function preDispatch() {
        parent::preDispatch();
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            if(count($auth->getIdentity()) <= 0) {
                if(($this->request->getParam("controller") == "home") && ($this->request->getParam("action") == "profile")) {
                    $this->profile = true;
                } else {
                    $this->_redirect(BASE_PATH);	
                }
            }
        } else {
            if(($this->request->getParam("controller") == "home") && ($this->request->getParam("action") == "profile")) {
                $this->profile = true;
            } else {
                $this->_redirect(BASE_PATH);
            }
        }
        $this->view->publicProfile = $this->profile;	
    }

    public function postDispatch() {
        parent::postDispatch();
    }

}