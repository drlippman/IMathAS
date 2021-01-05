<?php

/**
 * Display our keyset
 */

require('../init_without_validate.php');
require_once(__DIR__ . '/lib/lti.php');
require_once __DIR__ . '/Database.php';

use \IMSGlobal\LTI;
use \Firebase\JWT\JWK;
use \Firebase\JWT\JWT;

$db = new Imathas_LTI_Database($DBH);
$keys = $db->get_jwks_keys();

$jwks = array();
foreach ($keys as $key) {
  $keyInfo = openssl_pkey_get_details(openssl_pkey_get_public($key['publickey']));
  $jwks[] = array(
      'kty' => 'RSA',
      'alg' => $key['alg'],
      'use' => 'sig',
      'e' => JWT::urlsafeB64Encode($keyInfo['rsa']['e']),
      'n' => JWT::urlsafeB64Encode($keyInfo['rsa']['n']),
      'kid' => $key['kid']
  );
}
header('Content-Type: application/json');
echo json_encode(array('keys'=>$jwks));
