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
      $users[$row['id']] = array($row['FirstName'], $row['LastName']);
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
      $potential_group_members = explode(',', $potential_group_members);
    }
    $ph = Sanitize::generateQueryPlaceholders($potential_group_members);

    // look up all potential members to see if they're in a group already
    $query = "SELECT isgm.userid FROM imas_stugroupmembers AS isgm JOIN
              imas_stugroups AS isg ON isg.id=isgm.stugroupid AND
              isg.groupsetid=? WHERE isgm.userid IN ($ph)";
    $stm = $DBH->prepare($query);
    $stm->execute(array_merge(array($groupsetid), $potential_group_members));
    $in_group = $stm->fetchAll(PDO::FETCH_COLUMN, 0);

    return array_keys(array_diff($potential_group_members, $in_group));
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
      $users[$row['id']] = array($row['FirstName'], $row['LastName']);
    }
    return $users;
  }
}
