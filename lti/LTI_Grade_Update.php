<?php

foreach (glob(__DIR__ . "/php-jwt/*.php") as $filename) {
    require_once $filename;
}
Firebase\JWT\JWT::$leeway = 5;
use Firebase\JWT\JWT;

/**
 * A very simple LTI 1.3 grade update class.
 * This one assumes we've alredy checked scopes,
 * and that we already know the update lineitem.
 */
define('TOOL_HOST', $GLOBALS['basesiteurl']);

class LTI_Grade_Update {
  private $dbh;
  private $access_tokens = [];
  private $private_key = '';

  public function __construct($DBH) {
    $this->dbh = $DBH;
  }

  /**
   * Immediately send grade update (no ltiqueue)
   * @param  string $token            [description]
   * @param  string $score_url        [description]
   * @param  float $score            [description]
   * @param  string $ltiuserid        [description]
   * @param  string $activityProgress [description]
   * @param  string $gradingProgress  [description]
   * @param  string $comment          [description]
   * @return bool|array
   */
  public function send_update($token, $score_url, $score, $ltiuserid,
    $activityProgress='Submitted', $gradingProgress='Pending', $comment = ''
  ) {
    $pos = strpos($score_url, '?');
    $score_url = $pos === false ? $score_url . '/scores' : substr_replace($score_url, '/scores', $pos, 0);

    $content = $this->get_update_body($token, $score_url, $score, $ltiuserid,
      $activityProgress, $gradingProgress, $comment);

    // try to spawn a curl and don't wait for response
    $disabled = explode(', ', ini_get('disable_functions'));
    if (function_exists('exec') && !in_array('exec', $disabled)) {
  	  try {
    		$cmd = "curl -X POST";
    		foreach ($content['headers'] as $hdr) {
    			if (strlen($hdr)<2) {continue;}
    			//$cmd .= " -H '".str_replace("'","\\'",$hdr)."'";
    			$cmd .= " -H " . escapeshellarg($hdr);
    		}
    		$cmd .= " -d " . escapeshellarg($content['body']) . ' ' . escapeshellarg($score_url);
    		$cmd .= " > /dev/null 2>&1 &";
    		@exec($cmd, $output, $exit);
    		return ($exit == 0);
  	  } catch (Exception $e) {
  		//continue below
  	  }
    }
    // do it the usual way, waiting for a response
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $score_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, strval($content['body']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $content['headers']);
    $response = curl_exec($ch);
    if (curl_errno($ch)){
        //echo 'Request Error:' . curl_error($ch);
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

  /**
   * Get body and headers for grade update
   * @param  string $token            [description]
   * @param  float $score            [description]
   * @param  string $ltiuserid        [description]
   * @param  string $activityProgress [description]
   * @param  string $gradingProgress  [description]
   * @param  string $comment          [description]
   * @return array [body=>, headers=>]
   */
  public function get_update_body($token, $score, $ltiuserid, $activityProgress='Submitted',
    $gradingProgress='Pending', $comment = ''
  ) {
    $grade = [
      'scoreGiven' => max(0,$score),
      'scoreMaximum' => 1,
      'timestamp' => date('Y-m-d\TH:i:s.uP'),
      'userId' => $ltiuserid,
      'activityProgress' => $activityProgress,
      'gradingProgress' => $gradingProgress,
      'comment' => $comment
    ];
    $body = json_encode(array_filter($grade));

    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Content-Type: application/vnd.ims.lis.v1.score+json'
    ];
    return array('body'=>$body, 'headers'=>$headers);
  }

  /**
   * Get an access token
   * @param  int $platform_id  [description]
   * @param  string $client_id    [description]
   * @param  string $auth_server  [description]
   * @param  string $token_server [description]
   * @return string access token
   */
  public function get_access_token($platform_id, $client_id='', $auth_server='', $token_server='') {
    // see if we already have the token in our private variable cache
    if (isset($this->access_tokens[$platform_id]) &&
      $this->access_tokens[$platform_id]['expires'] < time()
    ) {
      return $this->access_tokens[$platform_id]['token'];
    }

    // see if we already have a token in the the database
    $scopes = array('https://purl.imsglobal.org/spec/lti-ags/scope/score');
    $scopehash = md5(implode('|',$scopes));

    $stm = $this->dbh->prepare('SELECT token,expires FROM imas_lti_tokens WHERE platformid=? AND scopes=?');
    $stm->execute(array($platform_id, $scopehash));
    $row = $stm->fetch($PDO::FETCH_ASSOC);
    if ($row === false) {

    } else if ($row['expires'] > time()) {
      $stm = $this->dbh->prepare('DELETE FROM imas_lti_tokens WHERE platformid=? AND scopes=?');
      $stm->execute(array($platform_id, $scope));
    } else {
      $this->access_tokens[$platform_id] = $row;
      return $row['token'];
    }

    // Need to request a token
    if (empty($client_id)) {
      $stm = $this->dbh->prepare('SELECT client_id,auth_login_url,auth_token_url FROM imas_lti_platforms WHERE id=?');
      $stm->execute(array($platform_id));
      list($client_id, $auth_server, $token_server) = $stm->fetch(PDO::FETCH_NUM);
    }
    // Build up JWT to exchange for an auth token
    $jwt_claim = [
      "iss" => $client_id,
      "sub" => $client_id,
      "aud" => $auth_server,
      "iat" => time() - 5,
      "exp" => time() + 60,
      "jti" => 'lti-service-token' . hash('sha256', random_bytes(64))
    ];

    // Get tool private key from our JWKS
    $private_key = $this->get_tool_private_key();

    // Sign the JWT with our private key (given by the platform on registration)
    $jwt = JWT::encode($jwt_claim, $private_key['privatekey'], 'RS256', $private_key['kid']);

    // Build auth token request headers
    $auth_request = [
        'grant_type' => 'client_credentials',
        'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
        'client_assertion' => $jwt,
        'scope' => 'https://purl.imsglobal.org/spec/lti-ags/scope/score'
    ];

    // Make request to get auth token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_server);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($auth_request));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $resp = curl_exec($ch);
    $token_data = json_decode($resp, true);
    curl_close ($ch);

    if (isset($token_data['access_token'])) {
      $stm = $this->dbh->prepare('REPLACE INTO imas_lti_tokens (platformid, scopes, token, expires) VALUES (?,?,?,?)');
      $stm->execute(array($id, $scope, $token_data['access_token'], time() + $token_data['expires_in'] - 1));
      $this->access_tokens[$platform_id] = array(
        'token' => $token_data['access_token'],
        'expires' => time() + $token_data['expires_in'] - 1
      );
      return $token_data['access_token'];
    } else {
      echo "Error: no access token returned";
      return false;
    }
  }


  private function get_tool_private_key() {
    if (!empty($this->private_key)) {
      return $this->private_key;
    }
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_keys WHERE key_set_url=? ORDER BY created_at DESC LIMIT 1');
    $stm->execute(array(TOOL_HOST.'/lti/jwks.php'));
    $row = $stm->fetch(PDO::FETCH_ASSOC);
    $this->private_key = $row;
    return $row;
  }

}
