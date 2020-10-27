<?php

require("../../init.php");

if ($myrights < 100) {
  exit;
}

$keyseturl = $basesiteurl.'/lti/jwks.php';

if (!empty($_POST)) {
  foreach ($_POST as $k=>$v) {
    if ($k == 'newkey') {
      $config = array(
        "digest_alg" => "sha256",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
      );
      $res = openssl_pkey_new($config);
      openssl_pkey_export($res, $privKey);
      $pubKey = openssl_pkey_get_details($res);
      $pubKey = $pubKey["key"];
      $stm = $DBH->prepare('INSERT INTO imas_lti_keys (key_set_url,kid,alg,publickey,privatekey) VALUES (?,?,?,?,?)');
      $stm->execute(array($keyseturl,uniqid(),'RS256',$pubKey,$privKey));
    } else if (substr($k,0,6)=='delete') {
      $kid = substr($k,6);
      $stm = $DBH->prepare('DELETE FROM imas_lti_keys WHERE key_set_url=? AND kid=?');
      $stm->execute(array($keyseturl, $kid));
    }
  }

  header('Location: ' . $basesiteurl . "/lti/admin/privkeys.php");
  exit;
}

$pagetitle = _('LTI Private Keys');
require("../../header.php");

echo '<div class=breadcrumb>'.$breadcrumbbase;
echo '<a href="../../admin/admin2.php">'._('Admin').'</a> ';
echo '&gt; <a href="platforms.php">'._('LTI 1.3 Platforms').'</a> ';
echo '&gt; '._('LTI Private Keys').'</div>';

echo '<h1>'._('LTI Private Keys').'</h1>';
echo '<p>'._('You MUST have at least one private key for LTI 1.3 to function. You normally would only create a new one as part of key rotation.').'</p>';

echo '<p>'.('Our JWT Key Set URL: ').$keyseturl.'</p>';

$stm = $DBH->prepare('SELECT kid,publickey,created_at FROM imas_lti_keys WHERE key_set_url=?');
$stm->execute(array($keyseturl));
echo '<form method="post" action="privkeys.php">';
echo '<ul class=nomark>';
if ($stm->rowCount()===0) {
  echo '<li>'._('No private keys').'</li>';
}
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
  echo '<li>'._('Key ID: ').Sanitize::encodeStringForDisplay($row['kid']).', '._('Created: ');
  echo date("M j Y ", strtotime($row['created_at']));
  $pubkey = Sanitize::encodeStringForJavascript('<pre>'.$row['publickey'].'</pre>');
  echo ' '.'<a href="#" onclick="GB_show(\'Public Key\',\''.$pubkey.'\',600,500);return false;">';
  echo _('View Public Key').'</a>';
  echo '<button type=submit name="delete'.Sanitize::encodeStringForDisplay($row['kid']).'" value=1 ';
  echo 'onclick="return confirm(\''._('Are you SURE you want to delete this key?').'\');">';
  echo _('Delete').'</button>';
  echo '</li>';
}
echo '</ul>';
echo '<p><button type=submit name="newkey" value=1>'._('Add New Key').'</button></p>';
echo '</form>';
require('../../footer.php');
