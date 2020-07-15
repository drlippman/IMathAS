<?php
namespace IMSGlobal\LTI;

use Firebase\JWT\JWT;

class LTI_Service_Connector {

    const NEXT_PAGE_REGEX = "/^Link:.*<([^>]*)>; ?rel=\"next\"/i";

    private $registration;
    private $db;
    private $access_tokens = [];

    public function __construct(LTI_Registration $registration, Database $db) {
        $this->registration = $registration;
        $this->db = $db;
    }

    public function get_access_token($scopes) {

        // Don't fetch the same key more than once.
        sort($scopes);
        $scope_key = md5(implode('|', $scopes));
        if (isset($this->access_tokens[$scope_key])) {
          return $this->access_tokens[$scope_key];
        }
        list($cached_token,$failures) = $this->db->get_token($this->registration->get_id(), $scope_key);
        if ($cached_token !== false) {
          return $cached_token;
        }

        // Build up JWT to exchange for an auth token
        $client_id = $this->registration->get_client_id();
        $jwt_claim = [
                "iss" => $client_id,
                "sub" => $client_id,
                "aud" => $this->registration->get_auth_server(),
                "iat" => time() - 5,
                "exp" => time() + 60,
                "jti" => 'lti-service-token' . hash('sha256', random_bytes(64))
        ];

        // Get tool private key from our JWKS
        $private_key = $this->db->get_tool_private_key();

        // Sign the JWT with our private key (given by the platform on registration)
        $jwt = JWT::encode($jwt_claim, $private_key['privatekey'], 'RS256', $private_key['kid']);

        // Build auth token request headers
        $auth_request = [
            'grant_type' => 'client_credentials',
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $jwt,
            'scope' => implode(' ', $scopes)
        ];

        // Make request to get auth token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->registration->get_auth_token_url());
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($auth_request));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if (!empty($GLOBALS['CFG']['LTI']['skipsslverify'])) {
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        $resp = curl_exec($ch);
        $token_data = json_decode($resp, true);
        curl_close ($ch);

        if (isset($token_data['access_token'])) {

          $this->db->record_token($this->registration->get_id(), $scope_key, $token_data);

          return $this->access_tokens[$scope_key] = $token_data['access_token'];
        } else {
          // record the failure in the token store
          $token_data = [
            'access_token' => 'failed'.$failures,
            'expires' => time() + min(pow(3, $failures-1), 24*60*60)
          ];
          $this->db->record_token($this->registration->get_id(), $scope_key, $token_data);
          return false;
        }
    }

    public function make_service_request($scopes, $method, $url, $body = null, $content_type = 'application/json', $accept = 'application/json') {
        $token = $this->get_access_token($scopes);
        if ($token === false) {
          return false;
        }
        $ch = curl_init();
        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept:' . $accept,
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, strval($body));
            $headers[] = 'Content-Type: ' . $content_type;
        }
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } else if ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if (!empty($GLOBALS['CFG']['LTI']['skipsslverify'])) {
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        $response = curl_exec($ch);
        $request_info = curl_getinfo($ch);
        if (curl_errno($ch) || round(intval($request_info['http_code'])/100) != 2) {
            echo curl_error($ch);
            return false;
        }
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close ($ch);

        $resp_headers = substr($response, 0, $header_size);
        $resp_body = substr($response, $header_size);
        return [
            'headers' => array_filter(explode("\r\n", $resp_headers)),
            'body' => json_decode($resp_body, true),
        ];
    }
}
?>
