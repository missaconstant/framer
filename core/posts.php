<?php

	namespace framer;

	/**
	* Le gerant des posts
	*/
	class Posts
	{
		static private $checkCSRF = 1;
		static private $givenfield = false;
		static public $_POST = null;

		function __construct()
		{
			// self::$_POST = $_POST;
		}

		static function setDataType($type='urlencoded')
		{
			self::$_POST = $type == 'json' ? json_decode( file_get_contents('php://input'), true ) : $_POST;
			// $_POST = self::$_POST;
		}

		static function getCSRF()
		{
			if (!self::$givenfield) {
				self::$givenfield = '<input type="hidden" name="_token" value="' . self::getCSRFTokenValue() . '">';
			}
			return self::$givenfield;
		}

		static function getCSRFTokenValue()
		{
			$token = md5(md5(date('dmYHis').uniqid()));
			self::saveToken($token);
			return $token;
		}

		static function checkCSRF($token)
		{
			if (Session::get('csrf_token') == $token) {
				return true;
			}
			else {
				throw new \Exception("Wrong token detected !", 1);
			}
		}

		static function destroyCSRF()
		{
			Session::unset('csrf_token');
		}

		static private function saveToken($token)
		{
			Session::set('csrf_token', $token);
		}

		static function post($value=null)
		{
			self::csrfWare();

			if (is_string($value))
			{
				return self::getRequestValue(self::$_POST, $value);
			}
			else if (is_array($value))
			{
				return self::setted(self::$_POST, $value);
			}
			else if ( $value == null )
			{
				return (object) self::$_POST;
			}
		}

		static function get($value)
		{
            if (is_string($value) || is_numeric($value)) {
                return self::getRequestValue($_GET, $value);
            }
            else if (is_array($value)) {
                return self::setted($_GET, $value);
            }
        }

		static function file($value)
		{
		    self::csrfWare();

            if (is_string($value)) {
                return self::getRequestValue($_FILES, $value);
            }
            else if (is_array($value)) {
                return self::setted($_FILES, $value);
            }
		}

		static function files()
		{
			return json_decode(json_encode($_FILES));
		}

		static private function csrfWare()
		{
			if (self::$checkCSRF) {
				if (isset($_POST['_token']) && $_POST['_token'] == Session::get('csrf_token')) {
					self::destroyCSRF();
					self::disableCSRF();
				}
				else {
					throw new \Exception("CSRF MISSING ERROR CODE 1", 1);
				}
			}
		}

		static function disableCSRF()
		{
			self::$checkCSRF = 0;
		}

		static function enableCSRF()
		{
			self::$checkCSRF = 1;
		}

		static private function getRequestValue($type, $index)
		{
			if (isset($type[$index])) {
				return is_array($type[$index]) ? $type[$index] : htmlspecialchars($type[$index]);
			}
			else {
				return NULL;
				// throw new \Exception("Index undefined", 1);
			}
		}

		static private function setted($type, $indexes)
		{
			$return = true;
			for ($i=0; $i<count($indexes); $i++) {
				if (!isset($type[$indexes[$i]])) {
					$return = false;
					break;
				}
			}
			return $return;
		}

		static public function setValue($type, $key, $value)
		{
			if ($type == 'post') {
				$_POST[$key] = $value;
			}
			else if ($type == 'get') {
				$_GET[$key] = $value;
			}
		}

		static public function CSRFEnabled()
		{
			return self::$checkCSRF;
		}
	}
