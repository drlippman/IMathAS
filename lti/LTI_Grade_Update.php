<?php

foreach (glob(__DIR__ . "/php-jwt/*.php") as $filename) {
    require_once $filename;
}
Firebase\JWT\JWT::$leeway = 5;
use Firebase\JWT\JWT;

/**
 * LTI 1.3 grade update class.
 * This one assumes we've alredy checked scopes,
 * and that we already know the update lineitem.
 *
 * This class allows doing updates across multiple platforms and lineitems,
 * making it handy for processing the lti queue
 *
 * This has code duplication with the other libraries
 */
define('TOOL_HOST', $GLOBALS['basesiteurl']);

class LTI_Grade_Update {
  private $dbh;
  private $access_tokens = [];
  private $private_key = '';
  private $failures = [];
  private $debug = false;

  public function __construct(PDO $DBH) {
    $this->dbh = $DBH;
    $this->debug = !empty($CFG['LTI']['noisydebuglog']);
  }

  /**
   * Determine if we have a token for particular platform already
   * If this returns true, call token_valid to make sure the token is valid.
   *
   * @param  int  $platform_id imas_lti_platforms.id
   * @return bool
   */
  public function have_token(int $platform_id): bool {
    // see if we already have the token in our private variable cache
    if (isset($this->access_tokens[$platform_id]) &&
      $this->access_tokens[$platform_id]['expires'] >= time()
    ) {
      return true;
    }

    // see if we already have a token in the the database
    $scopes = array('https://purl.imsglobal.org/spec/lti-ags/scope/score');
    $scopehash = md5(implode('|',$scopes));

    $stm = $this->dbh->prepare('SELECT token,expires FROM imas_lti_tokens WHERE platformid=? AND scopes=?');
    $stm->execute(array($platform_id, $scopehash));
    $row = $stm->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
      return false;
    } else if ($row['expires'] < time() + 60) { // expired or expires in the next minute
      if (substr($row['token'],0,6)==='failed') {
        $this->failures[$platform_id] = intval(substr($row['token'],6));
      }
      $stm = $this->dbh->prepare('DELETE FROM imas_lti_tokens WHERE platformid=? AND scopes=?');
      $stm->execute(array($platform_id, $scopehash));
      return false;
    } else {
      $row['failed'] = (substr($row['token'],0,6)==='failed');
      $this->access_tokens[$platform_id] = $row;
      return true;
    }
  }

  /**
   * Checks if an existing token is valid
   * it might not be valid if it's a record of a failed previous attempt
   *
   * @param  int  $platform_id imas_lti_platforms.id
   * @return bool
   */
  public function token_valid(int $platform_id): bool {
    if (isset($this->access_tokens[$platform_id]) &&
      empty($this->access_tokens[$platform_id]['failed'])
    ) {
      return true;
    }
    return false;
  }

  /**
   * Immediately send grade update (no ltiqueue)
   * @param  string $token            the token string
   * @param  string $score_url        the lineitem url
   * @param  float $score             normalized to 0-1
   * @param  string $ltiuserid        the LMS provided userid; imas_ltiusers.ltiuserid
   * @param  string $activityProgress default 'Submitted'
   * @param  string $gradingProgress  default 'FullyGraded'
   * @param  int    $isstu            default 1
   * @param  string $comment          default ''
   * @return false|array  false on failure, or array with body and headers
   */
  public function send_update(string $token, string $score_url, float $score,
    string $ltiuserid, string $activityProgress='Submitted',
    string $gradingProgress='FullyGraded', $isstu = 1, string $comment = ''
  ) {
    $pos = strpos($score_url, '?');
    $score_url = $pos === false ? $score_url . '/scores' : substr_replace($score_url, '/scores', $pos, 0);

    $content = $this->get_update_body($token, $score, $ltiuserid, $isstu, null,
      $activityProgress, $gradingProgress, $comment);
    $this->debuglog('Sending update: '.$content['body']);
    // try to spawn a curl and don't wait for response
    /*
    $disabled = explode(', ', ini_get('disable_functions'));
    if (function_exists('exec') && !in_array('exec', $disabled)) {
  	  try {
    		$cmd = "curl -X POST";
    		foreach ($content['header'] as $hdr) {
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
    */
    // do it the usual way, waiting for a response
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $score_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, strval($content['body']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $content['header']);

    if (!empty($GLOBALS['CFG']['LTI']['skipsslverify'])) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }

    $response = curl_exec($ch);
    $request_info = curl_getinfo($ch);
    if (curl_errno($ch) || round(intval($request_info['http_code'])/100) != 2) {
        $this->debuglog('Grade update Error:' . curl_error($ch));
        return false;
    }
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close ($ch);

    $resp_headers = substr($response, 0, $header_size);
    $resp_body = substr($response, $header_size);
    $this->debuglog('Grade update success:' . $resp_body);
    return [
        'headers' => array_filter(explode("\r\n", $resp_headers)),
        'body' => json_decode($resp_body, true),
    ];
  }

  /**
   * Get body and headers for grade update
   * @param  string $token            the token string
   * @param  float $score             the score, normalized 0-1
   * @param  string $ltiuserid        the LMS provided userid; imas_ltiusers.ltiuserid
   * @param  boolean    $isstu            default true
   * @param  int?   $addedon          the time the submission was added (null for default)
   * @param  string $activityProgress default 'Submitted'
   * @param  string $gradingProgress  default 'FullyGraded'
   * @param  string $comment          default ''
   * @return array [body=>, header=>]
   */
  public function get_update_body(string $token, float $score, string $ltiuserid, 
    $isstu = true, $addedon = null,
    string $activityProgress='Submitted', string $gradingProgress='FullyGraded',
    string $comment = ''
  ) {
    $canvasext = [
        'new_submission' => ($isstu ? true : false)
    ];
    if ($isstu && !empty($addedon)) {
        $canvasext['submitted_at'] = date('Y-m-d\TH:i:s.uP', $addedon);
    }
    $grade = [
      'scoreGiven' => max(0,$score),
      'scoreMaximum' => 1,
      'timestamp' => date('Y-m-d\TH:i:s.uP'),
      'userId' => $ltiuserid,
      'activityProgress' => $activityProgress,
      'gradingProgress' => $gradingProgress,
      'comment' => $comment,
      'https://canvas.instructure.com/lti/submission' => $canvasext
    ];
    $body = json_encode(array_filter($grade, function($v) { // don't filter 0
        return ($v !== null && $v !== '');
    }));

    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Content-Type: application/vnd.ims.lis.v1.score+json'
    ];
    return array('body'=>$body, 'header'=>$headers);
  }

  /**
   * Get an access token
   * @param  int $platform_id    imas_lti_platforms.id
   * @param  string $client_id    optional if known - the client_id
   * @param  string $token_server optional if known - the token_server_url
   * @param  string $auth_server  optional if known - the aud for token request
   * @return false|string access token, or false on failure
   */
  public function get_access_token(int $platform_id, string $client_id='', string $token_server='', string $auth_server='') {
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
    $row = $stm->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {

    } else if ($row['expires'] < time() + 10) {
      if (substr($row['token'],0,6)==='failed') {
        $this->failures[$platform_id] = intval(substr($row['token'],6));
      }
      $stm = $this->dbh->prepare('DELETE FROM imas_lti_tokens WHERE platformid=? AND scopes=?');
      $stm->execute(array($platform_id, $scopehash));
    } else {
      $row['failed'] = (substr($row['token'],0,6)==='failed');
      $this->access_tokens[$platform_id] = $row;
      return $row['token'];
    }

    // Need to request a token
    if (empty($client_id)) {
      $stm = $this->dbh->prepare('SELECT client_id,auth_token_url,auth_server FROM imas_lti_platforms WHERE id=?');
      $stm->execute(array($platform_id));
      list($client_id, $token_server, $auth_server) = $stm->fetch(PDO::FETCH_NUM);
    }
    $this->debuglog('requesting a token from '.$platform_id);
    $request_post = $this->get_token_request_post($platform_id, $client_id, $token_server, $auth_server);
    // Make request to get auth token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_server);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if (!empty($GLOBALS['CFG']['LTI']['skipsslverify'])) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }

    $resp = curl_exec($ch);
    $token_data = json_decode($resp, true);
    $error = curl_error($ch);
    curl_close ($ch);

    if (!empty($token_data['access_token']) && !empty($token_data['expires_in'])) {
      $this->store_access_token($platform_id, $token_data);
      $this->debuglog('got token from '.$platform_id);
      return $token_data['access_token'];
    } else {
        // record failure
      $this->token_request_failure($platform_id, $resp);
      $this->debuglog('token request error '.$error);
      return false;
    }
  }

  /**
   * Store an access token
   * @param int   $platform_id imas_lti_platforms.id
   * @param array $token_data  array with 'access_token', 'expires_in', and 'scope'
   * @return void
   */
  public function store_access_token(int $platform_id, array $token_data): void {
    /*  we know what the scope is here, so skip this
    $scopes = explode(' ', $token_data['scope']);
    sort($scopes);
    $scopehash = md5(implode('|',$scopes));
    */
    $scopehash = md5('https://purl.imsglobal.org/spec/lti-ags/scope/score');
    $stm = $this->dbh->prepare('REPLACE INTO imas_lti_tokens (platformid, scopes, token, expires) VALUES (?,?,?,?)');
    $stm->execute(array($platform_id, $scopehash, $token_data['access_token'], time() + $token_data['expires_in'] - 1));
    $this->access_tokens[$platform_id] = array(
      'token' => $token_data['access_token'],
      'expires' => time() + $token_data['expires_in'] - 1
    );
  }

  /**
   * Get the built query for a token request
   * @param  int    $platform_id
   * @param  string $client_id
   * @param  string $token_server
   * @param  string $auth_server
   * @return string output of http_build_query
   */
  public function get_token_request_post(int $platform_id, string $client_id,
    string $token_server, string $auth_server
  ): string {
    // Build up JWT to exchange for an auth token
    $jwt_claim = [
      "iss" => $client_id,
      "sub" => $client_id,
      "aud" => empty($auth_server) ? $token_server : $auth_server,
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

    return http_build_query($auth_request);
  }

  /**
   * Handle token request failure.  Store failure.
   * @param  int    $platform_id
   */
  public function token_request_failure(int $platform_id, $response = '') {
        if (isset($this->failures[$platform_id])) {
            $this->failures[$platform_id]++;
        } else {
            $this->failures[$platform_id] = 1;
        }
        $failures = $this->failures[$platform_id];
        if ($failures == 2 && !empty($response)) {
            // log failure response
            $logdata = 'Grade token failure for platform '. $platform_id . ', response: ' . $response;
            $logstm = $this->dbh->prepare("INSERT INTO imas_log (time,log) VALUES (?,?)");
            $logstm->execute([time(), $logdata]);
        }
        $token_data = [
            'access_token' => 'failed'.$failures,
            'scope' => 'https://purl.imsglobal.org/spec/lti-ags/scope/score',
            'expires_in' => min(300*$failures*$failures, 24*60*60)
        ];
        // store failure
        $this->store_access_token($platform_id, $token_data);
  }

  /**
   * Get client_id and auth_token_url and auth_server for a platform
   * @param  int   $platform_id
   * @return array
   */
  public function get_platform_info(int $platform_id): array {
    $stm = $this->dbh->prepare('SELECT client_id,auth_token_url,auth_server FROM imas_lti_platforms WHERE id=?');
    $stm->execute(array($platform_id));
    return $stm->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * We call this from ltiqueue if token failed, so we'll update the sendon
   * for a grade update using that token to just after the token failure expires
   * @param  string $hash       imas_ltiqueue.hash
   * @param  int $platform_id   imas_lti_platforms.id
   * @return void
   */
  public function update_sendon(string $hash, int $platform_id): void {
    $newsendon = $this->access_tokens[$platform_id]['expires'] + 1;
    $stm = $this->dbh->prepare('UPDATE imas_ltiqueue SET sendon=? WHERE hash=?');
    $stm->execute(array($newsendon, $hash));
  }

  /**
   * Get tool's private key
   * @return array
   */
  private function get_tool_private_key(): array {
    if (!empty($this->private_key)) {
      return $this->private_key;
    }
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_keys WHERE key_set_url=? AND privatekey != "" ORDER BY created_at DESC LIMIT 1');
    $stm->execute(array(TOOL_HOST.'/lti/jwks.php'));
    $row = $stm->fetch(PDO::FETCH_ASSOC);
    $this->private_key = $row;
    return $row;
  }

  /**
   * Write logging info to debug file, if enabled
   * @param  string  $info  string to write
   */
  private function debuglog(string $info): void {
    if ($this->debug) {
      $fh = fopen(__DIR__.'/ltidebug.txt','a');
      fwrite($fh, $info."\n");
      fclose($fh);
    }
  }

}
