<?php
/*
 * IMathAS: Assessment Utilities Class
 * (c) 2019 David Lippman
 */

class AssessUtils
{
  /**
    * Get the group ID and group members
    * @param  int $userid     The user ID
    * @param  int $groupsetid The group set ID
    * @return array           array(stugroupid, names), where names is an
    *                         array of userid=>array(firstname, lastname)
    */
  public static function getGroupMembers($userid, $groupsetid) {
    global $DBH;
    $query = 'SELECT iu.id,iu.FirstName,iu.LastName,isgm.stugroupid
              FROM imas_stugroupmembers AS isgm JOIN imas_users AS iu
              ON isgm.userid=iu.id WHERE isgm.stugroupid=(
                SELECT isgm.stugroupid FROM imas_stugroupmembers AS isgm
                JOIN imas_stugroups AS isg ON isg.id=isgm.stugroupid AND
                isg.groupsetid=? WHERE isgm.userid=?
              )';
    $stm = $DBH->prepare($query);
    $users = array();
    $stugroupid = 0;
    $stm->execute(array($groupsetid, $userid));
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $users[$row['id']] = $row['FirstName'] . ' ' . $row['LastName'];
      $stugroupid = $row['stugroupid'];
    }
    return array($stugroupid, $users);
  }

  /**
   * Filters an array of potential group members, removing any who are
   * already added to a group in the same groupsetid
   * @param  array $potential_group_members  Array of potential group member userids
   * @param  int $groupsetid              The groupset ID to look in
   * @return array                        A filtered array of userids, containing
   *                                      those not already in another group
   */
  public static function checkPotentialGroupMembers($potential_group_members, $groupsetid) {
    global $DBH;
    if (!is_array($potential_group_members)) {
      if ($potential_group_members == '') {
        return array();
      }
      $potential_group_members = explode(',', $potential_group_members);
    }
    if (count($potential_group_members) == 0) {
      return array();
    }
    $ph = Sanitize::generateQueryPlaceholders($potential_group_members);

    // look up all potential members to see if they're in a group already
    $query = "SELECT isgm.userid FROM imas_stugroupmembers AS isgm JOIN
              imas_stugroups AS isg ON isg.id=isgm.stugroupid AND
              isg.groupsetid=? WHERE isgm.userid IN ($ph)";
    $stm = $DBH->prepare($query);
    $stm->execute(array_merge(array($groupsetid), $potential_group_members));
    $in_group = $stm->fetchAll(PDO::FETCH_COLUMN, 0);

    return array_values(array_diff($potential_group_members, $in_group));
  }

  /**
   * Copy the imas_assessment_records for the given user/assessment to a
   * set of other users
   * @param  int $uid          User ID for the source record
   * @param  int $assessmentid Assessment ID
   * @param  array $users      Array of user IDs to copy to
   * @return void
   */
  public static function copyRecord($uid, $assessmentid, $users) {
    global $DBH;

    $stm = $DBH->prepare('SELECT * from imas_assessment_records WHERE userid=? AND assessmentid=?');
    $stm->execute(array($uid, $assessmentid));
    $source = $stm->fetch(PDO::FETCH_ASSOC);
    $fields = implode(',', array_diff(array_keys($source), array('userid')));

    // this isn't super-efficient, but avoids having to send the full assessment
    // record between PHP and the DB multiple times.
    $query = "INSERT INTO imas_assessment_records (userid,$fields) ";
    $query .= "(SELECT (?,$fields) FROM imas_assessment_records WHERE userid=? AND assessmentid=?)";
    $insstm = $DBH->prepare($query);
    foreach ($users as $newuid) {
      $insstm->execute(array($newuid, $uid, $assessmentid));
    }
  }


  /**
    * Get the group members
    * @param  int $groupid    The imas_stugroups ID
    * @return array           array of userid=>array(firstname, lastname)
    */
  public static function getGroupMembersByGroupId($groupid) {
    global $DBH;
    $query = 'SELECT iu.id,iu.FirstName,iu.LastName
              FROM imas_stugroupmembers AS isgm JOIN imas_users AS iu
              ON isgm.userid=iu.id WHERE isgm.stugroupid=?';
    $stm = $DBH->prepare($query);
    $users = array();
    $stm->execute(array($groupid));
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $users[$row['id']] = $row['FirstName'] . ' ' . $row['LastName'];
    }
    return $users;
  }

  /**
   * Check if the given IP address is in the desired range
   *
   * @param  string  $userip The user's IP address to check
   * @param  string  $range  The IP range to try.
   *                         This is a comma-separated list of IPs,
   *                         and elements may include * for wildcard or
   *                         value-value for ranges like
   *                         12.3.5.*, or 12.34.6.12-35
   * @return boolean         true if userip is in the range
   */
  public static function isIPinRange($userip, $range) {
    $ips = array_map('trim', explode(',', $range));
    $userip = explode('.', $userip);
		$isoneIPok = false;
    foreach ($ips as $ip) {
      $ip = explode('.', $ip);
      $thisIPok = true;
      for ($i=0;$i<4;$i++) {
        $pts = explode('-', $ip[$i]);
        if (count($pts) == 2 && $userip[$i] >= $pts[0] && $userip[$i] <= $pts[0]) {
          continue;
        } else if ($ip[$i] == '*') {
          continue;
        } else if ($ip[$i] == $userip[$i]) {
          continue;
        } else {
          $thisIPok = false;
          break;
        }
      }
      if ($thisIPok) {
				$isoneIPok = true;
				break;
			}
    }
    return $isoneIPok;
  }

  public static function getEndMsg($endmsg, $score, $possible) {
    if ($endmsgs === '') {
      return '';
    }
    $average = round(100*$score/$possible,1);

    $endmsg = unserialize($endmsg);
    $redirecturl = '';
    $outmsg = '';
    if (isset($endmsg['msgs'])) {
      foreach ($endmsg['msgs'] as $sc=>$msg) { //array must be reverse sorted
        if (($endmsg['type']==0 && $score>=$sc) || ($endmsg['type']==1 && $average>=$sc)) {
          $outmsg = $msg;
          break;
        }
      }
      if ($outmsg=='') {
        $outmsg = $endmsg['def'];
      }
      if (strpos($outmsg,'redirectto:')!==false) {
        $redirecturl = trim(substr($outmsg,11));
        $outmsg = "<input type=\"button\" value=\"". _('Continue'). "\" onclick=\"window.location.href='$redirecturl'\"/>";
      }
      $outmsg = '<p>'.$outmsg.'</p>';
      if (!empty($endmsg['commonmsg']) && $endmsg['commonmsg']!='<p></p>') {
        $outmsg .= $endmsg['commonmsg'];
      }
    }
    return $outmsg;
  }

  public static function formLTIsourcedId($uids, $aid, $asArray = false) {
    global $studentinfo, $DBH;  
    if (is_array($uids)) {
        $uids = array_map('intval', $uids);
    } else {
        $uids = [intval($uids)];
    }
    if (!empty($_SESSION['lti_lis_result_sourcedid'.$aid]) &&
        !empty($_SESSION['lti_outcomeurl'])
    ) {
        return $_SESSION['lti_lis_result_sourcedid'.$aid].':|:'.$_SESSION['lti_outcomeurl'].':|:'.$_SESSION['lti_origkey'].':|:'.$_SESSION['lti_keylookup'];
    } else if (!empty($studentinfo['lticourseid'])) {
        $stm = $DBH->prepare('SELECT contextid,org FROM imas_lti_courses WHERE id=?');
        $stm->execute(array($studentinfo['lticourseid']));
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $platformid = substr($row['org'], 6); // strip off LTI13-
        $ltiuserid = [];
        if (empty($_SESSION['lti_user_id']) || count($uids)>1 || $asArray) {
            $uidlist = implode(',', $uids);
            $stm = $DBH->prepare("SELECT userid,ltiuserid FROM imas_ltiusers WHERE userid in ($uidlist) AND org=?");
            $stm->execute(array($row['org']));
            while ($row = $stm->fetch(PDO::FETCH_NUM)) {
                $ltiuserid[$row[0]] = $row[1];
            }
        } else {
            $ltiuserid = [$uids[0] => $_SESSION['lti_user_id']];
        }
        // look up lineitemurl
        $stm = $DBH->prepare('SELECT lineitem FROM imas_lti_lineitems WHERE itemtype=0 AND typeid=? AND lticourseid=?');
        $stm->execute(array($aid, $studentinfo['lticourseid']));
        $lineitemurl = $stm->fetchColumn(0);
        if ($lineitemurl !== false) {
            $sourcedids = [];
            foreach ($ltiuserid as $uid=>$ltiuserid) {
                $sourcedids[$uid] = 'LTI1.3:|:'.$ltiuserid.':|:'.$lineitemurl.':|:'.$platformid;
            }
            if (count($uids)==1 && !$asArray) {
                return $sourcedids[$uids[0]];
            } else {
                return $sourcedids;
            }
        }
    }
    return '';
  }
}
