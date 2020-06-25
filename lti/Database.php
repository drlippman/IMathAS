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
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_platforms WHERE issuer=?');
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
      ->set_issuer($iss)
      ->set_id($row['id']);
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

  public function get_token($id, $scope) {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_tokens WHERE platformid=? AND scopes=?');
    $stm->execute(array($id, $scope));
    $row = $stm->fetch($PDO::FETCH_ASSOC);
    if ($row === false) {
      return false;
    } else if ($row['expires'] > time()) {
      $stm = $this->dbh->prepare('DELETE FROM imas_lti_tokens WHERE platformid=? AND scopes=?');
      $stm->execute(array($id, $scope));
      return false;
    } else {
      return $row['token'];
    }
  }

  public function record_token($id, $scope, $tokeninfo) {
    $stm = $this->dbh->prepare('REPLACE INTO imas_lti_tokens (platformid, scopes, token, expires) VALUES (?,?,?,?)');
    $stm->execute(array($id, $scope, $tokeninfo['access_token'], time() + $tokeninfo['expires_in'] - 1));
  }

  /**
   * Get local user id
   * @param  string $ltiuserid
   * @param  string $platform_id
   * @return false|int local userid
   */
  function get_local_userid($ltiuserid, $platform_id) {
    $stm = $this->dbh->prepare('SELECT userid FROM imas_ltiusers WHERE ltiuserid=? AND org=?');
    $stm->execute(array($ltiuserid, $platform_id));
    return $stm->fetchColumn(0);
  }

  function create_user_account($data) {
    $query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify,groupid) VALUES ";
    $query .= '(:SID,:password,:rights,:FirstName,:LastName,:email,:msgnotify,:groupid)';
    $stm = $this->dbh->prepare($query);
    $stm->execute(array(':SID'=>$data['SID'], ':password'=>$data['pwhash'],':rights'=>$data['rights'],
      ':FirstName'=>Sanitize::stripHtmlTags($data['firstname']),
      ':LastName'=>Sanitize::stripHtmlTags($data['lastname']),
      ':email'=>Sanitize::emailAddress($data['email']),
      ':msgnotify'=>$data['msgnot'],':groupid'=>$data['groupid']));
    return $this->dbh->lastInsertId();
  }

  function create_lti_user($userid, $ltiuserid, $platform_id) {
    $stm = $this->dbh->prepare('INSERT INTO imas_ltiusers (userid,ltiuserid,org) VALUES (?,?,?)');
    $stm->execute(array($userid, $ltiuserid, $platform_id));
    return $this->dbh->lastInsertId();
  }

  /**
   * Get local user course id
   * @param  string $ltiuserid
   * @param  string $platform_id
   * @return false|array local userid
   */
  function get_local_course($contextid, $platform_id) {
    $stm = $this->dbh->prepare('SELECT courseid,copiedfrom FROM imas_lti_courses WHERE contextid=? AND org=?');
    $stm->execute(array($contextid, $platform_id));
    return $stm->fetch(PDO::FETCH_ASSOC);
  }

  function get_groups($iss, $deployment) {
    $query = 'SELECT ig.id,ig.name FROM imas_groups AS ig
      JOIN imas_groupassoc AS iga ON ig.id=iga.groupid
      JOIN imas_lti_deployments AS ild ON ild.id=iga.deploymentid
      WHERE ild.issuer=? AND ild.deployment=?';
    $stm = $this->dbh->prepare($query);
    $stm->execute(array($iss, $deployment));
    return $stm->fetchAll(PDO::FETCH_ASSOC);
  }

}
