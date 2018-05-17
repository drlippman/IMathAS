<?php

//Adapted from OWASP Foundation's CSRFP Protector 
//Licensed under the Apache License, Version 2.0

if (!defined('__CSRF_PROTECTOR__')) {
	define('__CSRF_PROTECTOR__', true); 	// to avoid multiple declaration errors

	// name of HTTP POST variable for authentication
	define("CSRFP_TOKEN","csrfp_token");

	/**
	 * child exception classes
	 */
	class configFileNotFoundException extends \exception {};
	class logDirectoryNotFoundException extends \exception {};
	class jsFileNotFoundException extends \exception {};
	class logFileWriteError extends \exception {};
	class baseJSFileNotFoundExceptio extends \exception {};
	class incompleteConfigurationException extends \exception {};
	class alreadyInitializedException extends \exception {};

	class csrfProtector
	{
		/*
		 * Variable: $isValidHTML
		 * flag to check if output file is a valid HTML or not
		 * @var bool
		 */
		private static $isValidHTML = false;

		public static $config = array(
			'failedAuthAction' => 'block',
			'tokenLength' => 10,
			'logDirectory' => "log", 
			'jsUrl' => ''  //set in init, after basesiteurl is defined
		);
		
		public static function init($length = null, $action = null)
		{
			global $userid, $sessiondata;
			if (empty($userid)) { //only run if $userid is set
				return;
			}
			
			if ($GLOBALS['CFG']['use_csrfp']==='log') {
				self::$config['failedAuthAction'] = 'log';
			}
			
			self::$config['jsUrl'] = $GLOBALS['basesiteurl'] . "/csrfp/js/simplecsrfprotector.js";
			
			// Authorise the incoming request
			if (isset($sessiondata[CSRFP_TOKEN])) {
				self::authorizePost();
			}
			
			if (!isset($sessiondata[CSRFP_TOKEN])) {
				self::refreshToken();
			}

			// Initialize output buffering handler
			ob_start('csrfProtector::ob_handler');

		}
		public static function authorizePost()
		{
			global $sessiondata;
			if ($_SERVER['REQUEST_METHOD'] === 'POST') {

				// look for token in payload else from header
				$token = self::getTokenFromRequest();

				//currently for same origin only
				if (!($token && self::isValidToken($token))) {

					//action in case of failed validation
					self::failedValidationAction();
				} 
			} 
		}

		/*
		 * Fucntion: getTokenFromRequest
		 * function to get token in case of POST request
		 *
		 * Parameters:
		 * void
		 *
		 * Returns:
		 * any (string / bool) - token retrieved from header or form payload
		 */
		private static function getTokenFromRequest() {
			// look for in $_POST, then header
			if (isset($_POST[CSRFP_TOKEN])) {
				return $_POST[CSRFP_TOKEN];
			}

			if (function_exists('apache_request_headers')) {
				if (isset(apache_request_headers()[CSRFP_TOKEN])) {
					return apache_request_headers()[CSRFP_TOKEN];
				}
			}

			return false;
		}

		/*
		 * Function: isValidToken
		 * function to check the validity of token in session array
		 * Function also clears all tokens older than latest one
		 *
		 * Parameters:
		 * $token - the token sent with GET or POST payload
		 *
		 * Returns:
		 * bool - true if its valid else false
		 */
		private static function isValidToken($token) {
			global $sessiondata;
			if (!isset($sessiondata[CSRFP_TOKEN])) return false;
			return ($sessiondata[CSRFP_TOKEN] == $token);
		}

		/*
		 * Function: failedValidationAction
		 * function to be called in case of failed validation
		 * performs logging and take appropriate action
		 *
		 * Parameters:
		 * void
		 *
		 * Returns:
		 * void
		 */
		private static function failedValidationAction()
		{
			if (!file_exists(__DIR__ . '/' . self::$config['logDirectory']))
				throw new logDirectoryNotFoundException("OWASP CSRFProtector: Log Directory Not Found!");

			//call the logging function
			static::logCSRFattack();

			if (self::$config['failedAuthAction']==='log') {
				//just log - no action	
			} else {
				if (isset($GLOBALS['CFG']['csrfp_error_message'])) {
					exit($GLOBALS['CFG']['csrfp_error_message']);
				} else {
					$err = 'Your submission has been blocked because we were unable to verify it came from a valid source. ';
					$err .= 'This can happen if you have two browser windows open and logged in within one while the other was open, ';
					$err .= 'or for some other reason your browing session reset.';
					exit($err);
				}
			}
		}

		/*
		 * Function: refreshToken
		 * Function to set auth cookie
		 *
		 * Parameters:
		 * void
		 *
		 * Returns:
		 * void
		 */
		public static function refreshToken()
		{
			global $sessiondata;
			$token = self::generateAuthToken();

			$sessiondata[CSRFP_TOKEN] = $token;
			writesessiondata();
		}
		/*
		 * Function: generateAuthToken
		 * function to generate random hash of length as given in parameter
		 * max length = 128
		 *
		 * Parameters:
		 * length to hash required, int
		 *
		 * Returns:
		 * string, token
		 */
		public static function generateAuthToken()
		{
			// todo - make this a member method / configurable
			$randLength = 64;

			$tokenLength = self::$config['tokenLength'];
			
			//#todo - if $length > 128 throw exception

			if (function_exists("random_bytes")) {
				$token = bin2hex(random_bytes($randLength));
			} elseif (function_exists("openssl_random_pseudo_bytes")) {
				$token = bin2hex(openssl_random_pseudo_bytes($randLength));
			} else {
				$token = '';
				for ($i = 0; $i < 128; ++$i) {
					$r = mt_rand (0, 35);
					if ($r < 26) {
						$c = chr(ord('a') + $r);
					} else {
						$c = chr(ord('0') + $r - 26);
					}
					$token .= $c;
				}
			}
			return substr($token, 0, $tokenLength);
		}
		
		/*
		* Function: get_csrf_input_tag
		*
		* Return:
		* string, of HTML input tag containing CSRFP token
		*/
		private static function get_csrf_input_tag()
		{
			global $sessiondata;
			$out = '<input type="hidden" name="'.CSRFP_TOKEN.'" ';
			$out .= 'class="'.CSRFP_TOKEN.'" value="'.$sessiondata[CSRFP_TOKEN].'" />';
			return $out;
		}

		/*
		 * Function: output_header_code
		 * outputs the token information and script tag 
		 * for placement into the <head>
		 *
		 * Return:
		 * string, for inclusion in <head>
		 */
		public static function output_header_code()
		{
			global $sessiondata;
			$out = '<script type="text/javascript" src="' . self::$config['jsUrl'] . '"></script>';
			$out .= '<script type="text/javascript">';
			$out .= 'CSRFP.setToken("'.$sessiondata[CSRFP_TOKEN].'");</script>';

			return $out;
		}
		

		/*
		 * Function: ob_handler
		 * Rewrites <form> on the fly to add CSRF tokens to them. 
		 *
		 * Parameters:
		 * $buffer - output buffer to which all output are stored
		 * $flag - INT
		 *
		 * Return:
		 * string, complete output buffer
		 */
		public static function ob_handler($buffer, $flags)
		{
			// Even though the user told us to rewrite, we should do a quick heuristic
		    // to check if the page is *actually* HTML. We don't begin rewriting until
		    // we hit the first <html tag.
		    if (!self::$isValidHTML) {
		        // not HTML until proven otherwise
		        if (stripos($buffer, '<html') !== false) {
		            self::$isValidHTML = true;
		        } else {
		            return $buffer;
		        }
		    }

		    //implant hidden fields with token info into forms
	        $buffer = str_ireplace('</form>', self::get_csrf_input_tag() . '</form>', $buffer);

		    return $buffer;
		}

		/*
		 * Function: logCSRFattack
		 * Function to log CSRF Attack
		 *
		 * Parameters:
		 * void
		 *
		 * Retruns:
		 * void
		 *
		 * Throws:
		 * logFileWriteError - if unable to log an attack
		 */
		protected static function logCSRFattack()
		{
			global $sessiondata;
			//miniature version of the log
			$log = array();
			$log['timestamp'] = time();
			$log['HOST'] = $_SERVER['HTTP_HOST'];
			$log['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
			$log['APACHE_HEADERS'] = apache_request_headers();
			$log['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$log['query'] = $_POST;

			if (!isset($sessiondata[CSRFP_TOKEN])) {
				$log['csrfp_token'] = "not set";
			} else {
				$log['csrfp_token'] = $sessiondata[CSRFP_TOKEN];
			}
			global $DBH;
			$stm = $DBH->prepare("SELECT time FROM imas_sessions WHERE sessionid=?");
			$stm->execute(array(session_id()));
			$log['session_time'] = $stm->fetchColumn(0);

			//remove password from query
			if (isset($log['query']['password'])) {
				$log['query']['password'] = "l0Lz";
			}

			//convert log array to JSON format to be logged
			$log = json_encode($log) .PHP_EOL;

			if (isset($GLOBALS['CFG']['csrfp_logtype']) && $GLOBALS['CFG']['csrfp_logtype']=='error_log') {
				//log to error log
				error_log($log);
			} else {
				//use default local log

				//if file doesnot exist for, create it
				$logfilename = __DIR__ . '/' .self::$config['logDirectory']	."/csrferrors.log";
				if (file_exists($logfilename) && filesize($logfilename)>1000000) { //restart log if over 1MB
					$logFile = fopen($logfilename, "w+");
				} else {
					$logFile = fopen($logfilename, "a+");
				}

				//throw exception if above fopen fails
				if (!$logFile)
					throw new logFileWriteError("CSRFProtector: Unable to write to the log file");

				//append log to the file
				fwrite($logFile, $log);

				//close the file handler
				fclose($logFile);
			}
		}
	};
}

			