<?php
/*
 * IMathAS: Assessment launch endpoint
 * (c) 2019 David Lippman
 *
 * Some common processing useful at the start of scripts, after init.
 */

$isteacher = isset($teacherid);
$istutor = isset($tutorid);
$isstudent = isset($studentid);

if (!$isteacher && !$istutor && !$isstudent) {
  echo '{error: "no_access"}';
  exit;
}

if (!$isstudent) {
  $studentinfo = array('latepasses' => 0, 'timelimitmult' => 1);
}

$canViewAll = $isteacher || $istutor;

/**
 * Check if the required parameters are set
 * @param  string $method   'GET' or 'POST'
 * @param  array $required  array of required parameter strings
 * @return void
 */
function check_for_required($method, $required) {
  foreach ($required as $r) {
    if (($method == 'POST' && !isset($_POST[$r])) ||
      ($method == 'GET' && !isset($_GET[$r]))
    ) {
      echo '{error: "missing_param", "error_details": "Missing parameter '.sanitize::encodeStringForJavascript($r).'"}';
      exit;
    }
  }
}

if ($_SERVER['HTTP_HOST'] == 'localhost') {
  //to help with development, while vue runs on 8080
  header('Access-Control-Allow-Origin: http://localhost:8080');
  header("Access-Control-Allow-Credentials: true");
  header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
  header("Access-Control-Allow-Headers: Origin");
}
