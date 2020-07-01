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

  function __construct($DBH) {
    $this->dbh = $DBH;
  }

  public function find_registration_by_issuer($iss, $client_id) {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_platforms WHERE issuer=? AND client_id=?');
    $stm->execute(array($iss, $client_id));
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

  public function find_deployment($platform_id, $deployment_id) {
    $stm = $this->dbh->prepare('SELECT * FROM imas_lti_deployments WHERE platform=? AND deployment=?');
    $stm->execute(array($platform_id, $deployment_id));
    if ($stm->rowCount()===0) {
      // no existing deployment record, create one
      $stm = $this->dbh->prepare('INSERT INTO imas_lti_deployments (platform,deployment) VALUES (?,?)');
      $stm->execute(array($platform_id, $deployment_id));
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
  public function get_local_userid($ltiuserid, $platform_id) {
    $stm = $this->dbh->prepare('SELECT userid FROM imas_ltiusers WHERE ltiuserid=? AND org=?');
    $stm->execute(array($ltiuserid, 'LTI13-'.$platform_id));
    return $stm->fetchColumn(0);
  }

  public function create_user_account($data) {
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

  public function enroll_if_needed($userid, $role, $localcourse, $section='') {
    if ($role == 'Instructor') {
      $stm = $this->dbh->prepare('SELECT id FROM imas_teachers WHERE userid=? AND courseid=?');
      $stm->execute(array($userid, $localcourse['courseid']));
      if (!$stm->fetchColumn(0)) {
        $stm = $this->dbh->prepare('INSERT INTO imas_teachers (userid,courseid) VALUES (?,?)');
        $stm->execute(array($userid, $localcourse['courseid']));
      }
    } else {
      $stm = $this->dbh->prepare('SELECT id,lticourseid FROM imas_students WHERE userid=? AND courseid=?');
      $stm->execute(array($userid, $localcourse['courseid']));
      $row = $stm->fetch(PDO::FETCH_ASSOC);
      if ($row === false) {
        $stm = $this->dbh->prepare('INSERT INTO imas_students (userid,courseid,section,lticourseid) VALUES (?,?,?,?)');
        $stm->execute(array($userid, $localcourse['courseid'], $section, $localcourse['id']));
      } else if ($row['lticourseid'] !== $localcourse['id']) {
        $stm = $this->dbh->prepare('UPDATE imas_students SET lticourseid=? WHERE id=?');
        $stm->execute(array($localcourse['id'], $row['id']));
      }
    }
  }

  public function create_lti_user($userid, $ltiuserid, $platform_id) {
    $stm = $this->dbh->prepare('INSERT INTO imas_ltiusers (userid,ltiuserid,org) VALUES (?,?,?)');
    $stm->execute(array($userid, $ltiuserid, 'LTI13-'.$platform_id));
    return $this->dbh->lastInsertId();
  }

  /**
   * Get local user course id
   * @param  string $contextid
   * @param  string $platform_id
   * @return false|array local course info
   */
  public function get_local_course($contextid, $platform_id) {
    $query = 'SELECT ilc.id,ilc.courseid,ilc.copiedfrom,ic.UIver,ic.dates_by_lti FROM
      imas_lti_courses AS ilc JOIN imas_courses AS ic ON ilc.courseid=ic.id
      WHERE ilc.contextid=? AND ilc.org=?';
    $stm = $this->dbh->prepare($query);
    $stm->execute(array($contextid, 'LTI13-'.$platform_id));
    return $stm->fetch(PDO::FETCH_ASSOC);
  }

  public function add_lti_course($contextid, $platform_id, $localcid, $label = '', $copiedfrom = 0) {
    $stm = $this->dbh->prepare('INSERT INTO imas_lti_courses (contextid,org,courseid,contextlabel,copiedfrom) VALUES (?,?,?,?,?)');
    $stm->execute(array($contextid, 'LTI13-'.$platform_id, $localcid, $label, $copiedfrom));
    return $this->dbh->lastInsertId();
  }

  public function get_UIver($courseid) {
    $stm = $this->dbh->prepare('SELECT UIver FROM imas_courses WHERE id=?');
    $stm->execute(array($courseid));
    return $stm->fetchColumn(0);
  }

  public function get_groups($iss, $deployment) {
    $query = 'SELECT ig.id,ig.name FROM imas_groups AS ig
      JOIN imas_groupassoc AS iga ON ig.id=iga.groupid
      JOIN imas_lti_deployments AS ild ON ild.id=iga.deploymentid
      WHERE ild.issuer=? AND ild.deployment=?';
    $stm = $this->dbh->prepare($query);
    $stm->execute(array($iss, $deployment));
    return $stm->fetchAll(PDO::FETCH_ASSOC);
  }

  public function set_group_assoc($platform_id, $deployment, $groupid) {
    $stm = $this->dbh->prepare('SELECT id FROM imas_lti_deployments WHERE platform=? AND deployment=?');
    $stm->execute(array($platform_id, $deployment));
    $internal_deployment_id = $stm->fetchColumn(0);
    $stm = $this->dbh->prepare('INSERT IGNORE INTO imas_lti_deployments (deploymentid,groupid) VALUES (?,?)');
    $stm->execute(array($internal_deployment_id, $groupid));
  }

  public function get_course_from_aid($aid) {
    $stm = $this->dbh->prepare('SELECT courseid FROM imas_assessments WHERE id=?');
    $stm->execute(array($aid));
    return $stm->fetchColumn(0);
  }

  public function get_potential_courses($target,$lastcopied,$userid) {
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

  public function ok_to_associate($destcid, $userid) {
    $stm = $this->dbh->prepare('SELECT it.id FROM imas_courses AS ic JOIN
      imas_teachers AS it ON it.courseid=ic.id WHERE ic.id=? AND it.userid=?');
    $stm->execute(array($destcid, $userid));
    return ($stm->fetchColumn(0) !== false);
  }

  public function get_link_assoc($linkid, $contextid, $platform_id) {
    $query = "SELECT ip.typeid,ip.placementtype,ia.date_by_lti,ia.enddate,ia.startdate
      FROM imas_lti_placements AS ip LEFT JOIN imas_assessments AS ia
      ON ip.typeid=ia.id AND ip.placementtype='assess'
      WHERE linkid=? AND contextid=? AND org=?";
    $stm = $this->dbh->prepare($query);
    $stm->execute(array($linkid, $contextid, 'LTI13-'.$platform_id));
    $row = $stm->fetch(PDO::FETCH_ASSOC);
    $row['typenum'] = $this->types_as_num[$row['placementtype']];
    return $row;
  }

  public function make_link_assoc($typeid,$placementtype,$linkid,$contextid,$platform_id) {
    $query = 'INSERT INTO imas_lti_placements (typeid,placementtype,linkid,contextid,org) VALUES (?,?,?,?,?)';
    $stm = $this->dbh->prepare($query);
    $stm->execute(array($typeid,$placementtype,$linkid,$contextid,'LTI13-'.$platform_id));
    $typenum = $this->types_as_num[$placementtype];
    return array('typeid'=>$typeid, 'placementtype'=>$placementtype, 'typenum'=>$typenum);
  }

  public function get_dates_by_aid($aid) {
    $stm = $this->dbh->prepare('SELECT date_by_lti,startdate,enddate FROM imas_assessments WHERE id=?');
    $stm->execute(array($aid));
    return $stm->fetch(PDO::FETCH_ASSOC);
  }

  public function get_assess_info($aid) {
    $stm = $this->dbh->prepare('SELECT name,ptsposs,startdate,enddate,date_by_lti FROM imas_assessments WHERE id=?');
    $stm->execute(array($aid));
    return $stm->fetch(PDO::FETCH_ASSOC);
  }
  public function set_assessment_dates($aid, $enddate, $datebylti) {
    $stm = $this->dbh->prepare("UPDATE imas_assessments SET startdate=:startdate,enddate=:enddate,date_by_lti=:datebylti WHERE id=:id");
    $stm->execute(array(':startdate'=>min(time(), $enddate),
      ':enddate'=>$enddate, ':datebylti'=>$datebylti, ':id'=>$aid));
  }
  public function set_or_update_duedate_exception($userid, $link, $lms_duedate) {
    $aid = $link['typeid'];
    $stm = $this->dbh->prepare("SELECT startdate,enddate,islatepass,is_lti FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
		$exceptionrow = $stm->fetch(PDO::FETCH_NUM);
		$useexception = false;
		if ($exceptionrow!=null) {
			//have exception.  Update using lti_duedate if needed
			if ($link['date_by_lti'] > 0 && $lms_duedate != $exceptionrow[1]) {
				//if new due date is later, or no latepass used, then update
				if ($exceptionrow[2]==0 || $lms_duedate > $exceptionrow[1]) {
					$stm = $this->dbh->prepare("UPDATE imas_exceptions SET startdate=:startdate,enddate=:enddate,is_lti=1,islatepass=0 WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
					$stm->execute(array(':startdate'=>min($now, $lms_duedate, $exceptionrow[0]),
						':enddate'=>$lms_duedate, ':userid'=>$userid, ':assessmentid'=>$aid));
				}
			}
		} else if ($link['date_by_lti']==3 && ($link['enddate'] != $lms_duedate || $now<$link['startdate'])) {
			//default dates already set by LTI, and users's date doesn't match - create new exception
			//also create if it's before the default assessment startdate - since they could access via LMS, it should be available.
			$stm = $this->dbh->prepare("INSERT INTO imas_exceptions (startdate,enddate,islatepass,is_lti,userid,assessmentid,itemtype) VALUES (?,?,?,?,?,?,'A')");
			$stm->execute(array(min($now,$lms_duedate), $lms_duedate, 0, 1, $userid, $aid));
		}
  }

  public function find_aid_by_immediate_ancestor($aidtolookfor, $destcid) {
    $anregex = '^([0-9]+:)?'.$aidtolookfor.'[[:>:]]';
    $stm = $this->dbh->prepare("SELECT id FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:destcid");
    $stm->execute(array(':ancestors'=>$anregex, ':destcid'=>$destcid));
    return $stm->fetchColumn(0);
  }

  public function find_aid_by_ancestor_walkback($sourceaid, $aidsourcecid, $copiedfrom, $destcid) {
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

  public function set_or_create_lineitem($launch, $link, $info, $localcourse) {
    $platform_id = $launch->get_platform_id();
    $itemtype = $link['typenum'];
    $typeid = $line['typeid'];

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
        if (empty($info['date_by_lti']) && !empty($info['startdate'])) {
          $lineitem->set_start_date_time(date(DATE_ATOM, $info['startdate']));
        }
        if (empty($info['date_by_lti']) && !empty($info['enddate'])) {
          $lineitem->set_end_date_time(date(DATE_ATOM, $info['enddate']));
        }
        $newlineitem = $ags->find_or_create_lineitem($lineitem);
        $lineitemstr = $newlineitem->get_id();
      }
      if (!empty($lineitemstr)) {
        $stm = $this->dbh->prepare('INSERT INTO imas_lti_lineitems (itemtype,typeid,lticourseid,lineitem) VALUES (?,?,?,?)');
        $stm->execute(array($itemtype, $typeid, $localcourse['id'], $lineitemstr));
      }
    }
  }

}
