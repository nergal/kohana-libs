<?php defined('SYSPATH') or die('No direct script access.');

class Mailer extends Kohana_Mailer {
    protected $headers = array();
    
    public function setup($method = NULL) {
	parent::setup($method);
	
	if (is_array($this->headers)) {
	    foreach ($this->headers as $name => $value) {
//		$this->message->_setHeaderParameter($name, $value);
	    }
	}
	
	return $this;
    }
}