<?php

/**
 * Implements IMSGlobal\LTI\Database interface
 */

define('TOOL_HOST', $GLOBALS['basesiteurl']);

use \IMSGlobal\LTI;

class Imathas_LTI_Database implements LTI\Database {
  private $dbh;

  function __construct($DBH) {
    $this->dbh = $DBH;
  }

  public function find_registration_by_issuer($iss) {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_issuers WHERE issuer=?');
    $stm->execute(array($iss));
    $row = $stm->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
      return false;
    }
    return LTI\LTI_Registration::new()
      ->set_auth_login_url($row['auth_login_url'])
      ->set_auth_token_url($row['auth_token_url'])
      ->set_client_id($row['client_id'])
      ->set_key_set_url($row['key_set_url'])
      ->set_issuer($iss);
  }
  public function find_deployment($iss, $deployment_id) {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_deployments WHERE issuer=? AND deployment=?');
    $stm->execute(array($iss, $deployment_id));
    if ($stm->rowCount()===0) {
      // no existing deployment record, create one
      $stm = $this->dbh->prepare('INSERT INTO imas_lti_deployments (issuer,deployment) VALUES (?,?)');
      $stm->execute(array($iss, $deployment_id));
    }
    return LTI\LTI_Deployment::new()->set_deployment_id($deployment_id);
  }


  public function get_jwks_keys() {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_keys WHERE key_set_url=?');
    $stm->execute(array(TOOL_HOST.'/lti/jwks.php'));
    return $stm->fetchAll(PDO::FETCH_ASSOC);
  }

  public function get_tool_private_key() {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_keys WHERE key_set_url=? ORDER BY created_at DESC LIMIT 1');
    $stm->execute(array(TOOL_HOST.'/lti/jwks.php'));
    return $stm->fetch(PDO::FETCH_ASSOC);
  }

  public function get_key($keyseturl, $kid) {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_keys WHERE key_set_url=? AND kid=?');
    $stm->execute(array($keyseturl, $kid));
    return $stm->fetch(PDO::FETCH_ASSOC);
  }

  public function delete_key($keyseturl, $kid) {
    $stm = $this->dbh->prepare('DELETE FROM imas_lti_keys WHERE key_set_url=? AND kid=?');
    $stm->execute(array($keyseturl, $kid));
    return $stm->fetch(PDO::FETCH_ASSOC);
  }

  public function record_keys($keyseturl,$keys) {
    $stm = $this->dbh->prepare('INSERT IGNORE INTO imas_lti_keys (key_set_url,kid,alg,publickey) VALUES (?,?,?,?)');
    foreach ($keys as $kid=>$keyinfo) {
      $stm->execute(array($keyseturl,$kid,$keyinfo['alg'],$keyinfo['pub']));
    }
  }

  public function get_token($iss, $scope) {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_tokens WHERE issuer=? AND scopes=?');
    $stm->execute(array($iss, $scope));
    $row = $stm->fetch($PDO::FETCH_ASSOC);
    if ($row === false) {
      return false;
    } else if ($row['expires'] > time()) {
      $stm = $this->dbh->prepare('DELETE FROM imas_lti_tokens WHERE issuer=? AND scopes=?');
      $stm->execute(array($iss, $scope));
      return false;
    } else {
      return $row['token'];
    }
  }

  public function record_token($iss, $scope, $tokeninfo) {
    $stm = $this->dbh->prepare('REPLACE INTO imas_lti_tokens (issuer, scopes, token, expires) VALUES (?,?,?,?)');
    $stm->execute(array($iss, $scope, $tokeninfo['access_token'], time() + $tokeninfo['expires_in'] - 1));
  }

}
