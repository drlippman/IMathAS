<?php

/**
 * Implements IMSGlobal\LTI\Database interface
 */

define('TOOL_HOST', $GLOBALS['basesiteurl']);

use \IMSGlobal\LTI;

class Imathas_LTI_Database implements LTI\Database {
  private $dbh;

  private $types_as_num = [
    'assess'=>0,
    'course'=>1
  ];

  function __construct(PDO $DBH) {
    $this->dbh = $DBH;
  }

  /**
   * Find a registration by issuer and client id
   * @param  string           $iss
   * @param  string           $client_id
   * @return LTI_Registration
   */
  public function find_registration_by_issuer(string $iss, string $client_id): LTI\LTI_Registration {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_platforms WHERE issuer=? AND client_id=?');
    $stm->execute(array($iss, $client_id));
    $row = $stm->fetch(PDO::FETCH_ASSOC);
    if ($row === false || $row === null) {
      return false;
    }
    return LTI\LTI_Registration::new()
      ->set_auth_login_url($row['auth_login_url'])
      ->set_auth_token_url($row['auth_token_url'])
      ->set_client_id($row['client_id'])
      ->set_key_set_url($row['key_set_url'])
      ->set_issuer($iss)
      ->set_id(intval($row['id']));
  }

  /**
   * Find deployment.  If doesn't exist, creates it.
   * @param  int            $platform_id
   * @param  string         $deployment_id
   * @return LTI_Deployment
   */
  public function find_deployment(int $platform_id, string $deployment_id): LTI\LTI_Deployment {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_deployments WHERE platform=? AND deployment=?');
    $stm->execute(array($platform_id, $deployment_id));
    if ($stm->rowCount()===0) {
      // no existing deployment record, create one
      $stm = $this->dbh->prepare('INSERT INTO imas_lti_deployments (platform,deployment) VALUES (?,?)');
      $stm->execute(array($platform_id, $deployment_id));
    }
    return LTI\LTI_Deployment::new()->set_deployment_id($deployment_id);
  }


  /**
   * Get an array of our public keys, for generating our jwks page
   * @return array
   */
  public function get_jwks_keys(): array {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_keys WHERE key_set_url=?');
    $stm->execute(array(TOOL_HOST.'/lti/jwks.php'));
    return $stm->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Get our private key
   * @return array
   */
  public function get_tool_private_key(): array {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_keys WHERE key_set_url=? ORDER BY created_at DESC LIMIT 1');
    $stm->execute(array(TOOL_HOST.'/lti/jwks.php'));
    return $stm->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Get key from the database
   * @param  string $keyseturl [description]
   * @param  string $kid       [description]
   * @return array with key_set_url,kid,alg,publickey,privatekey,created_at
   */
  public function get_key(string $keyseturl, string $kid): array {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_keys WHERE key_set_url=? AND kid=?');
    $stm->execute(array($keyseturl, $kid));
    return $stm->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Delete a key from the database
   * @param string $keyseturl
   * @param string $kid
   */
  public function delete_key(string $keyseturl, string $kid): void {
    $stm = $this->dbh->prepare('DELETE FROM imas_lti_keys WHERE key_set_url=? AND kid=?');
    $stm->execute(array($keyseturl, $kid));
  }

  /**
   * Record keys from a JWKS for caching
   * @param string $keyseturl the keyset url we got these from
   * @param array  $keys      array of the keys
   */
  public function record_keys(string $keyseturl, array $keys): void {
    $stm = $this->dbh->prepare('INSERT IGNORE INTO imas_lti_keys (key_set_url,kid,alg,publickey) VALUES (?,?,?,?)');
    foreach ($keys as $kid=>$keyinfo) {
      $stm->execute(array($keyseturl,$kid,$keyinfo['alg'],$keyinfo['pub']));
    }
  }

  /**
   * Get token from database.
   * If a failed token request has reached it's retry delay, this will return
   * (false, number of previous failures)
   *
   * If the previous request failed and it's before the retry delay, the token
   * will start with 'failed'
   *
   * @param  int    $platform_id
   * @param  string $scope   an md5 hash of the pipe-concatenated scopes
   * @return array  (token string | false, number of previous failures)
   */
  public function get_token(int $platform_id, string $scope): array {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_tokens WHERE platformid=? AND scopes=?');
    $stm->execute(array($platform_id, $scope));
    $row = $stm->fetch(PDO::FETCH_ASSOC);
    if ($row === false || $row === null) {
      return array(false,0);
    } else if ($row['expires'] > time()) {
      $stm = $this->dbh->prepare('DELETE FROM imas_lti_tokens WHERE platformid=? AND scopes=?');
      $stm->execute(array($platform_id, $scope));
      if (substr($row['token'],0,6)==='failed') {
        return array(false, intval(substr($row['token'],6)));
      } else {
        return array(false, 0);
      }
    } else {
      return array($row['token'], 0);
    }
  }

  /**
   * Record an access token
   * @param int    $platform_id
   * @param string $scope       an md5 hash of the pipe-concatenated scopes
   * @param array  $tokeninfo   token response, with 'access_token' and 'expires_in'
   */
  public function record_token(int $platform_id, string $scope, array $tokeninfo): void {
    $stm = $this->dbh->prepare('REPLACE INTO imas_lti_tokens (platformid, scopes, token, expires) VALUES (?,?,?,?)');
    $stm->execute(array($platform_id, $scope, $tokeninfo['access_token'], time() + $tokeninfo['expires_in'] - 1));
  }

  /**
   * Get local user id
   * @param  string $ltiuserid
   * @param  int $platform_id
   * @return false|int local userid
   */
  public function get_local_userid(string $ltiuserid, int $platform_id) {
    $stm = $this->dbh->prepare('SELECT userid FROM imas_ltiusers WHERE ltiuserid=? AND org=?');
    $stm->execute(array($ltiuserid, 'LTI13-'.$platform_id));
    return $stm->fetchColumn(0);
  }

  /**
   * Create a user account
   * @param  array $data assoc array of account data
   * @return int   new local userid
   */
  public function create_user_account(array $data): int {
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

  /**
   * Enroll a user as teacher or students in a course if needed
   * @param int             $userid
   * @param string          $role        standardized role, 'Learner' or 'Instructor'
   * @param LTI_Localcourse $localcourse
   * @param string          $section     a section identifer
   */
  public function enroll_if_needed(int $userid, string $role,
    LTI\LTI_Localcourse $localcourse, string $section=''
  ): void {
    if ($role == 'Instructor') {
        print_r($localcourse);
      $stm = $this->dbh->prepare('SELECT id FROM imas_teachers WHERE userid=? AND courseid=?');
      $stm->execute(array($userid, $localcourse->get_courseid()));
      if (!$stm->fetchColumn(0)) {
        $stm = $this->dbh->prepare('INSERT INTO imas_teachers (userid,courseid) VALUES (?,?)');
        $stm->execute(array($userid, $localcourse->get_courseid()));
      }
    } else {
      $stm = $this->dbh->prepare('SELECT id,lticourseid FROM imas_students WHERE userid=? AND courseid=?');
      $stm->execute(array($userid, $localcourse->get_courseid()));
      $row = $stm->fetch(PDO::FETCH_ASSOC);
      if ($row === false || $row === null) {
        $stm = $this->dbh->prepare('INSERT INTO imas_students (userid,courseid,section,lticourseid) VALUES (?,?,?,?)');
        $stm->execute(array($userid, $localcourse->get_courseid(), $section, $localcourse->get_id()));
      } else if ($row['lticourseid'] !== $localcourse->get_id()) {
        $stm = $this->dbh->prepare('UPDATE imas_students SET lticourseid=? WHERE id=?');
        $stm->execute(array($localcourse->get_id(), $row['id']));
      }
    }
  }

  /**
   * Create lti user connection record
   * @param  int    $userid      local imas_users.id
   * @param  string $ltiuserid   user id provided by LMS
   * @param  int    $platform_id imas_lti_platforms.id
   * @return int    new imas_ltiusers.id
   */
  public function create_lti_user(int $userid, string $ltiuserid, int $platform_id): int {
    $stm = $this->dbh->prepare('INSERT INTO imas_ltiusers (userid,ltiuserid,org) VALUES (?,?,?)');
    $stm->execute(array($userid, $ltiuserid, 'LTI13-'.$platform_id));
    return $this->dbh->lastInsertId();
  }

  /**
   * Update the SID for a user
   * @param int    $userid
   * @param string $SID
   */
  public function set_user_SID(int $userid, string $SID): void {
    $stm = $this->dbh->prepare('UPDATE imas_users SET SID=? WHERE id=?');
    $stm->execute(array($SID,$userid));
  }

  /**
   * Get local user course id
   * @param  string $contextid
   * @param  int $platform_id
   * @return null|LTI_Localcourse local course info
   */
  public function get_local_course(string $contextid, int $platform_id): ?LTI\LTI_Localcourse {
    $query = 'SELECT ilc.id,ilc.courseid,ilc.copiedfrom,ic.UIver,ic.dates_by_lti FROM
      imas_lti_courses AS ilc JOIN imas_courses AS ic ON ilc.courseid=ic.id
      WHERE ilc.contextid=? AND ilc.org=?';
    $stm = $this->dbh->prepare($query);
    $stm->execute(array($contextid, 'LTI13-'.$platform_id));
    $row = $stm->fetch(PDO::FETCH_ASSOC);
    if ($row === false || $row === null) {
      return null;
    } else {
      return LTI\LTI_Localcourse::new($row);
    }
  }

  /**
   * Add lti course association
   * @param  string  $contextid   LMS contextid
   * @param  int     $platform_id imas_lti_platforms.id
   * @param  int     $localcid    imas_courses.id
   * @param  string  $label       LMS provided course label
   * @param  integer $copiedfrom  local id course was copied from
   * @return int  new imas_lti_courses.id
   */
  public function add_lti_course(string $contextid, int $platform_id,
    int $localcid, string $label = '', int $copiedfrom = 0
  ): int {
    $stm = $this->dbh->prepare('INSERT INTO imas_lti_courses (contextid,org,courseid,contextlabel,copiedfrom) VALUES (?,?,?,?,?)');
    $stm->execute(array($contextid, 'LTI13-'.$platform_id, $localcid, $label, $copiedfrom));
    return $this->dbh->lastInsertId();
  }

  /**
   * Get a course UIver
   * @param  int $courseid
   * @return int UIver
   */
  public function get_UIver(int $courseid): int {
    $stm = $this->dbh->prepare('SELECT UIver FROM imas_courses WHERE id=?');
    $stm->execute(array($courseid));
    return $stm->fetchColumn(0);
  }

  /**
   * Get groups associated with deployment
   * @param  string $iss        issuer
   * @param  string $deployment LMS provided deployment string
   * @return array  of groups, with indices id and name
   */
  public function get_groups(string $iss, string $deployment): array {
    $query = 'SELECT ig.id,ig.name FROM imas_groups AS ig
      JOIN imas_groupassoc AS iga ON ig.id=iga.groupid
      JOIN imas_lti_deployments AS ild ON ild.id=iga.deploymentid
      WHERE ild.issuer=? AND ild.deployment=?';
    $stm = $this->dbh->prepare($query);
    $stm->execute(array($iss, $deployment));
    return $stm->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Associate group with deployment
   * @param int    $platform_id imas_lti_platforms.id
   * @param string $deployment  LMS provided deployment string
   * @param int    $groupid     imas_groups.id
   * @return void
   */
  public function set_group_assoc(int $platform_id, string $deployment, int $groupid): void {
    $stm = $this->dbh->prepare('SELECT id FROM imas_lti_deployments WHERE platform=? AND deployment=?');
    $stm->execute(array($platform_id, $deployment));
    $internal_deployment_id = $stm->fetchColumn(0);
    $stm = $this->dbh->prepare('INSERT IGNORE INTO imas_lti_groupassoc (deploymentid,groupid) VALUES (?,?)');
    $stm->execute(array($internal_deployment_id, $groupid));
  }

  /**
   * Get course id from assessment id
   * @param  int $aid imas_assessments.id
   * @return int imas_courses.id
   */
  public function get_course_from_aid(int $aid): int {
    $stm = $this->dbh->prepare('SELECT courseid FROM imas_assessments WHERE id=?');
    $stm->execute(array($aid));
    return $stm->fetchColumn(0);
  }

  /**
   * Get courses we might want to copy or associate with
   *
   * TODO: extend with hooks to handle initial launches to other item types
   *
   * @param  array $target    should have 'refcid' and 'refaid' defined
   * @param  int   $lastcopied last copied course id
   * @param  int   $userid     imas_users.id
   * @return array [courses to copy, courses to associate, sourceUIver]
   */
  public function get_potential_courses(array $target, int $lastcopied, int $userid): array {
    // see if we're allowed to do copies of copies
    $stm = $this->dbh->prepare('SELECT jsondata,UIver FROM imas_courses WHERE id=:sourcecid');
    $stm->execute(array(':sourcecid'=>$target['refcid']));
    list($sourcejsondata,$sourceUIver) = $stm->fetch(PDO::FETCH_NUM);
    $sourcejsondata = json_decode($sourcejsondata, true);
    $blockLTICopyOfCopies = ($sourcejsondata!==null && !empty($sourcejsondata['blockLTICopyOfCopies']));

    // look for other courses we could associate with
    // TODO: adjust this to handle other target types
    $query = "SELECT DISTINCT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers AS imt ON ic.id=imt.courseid ";
    $query .= "AND imt.userid=:userid JOIN imas_assessments AS ia ON ic.id=ia.courseid ";
    $query .= "WHERE ic.available<4 AND ic.ancestors REGEXP :cregex AND ia.ancestors REGEXP :aregex ORDER BY ic.name";
    $stm = $this->dbh->prepare($query);
    $stm->execute(array(
      ':userid'=>$userid,
      ':cregex'=>'[[:<:]]'.$target['refcid'].'[[:>:]]',
      ':aregex'=>'[[:<:]]'.$target['refaid'].'[[:>:]]'));
    $othercourses = array();
    while ($row = $stm->fetch(PDO::FETCH_NUM)) {
      $othercourses[$row[0]] = $row[1];
    }

    if ($blockLTICopyOfCopies) {
      $copycourses = array();
    } else {
      $copycourses = $othercourses;
    }

    // get origin course
    // TODO: if the $lastcopied isn't owned by us, should we still offer it as
    // an option to be copied?
    $stm = $this->dbh->prepare('SELECT DISTINCT id,name,ownerid FROM imas_courses WHERE id=?');
    $stm->execute(array($target['refcid']));
    while ($row = $stm->fetch(PDO::FETCH_NUM)) {
      $copycourses[$row[0]] = $row[1];
      // if it's user's course, also include in assoc list
      if ($row[2] == $userid) {
        $othercourses[$row[0]] = $row[1];
      }
    }
    return array($copycourses,$othercourses,$sourceUIver);
  }

  /**
   * Make sure use is a teacher on course to associate
   * @param  int  $destcid imas_courses.id
   * @param  int  $userid
   * @return bool if user is a teacher
   */
  public function ok_to_associate(int $destcid, int $userid): bool {
    $stm = $this->dbh->prepare('SELECT it.id FROM imas_courses AS ic JOIN
      imas_teachers AS it ON it.courseid=ic.id WHERE ic.id=? AND it.userid=?');
    $stm->execute(array($destcid, $userid));
    return ($stm->fetchColumn(0) !== false);
  }

  /**
   * Get placement from data, if exists
   * @param  string $resource_link_id LMS provided resource link id
   * @param  string $contextid        LMS provided contextid
   * @param  int    $platform_id      imas_lti_platforms.id
   * @return null|LTI_Placement
   */
  public function get_link_assoc(string $resource_link_id, string $contextid, int $platform_id): ?LTI\LTI_Placement {
    $query = "SELECT ip.typeid,ip.placementtype,ia.date_by_lti,ia.enddate,ia.startdate
      FROM imas_lti_placements AS ip LEFT JOIN imas_assessments AS ia
      ON ip.typeid=ia.id AND ip.placementtype='assess'
      WHERE linkid=? AND contextid=? AND org=?";
    $stm = $this->dbh->prepare($query);
    $stm->execute(array($resource_link_id, $contextid, 'LTI13-'.$platform_id));
    $row = $stm->fetch(PDO::FETCH_ASSOC);
    if ($row !== false) {
      return LTI\LTI_Placement::new()
        ->set_typeid($row['typeid'])
        ->set_placementtype($row['placementtype'])
        ->set_typenum($this->types_as_num[$row['placementtype']])
        ->set_date_by_lti($row['date_by_lti'])
        ->set_startdate($row['startdate'])
        ->set_enddate($row['enddate']);
    }
    return null;
  }

  /**
   * Create a placement record
   * @param  int           $typeid           imas_(itemtype).id
   * @param  string        $placementtype    'assess' or otherwise
   * @param  string        $resource_link_id LMS provided resource link id
   * @param  string        $contextid        LMS provided contextid
   * @param  int           $platform_id      imas_lti_platforms.id
   * @return LTI_Placement
   */
  public function make_link_assoc(int $typeid, string $placementtype,
    string $resource_link_id, string $contextid, int $platform_id
  ): LTI\LTI_Placement {
    $query = 'INSERT INTO imas_lti_placements (typeid,placementtype,linkid,contextid,org) VALUES (?,?,?,?,?)';
    $stm = $this->dbh->prepare($query);
    $stm->execute(array($typeid,$placementtype,$resource_link_id,$contextid,'LTI13-'.$platform_id));
    $typenum = $this->types_as_num[$placementtype];
    return LTI\LTI_Placement::new()
      ->set_typeid($typeid)
      ->set_placementtype($placementtype)
      ->set_typenum($typenum);
  }

  /**
   * Get date info for an assessment
   * @param  int   $aid  imas_assessments.id
   * @return array
   */
  public function get_dates_by_aid(int $aid): array {
    $stm = $this->dbh->prepare('SELECT date_by_lti,startdate,enddate FROM imas_assessments WHERE id=?');
    $stm->execute(array($aid));
    return $stm->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Get assessment info, including name, ptsposs, and dates
   * @param  int   $aid imas_assessments.id
   * @return array
   */
  public function get_assess_info(int $aid): array {
    $stm = $this->dbh->prepare('SELECT name,ptsposs,startdate,enddate,date_by_lti FROM imas_assessments WHERE id=?');
    $stm->execute(array($aid));
    return $stm->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Set assessment dates when setting dates by lti
   * @param int $aid       imas_assessments.id
   * @param int $enddate   new enddate
   * @param int $datebylti new value for date_by_lti
   * @return void
   */
  public function set_assessment_dates(int $aid, int $enddate, int $datebylti): void {
    $stm = $this->dbh->prepare("UPDATE imas_assessments SET startdate=:startdate,enddate=:enddate,date_by_lti=:datebylti WHERE id=:id");
    $stm->execute(array(':startdate'=>min(time(), $enddate),
      ':enddate'=>$enddate, ':datebylti'=>$datebylti, ':id'=>$aid));
  }

  /**
   * Sets or updates a duedate exception
   * @param int           $userid
   * @param LTI_Placement $link
   * @param int           $lms_duedate  the new duedate provided by the LMS
   * @return void
   */
  public function set_or_update_duedate_exception(int $userid, LTI\LTI_Placement $link, int $lms_duedate): void {
    $aid = $link->get_typeid();
    $stm = $this->dbh->prepare("SELECT startdate,enddate,islatepass,is_lti FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
		$exceptionrow = $stm->fetch(PDO::FETCH_NUM);
		$useexception = false;
		if ($exceptionrow!=null) {
			//have exception.  Update using lti_duedate if needed
			if ($link->get_date_by_lti() > 0 && $lms_duedate != $exceptionrow[1]) {
				//if new due date is later, or no latepass used, then update
				if ($exceptionrow[2]==0 || $lms_duedate > $exceptionrow[1]) {
					$stm = $this->dbh->prepare("UPDATE imas_exceptions SET startdate=:startdate,enddate=:enddate,is_lti=1,islatepass=0 WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
					$stm->execute(array(':startdate'=>min($now, $lms_duedate, $exceptionrow[0]),
						':enddate'=>$lms_duedate, ':userid'=>$userid, ':assessmentid'=>$aid));
				}
			}
		} else if ($link->get_date_by_lti()==3 &&
      ($link->get_date_by_lti() != $lms_duedate || $now<$link->get_startdate())
    ) {
			//default dates already set by LTI, and users's date doesn't match - create new exception
			//also create if it's before the default assessment startdate - since they could access via LMS, it should be available.
			$stm = $this->dbh->prepare("INSERT INTO imas_exceptions (startdate,enddate,islatepass,is_lti,userid,assessmentid,itemtype) VALUES (?,?,?,?,?,?,'A')");
			$stm->execute(array(min($now,$lms_duedate), $lms_duedate, 0, 1, $userid, $aid));
		}
  }

  /**
   * Find assessment in $destcid that has $aidtolookfor as an immediate ancestor
   * @param  int    $aidtolookfor
   * @param  int    $destcid
   * @return int|false   imas_assessments.id if found
   */
  public function find_aid_by_immediate_ancestor(int $aidtolookfor, int $destcid) {
    $anregex = '^([0-9]+:)?'.$aidtolookfor.'[[:>:]]';
    $stm = $this->dbh->prepare("SELECT id FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:destcid");
    $stm->execute(array(':ancestors'=>$anregex, ':destcid'=>$destcid));
    return $stm->fetchColumn(0);
  }

  /**
   * Attempts to find an appropriate assessment in $destcid which was copied
   * from course $copiedfrom that might be a copy
   * of $sourceaid in course $aidsourcecid.
   *
   * @param  int    $sourceaid
   * @param  int    $aidsourcecid
   * @param  int    $copiedfrom
   * @param  int    $destcid
   * @return int|false  imas_assessments.id if found
   */
  public function find_aid_by_ancestor_walkback(int $sourceaid, int $aidsourcecid,
    int $copiedfrom, int $destcid
  ) {
    $stm = $this->dbh->prepare("SELECT ancestors FROM imas_courses WHERE id=?");
    $stm->execute(array($destcid));
    $ancestors = explode(',', $stm->fetchColumn(0));
    $ciddepth = array_search($aidsourcecid, $ancestors);  //so if we're looking for 23, "20,24,23,26" would give 2 here.
    if ($ciddepth !== false) {
      // first approach: look for aidsourcecid:sourcecid in ancestry of current course
      // then we'll walk back through the ancestors and make sure the course
      // history path matches.
      // This approach will work as long as there's a newer-format ancestry record
      $anregex = '[[:<:]]'.$aidsourcecid.':'.$sourceaid.'[[:>:]]';
      $stm = $this->dbh->prepare("SELECT id,ancestors FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:destcid");
      $stm->execute(array(':ancestors'=>$anregex, ':destcid'=>$destcid));
      while ($res = $stm->fetch(PDO::FETCH_ASSOC)) {
        $aidanc = explode(',',$res['ancestors']);
        $isok = true;
        for($i=0;$i<=$ciddepth;$i++) {
          if (!isset($aidanc[$i])) {
            $isok = false;
            break;
          }
          $ancparts = explode(':', $aidanc[$i]);
          if ($ancparts[0] != $ancestors[$i]) {
            $isok = false;
            break; // not the same ancestry path
          }
        }
        if ($isok) { // found it!
          return $res['id'];
        }
      }

      // last approach didn't work, so maybe it was an older-format ancestry.
      // We'll try a heavier-duty walkback that calls the database for each walkback
      array_unshift($ancestors, $destcid);  //add current course to front
      $foundsubaid = true;
      $aidtolookfor = $sourceaid;
      for ($i=$ciddepth;$i>=0;$i--) {  //starts one course back from aidsourcecid because of the unshift
        $stm = $this->dbh->prepare("SELECT id FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:cid");
        $stm->execute(array(':ancestors'=>'^([0-9]+:)?'.$aidtolookfor.'[[:>:]]', ':cid'=>$ancestors[$i]));
        if ($stm->rowCount()>0) {
          $aidtolookfor = $stm->fetchColumn(0);
        } else {
          $foundsubaid = false;
          break;
        }
      }
      if ($foundsubaid) { // tracked it back all the way
        return $aidtolookfor;
      }

      // ok, still didn't work, so assessment wasn't copied through the whole
      // history.  So let's see if we have a copy in our course with the assessment
      // anywhere in the ancestry.
      $anregex = '[[:<:]]'.$sourceaid.'[[:>:]]';
      $stm = $this->dbh->prepare("SELECT id,name,ancestors FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:destcid");
      $stm->execute(array(':ancestors'=>$anregex, ':destcid'=>$destcid));
      $res = $stm->fetchAll(PDO::FETCH_ASSOC);
      if (count($res)==1) {  //only one result - we found it
        return $res[0]['id'];
      }
      $stm = $this->dbh->prepare("SELECT name FROM imas_assessments WHERE id=?");
      $stm->execute(array($sourceaid));
      $aidsourcename = $stm->fetchColumn(0);
      if (count($res)>1) { //multiple results - look for the identical name
        foreach ($res as $k=>$row) {
          $res[$k]['loc'] = strpos($row['ancestors'], (string) $aidtolookfor);
          if ($row['name']==$aidsourcename) {
            return $row['id'];
          }
        }
        //no name match. pick the one with the assessment closest to the start
        usort($res, function($a,$b) { return $a['loc'] - $b['loc'];});
        return $res[0]['id'];
      }

      // still haven't found it, so nothing in our current course has the
      // desired assessment as an ancestor.  Try finding something just with
      // the right name maybe?
      $stm = $this->dbh->prepare("SELECT id FROM imas_assessments WHERE name=:name AND courseid=:courseid");
      $stm->execute(array(':name'=>$aidsourcename, ':courseid'=>$destcid));
      if ($stm->rowCount()>0) {
        return $stm->fetchColumn(0);
      }
    }
    return false;
  }

  /**
   * Records lineitem in database if set, or cretes a new one if possible
   *
   * TODO: figure out what to do in case of failure or inability to create lineitem
   *
   * @param LTI_Message_Launch $launch
   * @param LTI_Placement      $link
   * @param array              $info    name, ptsposs, and optionally date_by_lti, enddate, startdate
   * @param LTI_Localcourse    $localcourse
   */
  public function set_or_create_lineitem(LTI\LTI_Message_Launch $launch, LTI\LTI_Placement $link,
      array $info, LTI\LTI_Localcourse $localcourse
  ): bool {
    $platform_id = $launch->get_platform_id();
    $itemtype = $link->get_typenum();
    $typeid = $link->get_typeid();
    if ($launch->can_set_grades()) {
      // no need to proceed if we can't send back grades
      $lineitemstr = $launch->get_lineitem();
      if ($lineitemstr === false && $launch->can_create_lineitem()) {
        // there wasn't a lineitem in the launch, so find or create one
        $ags = $launch->get_ags();
        $lineitem = LTI\LTI_Lineitem::new()
          ->set_resource_id($itemtype.'-'.$typeid)
          ->set_score_maximum($info['ptsposs'])
          ->set_label($info['name']);
        if ($link->get_placementtype() == 'assess') {
          // TODO: figure this out.  Ideally we should link the lineitem to
          // the resource_link.id, but Canvas doesn't seem to like this ?
          // Perhaps it doesn't recognize the link as owned by the tool.
          // $lineitem->set_resource_link_id($launch->get_resource_link()['id']);
        }
        if (empty($info['date_by_lti']) && !empty($info['startdate'])) {
          $lineitem->set_start_date_time(date(DATE_ATOM, $info['startdate']));
        }
        if (empty($info['date_by_lti']) && !empty($info['enddate']) && $info['enddate'] < 2000000000) {
          $lineitem->set_end_date_time(date(DATE_ATOM, $info['enddate']));
        }
        $newlineitem = $ags->find_or_create_lineitem($lineitem);
        if ($newlineitem === false) {
          // TODO: handle lineitem creation failure somehow.
          // What are we going to do in this case?
          return false;
        }
        $lineitemstr = $newlineitem->get_id();
      }
      if (!empty($lineitemstr)) {
        $stm = $this->dbh->prepare('INSERT INTO imas_lti_lineitems (itemtype,typeid,lticourseid,lineitem) VALUES (?,?,?,?)');
        $stm->execute(array($itemtype, $typeid, $localcourse->get_id(), $lineitemstr));
      }
      return true;
    } else {
        return false;
    }
  }

  /**
   * Determine if there is a lineitem stored in the database already
   * @param   LTI_Placement      $link
   * @param   LTI_Localcourse    $localcourse
   * @return bool
   */ 
  public function has_lineitem(LTI\LTI_Placement $link, LTI\LTI_Localcourse $localcourse): bool {
    $itemtype = $link->get_typenum();
    $typeid = $link->get_typeid();
    $stm = $this->dbh->prepare('SELECT lineitem FROM imas_lti_lineitems WHERE itemtype=? AND typeid=? AND lticourseid=?');
    $stm->execute(array($itemtype, $typeid,$localcourse->get_id()));
    return ($stm->fetchColumn(0) !== false);
  }

  /**
   * Get all assessments in course
   * @param  int   $cid [description]
   * @return array of courses with id,name
   */
  public function get_assessments(int $cid): array {
    $stm = $this->dbh->prepare('SELECT id,name FROM imas_assessments WHERE courseid=? ORDER BY name');
    $stm->execute(array($cid));
    return $stm->fetchAll(PDO::FETCH_ASSOC);
  }

}
