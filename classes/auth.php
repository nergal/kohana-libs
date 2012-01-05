<?php defined('SYSPATH') or die('No direct access allowed.');

abstract class Auth extends Kohana_Auth {
	protected $_hasher = NULL;

	public function __construct($config = array())
	{
	    parent::__construct($config);
	    
	    include Kohana::find_file('vendor', 'phpass/hash');
	    $this->_hasher = new PasswordHash(6, TRUE);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see modules/auth/classes/kohana/Kohana_Auth#login()
	 */
	public function login($username, $password, $remember = FALSE)
	{
		if (empty($password))
			return FALSE;

		return $this->_login($username, $password, $remember);
	}

	/**
	 * (non-PHPdoc)
	 * @see modules/auth/classes/kohana/Kohana_Auth#hash()
	 */
	public function hash($str)
	{
		return $this->_hasher->HashPassword($str);
	}

}
