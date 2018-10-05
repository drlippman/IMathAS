<?php

//Parse query string parameters
// str: query string
// returns:  array(parameter associate array, auth key, signature)
function parse_params($str) {
	$pairs = explode('&', $str);
	$params = array();
	$sig = ''; $auth = '';
	foreach ($pairs	as $p) {
		$pieces = explode('=', $p, 2);
		if ($pieces[0]=='signature') {
			$sig = $pieces[1];
		} else {
			$params[$pieces[0]] = urldecode($pieces[1]);
		}
		if ($pieces[0]=='auth') {
			$auth = $pieces[1];
		}
	}
	return array($params, $auth, $sig);
}

//Check param signature
// params:  parameter array, stripped of signature
// secret:  shared secret
// sig:     signature to check
// returns: true or false
function check_signature($params, $secret, $sig) {
	$pairs = array();
	foreach ($params as $k=>$v) {
		$pairs[] = $k . '=' . urlencode($v);
	}
	$str = implode('&',$pairs);
	return (base64_encode(hash_hmac('sha1', $str, $secret, true))==$sig);
}


//take an associative array of parameters and turn it into a signed query string
// messagearray: associative parameter array
// secret:       shared secret to sign with
// returns: query string including signature 
//   adds in time parameter if not already included
function build_signed_querystring($messagearray, $secret) {
	if (!isset($messagearray['time'])) {
		$messagearray['time'] = time();
	}
	$pairs = array();
	foreach ($messagearray as $k=>$v) {
		$pairs[] = $k . '=' . urlencode($v);
	}
	
	$str = implode('&',$pairs);
	$sig = base64_encode(hash_hmac('sha1', $str, $secret, true));
	return $str . '&signature=' . $sig;
}

?>
