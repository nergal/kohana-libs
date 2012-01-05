<?php defined('SYSPATH') or die('No direct access allowed.');

class Auth_ORM extends Kohana_Auth_ORM {

	/**
	 * Logs in a user via an OAuth provider.
	 *
	 * @param   string   $provider
	 * @return  boolean
	 */
	public function sso($provider)
	{
		return SSO::factory($provider, 'orm')->login();
	}

	/**
	 * Forces a user to be logged in when using SSO, without specifying a password.
	 *
	 * @param   ORM      $user
	 * @param   boolean  $mark_session_as_forced
	 * @return  boolean
	 */
	public function force_sso_login(ORM $user, $mark_session_as_forced = FALSE)
	{
		if ($mark_session_as_forced === TRUE)
		{
			// Mark the session as forced, to prevent users from changing account information
			$this->_session->set('auth_forced', TRUE);
		}

		// Token data
		$data = array(
			'user_id'    => $user->id,
			'expires'    => time() + $this->_config['lifetime'],
			'user_agent' => sha1(Request::$user_agent),
		);

		// Create a new autologin token
		$token = ORM::factory('user_token')
					->values($data)
					->create();

		// Set the autologin cookie
		Cookie::set('authautologin', $token->token, $this->_config['lifetime']);

		// Run the standard completion
		$this->complete_login($user);
    }

        /**
         * Compare password with original (hashed). Works for current (logged in) user
         *
         * @param   string  $password
         * @return  boolean
         */
        public function check_password($password)
        {
                $user = $this->get_user();

                if ( ! $user)
                        return FALSE;

                return ($this->_hasher->CheckPassword($password, $user->password));
        }

        /**
         * Logs a user in.
         *
         * @param   string   username
         * @param   string   password
         * @param   boolean  enable autologin
         * @return  boolean
         */
        protected function _login($user, $password, $remember)
        {
                if ( ! is_object($user))
                {
                        $username = $user;

                        // Load the user
                        $user = ORM::factory('user');
                        $user->where($user->unique_key($username), '=', $username)->find();
                }

                // Restore user role from phpbb record
                $login_role = ORM::factory('role', array('name' => 'login'));
                $logged_in = $user->has('roles', $login_role);
                if ( ! $logged_in) {
                	$user_type = $user->user_type;
                	if (is_numeric($user_type)) {
                		$user_type = intval($user_type);
                	}

                	$logged_in = $user_type === 0;
                	if ($logged_in) {
                		$user->add('roles', $login_role);
                		$user->update();
                	}
                }
                
                // If the passwords match, perform a login
                if ($logged_in AND $this->_hasher->CheckPassword($password, $user->password))
                {
                        if ($remember === TRUE)
                        {
                                // Token data
                                $data = array(
                                        'user_id'    => $user->id,
                                        'expires'    => time() + $this->_config['lifetime'],
                                        'user_agent' => sha1(Request::$user_agent),
                                );

                                // Create a new autologin token
                                $token = ORM::factory('user_token')
                                                        ->values($data)
                                                        ->create();

                                // Set the autologin cookie
                                Cookie::set('authautologin', $token->token, $this->_config['lifetime']);
                        }
                        // Finish the login
                        $this->complete_login($user);

                        return TRUE;
                }

                // Login failed
                return FALSE;
        }

} // End Auth_ORM
