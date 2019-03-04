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
     $stm->execute(array($groupsetid, $userid));
     while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
       $users[$row['id']] = array($row['FirstName'], $row['LastName']);
       $stugroupid = $row['stugroupid'];
     }
     return array($stugroupid, $users);
   }
 }
