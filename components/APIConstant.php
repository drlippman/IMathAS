<?php

namespace app\components;

class APIConstant {

	const AUTHENTICATION_FAILED = '-101';
	const PARAMETER_MISSING = '-102';
	const EMAIL_NOT_VALID = '-103';
	const EMAIL_EXISTS = '-104';
	const EMAIL_DOES_NOT_EXIST = '-105';
	const EMAIL_ALREADY_ACTIVATED = '-106';
	const METHOD_CALL_NOT_IMPLEMENTED = '-107';
	const KEY_NOT_VALID = '-108';
	const API_KEY_MISSING_OR_INCORRECT = '-109';
	const ERROR_CODE_NOT_EXIST = '-110';
	const JSON_WITH_INVALID_KEYS = '-111';
	const UNEXPECTED_ERROR = '-112';
	const CHECK_MAPPING = '-113';

	public static $english = array(
			APIConstant::AUTHENTICATION_FAILED => 'User authentication failed.',
			APIConstant::API_KEY_MISSING_OR_INCORRECT => 'Failed API Authentication.',
			APIConstant::PARAMETER_MISSING => 'Missing parameter in method call',
			APIConstant::EMAIL_NOT_VALID => 'The email address you provided does not appear to be a valid email address.',
			APIConstant::EMAIL_EXISTS => 'This email address has already been registered.',
			APIConstant::EMAIL_ALREADY_ACTIVATED => 'The email address you have provided is already activated.',
			APIConstant::METHOD_CALL_NOT_IMPLEMENTED => 'Method call is not implemented.',
			APIConstant::KEY_NOT_VALID => 'Sorry, entered key does not match with serial number.',
			APIConstant::ERROR_CODE_NOT_EXIST => 'Provided error code does not exist.',
			APIConstant::JSON_WITH_INVALID_KEYS => 'Provided JSON does not contain considered keys. Please refer respective API document.',
			APIConstant::UNEXPECTED_ERROR => 'Unexpected error occurred.',
			APIConstant::CHECK_MAPPING => 'Check the mapping of method and action of API in expose function'
	);

	static function getMessageForErrorCode($errorCode) {
		if (isset(self::$english[$errorCode])) {
			return self::$english[$errorCode];
		} else {
			return $errorCode;
		}
	}

}