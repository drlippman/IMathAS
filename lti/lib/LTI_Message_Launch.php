<?php
namespace IMSGlobal\LTI;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Sanitize;

JWT::$leeway = 5;

class LTI_Message_Launch {

    private $db;
    private $cache;
    private $request;
    private $cookie;
    private $jwt;
    private $registration;
    private $launch_id;

    /**
     * Constructor
     *
     * @param Database  $database   Instance of the database interface used for looking up registrations and deployments.
     * @param Cache     $cache      Instance of the Cache interface used to loading and storing launches. If non is provided launch data will be store in $_SESSION.
     * @param Cookie    $cookie     Instance of the Cookie interface used to set and read cookies. Will default to using $_COOKIE and setcookie.
     */
    function __construct(Database $database, Cache $cache = null, Cookie $cookie = null) {
        $this->db = $database;

        $this->launch_id = uniqid("lti1p3_launch_", true);

        if ($cache === null) {
            $cache = new Cache();
        }
        $this->cache = $cache;

        if ($cookie === null) {
            $cookie = new Cookie();
        }
        $this->cookie = $cookie;
    }

    /**
     * Static function to allow for method chaining without having to assign to a variable first.
     */
    public static function new(Database $database, Cache $cache = null, Cookie $cookie = null) {
        return new LTI_Message_Launch($database, $cache, $cookie);
    }

    /**
     * Load an LTI_Message_Launch from a Cache using a launch id.
     *
     * @param string    $launch_id  The launch id of the LTI_Message_Launch object that is being pulled from the cache.
     * @param Database  $database   Instance of the database interface used for looking up registrations and deployments.
     * @param Cache     $cache      Instance of the Cache interface used to loading and storing launches. If non is provided launch data will be store in $_SESSION.
     *
     * @throws LTI_Exception        Will throw an LTI_Exception if validation fails or launch cannot be found.
     * @return LTI_Message_Launch   A populated and validated LTI_Message_Launch.
     */
    public static function from_cache($launch_id, Database $database, Cache $cache = null) {
        $new = new LTI_Message_Launch($database, $cache, null);
        $new->launch_id = $launch_id;
        $new->jwt = [ 'body' => $new->cache->get_launch_data($launch_id) ];
        return $new->validate_registration();
    }

    /**
     * Validates all aspects of an incoming LTI message launch and caches the launch if successful.
     *
     * @param array|string  $request    An array of post request parameters. If not set will default to $_POST.
     *
     * @throws LTI_Exception        Will throw an LTI_Exception if validation fails.
     * @return LTI_Message_Launch   Will return $this if validation is successful.
     */
    public function validate(array $request = null) {

        if ($request === null) {
            $request = $_POST;
        }
        $this->request = $request;

        return $this->validate_state()
            ->validate_jwt_format()
            ->validate_nonce()
            ->validate_registration()
            ->validate_jwt_signature()
            ->validate_deployment()
            ->validate_message()
            ->cache_launch_data();
    }


    /**
     * Returns whether or not the current launch can use the names and roles service.
     *
     * @return boolean  Returns a boolean indicating the availability of names and roles.
     */
    public function has_nrps() {
        return !empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice']['context_memberships_url']);
    }

    /**
     * Fetches an instance of the names and roles service for the current launch.
     *
     * @return LTI_Names_Roles_Provisioning_Service An instance of the names and roles service that can be used to make calls within the scope of the current launch.
     */
    public function get_nrps() {
        return new LTI_Names_Roles_Provisioning_Service(
            new LTI_Service_Connector($this->registration, $this->db),
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice']);
    }

    /**
     * Returns whether or not the current launch can use the groups service.
     *
     * @return boolean  Returns a boolean indicating the availability of groups.
     */
    public function has_gs() {
        return !empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti-gs/claim/groupsservice']['context_groups_url']);
    }

    /**
     * Fetches an instance of the groups service for the current launch.
     *
     * @return LTI_Course_Groups_Service An instance of the groups service that can be used to make calls within the scope of the current launch.
     */
    public function get_gs() {
        return new LTI_Course_Groups_Service(
            new LTI_Service_Connector($this->registration, $this->db),
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti-gs/claim/groupsservice']);
    }

    /**
     * Returns whether or not the current launch can use the assignments and grades service.
     *
     * @return boolean  Returns a boolean indicating the availability of assignments and grades.
     */
    public function has_ags() {
        return !empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint']);
    }

    /**
     * Fetches an instance of the assignments and grades service for the current launch.
     *
     * @return LTI_Assignments_Grades_Service An instance of the assignments an grades service that can be used to make calls within the scope of the current launch.
     */
    public function get_ags() {
        return new LTI_Assignments_Grades_Service(
            new LTI_Service_Connector($this->registration, $this->db),
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint']);
    }

    /**
     * If launch has a single lineitem, return it
     * @return string|bool
     */
    public function get_lineitem() {
      if ($this->has_ags()) {
        $ags = $this->jwt['body']['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint'];
        if (!empty($ags['lineitem'])) {
          return $ags['lineitem'];
        }
      }
      return false;
    }

    public function can_create_lineitem() {
      if ($this->has_ags()) {
        $ags = $this->jwt['body']['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint'];
        if (!empty($ags['scope']) &&
          in_array('https://purl.imsglobal.org/spec/lti-ags/scope/lineitem', $ags['scope'])
        ) {
          return true;
        }
      }
      return false;
    }

    public function can_set_grades() {
      if ($this->has_ags()) {
        $ags = $this->jwt['body']['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint'];
        if (!empty($ags['scope']) &&
          in_array('https://purl.imsglobal.org/spec/lti-ags/scope/score', $ags['scope'])
        ) {
          return true;
        }
      }
      return false;
    }

    /**
     * Fetches a deep link that can be used to construct a deep linking response.
     *
     * @return LTI_Deep_Link An instance of a deep link to construct a deep linking response for the current launch.
     */
    public function get_deep_link() {
        return new LTI_Deep_Link(
            $this->registration,
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/deployment_id'],
            $this->jwt['body']['https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings'],
            $this->db->get_tool_private_key());
    }

    /**
     * Returns whether or not the current launch is a deep linking launch.
     *
     * @return boolean  Returns true if the current launch is a deep linking launch.
     */
    public function is_deep_link_launch() {
        return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/message_type'] === 'LtiDeepLinkingRequest';
    }

    /**
     * Returns whether or not the current launch is a submission review launch.
     *
     * @return boolean  Returns true if the current launch is a submission review launch.
     */
    public function is_submission_review_launch() {
        return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/message_type'] === 'LtiSubmissionReviewRequest';
    }

    /**
     * Returns whether or not the current launch is a resource launch.
     *
     * @return boolean  Returns true if the current launch is a resource launch.
     */
    public function is_resource_launch() {
        return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/message_type'] === 'LtiResourceLinkRequest';
    }

    /**
     * Fetches the decoded body of the JWT used in the current launch.
     *
     * @return array|object Returns the decoded json body of the launch as an array.
     */
    public function get_launch_data() {
        return $this->jwt['body'];
    }

    /**
     * Get the unique launch id for the current launch.
     *
     * @return string   A unique identifier used to re-reference the current launch in subsequent requests.
     */
    public function get_launch_id() {
        return $this->launch_id;
    }

    public function get_client_id() {
      return is_array($this->jwt['body']['aud']) ? $this->jwt['body']['aud'][0] : $this->jwt['body']['aud'];
    }
    public function get_issuer() {
      return $this->jwt['body']['iss'];
    }
    public function get_deployment_id() {
      return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/deployment_id'];
    }
    public function get_platform_id() {
      // return a unique identifier for the platform
      return $this->registration->get_id();
    }
    public function get_target_link() {
      return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/target_link_uri'];
    }
    public function get_resource_link() {
      return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/resource_link'];
    }
    public function get_custom() {
      if (!empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/custom'])) {
        return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/custom'];
      } else {
        return array();
      }
    }

    public function get_due_date() {
      $custom = $this->get_custom();
      if (!empty($custom['canvas_assignment_due_at'])) {
        $duedate = strtotime($custom['canvas_assignment_due_at']);
        if ($duedate === false) {
          return 2000000000;
        } else {
          return $duedate;
        }
      } else if (!empty($custom['link_end_sub_time'])) {
        $duedate = strtotime($custom['link_end_sub_time']);
        if ($duedate === false) {
          return 2000000000;
        } else {
          return $duedate;
        }
      } else if (!empty($custom['link_end_avail_time'])) {
        $duedate = strtotime($custom['link_end_avail_time']);
        if ($duedate === false) {
          return 2000000000;
        } else {
          return $duedate;
        }
      }
      return false;
    }

    /**
     * Get the array of role claims
     * @return array
     */
    public function get_roles() {
      return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/roles'];
    }

    /**
     * Get user id provided by LMS (sub)
     * @return string
     */
    public function get_platform_user_id() {
      return $this->jwt['body']['sub'];
    }

    /**
     * Get the LMS user id that is the target of a submission review request
     */
    public function get_submission_review_user_id() {
        if (!empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/for_user'])) {
            $for_user = $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/for_user'];
            return $for_user['user_id'];
        }
        return false;
    }

    /**
     * Get context id provided by LMS
     * @return string
     */
    public function get_platform_context_id() {
      return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/context']['id'];
    }
    public function get_platform_context_label() {
      $context = $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/context'];
      if (isset($context['label'])) {
        return $context['label'];
      } else if (isset($context['title'])) {
        return $context['title'];
      } else {
        return '';
      }
    }
    public function get_platform_context_title() {
      $context = $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/context'];
      if (isset($context['title'])) {
        return $context['title'];
      } else if (isset($context['label'])) {
        return $context['label'];
      } else {
        return '';
      }
    }
    public function get_platform_context_history() {
      $custom = $this->get_custom();
      if (!empty($custom['context_history'])) {
        return explode(',', $custom['context_history']);
      } else {
        return array();
      }
    }

    private function get_public_key() {
      $key_set_url = $this->registration->get_key_set_url();
      if (empty($this->jwt['header']['kid'])) {
        throw new LTI_Exception("Missing key id", 1);
      }
      $kid = $this->jwt['header']['kid'];
      $key = $this->db->get_key($key_set_url, $kid);
      if ($key !== null) {
        return $key['publickey'];
      }

      // Download key set
      $public_key_set = json_decode(file_get_contents($key_set_url), true);

      if (empty($public_key_set)) {
          // Failed to fetch public keyset from URL.
          throw new LTI_Exception("Failed to fetch public key", 1);
      }

      // Find key used to sign the JWT (matches the KID in the header)
      $newkeys = array();
      foreach ($public_key_set['keys'] as $key) {
        try {
          $pubkey = openssl_pkey_get_details(JWK::parseKey($key));
        } catch(\Exception $e) {
          continue;
        }
        $newkeys[$key['kid']] = array('alg'=> $key['alg'] ?? 'RS256', 'pub'=>$pubkey['key']);
      }
      // record keys
      $this->db->record_keys($key_set_url, $newkeys);
      if (isset($newkeys[$this->jwt['header']['kid']])) {
        return $newkeys[$this->jwt['header']['kid']]['pub'];
      } else {
        // Could not find public key with a matching kid and alg.
        throw new LTI_Exception("Unable to find public key", 1);
      }
    }

    public function cache_launch_data() {
        $this->cache->cache_launch_data($this->launch_id, $this->jwt['body']);
        return $this;
    }

    public function get_migration_claim() {
        if (!empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/lti1p1']) &&
            !empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/lti1p1']['oauth_consumer_key'])
        ) {
            $claim = $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/lti1p1'];
            $claim['signing_string'] = $claim['oauth_consumer_key'] . '&' .
                $this->jwt['body']["https://purl.imsglobal.org/spec/lti/claim/deployment_id"]. '&' . 
                $this->jwt['body']['iss'] . '&' . 
                $this->get_client_id() . '&' . 
                $this->jwt['body']['exp'] . '&' . 
                $this->jwt['body']['nonce'];
            return $claim;
        } else {
            return false;
        }
    }

    public function get_lti1p1_userid() {
        if (!empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/lti1p1']) &&
            !empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/lti1p1']['user_id'])
        ) {
            return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/lti1p1']['user_id'];
        } else if (!empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/lti11_legacy_user_id'])) {
            return $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/lti11_legacy_user_id'];
        } else {
            return false;
        }
    }

    private function validate_state() {
        // Check State for OIDC.
        if (!isset($this->request['state']) || $this->cookie->get_cookie('lti1p3_' . $this->request['state']) !== $this->request['state']) {
            // Error if state doesn't match
            throw new LTI_Exception("State not found", 1);
        }
        return $this;
    }

    private function validate_jwt_format() {
        if (empty($this->request['id_token'])) {
            throw new LTI_Exception("Missing id_token", 1);
        }
        $jwt = $this->request['id_token'];

        // Get parts of JWT.
        $jwt_parts = explode('.', $jwt);

        if (count($jwt_parts) !== 3) {
            // Invalid number of parts in JWT.
            throw new LTI_Exception("Invalid id_token, JWT must contain 3 parts", 1);
        }

        // Decode JWT headers.
        $this->jwt['header'] = json_decode(JWT::urlsafeB64Decode($jwt_parts[0]), true);
        // Decode JWT Body.
        $this->jwt['body'] = json_decode(JWT::urlsafeB64Decode($jwt_parts[1]), true);

        return $this;
    }

    private function validate_nonce() {
        if (!$this->cache->check_nonce($this->jwt['body']['nonce'])) {
            //throw new LTI_Exception("Invalid Nonce");
        }
        return $this;
    }

    private function validate_registration() {
        // Find registration.
        if (empty($this->jwt['body']['aud'])) {
            echo "Unable to find registration. Missing client_id; no aud in JWT";
            throw new LTI_Exception("Missing aud in JWT.", 1);
        }
        $client_id = is_array($this->jwt['body']['aud']) ? $this->jwt['body']['aud'][0] : $this->jwt['body']['aud'];

        $this->registration = $this->db->find_registration_by_issuer($this->jwt['body']['iss'], $client_id);

        if (empty($this->registration)) {
          echo "Unable to find registration with issuer ".
            Sanitize::encodeStringForDisplay($this->jwt['body']['iss']).
            ' and client_id '.$client_id.'. ';
          echo "Ensure the LTI registration information (Client ID and such) from the LMS has been provided to the system admin.";
            throw new LTI_Exception("Registration not found.", 1);
        }

        return $this;
    }

    private function validate_jwt_signature() {
        // Fetch public key.
        $public_key = $this->get_public_key();

        // Validate JWT signature
        try {
            JWT::decode($this->request['id_token'], $public_key, array('RS256'));
        } catch(\Exception $e) {
            // var_dump($e);
            // Error validating signature.
            throw new LTI_Exception("Invalid signature on id_token", 1);
        }

        return $this;
    }

    private function validate_deployment() {
        if (empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/deployment_id'])) {
            // deployment not provided.
            throw new LTI_Exception("Missing deployment_id", 1);
        }
        // Find deployment.
        $deployment = $this->db->find_deployment($this->registration->get_id(),
          $this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/deployment_id']);

        if (empty($deployment)) {
            // deployment not recognized.
            throw new LTI_Exception("Unable to find deployment", 1);
        }

        return $this;
    }

    private function validate_message() {
        if (empty($this->jwt['body']['https://purl.imsglobal.org/spec/lti/claim/message_type'])) {
            // Unable to identify message type.
            throw new LTI_Exception("Invalid message type", 1);
        }

        // Do message type validation

        // Import all validators
        foreach (glob(__DIR__ . "/message_validators/*.php") as $filename) {
            include_once $filename;
        }

        // Create instances of all validators
        $classes = get_declared_classes();
        $validators = array();
        foreach ($classes as $class_name) {
            // Check the class implements message validator
            $reflect = new \ReflectionClass($class_name);
            if ($reflect->implementsInterface('\IMSGlobal\LTI\Message_Validator')) {
                // Create instance of class
                $validators[] = new $class_name();
            }
        }

        $message_validator = false;
        foreach ($validators as $validator) {
            if ($validator->can_validate($this->jwt['body'])) {
                if ($message_validator !== false) {
                    // Can't have more than one validator apply at a time.
                    throw new LTI_Exception("Validator conflict", 1);
                }
                $message_validator = $validator;
            }
        }

        if ($message_validator === false) {
            throw new LTI_Exception("Unrecognized message type.", 1);
        }

        if (!$message_validator->validate($this->jwt['body'])) {
            throw new LTI_Exception("Message validation failed.", 1);
        }

        return $this;

    }
}
?>
