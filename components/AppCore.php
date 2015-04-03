<?php

namespace app\components;


use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use app\models\ApiUser;
use app\models\WsLogging;

class AppCore extends Component {

	/**
	 * Authenticate Api Key
	 * @return boolean
	 */

	public static function authenticate_api_key(){
		$response = false;
		$type = Yii::$app->request->getMethod();
		$api_key = Yii::$app->request->$type('api_key', false);
		if($api_key){
			$apiUserModel = ApiUser::find()
			->where(['api_key' => $api_key])
			->one();
			if($apiUserModel != null){
				$response = true;
			}
		}
		return $response;
	}

	/**
	 *
	 * Store API call deatils
	 * @param  int $status
	 * @param array() $result
	 * @param array() $request_type
	 */
	public static function ws_log_details($status = 1, $result = array(), $request_type = 'post'){
		try{
			//check for enable web service logging flag

			if(Yii::$app->params['enablewebservicelogging']){

				$request_data = $_REQUEST;
				if(!isset($request_data['method'])){
					return false;
				}

				$ws_logging_model = new WsLogging();

				$serialized_request_and_server_data['request'] = $request_data;
				$serialized_request_and_server_data['server'] = $_SERVER;
				$serialized_request_data = serialize($serialized_request_and_server_data);
				$serialized_response_data = serialize($result);
				$api_verbosity = Yii::$app->params['api_verbosity'];
				$api_verbosity_level = Yii::$app->params['default_api_verbosity'];

				if(isset($api_verbosity[$request_data['method']])){
					$api_verbosity_level = $api_verbosity[$request_data['method']];
				}

				switch ($api_verbosity_level) {
					case 0: //None
						return false;
					case 1: //Low
						$ws_logging_model->response_data = $serialized_response_data;
						break;
					case 2: //High
						$ws_logging_model->request_data = $serialized_request_data;
						$ws_logging_model->response_data = $serialized_response_data;
						break;
				}

				$remote_address = isset($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR'] : 'not found';
				$method_name = $request_data['method'];
				$start_time = Yii::$app->params['start_time'];
				$end_time = round(microtime(true) * 1000);
				$response_time = $end_time - $start_time;
				$ws_logging_model->remote_address = $remote_address;
				$ws_logging_model->method_name = $method_name;
				$ws_logging_model->status = $status;
				$ws_logging_model->response_time = $response_time;
				$ws_logging_model->api_request = json_encode($request_data);
				$ws_logging_model->start_time = $start_time;
				$ws_logging_model->end_time = $end_time;
				$ws_logging_model->save();

			}
		} catch (Exception $e) {
			//do nothing
		}
	}

	public function yii_echo($message_key, $args = array(), $language = "ln"){

		$english = array(
				/**
				 * Sites
		*/
				'item:site' => 'Sites',

				/**
				 * Sessions
		*/
				'login' => "Log In",
				'signuptext' => "<p>Create an account and register your Nookdom now.</p><p> We'll send you a FREE filter.</p>",
				'newusersignup' => "Register",
				'orconnectwith' => "Or connect with ",
				'loginok' => "You have been logged in.",
				'loginerror' => "We couldn't log you in. Please check your credentials and try again.",
				'login:empty' => "Email and password are required.",
				'login:baduser' => "Unable to load your user account.",
				'auth:nopams' => "Internal error. No user authentication method installed.",

				'logout' => "Log Out",
				'logoutok' => "You have been logged out.",
				'logouterror' => "We couldn't log you out. Please try again.",

				'loggedinrequired' => "You must be logged in to view that page.",
				'adminrequired' => "You must be an administrator to view that page.",
				'membershiprequired' => "You must be a member of this group to view that page.",

				/**
				 * Errors
		*/
				'exception:title' => "Fatal Error.",
				'exception:contact_admin' => 'An unrecoverable error has occurred and has been logged. Contact the site administrator with the following information:',

				'actionundefined' => "The requested action (%s) was not defined in the system.",
				'actionnotfound' => "The action file for %s was not found.",
				'actionloggedout' => "Sorry, you cannot perform this action while logged out.",
				'actionunauthorized' => 'You are unauthorized to perform this action',

				'InstallationException:SiteNotInstalled' => 'Unable to handle this request. This site '
				. ' is not configured or the database is down.',
				'InstallationException:MissingLibrary' => 'Could not load %s',
				'InstallationException:CannotLoadSettings' => 'Yii could not load the settings file. It does not exist or there is a file permissions issue.',

				'SecurityException:Codeblock' => "Denied access to execute privileged code block",
				'DatabaseException:WrongCredentials' => "Yii couldn't connect to the database using the given credentials. Check the settings file.",
				'DatabaseException:NoConnect' => "Yii couldn't select the database '%s', please check that the database is created and you have access to it.",
				'SecurityException:FunctionDenied' => "Access to privileged function '%s' is denied.",
				'DatabaseException:DBSetupIssues' => "There were a number of issues: ",
				'DatabaseException:ScriptNotFound' => "Yii couldn't find the requested database script at %s.",
				'DatabaseException:InvalidQuery' => "Invalid query",
				'DatabaseException:InvalidDBLink' => "Connection to database was lost.",

				'IOException:FailedToLoadGUID' => "Failed to load new %s from GUID:%d",
				'InvalidParameterException:NonYiiObject' => "Passing a non-YiiObject to an YiiObject constructor!",
				'InvalidParameterException:UnrecognisedValue' => "Unrecognised value passed to constuctor.",

				'InvalidClassException:NotValidYiiStar' => "GUID:%d is not a valid %s",

				'PluginException:MisconfiguredPlugin' => "%s (guid: %s) is a misconfigured plugin. It has been disabled. Please search the Yii wiki for possible causes (http://docs.Yii.org/wiki/).",
				'PluginException:CannotStart' => '%s (guid: %s) cannot start and has been deactivated.  Reason: %s',
				'PluginException:InvalidID' => "%s is an invalid plugin ID.",
				'PluginException:InvalidPath' => "%s is an invalid plugin path.",
				'PluginException:InvalidManifest' => 'Invalid manifest file for plugin %s',
				'PluginException:InvalidPlugin' => '%s is not a valid plugin.',
				'PluginException:InvalidPlugin:Details' => '%s is not a valid plugin: %s',
				'PluginException:NullInstantiated' => 'YiiPlugin cannot be null instantiated. You must pass a GUID, a plugin ID, or a full path.',

				'YiiPlugin:MissingID' => 'Missing plugin ID (guid %s)',
				'YiiPlugin:NoPluginPackagePackage' => 'Missing YiiPluginPackage for plugin ID %s (guid %s)',

				'YiiPluginPackage:InvalidPlugin:MissingFile' => 'The required file "%s" is missing.',
				'YiiPluginPackage:InvalidPlugin:InvalidDependency' => 'Its manifest contains an invalid dependency type "%s".',
				'YiiPluginPackage:InvalidPlugin:InvalidProvides' => 'Its manifest contains an invalid provides type "%s".',
				'YiiPluginPackage:InvalidPlugin:CircularDep' => 'There is an invalid %s dependency "%s" in plugin %s.  Plugins cannot conflict with or require something they provide!',

				'YiiPlugin:Exception:CannotIncludeFile' => 'Cannot include %s for plugin %s (guid: %s) at %s.',
				'YiiPlugin:Exception:CannotRegisterViews' => 'Cannot open views dir for plugin %s (guid: %s) at %s.',
				'YiiPlugin:Exception:CannotRegisterLanguages' => 'Cannot register languages for plugin %s (guid: %s) at %s.',
				'YiiPlugin:Exception:NoID' => 'No ID for plugin guid %s!',

				'PluginException:ParserError' => 'Error parsing manifest with API version %s in plugin %s.',
				'PluginException:NoAvailableParser' => 'Cannot find a parser for manifest API version %s in plugin %s.',
				'PluginException:ParserErrorMissingRequiredAttribute' => "Missing required '%s' attribute in manifest for plugin %s.",

				'YiiPlugin:Dependencies:Requires' => 'Requires',
				'YiiPlugin:Dependencies:Suggests' => 'Suggests',
				'YiiPlugin:Dependencies:Conflicts' => 'Conflicts',
				'YiiPlugin:Dependencies:Conflicted' => 'Conflicted',
				'YiiPlugin:Dependencies:Provides' => 'Provides',
				'YiiPlugin:Dependencies:Priority' => 'Priority',

				'YiiPlugin:Dependencies:Yii' => 'Yii version',
				'YiiPlugin:Dependencies:PhpExtension' => 'PHP extension: %s',
				'YiiPlugin:Dependencies:PhpIni' => 'PHP ini setting: %s',
				'YiiPlugin:Dependencies:Plugin' => 'Plugin: %s',
				'YiiPlugin:Dependencies:Priority:After' => 'After %s',
				'YiiPlugin:Dependencies:Priority:Before' => 'Before %s',
				'YiiPlugin:Dependencies:Priority:Uninstalled' => '%s is not installed',
				'YiiPlugin:Dependencies:Suggests:Unsatisfied' => 'Missing',

				'YiiPlugin:InvalidAndDeactivated' => '%s is an invalid plugin and has been deactivated.',

				'InvalidParameterException:NonYiiUser' => "Passing a non-YiiUser to an YiiUser constructor!",

				'InvalidParameterException:NonYiiSite' => "Passing a non-YiiSite to an YiiSite constructor!",

				'InvalidParameterException:NonYiiGroup' => "Passing a non-YiiGroup to an YiiGroup constructor!",

				'IOException:UnableToSaveNew' => "Unable to save new %s",

				'InvalidParameterException:GUIDNotForExport' => "GUID has not been specified during export, this should never happen.",
				'InvalidParameterException:NonArrayReturnValue' => "Entity serialisation function passed a non-array returnvalue parameter",

				'ConfigurationException:NoCachePath' => "Cache path set to nothing!",
				'IOException:NotDirectory' => "%s is not a directory.",

				'IOException:BaseEntitySaveFailed' => "Unable to save new object's base entity information!",
				'InvalidParameterException:UnexpectedODDClass' => "import() passed an unexpected ODD class",
				'InvalidParameterException:EntityTypeNotSet' => "Entity type must be set.",

				'ClassException:ClassnameNotClass' => "%s is not a %s.",
				'ClassNotFoundException:MissingClass' => "Class '%s' was not found, missing plugin?",
				'InstallationException:TypeNotSupported' => "Type %s is not supported. This indicates an error in your installation, most likely caused by an incomplete upgrade.",

				'ImportException:ImportFailed' => "Could not import element %d",
				'ImportException:ProblemSaving' => "There was a problem saving %s",
				'ImportException:NoGUID' => "New entity created but has no GUID, this should not happen.",

				'ImportException:GUIDNotFound' => "Entity '%d' could not be found.",
				'ImportException:ProblemUpdatingMeta' => "There was a problem updating '%s' on entity '%d'",

				'ExportException:NoSuchEntity' => "No such entity GUID:%d",

				'ImportException:NoODDElements' => "No OpenDD elements found in import data, import failed.",
				'ImportException:NotAllImported' => "Not all elements were imported.",

				'InvalidParameterException:UnrecognisedFileMode' => "Unrecognised file mode '%s'",
				'InvalidParameterException:MissingOwner' => "File %s (file guid:%d) (owner guid:%d) is missing an owner!",
				'IOException:CouldNotMake' => "Could not make %s",
				'IOException:MissingFileName' => "You must specify a name before opening a file.",
				'ClassNotFoundException:NotFoundNotSavedWithFile' => "Unable to load filestore class %s for file %u",
				'NotificationException:NoNotificationMethod' => "No notification method specified.",
				'NotificationException:NoHandlerFound' => "No handler found for '%s' or it was not callable.",
				'NotificationException:ErrorNotifyingGuid' => "There was an error while notifying %d",
				'NotificationException:NoEmailAddress' => "Could not get the email address for GUID:%d",
				'NotificationException:MissingParameter' => "Missing a required parameter, '%s'",

				'DatabaseException:WhereSetNonQuery' => "Where set contains non WhereQueryComponent",
				'DatabaseException:SelectFieldsMissing' => "Fields missing on a select style query",
				'DatabaseException:UnspecifiedQueryType' => "Unrecognised or unspecified query type.",
				'DatabaseException:NoTablesSpecified' => "No tables specified for query.",
				'DatabaseException:NoACL' => "No access control was provided on query",

				'InvalidParameterException:NoEntityFound' => "No entity found, it either doesn't exist or you don't have access to it.",

				'InvalidParameterException:GUIDNotFound' => "GUID:%s could not be found, or you can not access it.",
				'InvalidParameterException:IdNotExistForGUID' => "Sorry, '%s' does not exist for guid:%d",
				'InvalidParameterException:CanNotExportType' => "Sorry, I don't know how to export '%s'",
				'InvalidParameterException:NoDataFound' => "Could not find any data.",
				'InvalidParameterException:DoesNotBelong' => "Does not belong to entity.",
				'InvalidParameterException:DoesNotBelongOrRefer' => "Does not belong to entity or refer to entity.",
				'InvalidParameterException:MissingParameter' => "Missing parameter, you need to provide a GUID.",
				'InvalidParameterException:LibraryNotRegistered' => '%s is not a registered library',
				'InvalidParameterException:LibraryNotFound' => 'Could not load the %s library from %s',

				'APIException:ApiResultUnknown' => "API Result is of an unknown type, this should never happen.",
				'ConfigurationException:NoSiteID' => "No site ID has been specified.",
				'SecurityException:APIAccessDenied' => "Sorry, API access has been disabled by the administrator.",
				'SecurityException:NoAuthMethods' => "No authentication methods were found that could authenticate this API request.",
				'SecurityException:ForwardFailedToRedirect' => 'Redirect could not be issued due to headers already being sent. Halting execution for security. Search http://docs.Yii.org/ for more information.',
				'InvalidParameterException:APIMethodOrFunctionNotSet' => "Method or function not set in call in expose_method()",
				'InvalidParameterException:APIParametersArrayStructure' => "Parameters array structure is incorrect for call to expose method '%s'",
				'InvalidParameterException:UnrecognisedHttpMethod' => "Unrecognised http method %s for api method '%s'",
				'APIException:MissingParameterInMethod' => "Missing parameter %s in method %s",
				'APIException:ParameterNotArray' => "%s does not appear to be an array.",
				'APIException:UnrecognisedTypeCast' => "Unrecognised type in cast %s for variable '%s' in method '%s'",
				'APIException:InvalidParameter' => "Invalid parameter found for '%s' in method '%s'.",
				'APIException:FunctionParseError' => "%s(%s) has a parsing error.",
				'APIException:FunctionNoReturn' => "%s(%s) returned no value.",
				'APIException:APIAuthenticationFailed' => "Method call failed the API Authentication",
				'APIException:UserAuthenticationFailed' => "Method call failed the User Authentication",
				'SecurityException:AuthTokenExpired' => "Authentication token either missing, invalid or expired.",
				'CallException:InvalidCallMethod' => "%s must be called using '%s'",
				'APIException:MethodCallNotImplemented' => "Method call '%s' has not been implemented.",
				'APIException:FunctionDoesNotExist' => "Function for method '%s' is not callable",
				'APIException:AlgorithmNotSupported' => "Algorithm '%s' is not supported or has been disabled.",
				'ConfigurationException:CacheDirNotSet' => "Cache directory 'cache_path' not set.",
				'APIException:NotGetOrPost' => "Request method must be GET or POST",
				'APIException:MissingAPIKey' => "Missing API key",
				'APIException:BadAPIKey' => "Bad API key",
				'APIException:MissingHmac' => "Missing X-Yii-hmac header",
				'APIException:MissingHmacAlgo' => "Missing X-Yii-hmac-algo header",
				'APIException:MissingTime' => "Missing X-Yii-time header",
				'APIException:MissingNonce' => "Missing X-Yii-nonce header",
				'APIException:TemporalDrift' => "X-Yii-time is too far in the past or future. Epoch fail.",
				'APIException:NoQueryString' => "No data on the query string",
				'APIException:MissingPOSTHash' => "Missing X-Yii-posthash header",
				'APIException:MissingPOSTAlgo' => "Missing X-Yii-posthash_algo header",
				'APIException:MissingContentType' => "Missing content type for post data",
				'SecurityException:InvalidPostHash' => "POST data hash is invalid - Expected %s but got %s.",
				'SecurityException:DupePacket' => "Packet signature already seen.",
				'SecurityException:InvalidAPIKey' => "Invalid or missing API Key.",
				'NotImplementedException:CallMethodNotImplemented' => "Call method '%s' is currently not supported.",

				'NotImplementedException:XMLRPCMethodNotImplemented' => "XML-RPC method call '%s' not implemented.",
				'InvalidParameterException:UnexpectedReturnFormat' => "Call to method '%s' returned an unexpected result.",
				'CallException:NotRPCCall' => "Call does not appear to be a valid XML-RPC call",

				'PluginException:NoPluginName' => "The plugin name could not be found",

				'SecurityException:authenticationfailed' => "User could not be authenticated",

				'CronException:unknownperiod' => '%s is not a recognised period.',

				'SecurityException:deletedisablecurrentsite' => 'You can not delete or disable the site you are currently viewing!',

				'RegistrationException:EmptyPassword' => 'The password fields cannot be empty',
				'RegistrationException:PasswordMismatch' => 'Passwords must match',
				'Robot is dead' => 'Robot is dead',
				'Robot is alive' => 'Robot is alive',
				'LoginException:BannedUser' => 'You have been banned from this site and cannot log in',
				'LoginException:UsernameFailure' => 'We could not log you in. Please check your email and password.',
				'LoginException:PasswordFailure' => 'We could not log you in. Please check your email and password.',
				'LoginException:AccountLocked' => 'Your account has been locked for too many log in failures.',
				'LoginException:ChangePasswordFailure' => 'Failed current password check.',

				'deprecatedfunction' => 'Warning: This code uses the deprecated function \'%s\' and is not compatible with this version of Yii',

				'pageownerunavailable' => 'Warning: The page owner %d is not accessible!',
				'viewfailure' => 'There was an internal failure in the view %s',
				'changebookmark' => 'Please change your bookmark for this page',
				'noaccess' => 'You need to login to view this content or the content has been removed or you do not have permission to view it.',
				'error:missing_data' => 'There was some data missing in your request',

				'error:default' => 'Oops...something went wrong.',
				'error:404' => 'Sorry. We could not find the page that you requested.',

				/**
				 * API
		*/
				'system.api.list' => "List all available API calls on the system.",
				'auth.gettoken' => "This API call lets a user obtain a user authentication token which can be used for authenticating future API calls. Pass it as the parameter auth_token",
		);

		if (isset($english[$message_key])) {
			$string = $english[$message_key];
		} else {
			$string = $message_key;
		}

		// only pass through if we have arguments to allow backward compatibility
		// with manual sprintf() calls.
		if ($args) {
			$string = vsprintf($string, $args);
		}

		return $string;
	}

}

?>